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
    RefreshCw,
    Wallet,
    Building2,
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

// Reuse SupplierFormModal sementara
import SupplierFormModal from '@/components/Supplier/SupplierFormModal.vue';
import type { City } from '@/components/Supplier/SupplierFormModal.vue';

// ── Types ──────────────────────────────────────────────────────

interface BankAccount {
    id: string;
    code: string;
    name: string;
    type: 'cash' | 'bank';
    is_active: boolean;
    notes?: string | null;
    // computed/virtual untuk display
    balance: number;
}

interface CashTransaction {
    id: string;
    date: string;
    description: string;
    type: 'in' | 'out';         // Masuk / Keluar
    account: string;            // nama akun (BCA, Mandiri, Kas Tunai)
    amount_in: number | null;
    amount_out: number | null;
    balance: number;
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

// ── Props ──────────────────────────────────────────────────────

const props = defineProps<{
    bankAccounts: BankAccount[];        // kartu atas (Kas Tunai, BCA, Mandiri, dst)
    transactions: Paginated<CashTransaction>;
    filters: {
        search?: string;
        perPage?: number;
        type?: string;
        account?: string;
        sort?: string;
        direction?: 'asc' | 'desc';
    };
    allCities?: City[];
}>();

// ── Breadcrumbs ────────────────────────────────────────────────

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Kas / Bank', href: '/kas-bank' },
];

// ── Static dummy (sementara) ───────────────────────────────────

const dummyAccounts: BankAccount[] = [
    { id: '1', code: 'KAS-001', name: 'Kas Tunai', type: 'cash', is_active: true, balance: 15_000_000 },
    { id: '2', code: 'BCA-001', name: 'Bank BCA', type: 'bank', is_active: true, balance: 120_000_000 },
    { id: '3', code: 'MDR-001', name: 'Bank Mandiri', type: 'bank', is_active: true, balance: 80_000_000 },
];

const accounts = computed(() =>
    props.bankAccounts?.length ? props.bankAccounts : dummyAccounts
);

const totalBalance = computed(() =>
    accounts.value.reduce((sum, a) => sum + (a.balance ?? 0), 0)
);

const dummyTransactions: CashTransaction[] = Array.from({ length: 57 }, (_, i) => ({
    id: String(i + 1),
    date: '03 Apr 2026',
    description: 'Penjualan ke PT Bioenergi (BK-042)',
    type: i % 3 === 0 ? 'in' : 'out',
    account: ['BCA', 'BCA', 'Mandiri', 'Kas Tunai'][i % 4],
    amount_in: i % 3 === 0 ? 22_000_000 : null,
    amount_out: i % 3 !== 0 ? 5_400_000 : null,
    balance: 215_000_000 - i * 1_000_000,
}));

// ── Table State ────────────────────────────────────────────────

const searchQuery = ref(props.filters.search ?? '');
const perPage = ref(props.filters.perPage ?? 10);
const sortColumn = ref(props.filters.sort ?? 'date');
const sortDirection = ref<'asc' | 'desc'>(props.filters.direction ?? 'desc');
const filterValues = ref<FilterValues>({
    type: props.filters.type ?? undefined,
    account: props.filters.account ?? undefined,
});

const filterFields = computed(() => [
    {
        key: 'type', label: 'Jenis', type: 'select' as const,
        options: [
            { label: 'Masuk', value: 'in' },
            { label: 'Keluar', value: 'out' },
        ],
    },
    {
        key: 'account', label: 'Akun', type: 'select' as const,
        options: accounts.value.map(a => ({ label: a.name, value: a.name })),
    },
]);

// ── Client-side filtering (static dummy) ──────────────────────

const filteredTx = computed(() => {
    let data = dummyTransactions;
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        data = data.filter(t =>
            t.description.toLowerCase().includes(q) ||
            t.account.toLowerCase().includes(q)
        );
    }
    if (filterValues.value.type)
        data = data.filter(t => t.type === filterValues.value.type);
    if (filterValues.value.account)
        data = data.filter(t => t.account === filterValues.value.account);
    return data;
});

const txLastPage = computed(() => Math.max(1, Math.ceil(filteredTx.value.length / perPage.value)));
const currentPage = ref(1);

const pagedTx = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return filteredTx.value.slice(start, start + perPage.value);
});

const txPaginator = computed(() => {
    const total = filteredTx.value.length;
    const current = currentPage.value;
    const last = txLastPage.value;
    const from = total === 0 ? null : (current - 1) * perPage.value + 1;
    const to = total === 0 ? null : Math.min(current * perPage.value, total);
    const links = [
        { url: current > 1 ? `#p=${current - 1}` : null, label: '&laquo; Previous', active: false },
        ...Array.from({ length: last }, (_, i) => ({
            url: `#p=${i + 1}`, label: String(i + 1), active: i + 1 === current,
        })),
        { url: current < last ? `#p=${current + 1}` : null, label: 'Next &raquo;', active: false },
    ];
    return { current_page: current, last_page: last, from, to, total, links };
});

watch([searchQuery, perPage, filterValues], () => { currentPage.value = 1; }, { deep: true });

function handleTxNavigate(url: string) {
    const m = url.match(/p=(\d+)/);
    if (m) currentPage.value = parseInt(m[1]);
}

// ── Helpers ────────────────────────────────────────────────────

function formatRp(val: number | null | undefined) {
    if (val == null) return '—';
    return 'Rp ' + val.toLocaleString('id-ID');
}

const sortClass = (col: string) =>
    sortColumn.value === col ? 'text-[#007C95]' : 'text-gray-500 hover:text-gray-700';

const chevronClass = (col: string, dir: 'asc' | 'desc') =>
    sortColumn.value === col && sortDirection.value === dir ? 'text-[#007C95]' : 'text-gray-300';

// ── Modal ──────────────────────────────────────────────────────

const isFormOpen = ref(false);
</script>

<template>

    <Head title="Kas / Bank" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 pb-10 md:p-6">

            <!-- ── Header ───────────────────────────────────── -->
            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div class="flex-1 min-w-0">
                    <h1 class="text-[24px] font-bold text-gray-900">Kas / Bank</h1>
                    <p class="mt-0.5 text-[16px] text-gray-500">Kelola aliran kas dan rekening bank</p>
                </div>

                <div class="flex w-full justify-end md:w-auto md:flex-shrink-0">
                    <div class="flex flex-col gap-2 md:flex-row">
                        <Button variant="outline"
                            class="flex w-fit items-center justify-center gap-1.5 rounded-lg px-4 py-2.5 text-sm font-medium">
                            <RefreshCw class="size-4" />
                            Rekonsiliasi
                        </Button>
                        <Button
                            class="flex w-fit items-center justify-center gap-1.5 rounded-lg bg-[#007C95] px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-[#006b80]"
                            @click="isFormOpen = true">
                            <Plus class="size-4" />
                            Tambah Transaksi
                        </Button>
                    </div>
                </div>
            </div>

            <!-- ── Account Cards ────────────────────────────── -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

                <!-- Individual accounts -->
                <div v-for="acc in accounts" :key="acc.id"
                    class="bg-white rounded-xl border border-[#EDEDED] shadow-sm p-4 flex flex-col">
                    <div class="flex items-center justify-between">
                        <span class="text-[14px] text-[#101010] font-semibold">{{ acc.name }}</span>
                        <!-- icon -->
                        <span class="text-gray-300">
                            <template v-if="acc.type === 'cash'">
                                <!-- wallet icon -->
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.2"
                                        d="M10 8C10 8.39556 9.8827 8.78224 9.66294 9.11114C9.44318 9.44004 9.13082 9.69638 8.76537 9.84776C8.39991 9.99913 7.99778 10.0387 7.60982 9.96157C7.22186 9.8844 6.86549 9.69392 6.58579 9.41421C6.30608 9.13451 6.1156 8.77814 6.03843 8.39018C5.96126 8.00222 6.00087 7.60009 6.15224 7.23463C6.30362 6.86918 6.55996 6.55682 6.88886 6.33706C7.21776 6.1173 7.60444 6 8 6C8.53043 6 9.03914 6.21071 9.41421 6.58579C9.78929 6.96086 10 7.46957 10 8ZM12.5 4C12.6059 4.62457 12.9034 5.20075 13.3513 5.64869C13.7992 6.09663 14.3754 6.39414 15 6.5V4H12.5ZM12.5 12H15V9.5C14.3754 9.60586 13.7992 9.90337 13.3513 10.3513C12.9034 10.7992 12.6059 11.3754 12.5 12ZM1 9.5V12H3.5C3.39414 11.3754 3.09663 10.7992 2.64869 10.3513C2.20075 9.90337 1.62457 9.60586 1 9.5ZM1 6.5C1.62457 6.39414 2.20075 6.09663 2.64869 5.64869C3.09663 5.20075 3.39414 4.62457 3.5 4H1V6.5Z"
                                        fill="#F14141" />
                                    <path
                                        d="M8 5.5C7.50555 5.5 7.0222 5.64662 6.61107 5.92133C6.19995 6.19603 5.87952 6.58648 5.6903 7.04329C5.50108 7.50011 5.45157 8.00277 5.54804 8.48773C5.6445 8.97268 5.8826 9.41814 6.23223 9.76777C6.58186 10.1174 7.02732 10.3555 7.51227 10.452C7.99723 10.5484 8.49989 10.4989 8.95671 10.3097C9.41352 10.1205 9.80397 9.80005 10.0787 9.38893C10.3534 8.9778 10.5 8.49445 10.5 8C10.5 7.33696 10.2366 6.70107 9.76777 6.23223C9.29893 5.76339 8.66304 5.5 8 5.5ZM8 9.5C7.70333 9.5 7.41332 9.41203 7.16664 9.2472C6.91997 9.08238 6.72771 8.84811 6.61418 8.57403C6.50065 8.29994 6.47094 7.99834 6.52882 7.70736C6.5867 7.41639 6.72956 7.14912 6.93934 6.93934C7.14912 6.72956 7.41639 6.5867 7.70736 6.52882C7.99834 6.47094 8.29994 6.50065 8.57403 6.61418C8.84811 6.72771 9.08238 6.91997 9.2472 7.16664C9.41203 7.41332 9.5 7.70333 9.5 8C9.5 8.39782 9.34196 8.77936 9.06066 9.06066C8.77936 9.34196 8.39782 9.5 8 9.5ZM15 3.5H1C0.867392 3.5 0.740215 3.55268 0.646447 3.64645C0.552678 3.74021 0.5 3.86739 0.5 4V12C0.5 12.1326 0.552678 12.2598 0.646447 12.3536C0.740215 12.4473 0.867392 12.5 1 12.5H15C15.1326 12.5 15.2598 12.4473 15.3536 12.3536C15.4473 12.2598 15.5 12.1326 15.5 12V4C15.5 3.86739 15.4473 3.74021 15.3536 3.64645C15.2598 3.55268 15.1326 3.5 15 3.5ZM1.5 4.5H2.83562C2.57774 5.09973 2.09973 5.57775 1.5 5.83563V4.5ZM1.5 11.5V10.1644C2.09973 10.4223 2.57774 10.9003 2.83562 11.5H1.5ZM14.5 11.5H13.1644C13.4223 10.9003 13.9003 10.4223 14.5 10.1644V11.5ZM14.5 9.10312C13.9323 9.271 13.4155 9.57825 12.9969 9.99689C12.5782 10.4155 12.271 10.9323 12.1031 11.5H3.89687C3.729 10.9323 3.42175 10.4155 3.00311 9.99689C2.58447 9.57825 2.06775 9.271 1.5 9.10312V6.89687C2.06775 6.729 2.58447 6.42175 3.00311 6.00311C3.42175 5.58447 3.729 5.06775 3.89687 4.5H12.1031C12.271 5.06775 12.5782 5.58447 12.9969 6.00311C13.4155 6.42175 13.9323 6.729 14.5 6.89687V9.10312ZM14.5 5.83563C13.9003 5.57775 13.4223 5.09973 13.1644 4.5H14.5V5.83563Z"
                                        fill="#101010" />
                                </svg>
                            </template>
                            <template v-else>
                                <!-- bank icon -->
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.2" d="M14.5 6H1.5L8 2L14.5 6Z" fill="#FBD14E" />
                                    <path
                                        d="M1.5 6.49969H3V10.4997H2C1.86739 10.4997 1.74021 10.5524 1.64645 10.6461C1.55268 10.7399 1.5 10.8671 1.5 10.9997C1.5 11.1323 1.55268 11.2595 1.64645 11.3532C1.74021 11.447 1.86739 11.4997 2 11.4997H14C14.1326 11.4997 14.2598 11.447 14.3536 11.3532C14.4473 11.2595 14.5 11.1323 14.5 10.9997C14.5 10.8671 14.4473 10.7399 14.3536 10.6461C14.2598 10.5524 14.1326 10.4997 14 10.4997H13V6.49969H14.5C14.6088 6.49958 14.7146 6.46399 14.8013 6.39832C14.888 6.33265 14.951 6.24049 14.9806 6.13581C15.0102 6.03112 15.0049 5.91964 14.9654 5.81826C14.9259 5.71689 14.8545 5.63115 14.7619 5.57406L8.26188 1.57406C8.18311 1.52564 8.09246 1.5 8 1.5C7.90754 1.5 7.81689 1.52564 7.73812 1.57406L1.23812 5.57406C1.14552 5.63115 1.07406 5.71689 1.03458 5.81826C0.995107 5.91964 0.989773 6.03112 1.01939 6.13581C1.04901 6.24049 1.11195 6.33265 1.19869 6.39832C1.28542 6.46399 1.39121 6.49958 1.5 6.49969ZM4 6.49969H6V10.4997H4V6.49969ZM9 6.49969V10.4997H7V6.49969H9ZM12 10.4997H10V6.49969H12V10.4997ZM8 2.58656L12.7338 5.49969H3.26625L8 2.58656ZM15.5 12.9997C15.5 13.1323 15.4473 13.2595 15.3536 13.3532C15.2598 13.447 15.1326 13.4997 15 13.4997H1C0.867392 13.4997 0.740215 13.447 0.646447 13.3532C0.552678 13.2595 0.5 13.1323 0.5 12.9997C0.5 12.8671 0.552678 12.7399 0.646447 12.6461C0.740215 12.5524 0.867392 12.4997 1 12.4997H15C15.1326 12.4997 15.2598 12.5524 15.3536 12.6461C15.4473 12.7399 15.5 12.8671 15.5 12.9997Z"
                                        fill="#101010" />
                                </svg>
                            </template>
                        </span>
                    </div>
                    <p class="text-[24px] font-bold tracking-tight text-[#101010]">
                        {{ formatRp(acc.balance) }}
                    </p>
                </div>

                <!-- Total card -->
                <div class="bg-white rounded-xl border border-[#EDEDED] shadow-sm p-4 flex flex-col">
                    <div class="flex items-center justify-between">
                        <span class="text-[14px] text-[#101010] font-semibold">Total Kas & Bank</span>
                        <span class="text-gray-300">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.2" d="M13 2.5V13H9.5V2.5H13Z" fill="#50CD89" />
                                <path
                                    d="M14 12.5H13.5V2.5C13.5 2.36739 13.4473 2.24021 13.3536 2.14645C13.2598 2.05268 13.1326 2 13 2H9.5C9.36739 2 9.24021 2.05268 9.14645 2.14645C9.05268 2.24021 9 2.36739 9 2.5V5H6C5.86739 5 5.74021 5.05268 5.64645 5.14645C5.55268 5.24021 5.5 5.36739 5.5 5.5V8H3C2.86739 8 2.74021 8.05268 2.64645 8.14645C2.55268 8.24021 2.5 8.36739 2.5 8.5V12.5H2C1.86739 12.5 1.74021 12.5527 1.64645 12.6464C1.55268 12.7402 1.5 12.8674 1.5 13C1.5 13.1326 1.55268 13.2598 1.64645 13.3536C1.74021 13.4473 1.86739 13.5 2 13.5H14C14.1326 13.5 14.2598 13.4473 14.3536 13.3536C14.4473 13.2598 14.5 13.1326 14.5 13C14.5 12.8674 14.4473 12.7402 14.3536 12.6464C14.2598 12.5527 14.1326 12.5 14 12.5ZM10 3H12.5V12.5H10V3ZM6.5 6H9V12.5H6.5V6ZM3.5 9H5.5V12.5H3.5V9Z"
                                    fill="#101010" />
                            </svg>

                        </span>
                    </div>
                    <p class="text-[24px] font-bold tracking-tight text-[#101010]">
                        {{ formatRp(totalBalance) }}
                    </p>
                </div>
            </div>

            <!-- ── Toolbar ───────────────────────────────────── -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-2">
                    <select v-model="perPage"
                        class="h-[45px] w-16 rounded-lg border border-gray-300 bg-white px-2 text-sm text-gray-700 focus:border-[#007C95] focus:ring-1 focus:ring-[#007C95] focus:outline-none">
                        <option :value="10">10</option>
                        <option :value="25">25</option>
                        <option :value="50">50</option>
                    </select>
                    <span class="text-sm text-gray-500">Entri per halaman</span>
                </div>
                <div class="flex items-center gap-2">
                    <TableFilter :filters="filterFields" :model-value="filterValues"
                        @update:model-value="v => { filterValues = v; }" @reset="filterValues = {}" />
                    <div class="relative flex-1 sm:flex-none">
                        <Search class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-gray-400" />
                        <input v-model="searchQuery" type="text" placeholder="Cari data..."
                            class="h-[45px] w-full rounded-lg border border-gray-300 bg-white py-2 pr-3 pl-9 text-sm placeholder-gray-400 focus:border-[#007C95] focus:ring-1 focus:ring-[#007C95] focus:outline-none sm:w-56" />
                    </div>
                </div>
            </div>

            <!-- ── Table ─────────────────────────────────────── -->
            <div>
                <div class="overflow-hidden rounded-xl border-gray-200 bg-white">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 bg-[#F9F9F9]">
                                    <!-- Tanggal -->
                                    <th class="px-4 py-3 text-left">
                                        <button
                                            class="flex items-center gap-1 text-xs font-semibold uppercase tracking-wider transition"
                                            :class="sortClass('date')" @click="sortColumn = 'date'">
                                            Tanggal
                                            <span class="flex flex-col">
                                                <ChevronUp class="size-3 -mb-0.5" :class="chevronClass('date', 'asc')" />
                                                <ChevronDown class="size-3 -mt-0.5" :class="chevronClass('date', 'desc')" />
                                            </span>
                                        </button>
                                    </th>
                                    <!-- Keterangan -->
                                    <th class="px-4 py-3 text-left">
                                        <span
                                            class="text-xs font-semibold uppercase tracking-wider text-gray-500">Keterangan</span>
                                    </th>
                                    <!-- Jenis -->
                                    <th class="px-4 py-3 text-left">
                                        <span
                                            class="text-xs font-semibold uppercase tracking-wider text-gray-500">Jenis</span>
                                    </th>
                                    <!-- Akun -->
                                    <th class="px-4 py-3 text-left">
                                        <span
                                            class="text-xs font-semibold uppercase tracking-wider text-gray-500">Akun</span>
                                    </th>
                                    <!-- Masuk -->
                                    <th class="px-4 py-3 text-left">
                                        <button
                                            class="flex items-center gap-1 text-xs font-semibold uppercase tracking-wider transition"
                                            :class="sortClass('amount_in')" @click="sortColumn = 'amount_in'">
                                            Masuk
                                            <span class="flex flex-col">
                                                <ChevronUp class="size-3 -mb-0.5"
                                                    :class="chevronClass('amount_in', 'asc')" />
                                                <ChevronDown class="size-3 -mt-0.5"
                                                    :class="chevronClass('amount_in', 'desc')" />
                                            </span>
                                        </button>
                                    </th>
                                    <!-- Keluar -->
                                    <th class="px-4 py-3 text-left">
                                        <button
                                            class="flex items-center gap-1 text-xs font-semibold uppercase tracking-wider transition"
                                            :class="sortClass('amount_out')" @click="sortColumn = 'amount_out'">
                                            Keluar
                                            <span class="flex flex-col">
                                                <ChevronUp class="size-3 -mb-0.5"
                                                    :class="chevronClass('amount_out', 'asc')" />
                                                <ChevronDown class="size-3 -mt-0.5"
                                                    :class="chevronClass('amount_out', 'desc')" />
                                            </span>
                                        </button>
                                    </th>
                                    <!-- Saldo -->
                                    <th class="px-4 py-3 text-left">
                                        <button
                                            class="flex items-center gap-1 text-xs font-semibold uppercase tracking-wider transition"
                                            :class="sortClass('balance')" @click="sortColumn = 'balance'">
                                            Saldo
                                            <span class="flex flex-col">
                                                <ChevronUp class="size-3 -mb-0.5" :class="chevronClass('balance', 'asc')" />
                                                <ChevronDown class="size-3 -mt-0.5"
                                                    :class="chevronClass('balance', 'desc')" />
                                            </span>
                                        </button>
                                    </th>
                                    <!-- Aksi -->
                                    <th class="px-4 py-3 text-left w-[50px]">
                                        <span
                                            class="text-xs font-semibold uppercase tracking-wider text-gray-500">Aksi</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="tx in pagedTx" :key="tx.id" class="transition hover:bg-gray-50/60">
                                    <!-- Tanggal -->
                                    <td class="px-4 py-3 whitespace-nowrap text-[13px] text-gray-500">
                                        {{ tx.date }}
                                    </td>
                                    <!-- Keterangan -->
                                    <td class="px-4 py-3 text-[13px] text-[#101010] max-w-[280px]">
                                        {{ tx.description }}
                                    </td>
                                    <!-- Jenis -->
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="text-[13px] font-medium"
                                            :class="tx.type === 'in' ? 'text-emerald-600' : 'text-red-500'">
                                            {{ tx.type === 'in' ? 'Masuk' : 'Keluar' }}
                                        </span>
                                    </td>
                                    <!-- Akun -->
                                    <td class="px-4 py-3 whitespace-nowrap text-[13px] text-gray-700">
                                        {{ tx.account }}
                                    </td>
                                    <!-- Masuk -->
                                    <td class="px-4 py-3 whitespace-nowrap text-[13px] text-emerald-700">
                                        {{ tx.amount_in != null ? formatRp(tx.amount_in) : '—' }}
                                    </td>
                                    <!-- Keluar -->
                                    <td class="px-4 py-3 whitespace-nowrap text-[13px] text-red-500">
                                        {{ tx.amount_out != null ? formatRp(tx.amount_out) : '—' }}
                                    </td>
                                    <!-- Saldo -->
                                    <td class="px-4 py-3 whitespace-nowrap text-[13px] font-medium text-[#101010]">
                                        {{ formatRp(tx.balance) }}
                                    </td>
                                    <!-- Aksi -->
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
                                                    <Eye class="size-3.5" />
                                                    Lihat Detail
                                                </DropdownMenuItem>
                                                <DropdownMenuItem class="gap-2 text-sm">
                                                    <Pencil class="size-3.5" />
                                                    Edit
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </td>
                                </tr>

                                <!-- Empty -->
                                <tr v-if="pagedTx.length === 0">
                                    <td colspan="8" class="px-5 py-10 text-center">
                                        <div class="flex flex-col items-center gap-2">
                                            <Vue3Lottie :animationData="emptyAnimation" :height="160" :width="160"
                                                :loop="true" />
                                            <p class="text-sm font-medium text-gray-600">Belum ada transaksi</p>
                                            <p class="text-xs text-gray-400">Coba ubah kata kunci pencarian atau filter</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <TablePagination :paginator="txPaginator" @navigate="handleTxNavigate" />
            </div>

        </div>

        <!-- Modal (sementara pakai SupplierFormModal) -->
        <SupplierFormModal v-model:open="isFormOpen" :editing-supplier="null" :cities="allCities ?? []"
            post-url="/kas-bank/transaksi"
            @success="toast.success('Berhasil!', { description: 'Transaksi berhasil ditambahkan' })" />

    </AppLayout>
</template>