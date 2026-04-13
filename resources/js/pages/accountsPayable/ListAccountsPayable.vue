<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    Search,
    ChevronUp,
    ChevronDown,
    Plus,
    EllipsisVertical,
    Eye,
    Trash2,
    CreditCard,
    AlertTriangle,
    CheckCircle2,
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

interface HutangStats {
    total_hutang: number;
    total_tagihan: number;
    jatuh_tempo_7hari: number;
    jatuh_tempo_count: number;
    lunas_bulan_ini: number;
    lunas_count: number;
}

interface HutangItem {
    id: string;
    no_invoice: string;
    supplier: string;
    tanggal: string;
    jatuh_tempo: string;
    jumlah: number;
    terbayar: number;
    sisa_utang: number;
    status: 'lunas' | 'parsial' | 'belum_bayar';
    is_overdue: boolean;
    is_near_due: boolean;
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
    stats: HutangStats;
    hutang: Paginated<HutangItem>;
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
    { title: 'Hutang (AP)', href: '/hutang-ap' },
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
            { label: 'Parsial', value: 'parsial' },
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
    router.get('/hutang-ap', buildParams(filterValues.value), { preserveState: true, replace: true });
};

const handleFilter = (values: FilterValues) => {
    filterValues.value = values;
    router.get('/hutang-ap', buildParams(values), { preserveState: true, replace: true });
};

const handleFilterReset = () => {
    filterValues.value = {};
    router.get('/hutang-ap', buildParams({}), { preserveState: true, replace: true });
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
            router.get('/hutang-ap', buildParams(filterValues.value), { preserveState: true, replace: true });
        }, 400);
    }
});

watch(perPage, () => {
    router.get('/hutang-ap', buildParams(filterValues.value), { preserveState: true, replace: true });
});

// ── Helpers ────────────────────────────────────────────────────

function formatRp(val: number | null | undefined) {
    if (val == null) return '—';
    return 'Rp ' + val.toLocaleString('id-ID');
}

const statusConfig = {
    lunas: { label: 'Lunas', class: 'bg-emerald-50 text-emerald-700' },
    parsial: { label: 'Parsial', class: 'bg-amber-50 text-amber-600' },
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

    <Head title="Hutang (AP)" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 pb-10 md:p-6">

            <!-- ── Header ─────────────────────────────────────── -->
            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div class="flex-1 min-w-0">
                    <h1 class="text-[24px] font-bold text-gray-900">Hutang (AP)</h1>
                    <p class="mt-0.5 text-[16px] text-gray-500">Kelola kewajiban pembayaran ke supplier</p>
                </div>
                <div class="flex w-full justify-end md:w-auto md:flex-shrink-0">
                    <Button
                        class="flex w-fit items-center justify-center gap-1.5 rounded-lg bg-[#007C95] px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-[#006b80]"
                        @click="isFormOpen = true">
                        <Plus class="size-4" />
                        Bayar Hutang
                    </Button>
                </div>
            </div>

            <!-- ── Stat Cards ─────────────────────────────────── -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">

                <div class="bg-white rounded-xl border border-[#EDEDED] shadow-sm p-4 flex flex-col">
                    <div class="flex items-center justify-between">
                        <span class="text-[14px] text-[#101010] font-semibold">Total Hutang</span>
                        <span class="text-gray-300">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.2"
                                    d="M14.5 6V12C14.5 12.1326 14.4473 12.2598 14.3536 12.3536C14.2598 12.4473 14.1326 12.5 14 12.5H2C1.86739 12.5 1.74021 12.4473 1.64645 12.3536C1.55268 12.2598 1.5 12.1326 1.5 12V6H14.5Z"
                                    fill="#F14141" />
                                <path
                                    d="M14 3H2C1.73478 3 1.48043 3.10536 1.29289 3.29289C1.10536 3.48043 1 3.73478 1 4V12C1 12.2652 1.10536 12.5196 1.29289 12.7071C1.48043 12.8946 1.73478 13 2 13H14C14.2652 13 14.5196 12.8946 14.7071 12.7071C14.8946 12.5196 15 12.2652 15 12V4C15 3.73478 14.8946 3.48043 14.7071 3.29289C14.5196 3.10536 14.2652 3 14 3ZM14 4V5.5H2V4H14ZM14 12H2V6.5H14V12ZM13 10.5C13 10.6326 12.9473 10.7598 12.8536 10.8536C12.7598 10.9473 12.6326 11 12.5 11H10.5C10.3674 11 10.2402 10.9473 10.1464 10.8536C10.0527 10.7598 10 10.6326 10 10.5C10 10.3674 10.0527 10.2402 10.1464 10.1464C10.2402 10.0527 10.3674 10 10.5 10H12.5C12.6326 10 12.7598 10.0527 12.8536 10.1464C12.9473 10.2402 13 10.3674 13 10.5ZM9 10.5C9 10.6326 8.94732 10.7598 8.85355 10.8536C8.75979 10.9473 8.63261 11 8.5 11H7.5C7.36739 11 7.24021 10.9473 7.14645 10.8536C7.05268 10.7598 7 10.6326 7 10.5C7 10.3674 7.05268 10.2402 7.14645 10.1464C7.24021 10.0527 7.36739 10 7.5 10H8.5C8.63261 10 8.75979 10.0527 8.85355 10.1464C8.94732 10.2402 9 10.3674 9 10.5Z"
                                    fill="#101010" />
                            </svg>
                        </span>
                    </div>
                    <p class="text-[24px] font-bold tracking-tight text-[#101010]">{{ formatRp(stats.total_hutang) }}
                    </p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ stats.total_tagihan }} tagihan belum lunas</p>
                </div>

                <div class="bg-white rounded-xl border border-[#EDEDED] shadow-sm p-4 flex flex-col">
                    <div class="flex items-center justify-between">
                        <span class="text-[14px] text-[#101010] font-semibold">Jatuh Tempo &lt; 7 Hari</span>
                        <span class="text-gray-300">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.2"
                                    d="M13.4663 13.4999H2.53375C1.745 13.4999 1.25 12.6743 1.63313 12.0055L7.09938 2.51367C7.49313 1.82617 8.50688 1.82617 8.90063 2.51367L14.3669 12.0055C14.75 12.6743 14.255 13.4999 13.4663 13.4999Z"
                                    fill="#FBD14E" />
                                <path
                                    d="M14.8002 11.7557L9.33456 2.26379C9.19798 2.03124 9.00299 1.83843 8.76894 1.70445C8.53488 1.57048 8.26987 1.5 8.00018 1.5C7.73049 1.5 7.46549 1.57048 7.23143 1.70445C6.99737 1.83843 6.80239 2.03124 6.66581 2.26379L1.20018 11.7557C1.06877 11.9806 0.999512 12.2364 0.999512 12.4969C0.999512 12.7574 1.06877 13.0132 1.20018 13.2382C1.33501 13.4721 1.52966 13.666 1.76416 13.7999C1.99865 13.9338 2.26455 14.0028 2.53456 14H13.4658C13.7356 14.0026 14.0012 13.9334 14.2355 13.7996C14.4698 13.6657 14.6642 13.4719 14.7989 13.2382C14.9305 13.0134 15 12.7576 15.0002 12.4971C15.0004 12.2366 14.9314 11.9807 14.8002 11.7557ZM13.9333 12.7375C13.8857 12.8188 13.8173 12.886 13.7351 12.9321C13.653 12.9782 13.56 13.0017 13.4658 13H2.53456C2.44036 13.0017 2.34741 12.9782 2.26526 12.9321C2.18311 12.886 2.11471 12.8188 2.06706 12.7375C2.0239 12.6645 2.00113 12.5812 2.00113 12.4963C2.00113 12.4114 2.0239 12.3281 2.06706 12.255L7.53268 2.76317C7.58129 2.68227 7.65001 2.61534 7.73215 2.56887C7.81429 2.5224 7.90706 2.49797 8.00143 2.49797C8.09581 2.49797 8.18857 2.5224 8.27071 2.56887C8.35286 2.61534 8.42157 2.68227 8.47018 2.76317L13.9358 12.255C13.9786 12.3283 14.0009 12.4118 14.0005 12.4966C14 12.5815 13.9768 12.6647 13.9333 12.7375ZM7.50018 9.00004V6.50004C7.50018 6.36743 7.55286 6.24026 7.64663 6.14649C7.7404 6.05272 7.86757 6.00004 8.00018 6.00004C8.13279 6.00004 8.25997 6.05272 8.35374 6.14649C8.4475 6.24026 8.50018 6.36743 8.50018 6.50004V9.00004C8.50018 9.13265 8.4475 9.25983 8.35374 9.35359C8.25997 9.44736 8.13279 9.50004 8.00018 9.50004C7.86757 9.50004 7.7404 9.44736 7.64663 9.35359C7.55286 9.25983 7.50018 9.13265 7.50018 9.00004ZM8.75018 11.25C8.75018 11.3984 8.7062 11.5434 8.62378 11.6667C8.54137 11.7901 8.42424 11.8862 8.28719 11.943C8.15015 11.9997 7.99935 12.0146 7.85386 11.9856C7.70838 11.9567 7.57474 11.8853 7.46985 11.7804C7.36496 11.6755 7.29353 11.5418 7.26459 11.3964C7.23565 11.2509 7.25051 11.1001 7.30727 10.963C7.36404 10.826 7.46017 10.7088 7.5835 10.6264C7.70684 10.544 7.85185 10.5 8.00018 10.5C8.19909 10.5 8.38986 10.5791 8.53051 10.7197C8.67116 10.8604 8.75018 11.0511 8.75018 11.25Z"
                                    fill="#101010" />
                            </svg>

                        </span>
                    </div>
                    <p class="text-[24px] font-bold tracking-tight text-[#101010]">{{ formatRp(stats.jatuh_tempo_7hari)
                        }}</p>
                    <p class="text-xs text-amber-500 mt-0.5">{{ stats.jatuh_tempo_count }} tagihan segera</p>
                </div>

                <div class="bg-white rounded-xl border border-[#EDEDED] shadow-sm p-4 flex flex-col">
                    <div class="flex items-center justify-between">
                        <span class="text-[14px] text-[#101010] font-semibold">Lunas Bulan Ini</span>
                        <span class="text-gray-300">
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
                    <p class="text-[24px] font-bold tracking-tight text-[#101010]">{{ formatRp(stats.lunas_bulan_ini) }}
                    </p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ stats.lunas_count }} tagihan lunas</p>
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
                        <input v-model="searchQuery" type="text" placeholder="Cari hutang..."
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
                                            :class="sortClass('supplier')" @click="handleSort('supplier')">
                                            Supplier
                                            <span class="flex flex-col">
                                                <ChevronUp class="size-3 -mb-0.5"
                                                    :class="chevronClass('supplier', 'asc')" />
                                                <ChevronDown class="size-3 -mt-0.5"
                                                    :class="chevronClass('supplier', 'desc')" />
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
                                            :class="sortClass('terbayar')" @click="handleSort('terbayar')">
                                            Terbayar
                                            <span class="flex flex-col">
                                                <ChevronUp class="size-3 -mb-0.5"
                                                    :class="chevronClass('terbayar', 'asc')" />
                                                <ChevronDown class="size-3 -mt-0.5"
                                                    :class="chevronClass('terbayar', 'desc')" />
                                            </span>
                                        </button>
                                    </th>
                                    <th class="px-4 py-3 text-left">
                                        <button
                                            class="flex items-center gap-1 text-xs font-semibold uppercase tracking-wider transition"
                                            :class="sortClass('sisa_utang')" @click="handleSort('sisa_utang')">
                                            Sisa Utang
                                            <span class="flex flex-col">
                                                <ChevronUp class="size-3 -mb-0.5"
                                                    :class="chevronClass('sisa_utang', 'asc')" />
                                                <ChevronDown class="size-3 -mt-0.5"
                                                    :class="chevronClass('sisa_utang', 'desc')" />
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
                                <tr v-for="item in hutang.data" :key="item.id" class="transition hover:bg-gray-50/60">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="font-mono text-xs text-gray-500">{{ item.no_invoice }}</span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ item.supplier }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                        {{ item.tanggal }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium"
                                        :class="item.is_overdue ? 'text-rose-600' : item.is_near_due ? 'text-amber-500' : 'text-gray-500'">
                                        {{ item.jatuh_tempo }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                                        {{ formatRp(item.jumlah) }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                                        {{ formatRp(item.terbayar) }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ formatRp(item.sisa_utang) }}
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
                                                    <CreditCard class="size-3.5" /> Bayar
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
                                <tr v-if="hutang.data.length === 0">
                                    <td colspan="9" class="px-5 py-10 text-center">
                                        <div class="flex flex-col items-center gap-2">
                                            <Vue3Lottie :animationData="emptyAnimation" :height="160" :width="160"
                                                :loop="true" />
                                            <p class="text-sm font-medium text-gray-600">Tidak ada data hutang</p>
                                            <p class="text-xs text-gray-400">Coba ubah kata kunci pencarian atau filter
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <TablePagination :paginator="hutang" @navigate="goToPage" />
            </div>

        </div>

        <!-- Modal sementara -->
        <SupplierFormModal v-model:open="isFormOpen" :editing-supplier="null" :cities="allCities ?? []"
            post-url="/hutang-ap/bayar"
            @success="toast.success('Berhasil!', { description: 'Pembayaran berhasil dicatat' })" />

    </AppLayout>
</template>