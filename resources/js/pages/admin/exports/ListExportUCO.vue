<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { PermissionEnum } from '@/enums/PermissionEnum';
import {
    ShoppingCart,
    Download,
    ChevronLeft,
    ChevronRight,
    Search,
} from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { Vue3Lottie } from 'vue3-lottie';
import emptyAnimation from '@/../../public/assets/animations/Pencarian Tidak Ditemukan.json';

import AppLayout from '@/layouts/AppLayout.vue';
import { usePermission } from '@/composables/usePermission';
import { type BreadcrumbItem } from '@/types';

const { can } = usePermission();

interface Batch {
    id: string;
    code: string;
    poo_name: string;
    collection_date: string;
    volume: number;
    nilai: number;
    status: string;
}

interface FinalExport {
    id: string;
    code: string;
    poo_name: string;
    volume: number;
    exported_at: string;
}

interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: { url: string | null; label: string; active: boolean }[];
}

const props = defineProps<{
    readyBatches: Paginated<Batch>;
    history: Paginated<FinalExport>;
    filters: {
        search_batch?: string;
        search_history?: string;
        perPage_batch?: number;
        perPage_history?: number;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Penjualan / Export', href: '/exports' },
];

// ── Search & perPage state ────────────────────────────────────────
const searchBatch = ref(props.filters.search_batch ?? '');
const searchHistory = ref(props.filters.search_history ?? '');
const perPageBatch = ref(props.filters.perPage_batch ?? 10);
const perPageHistory = ref(props.filters.perPage_history ?? 10);

const reload = (extra: Record<string, unknown> = {}) => {
    router.get(
        '/exports',
        {
            search_batch: searchBatch.value,
            search_history: searchHistory.value,
            perPage_batch: perPageBatch.value,
            perPage_history: perPageHistory.value,
            ...extra,
        },
        { preserveState: true, replace: true },
    );
};

// Debounced search
let timerBatch: ReturnType<typeof setTimeout>;
let timerHistory: ReturnType<typeof setTimeout>;

watch(searchBatch, (val) => {
    clearTimeout(timerBatch);
    if (val.length === 0 || val.length >= 3) {
        timerBatch = setTimeout(() => reload(), 400);
    }
});

watch(searchHistory, (val) => {
    clearTimeout(timerHistory);
    if (val.length === 0 || val.length >= 3) {
        timerHistory = setTimeout(() => reload(), 400);
    }
});

watch(perPageBatch, () => reload());
watch(perPageHistory, () => reload());

const goToPage = (url: string | null) => {
    if (!url) return;
    router.get(
        url,
        {
            search_batch: searchBatch.value,
            search_history: searchHistory.value,
            perPage_batch: perPageBatch.value,
            perPage_history: perPageHistory.value,
        },
        { preserveState: true },
    );
};

// ── Helpers ───────────────────────────────────────────────────────
const formatDate = (d: string) =>
    new Date(d).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });

const formatRupiah = (n: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(n);

const goExport = (batchId: string) =>
    router.visit(`/exports/${batchId}/confirmation`);
const downloadISCC = (exportId: string) =>
    window.open(`/exports/${exportId}/download-stored`, '_blank');
</script>

<template>
    <Head title="Penjualan / Export" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col items-center gap-6 p-6">
            <div class="flex w-full max-w-3xl flex-col gap-6">
                <!-- Header -->
                <div>
                    <div class="flex items-center gap-2">
                        <div
                            class="flex h-8 w-8 items-center justify-center text-[#007C95]"
                        >
                            <ShoppingCart class="h-5 w-5" />
                        </div>
                        <h1 class="text-[18px] font-bold text-gray-900">
                            Penjualan / Final Export
                        </h1>
                    </div>
                    <p class="mt-0.5 text-[14px] text-gray-500">
                        Jual UCO ke refinery dan generate dokumen ISCC self
                        declaration
                    </p>
                </div>

                <!-- ── Batch Siap Export ───────────────────────────────────── -->
                <div
                    class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
                >
                    <div
                        class="flex flex-wrap items-center justify-between gap-3 px-5 py-4"
                    >
                        <h2 class="text-sm font-semibold text-gray-700">
                            Batch Siap Export
                            <span class="font-normal text-gray-400"
                                >({{ readyBatches.total }})</span
                            >
                        </h2>
                        <div class="relative">
                            <Search
                                class="absolute top-1/2 left-3 h-3.5 w-3.5 -translate-y-1/2 text-gray-400"
                            />
                            <input
                                v-model="searchBatch"
                                type="text"
                                placeholder="Cari kode / POO..."
                                class="h-8 w-52 rounded-lg border border-gray-200 bg-white pr-3 pl-8 text-xs placeholder-gray-400 focus:border-teal-400 focus:ring-2 focus:ring-teal-100 focus:outline-none"
                            />
                        </div>
                    </div>

                    <div class="overflow-x-auto px-5 pb-2">
                        <table class="w-full text-sm">
                            <thead>
                                <tr
                                    class="border-b border-gray-100 bg-gray-50/60"
                                >
                                    <th
                                        class="px-4 py-2 text-left text-xs font-semibold tracking-wide whitespace-nowrap"
                                    >
                                        Kode Batch
                                    </th>
                                    <th
                                        class="px-4 py-2 text-left text-xs font-semibold tracking-wide whitespace-nowrap"
                                    >
                                        POO
                                    </th>
                                    <th
                                        class="px-4 py-2 text-left text-xs font-semibold tracking-wide whitespace-nowrap"
                                    >
                                        Tanggal Ambil
                                    </th>
                                    <th
                                        class="px-4 py-2 text-left text-xs font-semibold tracking-wide whitespace-nowrap"
                                    >
                                        Volume
                                    </th>
                                    <th
                                        class="px-4 py-2 text-left text-xs font-semibold tracking-wide whitespace-nowrap"
                                    >
                                        Nilai
                                    </th>
                                    <th
                                        class="px-4 py-2 text-left text-xs font-semibold tracking-wide whitespace-nowrap"
                                    >
                                        Status
                                    </th>
                                    <th
                                        class="px-4 py-2 text-left text-xs font-semibold tracking-wide whitespace-nowrap"
                                    >
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr
                                    v-for="batch in readyBatches.data"
                                    :key="batch.id"
                                    class="transition hover:bg-gray-50/50"
                                >
                                    <td class="px-5 py-3.5">
                                        {{ batch.code }}
                                    </td>
                                    <td class="px-5 py-3.5">
                                        {{ batch.poo_name }}
                                    </td>
                                    <td class="px-5 py-3.5">
                                        {{ formatDate(batch.collection_date) }}
                                    </td>
                                    <td class="px-5 py-3.5">
                                        {{ batch.volume }} L
                                    </td>
                                    <td class="px-5 py-3.5">
                                        {{ formatRupiah(batch.nilai) }}
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <span
                                            class="inline-flex items-center truncate rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-700"
                                        >
                                            {{ batch.status }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <button
                                            v-if="can(PermissionEnum.CREATE_PENJUALAN)"
                                            @click="goExport(batch.id)"
                                            class="rounded-lg border border-primary px-4 py-1.5 text-xs font-semibold text-primary transition"
                                        >
                                            Export
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="readyBatches.data.length === 0">
                                    <td
                                        colspan="7"
                                        class="px-5 py-10 text-center"
                                    >
                                        <div
                                            class="flex flex-col items-center gap-2"
                                        >
                                            <Vue3Lottie
                                                :animationData="emptyAnimation"
                                                :height="160"
                                                :width="160"
                                                :loop="true"
                                            />
                                            <p
                                                class="text-sm font-medium text-gray-600"
                                            >
                                                Tidak ada batch siap export
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination batch -->
                    <div
                        class="flex items-center justify-between border-t border-gray-100 px-5 py-3"
                    >
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-500"
                                >Baris per halaman</span
                            >
                            <select
                                v-model="perPageBatch"
                                class="h-7 rounded-lg border border-gray-200 bg-white px-2 text-xs text-gray-600 focus:outline-none"
                            >
                                <option :value="10">10</option>
                                <option :value="25">25</option>
                                <option :value="50">50</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-xs text-gray-500">
                                {{ readyBatches.from }}–{{
                                    readyBatches.to
                                }}
                                dari {{ readyBatches.total }}
                            </span>
                            <div class="flex gap-1">
                                <button
                                    @click="
                                        goToPage(
                                            readyBatches.links[0]?.url ?? null,
                                        )
                                    "
                                    :disabled="readyBatches.current_page === 1"
                                    class="rounded-lg border border-gray-200 p-1.5 text-gray-400 transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40"
                                >
                                    <ChevronLeft class="h-3.5 w-3.5" />
                                </button>
                                <button
                                    @click="
                                        goToPage(
                                            readyBatches.links[
                                                readyBatches.links.length - 1
                                            ]?.url ?? null,
                                        )
                                    "
                                    :disabled="
                                        readyBatches.current_page ===
                                        readyBatches.last_page
                                    "
                                    class="rounded-lg border border-gray-200 p-1.5 text-gray-400 transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40"
                                >
                                    <ChevronRight class="h-3.5 w-3.5" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Riwayat Final Export ────────────────────────────────── -->
                <div
                    class="mb-4 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
                >
                    <div
                        class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-4"
                    >
                        <h2 class="text-sm font-semibold text-gray-700">
                            Riwayat Final Export
                            <span class="font-normal text-gray-400"
                                >({{ history.total }})</span
                            >
                        </h2>
                        <div class="relative">
                            <Search
                                class="absolute top-1/2 left-3 h-3.5 w-3.5 -translate-y-1/2 text-gray-400"
                            />
                            <input
                                v-model="searchHistory"
                                type="text"
                                placeholder="Cari kode / POO..."
                                class="h-8 w-52 rounded-lg border border-gray-200 bg-white pr-3 pl-8 text-xs placeholder-gray-400 focus:border-teal-400 focus:ring-2 focus:ring-teal-100 focus:outline-none"
                            />
                        </div>
                    </div>

                    <div class="divide-y divide-gray-100">
                        <div
                            v-for="item in history.data"
                            :key="item.id"
                            class="flex items-center justify-between px-5 py-4 transition hover:bg-gray-50/50"
                        >
                            <div class="flex flex-col gap-1">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="text-sm font-semibold text-teal-600"
                                        >{{ item.code }}</span
                                    >
                                    <span
                                        class="inline-flex items-center rounded-full bg-purple-100 px-2 py-0.5 text-[10px] font-bold tracking-wider text-purple-600 uppercase"
                                    >
                                        Final Export
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500">
                                    {{ item.poo_name }} • {{ item.volume }} L
                                </p>
                                <p class="text-xs text-gray-400">
                                    Ekspor: {{ formatDate(item.exported_at) }}
                                </p>
                            </div>
                            <button
                                v-if="can(PermissionEnum.DOWNLOAD_PENJUALAN)"
                                @click="downloadISCC(item.id)"
                                class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl bg-teal-600 text-white shadow-sm transition hover:bg-teal-700"
                            >
                                <Download class="h-4 w-4" />
                            </button>
                        </div>

                        <div
                            v-if="history.data.length === 0"
                            class="p-10"
                        >
                            <div class="flex flex-col items-center gap-2">
                                <Vue3Lottie
                                    :animationData="emptyAnimation"
                                    :height="160"
                                    :width="160"
                                    :loop="true"
                                />
                                <p class="text-sm font-medium text-gray-600">
                                    Belum ada riwayat export
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination history -->
                    <div
                        class="flex items-center justify-between border-t border-gray-100 px-5 py-3"
                    >
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-500"
                                >Baris per halaman</span
                            >
                            <select
                                v-model="perPageHistory"
                                class="h-7 rounded-lg border border-gray-200 bg-white px-2 text-xs text-gray-600 focus:outline-none"
                            >
                                <option :value="10">10</option>
                                <option :value="25">25</option>
                                <option :value="50">50</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-xs text-gray-500">
                                {{ history.from }}–{{ history.to }} dari
                                {{ history.total }}
                            </span>
                            <div class="flex gap-1">
                                <button
                                    @click="
                                        goToPage(history.links[0]?.url ?? null)
                                    "
                                    :disabled="history.current_page === 1"
                                    class="rounded-lg border border-gray-200 p-1.5 text-gray-400 transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40"
                                >
                                    <ChevronLeft class="h-3.5 w-3.5" />
                                </button>
                                <button
                                    @click="
                                        goToPage(
                                            history.links[
                                                history.links.length - 1
                                            ]?.url ?? null,
                                        )
                                    "
                                    :disabled="
                                        history.current_page ===
                                        history.last_page
                                    "
                                    class="rounded-lg border border-gray-200 p-1.5 text-gray-400 transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40"
                                >
                                    <ChevronRight class="h-3.5 w-3.5" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
