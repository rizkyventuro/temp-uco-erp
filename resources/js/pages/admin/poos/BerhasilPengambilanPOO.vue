<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { CheckCircle2, Download, RefreshCw } from 'lucide-vue-next';
import QRCode from 'qrcode';
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface Batch {
    id: number;
    code: string;
    poo_name: string;
    volume: number;
    collection_date: string;
    status: string;
}

const props = defineProps<{ batch: Batch }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pengambilan dari POO', href: 'collections' },
    { title: 'Berhasil', href: '#' },
];

// ─── State ───────────────────────────────────────────
const qrDataUrl = ref<string>(''); // base64 image dari QRCode.js
const isLoading = ref(true);
const secondsLeft = ref(300); // 5 menit = 300 detik

let countdownInterval: ReturnType<typeof setInterval> | null = null;
let refreshTimeout: ReturnType<typeof setTimeout> | null = null;

// ─── Format Timer ─────────────────────────────────────
const timerDisplay = computed(() => {
    const m = Math.floor(secondsLeft.value / 60)
        .toString()
        .padStart(2, '0');
    const s = (secondsLeft.value % 60).toString().padStart(2, '0');
    return `${m}:${s}`;
});

const timerColor = computed(() => {
    if (secondsLeft.value <= 30) return 'text-red-500';
    if (secondsLeft.value <= 60) return 'text-orange-500';
    return 'text-teal-600';
});

// ─── Core: Fetch QR String dari API ──────────────────
const fetchAndRenderQR = async () => {
    isLoading.value = true;

    try {
        const res = await fetch(`/api/qr/generate/${props.batch.code}`);
        if (!res.ok) throw new Error('API error');

        const data: { qr_string: string; expired_at: number } =
            await res.json();

        // Render string menjadi gambar QR Code
        qrDataUrl.value = await QRCode.toDataURL(data.qr_string, {
            width: 200,
            margin: 1,
            color: { dark: '#0D4F3C', light: '#FFFFFF' },
        });

        // Hitung sisa detik yang akurat dari server
        const now = Math.floor(Date.now() / 1000);
        secondsLeft.value = Math.max(data.expired_at - now, 0);
    } catch (e) {
        console.error('Gagal generate QR:', e);
    } finally {
        isLoading.value = false;
    }
};

// ─── Timer & Auto-Refresh ─────────────────────────────
const startCountdown = () => {
    // Clear sebelumnya agar tidak terjadi memory leak
    if (countdownInterval) clearInterval(countdownInterval);
    if (refreshTimeout) clearTimeout(refreshTimeout);

    countdownInterval = setInterval(() => {
        if (secondsLeft.value > 0) {
            secondsLeft.value--;
        }
    }, 1000);

    // Saat timer habis: refresh QR
    refreshTimeout = setTimeout(async () => {
        clearInterval(countdownInterval!);
        await fetchAndRenderQR();
        startCountdown(); // mulai lagi
    }, secondsLeft.value * 1000);
};

// ─── Download QR ─────────────────────────────────────
const downloadQR = () => {
    if (!qrDataUrl.value) return;
    const link = document.createElement('a');
    link.href = qrDataUrl.value;
    link.download = `QR-${props.batch.code}.png`;
    link.click();
};

// ─── Lifecycle ────────────────────────────────────────
onMounted(async () => {
    await fetchAndRenderQR();
    startCountdown();
});

onUnmounted(() => {
    if (countdownInterval) clearInterval(countdownInterval);
    if (refreshTimeout) clearTimeout(refreshTimeout);
});

// ─── Format Tanggal ───────────────────────────────────
const formattedDate = new Date(props.batch.collection_date).toLocaleDateString(
    'id-ID',
    {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    },
);
</script>

<template>
    <Head title="Pengambilan Berhasil" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col items-center gap-6 p-6">
            <div class="flex w-full max-w-xl flex-col gap-5">
                <!-- Card -->
                <div
                    class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
                >
                    <!-- Success Banner -->
                    <div
                        class="flex flex-col items-center px-6 pt-8 pb-6 text-center"
                    >
                        <div
                            class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-green-100"
                        >
                            <CheckCircle2 class="h-8 w-8 text-green-500" />
                        </div>
                        <h1 class="text-[17px] font-bold text-gray-900">
                            Pengambilan Berhasil Dicatat!
                        </h1>
                        <p class="mt-1 max-w-xs text-sm text-gray-500">
                            Batch UCO dari
                            <span class="font-semibold text-gray-700">{{
                                batch.poo_name
                            }}</span>
                            sebesar
                            <span class="font-semibold text-gray-700"
                                >{{ batch.volume }} Liter</span
                            >
                            telah berhasil dicatat.
                        </p>
                    </div>

                    <!-- QR Section -->
                    <div class="flex flex-col items-center px-6 pb-6">
                        <div
                            class="flex w-fit flex-col items-center gap-3 rounded-2xl bg-primary-surface px-6 py-5"
                        >
                            <p
                                class="text-xs font-semibold tracking-widest text-gray-400 uppercase"
                            >
                                QR Batch
                            </p>

                            <!-- QR Image -->
                            <div
                                class="relative rounded-xl border-2 border-primary bg-white p-3 shadow-sm"
                            >
                                <!-- Loading overlay -->
                                <div
                                    v-if="isLoading"
                                    class="absolute inset-0 flex items-center justify-center rounded-xl bg-white/80 backdrop-blur-sm"
                                >
                                    <RefreshCw
                                        class="h-6 w-6 animate-spin text-primary"
                                    />
                                </div>
                                <img
                                    v-if="qrDataUrl"
                                    :src="qrDataUrl"
                                    :alt="`QR ${batch.code}`"
                                    class="h-40 w-40 object-contain"
                                    :class="{ 'opacity-30 blur-sm': isLoading }"
                                />
                                <div
                                    v-else
                                    class="h-40 w-40 animate-pulse rounded-lg bg-gray-100"
                                />
                            </div>

                            <!-- Kode Batch -->
                            <p
                                class="text-sm font-bold tracking-wide text-teal-600"
                            >
                                {{ batch.code }}
                            </p>

                            <!-- Countdown Timer -->
                            <div class="flex flex-col items-center gap-1">
                                <p class="text-xs text-gray-400">
                                    QR refresh otomatis dalam
                                </p>
                                <div class="flex items-center gap-1.5">
                                    <RefreshCw
                                        class="h-3.5 w-3.5"
                                        :class="[
                                            timerColor,
                                            secondsLeft <= 10
                                                ? 'animate-spin'
                                                : '',
                                        ]"
                                    />
                                    <span
                                        class="text-sm font-bold tabular-nums"
                                        :class="timerColor"
                                    >
                                        {{ timerDisplay }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Batch Details -->
                    <div class="px-6 py-5">
                        <div
                            class="grid grid-cols-2 gap-y-3 rounded-xl bg-gray-50 p-4 text-sm"
                        >
                            <span class="text-gray-500">Kode Batch</span>
                            <span
                                class="text-right font-semibold text-teal-600"
                                >{{ batch.code }}</span
                            >

                            <span class="text-gray-500">POO</span>
                            <span
                                class="text-right font-semibold text-gray-900"
                                >{{ batch.poo_name }}</span
                            >

                            <span class="text-gray-500">Volume</span>
                            <span class="text-right font-semibold text-gray-900"
                                >{{ batch.volume }} Liter</span
                            >

                            <span class="text-gray-500">Tanggal</span>
                            <span
                                class="text-right font-semibold text-gray-900"
                                >{{ formattedDate }}</span
                            >

                            <span class="text-gray-500">Status</span>
                            <span class="text-right">
                                <span
                                    class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-0.5 text-xs font-semibold text-green-600"
                                >
                                    {{ batch.status }}
                                </span>
                            </span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="grid grid-cols-2 gap-3 p-5">
                        <Button
                            @click="router.visit('/poos')"
                            class="w-full rounded bg-primary font-medium text-white hover:bg-primary-hover"
                        >
                            Pengambilan Baru
                        </Button>
                        <Button
                            variant="outline"
                            @click="downloadQR"
                            class="w-full rounded border-gray-200 font-medium text-gray-700 transition hover:border-primary hover:text-primary"
                        >
                            <Download class="mr-1.5 h-4 w-4" />
                            Download QR
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
