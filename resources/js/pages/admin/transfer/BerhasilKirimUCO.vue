<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { CheckCircle2, Download, Copy, Clock, XCircle } from 'lucide-vue-next';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { toast } from 'vue-sonner';
import BadgeBussines from '@/components/BadgeBussines.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface PooItem {
    id: string;
    transaction_code: string;
    volume: number;
}

interface Transfer {
    id: string;
    transfer_code: string;
    volume_actual: number;
    status: string;
    status_label: string;
    sender_name: string;
    expires_at: string | null;
    is_claimable: boolean;
    poos: PooItem[];
    created_at: string;
}

const props = defineProps<{ transfer: Transfer }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Transfer UCO', href: '/transfers' },
    { title: 'Berhasil', href: '#' },
];

// =================== QR ===================
// QR encode claim_url (URL lengkap) bukan sekadar kode mentah
const qrUrl = computed(
    () =>
        `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(props.transfer.transfer_code)}`,
);

// =================== COUNTDOWN ===================
const timeLeft = ref('');
const isExpired = ref(false);
let timer: ReturnType<typeof setInterval> | null = null;

const updateCountdown = () => {
    if (!props.transfer.expires_at) return;

    const expiry = new Date(props.transfer.expires_at).getTime();
    const now = Date.now();
    const diff = expiry - now;

    if (diff <= 0) {
        isExpired.value = true;
        timeLeft.value = 'Kadaluarsa';
        if (timer) clearInterval(timer);
        return;
    }

    const hours = Math.floor(diff / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((diff % (1000 * 60)) / 1000);

    timeLeft.value = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
};

onMounted(() => {
    if (props.transfer.expires_at) {
        updateCountdown();
        timer = setInterval(updateCountdown, 1000);
    }
});

onUnmounted(() => {
    if (timer) clearInterval(timer);
});

// =================== STATUS BADGE ===================
const statusBadgeClass = computed(() => {
    switch (props.transfer.status) {
        case 'claimed':
            return 'bg-green-100 text-green-700';
        case 'expired':
            return 'bg-red-100 text-red-600';
        default:
            return 'bg-amber-100 text-amber-700';
    }
});

// =================== ACTIONS ===================
const copyCode = () => {
    navigator.clipboard.writeText(props.transfer.transfer_code);
    toast.success('Kode disalin!');
};

const downloadQR = () => {
    const link = document.createElement('a');
    link.href = qrUrl.value;
    link.download = `QR-${props.transfer.transfer_code}.png`;
    link.click();
};
</script>

<template>
    <Head title="Transfer Berhasil" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col items-center gap-6 p-6">
            <div class="flex w-full max-w-xl flex-col gap-5">
                <div
                    class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
                >
                    <!-- Success / Expired Banner -->
                    <div
                        class="flex flex-col items-center px-6 pt-8 pb-5 text-center"
                    >
                        <div
                            class="mb-4 flex h-14 w-14 items-center justify-center rounded-full"
                            :class="
                                isExpired || transfer.status === 'expired'
                                    ? 'bg-red-100'
                                    : transfer.status === 'claimed'
                                      ? 'bg-green-100'
                                      : 'bg-green-100'
                            "
                        >
                            <XCircle
                                v-if="
                                    isExpired || transfer.status === 'expired'
                                "
                                class="h-8 w-8 text-red-500"
                            />
                            <CheckCircle2
                                v-else
                                class="h-8 w-8 text-green-500"
                            />
                        </div>
                        <h1 class="text-[17px] font-bold text-gray-900">
                            <template
                                v-if="
                                    isExpired || transfer.status === 'expired'
                                "
                                >Transfer Kadaluarsa</template
                            >
                            <template v-else-if="transfer.status === 'claimed'"
                                >Transfer Sudah Diterima</template
                            >
                            <template v-else
                                >Transfer Berhasil Dibuat!</template
                            >
                        </h1>
                        <p class="mt-1 text-sm text-gray-500">
                            <template
                                v-if="transfer.is_claimable && !isExpired"
                            >
                                Bagikan kode atau QR di bawah ke penerima
                            </template>
                            <template v-else-if="transfer.status === 'claimed'">
                                Transfer ini sudah berhasil diklaim oleh
                                penerima
                            </template>
                            <template v-else>
                                QR dan kode ini sudah tidak dapat digunakan
                            </template>
                        </p>
                    </div>

                    <!-- QR + Kode -->
                    <div class="flex flex-col items-center gap-3 px-6 pb-5">
                        <div
                            class="flex w-full flex-col items-center gap-3 rounded-2xl border border-primary/10 bg-primary/5 px-8 py-5"
                        >
                            <p
                                class="text-xs font-semibold tracking-widest text-gray-400 uppercase"
                            >
                                QR Transfer
                            </p>

                            <!-- QR Image — grayscale jika tidak bisa diklaim -->
                            <div
                                class="relative rounded-xl border-2 bg-white p-3 shadow-sm"
                                :class="
                                    transfer.is_claimable && !isExpired
                                        ? 'border-primary'
                                        : 'border-gray-200'
                                "
                            >
                                <img
                                    :src="qrUrl"
                                    :alt="transfer.transfer_code"
                                    class="h-40 w-40 object-contain transition"
                                    :class="
                                        transfer.is_claimable && !isExpired
                                            ? ''
                                            : 'opacity-30 grayscale'
                                    "
                                />

                                <!-- Overlay jika tidak claimable -->
                                <div
                                    v-if="!transfer.is_claimable || isExpired"
                                    class="absolute inset-0 flex items-center justify-center rounded-xl"
                                >
                                    <span
                                        class="rounded-lg bg-white/80 px-2 py-1 text-xs font-bold text-red-500"
                                    >
                                        {{
                                            transfer.status === 'claimed'
                                                ? 'Sudah Dipakai'
                                                : 'Kadaluarsa'
                                        }}
                                    </span>
                                </div>
                            </div>

                            <!-- Kode Transfer -->
                            <div class="flex items-center gap-2">
                                <span
                                    class="text-sm font-bold tracking-wide text-primary"
                                >
                                    {{ transfer.transfer_code }}
                                </span>
                                <button
                                    @click="copyCode"
                                    class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
                                >
                                    <Copy class="h-3.5 w-3.5" />
                                </button>
                            </div>

                            <!-- Countdown Timer -->
                            <div
                                v-if="transfer.expires_at"
                                class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold"
                                :class="
                                    isExpired
                                        ? 'bg-red-50 text-red-500'
                                        : 'bg-amber-50 text-amber-600'
                                "
                            >
                                <Clock class="h-3.5 w-3.5" />
                                <span v-if="!isExpired"
                                    >Kadaluarsa dalam {{ timeLeft }}</span
                                >
                                <span v-else>Sudah kadaluarsa</span>
                            </div>
                        </div>
                    </div>

                    <!-- Detail Transfer -->
                    <div class="px-6 pb-5">
                        <div
                            class="grid grid-cols-2 gap-y-3 rounded-xl bg-gray-50 p-4 text-sm"
                        >
                            <span class="text-gray-500">Kode Batch</span>
                            <span
                                class="text-right font-semibold text-primary"
                                >{{ transfer.transfer_code }}</span
                            >

                            <span class="text-gray-500">Pengirim</span>
                            <span
                                class="text-right font-semibold text-gray-900"
                                >{{ transfer.sender_name }}</span
                            >

                            <span class="text-gray-500">Total Volume</span>
                            <span class="text-right font-semibold text-teal-600"
                                >{{ transfer.volume_actual }} L</span
                            >

                            <span class="text-gray-500">Tanggal</span>
                            <span
                                class="text-right font-semibold text-gray-900"
                                >{{ transfer.created_at }}</span
                            >
                        </div>
                    </div>

                    <!-- Collections yang ditransfer -->
                    <div class="px-6 pb-5">
                        <p
                            class="mb-3 text-xs font-semibold tracking-widest text-gray-400 uppercase"
                        >
                            Collection Ditransfer ({{ transfer.poos.length }})
                        </p>
                        <div class="grid gap-2">
                            <div
                                v-for="poo in transfer.poos"
                                :key="poo.id"
                                class="flex items-center justify-between rounded-xl border border-gray-100 bg-gray-50 px-4 py-2.5"
                            >
                                <span
                                    class="text-sm font-medium text-gray-900"
                                    >{{ poo.transaction_code }}</span
                                >
                                <span
                                    class="text-sm font-semibold text-gray-900"
                                    >{{ poo.volume }} L</span
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div
                        class="grid grid-cols-2 gap-3 border-t border-gray-100 p-5"
                    >
                        <Button
                            variant="outline"
                            class="w-full rounded border-gray-200"
                            @click="router.visit('/transfers')"
                        >
                            Kembali
                        </Button>
                        <Button
                            @click="downloadQR"
                            :disabled="!transfer.is_claimable || isExpired"
                            class="w-full rounded bg-primary font-medium text-white hover:bg-primary-hover disabled:opacity-40"
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
