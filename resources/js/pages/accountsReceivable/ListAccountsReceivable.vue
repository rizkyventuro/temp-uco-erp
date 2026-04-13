<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    Search,
    ChevronUp,
    ChevronDown,
    Plus,
    EllipsisVertical,
    Eye,
    Pencil,
    Trash2,
    ReceiptText,
    Clock,
    TrendingUp,
} from 'lucide-vue-next';
import { ref, watch, computed } from 'vue';
import { toast } from 'vue-sonner';
import { Vue3Lottie } from 'vue3-lottie';
import emptyAnimation from '@/../../public/assets/animations/Pencarian Tidak Ditemukan.json';

import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import TableFilter from '@/components/TableFilter.vue';
import type { FilterValues } from '@/components/TableFilter.vue';
import TablePagination from '@/components/TablePagination.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

import SupplierFormModal from '@/components/Supplier/SupplierFormModal.vue';
import type { City } from '@/components/Supplier/SupplierFormModal.vue';

// ── Types ──────────────────────────────────────────────────────

interface PiutangStats {
    total_piutang: number;
    total_tagihan: number;
    lewat_jatuh_tempo: number;
    lewat_count: number;
    diterima_bulan_ini: number;
    diterima_pct: number;
}

interface PiutangItem {
    id: string;
    no_invoice: string;
    buyer: string;
    tanggal: string;
    jatuh_tempo: string;
    jumlah: number;
    diterima: number;
    sisa: number;
    status: 'lunas' | 'sebagian' | 'belum_bayar';
    is_overdue: boolean;
}

interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: { url: string | null; label: string; active: boolean }[];
}

// ── Props ──────────────────────────────────────────────────────

const props = defineProps<{
    stats: PiutangStats;
    piutang: Paginated<PiutangItem>;
    filters: {
        search?: string;
        perPage?: number;
        status?: string;
        sort?: string;
        direction?: 'asc' | 'desc';
    };
    allCities?: City[];
}>();

// ── Breadcrumbs ────────────────────────────────────────────────

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Piutang (AR)', href: '/piutang-ar' },
];

// ── Table State ────────────────────────────────────────────────

const searchQuery = ref(props.filters.search ?? '');
const perPage = ref(props.filters.perPage ?? 10);
const sortColumn = ref(props.filters.sort ?? 'tanggal');
const sortDirection = ref<'asc' | 'desc'>(props.filters.direction ?? 'desc');

const filterFields = computed(() => [
    {
        key: 'status', label: 'Status', type: 'select' as const,
        options: [
            { label: 'Lunas', value: 'lunas' },
            { label: 'Sebagian', value: 'sebagian' },
            { label: 'Belum Bayar', value: 'belum_bayar' },
        ],
    },
]);

const filterValues = ref<FilterValues>({
    status: props.filters.status ?? undefined,
});

// ── Navigation ─────────────────────────────────────────────────

const buildParams = (extra: FilterValues = {}) => ({
    search: searchQuery.value || undefined,
    perPage: perPage.value,
    sort: sortColumn.value,
    direction: sortDirection.value,
    ...extra,
});

const handleSort = (column: string) => {
    if (sortColumn.value === column) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortColumn.value = column;
        sortDirection.value = 'asc';
    }
    router.get('/piutang-ar', buildParams(filterValues.value), { preserveState: true, replace: true });
};

const handleFilter = (values: FilterValues) => {
    filterValues.value = values;
    router.get('/piutang-ar', buildParams(values), { preserveState: true, replace: true });
};

const handleFilterReset = () => {
    filterValues.value = {};
    router.get('/piutang-ar', buildParams({}), { preserveState: true, replace: true });
};

const goToPage = (url: string | null) => {
    if (!url) return;
    router.get(url, buildParams(filterValues.value), { preserveState: true });
};

let searchTimeout: ReturnType<typeof setTimeout>;
watch(searchQuery, (val) => {
    clearTimeout(searchTimeout);
    if (val.length === 0 || val.length >= 3) {
        searchTimeout = setTimeout(() => {
            router.get('/piutang-ar', buildParams(filterValues.value), { preserveState: true, replace: true });
        }, 400);
    }
});

watch(perPage, () => {
    router.get('/piutang-ar', buildParams(filterValues.value), { preserveState: true, replace: true });
});

// ── Helpers ────────────────────────────────────────────────────

function formatRp(val: number | null | undefined) {
    if (val == null) return '—';
    return 'Rp ' + val.toLocaleString('id-ID');
}

const statusConfig = {
    lunas: { label: 'Lunas', class: 'bg-emerald-50 text-emerald-700' },
    sebagian: { label: 'Sebagian', class: 'bg-amber-50 text-amber-600' },
    belum_bayar: { label: 'Belum Bayar', class: 'bg-rose-50 text-rose-600' },
};

const sortClass = (col: string) =>
    sortColumn.value === col ? 'text-[#007C95]' : 'text-gray-500 hover:text-gray-700';

const chevronClass = (col: string, dir: 'asc' | 'desc') =>
    sortColumn.value === col && sortDirection.value === dir ? 'text-[#007C95]' : 'text-gray-300';

// ── Modal ──────────────────────────────────────────────────────

const isFormOpen = ref(false);
</script>

<template>

    <Head title="Piutang (AR)" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 pb-10 md:p-6">

            <!-- ── Header ─────────────────────────────────────── -->
            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div class="flex-1 min-w-0">
                    <h1 class="text-[24px] font-bold text-gray-900">Piutang (AR)</h1>
                    <p class="mt-0.5 text-[16px] text-gray-500">Kelola tagihan ke buyer / pembeli</p>
                </div>
                <div class="flex w-full justify-end md:w-auto md:flex-shrink-0">
                    <Button
                        class="flex w-fit items-center justify-center gap-1.5 rounded-lg bg-[#007C95] px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-[#006b80]"
                        @click="isFormOpen = true">
                        <Plus class="size-4" />
                        Tambah Piutang
                    </Button>
                </div>
            </div>

            <!-- ── Stat Cards ─────────────────────────────────── -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">

                <div class="bg-white rounded-xl border border-[#EDEDED] shadow-sm p-4 flex flex-col">
                    <div class="flex items-center justify-between">
                        <span class="text-[14px] text-[#101010] font-semibold">Total Piutang</span>
                        <span class="text-gray-300">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.2"
                                    d="M14 5.5V12.5C14 12.6326 13.9473 12.7598 13.8536 12.8536C13.7598 12.9473 13.6326 13 13.5 13H3.5C3.23478 13 2.98043 12.8946 2.79289 12.7071C2.60536 12.5196 2.5 12.2652 2.5 12V4C2.5 4.26522 2.60536 4.51957 2.79289 4.70711C2.98043 4.89464 3.23478 5 3.5 5H13.5C13.6326 5 13.7598 5.05268 13.8536 5.14645C13.9473 5.24021 14 5.36739 14 5.5Z"
                                    fill="#FBD14E" />
                                <path
                                    d="M13.5 4.5H3.5C3.36739 4.5 3.24021 4.44732 3.14645 4.35355C3.05268 4.25979 3 4.13261 3 4C3 3.86739 3.05268 3.74021 3.14645 3.64645C3.24021 3.55268 3.36739 3.5 3.5 3.5H12C12.1326 3.5 12.2598 3.44732 12.3536 3.35355C12.4473 3.25979 12.5 3.13261 12.5 3C12.5 2.86739 12.4473 2.74021 12.3536 2.64645C12.2598 2.55268 12.1326 2.5 12 2.5H3.5C3.10218 2.5 2.72064 2.65804 2.43934 2.93934C2.15804 3.22064 2 3.60218 2 4V12C2 12.3978 2.15804 12.7794 2.43934 13.0607C2.72064 13.342 3.10218 13.5 3.5 13.5H13.5C13.7652 13.5 14.0196 13.3946 14.2071 13.2071C14.3946 13.0196 14.5 12.7652 14.5 12.5V5.5C14.5 5.23478 14.3946 4.98043 14.2071 4.79289C14.0196 4.60536 13.7652 4.5 13.5 4.5ZM13.5 12.5H3.5C3.36739 12.5 3.24021 12.4473 3.14645 12.3536C3.05268 12.2598 3 12.1326 3 12V5.41437C3.16055 5.47129 3.32966 5.50025 3.5 5.5H13.5V12.5ZM10.5 8.75C10.5 8.60166 10.544 8.45666 10.6264 8.33332C10.7088 8.20999 10.8259 8.11386 10.963 8.05709C11.1 8.00033 11.2508 7.98547 11.3963 8.01441C11.5418 8.04335 11.6754 8.11478 11.7803 8.21967C11.8852 8.32456 11.9566 8.4582 11.9856 8.60368C12.0145 8.74917 11.9997 8.89997 11.9429 9.03701C11.8861 9.17406 11.79 9.29119 11.6667 9.3736C11.5433 9.45601 11.3983 9.5 11.25 9.5C11.0511 9.5 10.8603 9.42098 10.7197 9.28033C10.579 9.13968 10.5 8.94891 10.5 8.75Z"
                                    fill="#101010" />
                            </svg>
                        </span>
                    </div>
                    <p class="text-[24px] font-bold tracking-tight text-[#101010]">{{ formatRp(stats.total_piutang) }}
                    </p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ stats.total_tagihan }} tagihan aktif</p>
                </div>

                <div class="bg-white rounded-xl border border-[#EDEDED] shadow-sm p-4 flex flex-col">
                    <div class="flex items-center justify-between">
                        <span class="text-[14px] text-[#101010] font-semibold">Lewat Jatuh Tempo</span>
                        <span class="text-rose-400">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.2"
                                    d="M13.5 8.5C13.5 9.5878 13.1774 10.6512 12.5731 11.5556C11.9687 12.4601 11.1098 13.1651 10.1048 13.5813C9.09977 13.9976 7.9939 14.1065 6.92701 13.8943C5.86011 13.6821 4.8801 13.1583 4.11092 12.3891C3.34173 11.6199 2.8179 10.6399 2.60568 9.573C2.39346 8.5061 2.50238 7.40023 2.91867 6.39524C3.33495 5.39025 4.0399 4.53126 4.94437 3.92692C5.84884 3.32257 6.91221 3 8 3C9.45869 3 10.8576 3.57946 11.8891 4.61091C12.9205 5.64236 13.5 7.04131 13.5 8.5Z"
                                    fill="#F14141" />
                                <path
                                    d="M8 2.5C6.81331 2.5 5.65328 2.85189 4.66658 3.51118C3.67989 4.17047 2.91085 5.10754 2.45673 6.2039C2.0026 7.30026 1.88378 8.50666 2.11529 9.67054C2.3468 10.8344 2.91825 11.9035 3.75736 12.7426C4.59648 13.5818 5.66558 14.1532 6.82946 14.3847C7.99335 14.6162 9.19975 14.4974 10.2961 14.0433C11.3925 13.5892 12.3295 12.8201 12.9888 11.8334C13.6481 10.8467 14 9.68669 14 8.5C13.9982 6.90926 13.3655 5.38419 12.2406 4.25937C11.1158 3.13454 9.59074 2.50182 8 2.5ZM8 13.5C7.0111 13.5 6.0444 13.2068 5.22215 12.6573C4.39991 12.1079 3.75904 11.327 3.38061 10.4134C3.00217 9.49979 2.90315 8.49445 3.09608 7.52455C3.289 6.55464 3.76521 5.66373 4.46447 4.96447C5.16373 4.2652 6.05465 3.789 7.02455 3.59607C7.99446 3.40315 8.99979 3.50216 9.91342 3.8806C10.8271 4.25904 11.6079 4.8999 12.1574 5.72215C12.7068 6.54439 13 7.51109 13 8.5C12.9985 9.82563 12.4713 11.0965 11.5339 12.0339C10.5965 12.9712 9.32563 13.4985 8 13.5ZM10.8538 5.64625C10.9002 5.69269 10.9371 5.74783 10.9623 5.80853C10.9874 5.86923 11.0004 5.93429 11.0004 6C11.0004 6.06571 10.9874 6.13077 10.9623 6.19147C10.9371 6.25217 10.9002 6.30731 10.8538 6.35375L8.35375 8.85375C8.3073 8.90021 8.25215 8.93705 8.19145 8.9622C8.13075 8.98734 8.0657 9.00028 8 9.00028C7.93431 9.00028 7.86925 8.98734 7.80855 8.9622C7.74786 8.93705 7.69271 8.90021 7.64625 8.85375C7.5998 8.80729 7.56295 8.75214 7.53781 8.69145C7.51266 8.63075 7.49972 8.5657 7.49972 8.5C7.49972 8.4343 7.51266 8.36925 7.53781 8.30855C7.56295 8.24786 7.5998 8.1927 7.64625 8.14625L10.1463 5.64625C10.1927 5.59976 10.2478 5.56288 10.3085 5.53772C10.3692 5.51256 10.4343 5.49961 10.5 5.49961C10.5657 5.49961 10.6308 5.51256 10.6915 5.53772C10.7522 5.56288 10.8073 5.59976 10.8538 5.64625ZM6 1C6 0.867392 6.05268 0.740215 6.14645 0.646447C6.24022 0.552678 6.36739 0.5 6.5 0.5H9.5C9.63261 0.5 9.75979 0.552678 9.85356 0.646447C9.94732 0.740215 10 0.867392 10 1C10 1.13261 9.94732 1.25979 9.85356 1.35355C9.75979 1.44732 9.63261 1.5 9.5 1.5H6.5C6.36739 1.5 6.24022 1.44732 6.14645 1.35355C6.05268 1.25979 6 1.13261 6 1Z"
                                    fill="#101010" />
                            </svg>

                        </span>
                    </div>
                    <p class="text-[24px] font-bold tracking-tight text-[#101010]">{{ formatRp(stats.lewat_jatuh_tempo)
                        }}</p>
                    <p class="text-xs text-rose-500 mt-0.5">{{ stats.lewat_count }} tagihan macet</p>
                </div>

                <div class="bg-white rounded-xl border border-[#EDEDED] shadow-sm p-4 flex flex-col">
                    <div class="flex items-center justify-between">
                        <span class="text-[14px] text-[#101010] font-semibold">Diterima Bulan Ini</span>
                        <span class="text-emerald-400">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.2"
                                    d="M14 8C14 9.18669 13.6481 10.3467 12.9888 11.3334C12.3295 12.3201 11.3925 13.0892 10.2961 13.5433C9.19975 13.9974 7.99335 14.1162 6.82946 13.8847C5.66558 13.6532 4.59648 13.0818 3.75736 12.2426C2.91825 11.4035 2.3468 10.3344 2.11529 9.17054C1.88378 8.00666 2.0026 6.80026 2.45673 5.7039C2.91085 4.60754 3.67989 3.67047 4.66658 3.01118C5.65328 2.35189 6.81331 2 8 2C9.5913 2 11.1174 2.63214 12.2426 3.75736C13.3679 4.88258 14 6.4087 14 8Z"
                                    fill="#50CD89" />
                                <path
                                    d="M10.8538 6.14625C10.9002 6.19269 10.9371 6.24783 10.9623 6.30853C10.9874 6.36923 11.0004 6.43429 11.0004 6.5C11.0004 6.56571 10.9874 6.63077 10.9623 6.69147C10.9371 6.75217 10.9002 6.80731 10.8538 6.85375L7.35375 10.3538C7.30732 10.4002 7.25217 10.4371 7.19147 10.4623C7.13077 10.4874 7.06571 10.5004 7 10.5004C6.9343 10.5004 6.86923 10.4874 6.80853 10.4623C6.74783 10.4371 6.69269 10.4002 6.64625 10.3538L5.14625 8.85375C5.05243 8.75993 4.99972 8.63268 4.99972 8.5C4.99972 8.36732 5.05243 8.24007 5.14625 8.14625C5.24007 8.05243 5.36732 7.99972 5.5 7.99972C5.63268 7.99972 5.75993 8.05243 5.85375 8.14625L7 9.29313L10.1463 6.14625C10.1927 6.09976 10.2478 6.06288 10.3085 6.03772C10.3692 6.01256 10.4343 5.99961 10.5 5.99961C10.5657 5.99961 10.6308 6.01256 10.6915 6.03772C10.7522 6.06288 10.8073 6.09976 10.8538 6.14625ZM14.5 8C14.5 9.28558 14.1188 10.5423 13.4046 11.6112C12.6903 12.6801 11.6752 13.5132 10.4874 14.0052C9.29973 14.4972 7.99279 14.6259 6.73192 14.3751C5.47104 14.1243 4.31285 13.5052 3.40381 12.5962C2.49477 11.6872 1.8757 10.529 1.6249 9.26809C1.37409 8.00721 1.50282 6.70028 1.99479 5.51256C2.48676 4.32484 3.31988 3.30968 4.3888 2.59545C5.45772 1.88122 6.71442 1.5 8 1.5C9.72335 1.50182 11.3756 2.18722 12.5942 3.40582C13.8128 4.62441 14.4982 6.27665 14.5 8ZM13.5 8C13.5 6.9122 13.1774 5.84883 12.5731 4.94436C11.9687 4.03989 11.1098 3.33494 10.1048 2.91866C9.09977 2.50238 7.9939 2.39346 6.92701 2.60568C5.86011 2.8179 4.8801 3.34172 4.11092 4.11091C3.34173 4.8801 2.8179 5.86011 2.60568 6.927C2.39347 7.9939 2.50238 9.09977 2.91867 10.1048C3.33495 11.1098 4.0399 11.9687 4.94437 12.5731C5.84884 13.1774 6.91221 13.5 8 13.5C9.45819 13.4983 10.8562 12.9184 11.8873 11.8873C12.9184 10.8562 13.4983 9.45818 13.5 8Z"
                                    fill="#101010" />
                            </svg>

                        </span>
                    </div>
                    <p class="text-[24px] font-bold tracking-tight text-[#101010]">{{ formatRp(stats.diterima_bulan_ini)
                        }}</p>
                    <p class="text-xs text-emerald-600 mt-0.5">↑ {{ stats.diterima_pct }}% dari bulan lalu</p>
                </div>
            </div>

            <!-- ── Toolbar ─────────────────────────────────────── -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-2">
                    <select v-model="perPage"
                        class="h-[45px] w-16 rounded-lg border border-gray-300 bg-white px-2 text-sm text-gray-700 focus:border-[#007C95] focus:ring-1 focus:ring-[#007C95] focus:outline-none">
                        <option :value="10">10</option>
                        <option :value="25">25</option>
                        <option :value="50">50</option>
                        <option :value="100">100</option>
                    </select>
                    <span class="text-sm text-gray-500">Entri per halaman</span>
                </div>
                <div class="flex items-center gap-2">
                    <TableFilter :filters="filterFields" :model-value="filterValues" @update:model-value="handleFilter"
                        @reset="handleFilterReset" />
                    <div class="relative flex-1 sm:flex-none">
                        <Search class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-gray-400" />
                        <input v-model="searchQuery" type="text" placeholder="Cari piutang..."
                            class="h-[45px] w-full rounded-lg border border-gray-300 bg-white py-2 pr-3 pl-9 text-sm placeholder-gray-400 focus:border-[#007C95] focus:ring-1 focus:ring-[#007C95] focus:outline-none sm:w-56" />
                    </div>
                </div>
            </div>

            <!-- ── Table ──────────────────────────────────────── -->
            <div>
                <div class="overflow-hidden rounded-xl border-gray-200 bg-white">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 bg-[#F9F9F9]">
                                    <th class="px-4 py-3 text-left">
                                        <button
                                            class="flex items-center gap-1 text-xs font-semibold uppercase tracking-wider transition"
                                            :class="sortClass('no_invoice')" @click="handleSort('no_invoice')">
                                            No Invoice
                                            <span class="flex flex-col">
                                                <ChevronUp class="size-3 -mb-0.5"
                                                    :class="chevronClass('no_invoice', 'asc')" />
                                                <ChevronDown class="size-3 -mt-0.5"
                                                    :class="chevronClass('no_invoice', 'desc')" />
                                            </span>
                                        </button>
                                    </th>
                                    <th class="px-4 py-3 text-left">
                                        <button
                                            class="flex items-center gap-1 text-xs font-semibold uppercase tracking-wider transition"
                                            :class="sortClass('buyer')" @click="handleSort('buyer')">
                                            Buyer
                                            <span class="flex flex-col">
                                                <ChevronUp class="size-3 -mb-0.5"
                                                    :class="chevronClass('buyer', 'asc')" />
                                                <ChevronDown class="size-3 -mt-0.5"
                                                    :class="chevronClass('buyer', 'desc')" />
                                            </span>
                                        </button>
                                    </th>
                                    <th class="px-4 py-3 text-left">
                                        <button
                                            class="flex items-center gap-1 text-xs font-semibold uppercase tracking-wider transition"
                                            :class="sortClass('tanggal')" @click="handleSort('tanggal')">
                                            Tanggal
                                            <span class="flex flex-col">
                                                <ChevronUp class="size-3 -mb-0.5"
                                                    :class="chevronClass('tanggal', 'asc')" />
                                                <ChevronDown class="size-3 -mt-0.5"
                                                    :class="chevronClass('tanggal', 'desc')" />
                                            </span>
                                        </button>
                                    </th>
                                    <th class="px-4 py-3 text-left">
                                        <button
                                            class="flex items-center gap-1 text-xs font-semibold uppercase tracking-wider transition"
                                            :class="sortClass('jatuh_tempo')" @click="handleSort('jatuh_tempo')">
                                            Jatuh Tempo
                                            <span class="flex flex-col">
                                                <ChevronUp class="size-3 -mb-0.5"
                                                    :class="chevronClass('jatuh_tempo', 'asc')" />
                                                <ChevronDown class="size-3 -mt-0.5"
                                                    :class="chevronClass('jatuh_tempo', 'desc')" />
                                            </span>
                                        </button>
                                    </th>
                                    <th class="px-4 py-3 text-left">
                                        <button
                                            class="flex items-center gap-1 text-xs font-semibold uppercase tracking-wider transition"
                                            :class="sortClass('jumlah')" @click="handleSort('jumlah')">
                                            Jumlah
                                            <span class="flex flex-col">
                                                <ChevronUp class="size-3 -mb-0.5"
                                                    :class="chevronClass('jumlah', 'asc')" />
                                                <ChevronDown class="size-3 -mt-0.5"
                                                    :class="chevronClass('jumlah', 'desc')" />
                                            </span>
                                        </button>
                                    </th>
                                    <th class="px-4 py-3 text-left">
                                        <button
                                            class="flex items-center gap-1 text-xs font-semibold uppercase tracking-wider transition"
                                            :class="sortClass('diterima')" @click="handleSort('diterima')">
                                            Diterima
                                            <span class="flex flex-col">
                                                <ChevronUp class="size-3 -mb-0.5"
                                                    :class="chevronClass('diterima', 'asc')" />
                                                <ChevronDown class="size-3 -mt-0.5"
                                                    :class="chevronClass('diterima', 'desc')" />
                                            </span>
                                        </button>
                                    </th>
                                    <th class="px-4 py-3 text-left">
                                        <button
                                            class="flex items-center gap-1 text-xs font-semibold uppercase tracking-wider transition"
                                            :class="sortClass('sisa')" @click="handleSort('sisa')">
                                            Sisa
                                            <span class="flex flex-col">
                                                <ChevronUp class="size-3 -mb-0.5"
                                                    :class="chevronClass('sisa', 'asc')" />
                                                <ChevronDown class="size-3 -mt-0.5"
                                                    :class="chevronClass('sisa', 'desc')" />
                                            </span>
                                        </button>
                                    </th>
                                    <th class="px-4 py-3 text-left">
                                        <span
                                            class="text-xs font-semibold uppercase tracking-wider text-gray-500">Status</span>
                                    </th>
                                    <th class="px-4 py-3 text-left w-[50px]">
                                        <span
                                            class="text-xs font-semibold uppercase tracking-wider text-gray-500">Aksi</span>
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="item in piutang.data" :key="item.id" class="transition hover:bg-gray-50/60">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="font-mono text-xs text-gray-500">{{ item.no_invoice }}</span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ item.buyer }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                        {{ item.tanggal }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium"
                                        :class="item.is_overdue ? 'text-rose-600' : 'text-gray-500'">
                                        {{ item.jatuh_tempo }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                                        {{ formatRp(item.jumlah) }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                                        {{ formatRp(item.diterima) }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ formatRp(item.sisa) }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span
                                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                            :class="statusConfig[item.status]?.class">
                                            {{ statusConfig[item.status]?.label }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap w-[50px]">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <button
                                                    class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                                                    <EllipsisVertical class="size-4" />
                                                </button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end" class="w-44">
                                                <DropdownMenuItem class="gap-2 text-sm">
                                                    <Eye class="size-3.5" /> Lihat Detail
                                                </DropdownMenuItem>
                                                <DropdownMenuItem class="gap-2 text-sm">
                                                    <Pencil class="size-3.5" /> Edit
                                                </DropdownMenuItem>
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem
                                                    class="gap-2 text-sm text-rose-600 focus:text-rose-600">
                                                    <Trash2 class="size-3.5" /> Hapus
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </td>
                                </tr>

                                <!-- Empty State -->
                                <tr v-if="piutang.data.length === 0">
                                    <td colspan="9" class="px-5 py-10 text-center">
                                        <div class="flex flex-col items-center gap-2">
                                            <Vue3Lottie :animationData="emptyAnimation" :height="160" :width="160"
                                                :loop="true" />
                                            <p class="text-sm font-medium text-gray-600">Tidak ada data piutang</p>
                                            <p class="text-xs text-gray-400">Coba ubah kata kunci pencarian atau filter
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <TablePagination :paginator="piutang" @navigate="goToPage" />
            </div>

        </div>

        <!-- Modal sementara -->
        <SupplierFormModal v-model:open="isFormOpen" :editing-supplier="null" :cities="allCities ?? []"
            post-url="/piutang-ar/tambah"
            @success="toast.success('Berhasil!', { description: 'Piutang berhasil ditambahkan' })" />

    </AppLayout>
</template>