<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ScanLine,
    CheckCircle2,
    XCircle,
    Clock,
} from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';
import { toast } from 'vue-sonner';
import BadgeBussines from '@/components/BadgeBussines.vue';
import QrScanner from '@/components/QrScanner.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface PooItem {
    id: string;
    transaction_code: string;
    volume: number;
}

interface TransferData {
    id: string;
    transfer_code: string;
    volume_actual: number;
    status: number;
    status_label: string;
    sender_name: string;
    expires_at: string | null;
    poos: PooItem[];
}

const props = defineProps<{
    transfer?: TransferData | null;
    searchCode?: string | null;
    claimError?: string | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Transfer UCO', href: '/transfers' },
    { title: 'Terima UCO', href: '#' },
];

const isScannerOpen = ref(false);
const manualCode = ref(props.searchCode ?? '');

const form = useForm({
    transfer_code: props.transfer?.transfer_code ?? '',
});

watch(
    () => props.transfer,
    (val) => {
        form.transfer_code = val?.transfer_code ?? '';
    },
);

const onQrDetected = (code: string) => {
    isScannerOpen.value = false;
    manualCode.value = code;
    router.get('/transfers/claim', { code }, { preserveState: false });
};

const handleSearch = () => {
    if (!manualCode.value.trim()) {
        toast.error('Masukkan kode transfer terlebih dahulu');
        return;
    }
    router.get(
        '/transfers/claim',
        { code: manualCode.value.trim() },
        { preserveState: false },
    );
};

const handleTerima = () => {
    if (!form.transfer_code) return;

    form.post('/transfers/confirm-claim', {
        onSuccess: () => {
            toast.success('Berhasil!', {
                description: 'Kepemilikan berhasil diterima',
            });
        },
        onError: () => {
            toast.error('Gagal!', {
                description: 'Terjadi kesalahan saat menerima transfer',
            });
        },
    });
};

// Status helpers
const isPending = computed(() => props.transfer?.status === 0);
const isExpired = computed(() => {
    if (!props.transfer?.expires_at) return false;
    return new Date(props.transfer.expires_at).getTime() < Date.now();
});
const isClaimable = computed(() => isPending.value && !isExpired.value);

// Expiry countdown (snapshot saat render, tidak live — cukup untuk halaman ini)
const expiryLabel = computed(() => {
    if (!props.transfer?.expires_at) return null;
    const diff = new Date(props.transfer.expires_at).getTime() - Date.now();
    if (diff <= 0) return 'Kadaluarsa';
    const hours = Math.floor(diff / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    return `${hours}j ${minutes}m lagi`;
});
</script>

<template>
    <Head title="Terima Transfer UCO" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col items-center gap-6 p-6">
            <div class="flex w-full max-w-xl flex-col gap-5">
                <button
                    @click="router.visit('/transfers')"
                    class="flex w-fit items-center gap-1.5 text-sm text-gray-500 transition hover:text-gray-700"
                >
                    <ArrowLeft class="h-4 w-4" />
                    Kembali
                </button>

                <div
                    class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
                >
                    <div class="border-b border-gray-100 px-6 pt-6 pb-4">
                        <h1 class="text-[16px] font-bold text-gray-900">
                            Terima Transfer UCO
                        </h1>
                        <p class="mt-0.5 text-[13px] text-gray-500">
                            Scan QR atau masukkan kode transfer
                        </p>
                    </div>

                    <div class="grid gap-5 p-6">
                        <!-- Scanner -->
                        <QrScanner
                            :is-open="isScannerOpen"
                            @detected="onQrDetected"
                            @close="isScannerOpen = false"
                        />

                        <!-- Camera CTA -->
                        <div
                            v-if="!isScannerOpen"
                            class="flex flex-col items-center justify-center gap-3 rounded-2xl border-2 border-dashed border-gray-200 bg-gray-50 py-10"
                        >
                            <div
                                class="flex h-14 w-14 items-center justify-center rounded-2xl border border-gray-200 bg-white text-teal-600 shadow-sm"
                            >
                                <ScanLine class="h-7 w-7" />
                            </div>
                            <div class="text-center">
                                <p class="text-sm font-semibold text-gray-700">
                                    Scan QR Transfer
                                </p>
                                <p class="mt-0.5 text-xs text-gray-400">
                                    Arahkan kamera ke QR Code pengirim
                                </p>
                            </div>
                            <Button
                                @click="isScannerOpen = true"
                                class="mt-1 rounded-lg bg-primary px-5 py-2 text-sm font-medium text-white hover:bg-primary-hover"
                            >
                                Buka Kamera
                            </Button>
                        </div>

                        <!-- Divider -->
                        <div class="flex items-center gap-3">
                            <div class="h-px flex-1 bg-gray-100" />
                            <span class="text-xs text-gray-400"
                                >atau masukkan kode manual</span
                            >
                            <div class="h-px flex-1 bg-gray-100" />
                        </div>

                        <!-- Manual Input -->
                        <div class="flex gap-2">
                            <Input
                                v-model="manualCode"
                                placeholder="Contoh: TRF-XXXXXXXX"
                                class="flex-1 border-gray-200"
                                @keyup.enter="handleSearch"
                            />
                            <Button
                                @click="handleSearch"
                                class="shrink-0 rounded bg-primary px-5 font-medium text-white hover:bg-primary-hover"
                            >
                                Cari
                            </Button>
                        </div>

                        <!-- Error BE (expired / sudah dipakai / tidak ditemukan) -->
                        <div
                            v-if="claimError"
                            class="flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 p-4"
                        >
                            <XCircle
                                class="h-5 w-5 flex-shrink-0 text-red-500"
                            />
                            <div>
                                <p class="text-sm font-semibold text-red-700">
                                    Transfer Tidak Tersedia
                                </p>
                                <p class="mt-0.5 text-xs text-red-500">
                                    {{ claimError }}
                                </p>
                            </div>
                        </div>

                        <!-- Transfer Found & Claimable -->
                        <div
                            v-else-if="transfer && isClaimable"
                            class="space-y-3 rounded-xl border border-teal-200 bg-teal-50 p-4"
                        >
                            <div class="flex items-center gap-2">
                                <CheckCircle2
                                    class="h-5 w-5 flex-shrink-0 text-teal-600"
                                />
                                <p class="text-sm font-semibold text-teal-700">
                                    Transfer Ditemukan
                                </p>
                            </div>

                            <div class="grid grid-cols-2 gap-y-2 text-sm">
                                <span class="text-gray-500">Kode</span>
                                <span
                                    class="text-right font-semibold text-teal-600"
                                    >{{ transfer.transfer_code }}</span
                                >

                                <span class="text-gray-500">Pengirim</span>
                                <span
                                    class="text-right font-semibold text-gray-900"
                                    >{{ transfer.sender_name }}</span
                                >

                                <span class="text-gray-500">Total Volume</span>
                                <span
                                    class="text-right font-semibold text-gray-900"
                                    >{{ transfer.volume_actual }} L</span
                                >
                            </div>

                            <!-- Expiry info -->
                            <div
                                v-if="expiryLabel"
                                class="flex items-center gap-1.5 rounded-lg border border-amber-100 bg-amber-50 px-3 py-2 text-xs font-medium text-amber-700"
                            >
                                <Clock class="h-3.5 w-3.5 shrink-0" />
                                Berlaku: {{ expiryLabel }}
                            </div>

                            <!-- Collections -->
                            <div class="border-t border-teal-100 pt-2">
                                <p
                                    class="mb-2 text-xs font-semibold text-teal-600"
                                >
                                    Collection ({{ transfer.poos.length }})
                                </p>
                                <div class="grid gap-1.5">
                                    <div
                                        v-for="(poo, i) in transfer.poos"
                                        :key="i"
                                        class="flex items-center justify-between rounded-lg border border-teal-100 bg-white px-3 py-2"
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
                        </div>

                        <!-- Transfer expired / already claimed / cancelled -->
                        <div
                            v-else-if="transfer && !isClaimable"
                            class="flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 p-4"
                        >
                            <XCircle
                                class="h-5 w-5 flex-shrink-0 text-red-500"
                            />
                            <div>
                                <p class="text-sm font-semibold text-red-700">
                                    Transfer Tidak Tersedia
                                </p>
                                <p class="mt-0.5 text-xs text-red-500">
                                    <template v-if="isExpired"
                                        >Transfer ini sudah kadaluarsa (lebih
                                        dari 24 jam).</template
                                    >
                                    <template v-else
                                        >Transfer ini sudah diklaim atau
                                        dibatalkan.</template
                                    >
                                </p>
                            </div>
                        </div>

                        <!-- Not Found -->
                        <div
                            v-else-if="searchCode && !transfer && !claimError"
                            class="flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 p-4"
                        >
                            <XCircle
                                class="h-5 w-5 flex-shrink-0 text-red-500"
                            />
                            <div>
                                <p class="text-sm font-semibold text-red-700">
                                    Tidak Ditemukan
                                </p>
                                <p class="mt-0.5 text-xs text-red-500">
                                    Kode "{{ searchCode }}" tidak ditemukan.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-6 pb-6">
                        <Button
                            @click="handleTerima"
                            :disabled="
                                form.processing || !transfer || !isClaimable
                            "
                            class="w-full rounded bg-primary py-2.5 font-medium text-white hover:bg-primary-hover disabled:opacity-50"
                        >
                            Terima Kepemilikan
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
