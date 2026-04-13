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
    Download,
    PackageOpen,
    DollarSign,
    CreditCard,
    Clock,
    CheckCircle2,
    XCircle,
} from 'lucide-vue-next';
import { ref, watch, computed } from 'vue';
import { toast } from 'vue-sonner';
import { Vue3Lottie } from 'vue3-lottie';
import emptyAnimation from '@/../../public/assets/animations/Pencarian Tidak Ditemukan.json';

import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
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

import GoodsReceiptFormModal from '@/components/goodsReceipt/GoodsReceiptFormModal.vue';
import type { GoodsReceipt, SupplierOption, WarehouseOption } from '@/components/goodsReceipt/GoodsReceiptFormModal.vue';

// ── Types ──────────────────────────────────────────────────────

interface PaginatedGoodsReceipts {
    data: GoodsReceipt[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: { url: string | null; label: string; active: boolean }[];
}

interface Stats {
    total_uco_in: number;
    total_uco_in_trend: number;
    total_uco_in_up: boolean;
    total_purchase: number;
    total_debt: number;
    due_date_count: number;
    due_date_nominal: number;
}

// ── Props ──────────────────────────────────────────────────────

const props = defineProps<{
    goodsReceipts: PaginatedGoodsReceipts;
    stats: Stats;
    suppliers: { id: string; name: string }[];
    warehouses: { id: number; name: string }[];
    allSuppliers: SupplierOption[];
    allWarehouses: WarehouseOption[];
    filters: {
        search?: string;
        perPage?: number;
        status?: string;
        supplier_id?: string;
        warehouse_id?: string;
        date_from?: string;
        date_to?: string;
        sort?: string;
        direction?: 'asc' | 'desc';
    };
}>();

// ── Breadcrumbs ────────────────────────────────────────────────

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Barang Masuk', href: '/goods-receipt' },
];

// ── Table State ────────────────────────────────────────────────

const searchQuery = ref(props.filters.search ?? '');
const perPage = ref(props.filters.perPage ?? 10);
const sortColumn = ref(props.filters.sort ?? 'date');
const sortDirection = ref<'asc' | 'desc'>(props.filters.direction ?? 'desc');

const filterFields = computed(() => [
    {
        key: 'status',
        label: 'Status',
        type: 'select' as const,
        options: [
            { label: 'Lunas', value: 'lunas' },
            { label: 'Belum Lunas', value: 'belum_lunas' },
        ],
    },
    {
        key: 'supplier_id',
        label: 'Supplier',
        type: 'select' as const,
        options: props.suppliers.map((s) => ({ label: s.name, value: String(s.id) })),
    },
    {
        key: 'warehouse_id',
        label: 'Gudang',
        type: 'select' as const,
        options: props.warehouses.map((w) => ({ label: w.name, value: String(w.id) })),
    },
]);

const filterValues = ref<FilterValues>({
    status: props.filters.status ?? undefined,
    supplier_id: props.filters.supplier_id ?? undefined,
    warehouse_id: props.filters.warehouse_id ?? undefined,
});

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
    router.get('/goods-receipt', buildParams(filterValues.value), { preserveState: true, replace: true });
};

const handleFilter = (values: FilterValues) => {
    filterValues.value = values;
    router.get('/goods-receipt', buildParams(values), { preserveState: true, replace: true });
};

const handleFilterReset = () => {
    filterValues.value = {};
    router.get('/goods-receipt', buildParams({}), { preserveState: true, replace: true });
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
            router.get('/goods-receipt', buildParams(filterValues.value), { preserveState: true, replace: true });
        }, 400);
    }
});

watch(perPage, () => {
    router.get('/goods-receipt', buildParams(filterValues.value), { preserveState: true, replace: true });
});

// ── Form Modal ─────────────────────────────────────────────────

const isFormOpen = ref(false);
const editingGoodsReceipt = ref<GoodsReceipt | null>(null);

function openCreate() {
    editingGoodsReceipt.value = null;
    isFormOpen.value = true;
}

function openEdit(gr: GoodsReceipt) {
    editingGoodsReceipt.value = gr;
    isFormOpen.value = true;
}

// ── Delete ─────────────────────────────────────────────────────

const isDeleteOpen = ref(false);
const deletingGr = ref<GoodsReceipt | null>(null);
const isDeleting = ref(false);

function openDelete(gr: GoodsReceipt) {
    deletingGr.value = gr;
    isDeleteOpen.value = true;
}

function confirmDelete() {
    if (!deletingGr.value) return;
    isDeleting.value = true;
    router.delete(`/goods-receipt/${deletingGr.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Berhasil!', { description: 'Data barang masuk berhasil dihapus' });
            isDeleteOpen.value = false;
        },
        onError: () => toast.error('Gagal!', { description: 'Gagal menghapus data' }),
        onFinish: () => { isDeleting.value = false; },
    });
}

// ── Update Status (Lunas / Belum Lunas) ───────────────────────

function toggleStatus(gr: GoodsReceipt) {
    const newStatus = gr.status === 'lunas' ? 'belum_lunas' : 'lunas';
    router.patch(
        `/goods-receipt/${gr.id}/update-status`,
        { status: newStatus },
        {
            preserveScroll: true,
            onSuccess: () => toast.success('Berhasil!', {
                description: newStatus === 'lunas'
                    ? 'Status diubah menjadi Lunas'
                    : 'Status diubah menjadi Belum Lunas',
            }),
            onError: () => toast.error('Gagal!', { description: 'Gagal mengubah status' }),
        },
    );
}

// ── Helpers ────────────────────────────────────────────────────

const sortClass = (col: string) =>
    sortColumn.value === col ? 'text-[#007C95]' : 'text-gray-500 hover:text-gray-700';

const chevronClass = (col: string, dir: 'asc' | 'desc') =>
    sortColumn.value === col && sortDirection.value === dir ? 'text-[#007C95]' : 'text-gray-300';

const formatRupiah = (val: number) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val);

const formatVolume = (val: number) =>
    new Intl.NumberFormat('id-ID').format(val) + ' kg';

const formatTanggal = (val: string) =>
    new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(val));
</script>

<template>

    <Head title="Barang Masuk" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 pb-10 md:p-6">

            <!-- ── Header ─────────────────────────────────────── -->
            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div class="flex-1 min-w-0">
                    <h1 class="text-[24px] font-bold text-gray-900">Barang Masuk</h1>
                    <p class="mt-0.5 text-[16px] text-gray-500">Pencatatan pembelian minyak jelantah dari supplier</p>
                </div>

                <div class="flex w-full justify-end md:w-auto md:flex-shrink-0">
                    <div class="flex flex-col gap-2 md:flex-row">
                        <Button variant="outline"
                            class="flex w-fit items-center justify-center gap-1.5 rounded-lg px-4 py-2.5 text-sm font-medium">
                            <Download class="size-4" />
                            Export
                        </Button>
                        <Button
                            class="flex w-fit items-center justify-center gap-1.5 rounded-lg bg-[#007C95] px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-[#006b80]"
                            @click="openCreate">
                            <Plus class="size-4" />
                            Tambah Barang Masuk
                        </Button>
                    </div>
                </div>
            </div>

            <!-- ── Stats Cards ────────────────────────────────── -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">

                <div class="bg-white rounded-xl border border-[#EDEDED] shadow-sm p-4 flex flex-col gap-1">
                    <div class="flex items-center justify-between">
                        <span class="text-[14px] text-[#101010] font-semibold">Total UCO Masuk</span>
                        <span class="text-gray-300">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.2"
                                    d="M8 8.06811V14.5C7.91602 14.4996 7.83348 14.4781 7.76 14.4375L2.26 11.4275C2.18147 11.3845 2.11591 11.3212 2.07017 11.2443C2.02444 11.1673 2.0002 11.0795 2 10.99V5.01248C2.00002 4.94203 2.01493 4.87239 2.04375 4.80811L8 8.06811Z"
                                    fill="#50CD89" />
                                <path
                                    d="M13.98 4.13414L8.48 1.12477C8.33305 1.04357 8.16789 1.00098 8 1.00098C7.83211 1.00098 7.66695 1.04357 7.52 1.12477L2.02 4.13539C1.86293 4.22133 1.73181 4.34787 1.64034 4.50178C1.54888 4.6557 1.50041 4.83135 1.5 5.01039V10.9879C1.50041 11.1669 1.54888 11.3426 1.64034 11.4965C1.73181 11.6504 1.86293 11.777 2.02 11.8629L7.52 14.8735C7.66695 14.9547 7.83211 14.9973 8 14.9973C8.16789 14.9973 8.33305 14.9547 8.48 14.8735L13.98 11.8629C14.1371 11.777 14.2682 11.6504 14.3597 11.4965C14.4511 11.3426 14.4996 11.1669 14.5 10.9879V5.01102C14.4999 4.83166 14.4516 4.65561 14.3601 4.50134C14.2686 4.34706 14.1373 4.22024 13.98 4.13414ZM8 1.99977L13.0212 4.74977L8 7.49977L2.97875 4.74977L8 1.99977ZM2.5 5.62477L7.5 8.36102V13.7229L2.5 10.9885V5.62477ZM8.5 13.7229V8.36352L13.5 5.62477V10.986L8.5 13.7229Z"
                                    fill="#101010" />
                            </svg>
                        </span>
                    </div>
                    <p class="text-[24px] font-bold tracking-tight text-[#101010]">
                        {{ formatVolume(stats.total_uco_in) }}
                    </p>
                    <p class="text-xs" :class="stats.total_uco_in_up ? 'text-emerald-600' : 'text-rose-500'">
                        <span>{{ stats.total_uco_in_up ? '▲' : '▼' }}</span>
                        {{ stats.total_uco_in_trend }}% from last month
                    </p>
                </div>

                <div class="bg-white rounded-xl border border-[#EDEDED] shadow-sm p-4 flex flex-col gap-1">
                    <div class="flex items-center justify-between">
                        <span class="text-[14px] text-[#101010] font-semibold">Total Pembelian</span>
                        <span class="text-gray-300">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.2"
                                    d="M12 10.5C12 10.8283 11.9353 11.1534 11.8097 11.4567C11.6841 11.76 11.4999 12.0356 11.2678 12.2678C11.0356 12.4999 10.76 12.6841 10.4567 12.8097C10.1534 12.9353 9.8283 13 9.5 13H8V8H9.5C9.8283 8 10.1534 8.06466 10.4567 8.1903C10.76 8.31594 11.0356 8.50009 11.2678 8.73223C11.4999 8.96438 11.6841 9.23998 11.8097 9.54329C11.9353 9.84661 12 10.1717 12 10.5ZM7 3C6.33696 3 5.70107 3.26339 5.23223 3.73223C4.76339 4.20107 4.5 4.83696 4.5 5.5C4.5 6.16304 4.76339 6.79893 5.23223 7.26777C5.70107 7.73661 6.33696 8 7 8H8V3H7Z"
                                    fill="#50CD89" />
                                <path
                                    d="M12.5 10.5C12.4992 11.2954 12.1828 12.058 11.6204 12.6204C11.058 13.1828 10.2954 13.4992 9.5 13.5H8.5V14.5C8.5 14.6326 8.44732 14.7598 8.35355 14.8536C8.25979 14.9473 8.13261 15 8 15C7.86739 15 7.74021 14.9473 7.64645 14.8536C7.55268 14.7598 7.5 14.6326 7.5 14.5V13.5H6.5C5.7046 13.4992 4.94202 13.1828 4.37959 12.6204C3.81716 12.058 3.50083 11.2954 3.5 10.5C3.5 10.3674 3.55268 10.2402 3.64645 10.1464C3.74021 10.0527 3.86739 10 4 10C4.13261 10 4.25979 10.0527 4.35355 10.1464C4.44732 10.2402 4.5 10.3674 4.5 10.5C4.5 11.0304 4.71071 11.5391 5.08579 11.9142C5.46086 12.2893 5.96957 12.5 6.5 12.5H9.5C10.0304 12.5 10.5391 12.2893 10.9142 11.9142C11.2893 11.5391 11.5 11.0304 11.5 10.5C11.5 9.96957 11.2893 9.46086 10.9142 9.08579C10.5391 8.71071 10.0304 8.5 9.5 8.5H7C6.20435 8.5 5.44129 8.18393 4.87868 7.62132C4.31607 7.05871 4 6.29565 4 5.5C4 4.70435 4.31607 3.94129 4.87868 3.37868C5.44129 2.81607 6.20435 2.5 7 2.5H7.5V1.5C7.5 1.36739 7.55268 1.24021 7.64645 1.14645C7.74021 1.05268 7.86739 1 8 1C8.13261 1 8.25979 1.05268 8.35355 1.14645C8.44732 1.24021 8.5 1.36739 8.5 1.5V2.5H9C9.7954 2.50083 10.558 2.81716 11.1204 3.37959C11.6828 3.94202 11.9992 4.7046 12 5.5C12 5.63261 11.9473 5.75979 11.8536 5.85355C11.7598 5.94732 11.6326 6 11.5 6C11.3674 6 11.2402 5.94732 11.1464 5.85355C11.0527 5.75979 11 5.63261 11 5.5C11 4.96957 10.7893 4.46086 10.4142 4.08579C10.0391 3.71071 9.53043 3.5 9 3.5H7C6.46957 3.5 5.96086 3.71071 5.58579 4.08579C5.21071 4.46086 5 4.96957 5 5.5C5 6.03043 5.21071 6.53914 5.58579 6.91421C5.96086 7.28929 6.46957 7.5 7 7.5H9.5C10.2954 7.50083 11.058 7.81716 11.6204 8.37959C12.1828 8.94202 12.4992 9.7046 12.5 10.5Z"
                                    fill="#101010" />
                            </svg>
                        </span>
                    </div>
                    <p class="text-[24px] font-bold tracking-tight text-[#101010]">
                        {{ formatRupiah(stats.total_purchase) }}
                    </p>
                    <p class="text-xs text-gray-400">Total value</p>
                </div>

                <div class="bg-white rounded-xl border border-[#EDEDED] shadow-sm p-4 flex flex-col gap-1">
                    <div class="flex items-center justify-between">
                        <span class="text-[14px] text-[#101010] font-semibold">Total Utang (AP)</span>
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
                    <p class="text-[24px] font-bold tracking-tight text-[#101010]">
                        {{ formatRupiah(stats.total_debt) }}
                    </p>
                    <p class="text-xs text-gray-400">Outstanding</p>
                </div>

                <div class="bg-white rounded-xl border border-[#EDEDED] shadow-sm p-4 flex flex-col gap-1">
                    <div class="flex items-center justify-between">
                        <span class="text-[14px] text-[#101010] font-semibold">Jatuh Tempo</span>
                        <span class="text-gray-300">
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
                    <p class="text-[24px] font-bold tracking-tight text-[#101010]">
                        {{ stats.due_date_count }}
                    </p>
                    <p class="text-xs text-gray-400">{{ formatRupiah(stats.due_date_nominal) }}</p>
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
                        <input v-model="searchQuery" type="text" placeholder="Cari no. transaksi / supplier..."
                            class="h-[45px] w-full rounded-lg border border-gray-300 bg-white py-2 pr-3 pl-9 text-sm placeholder-gray-400 focus:border-[#007C95] focus:ring-1 focus:ring-[#007C95] focus:outline-none sm:w-64" />
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
                                            :class="sortClass('transaction_number')" @click="handleSort('transaction_number')">
                                            No Transaksi
                                            <span class="flex flex-col">
                                                <ChevronUp class="size-3 -mb-0.5"
                                                    :class="chevronClass('transaction_number', 'asc')" />
                                                <ChevronDown class="size-3 -mt-0.5"
                                                    :class="chevronClass('transaction_number', 'desc')" />
                                            </span>
                                        </button>
                                    </th>

                                    <th class="px-4 py-3 text-left">
                                        <button
                                            class="flex items-center gap-1 text-xs font-semibold uppercase tracking-wider transition"
                                            :class="sortClass('date')" @click="handleSort('date')">
                                            Tanggal
                                            <span class="flex flex-col">
                                                <ChevronUp class="size-3 -mb-0.5"
                                                    :class="chevronClass('date', 'asc')" />
                                                <ChevronDown class="size-3 -mt-0.5"
                                                    :class="chevronClass('date', 'desc')" />
                                            </span>
                                        </button>
                                    </th>

                                    <th class="px-4 py-3 text-left">
                                        <span
                                            class="text-xs font-semibold uppercase tracking-wider text-gray-500">Supplier</span>
                                    </th>

                                    <th class="px-4 py-3 text-left">
                                        <span
                                            class="text-xs font-semibold uppercase tracking-wider text-gray-500">Gudang</span>
                                    </th>

                                    <th class="px-4 py-3 text-left">
                                        <button
                                            class="flex items-center gap-1 text-xs font-semibold uppercase tracking-wider transition"
                                            :class="sortClass('volume')" @click="handleSort('volume')">
                                            Volume
                                            <span class="flex flex-col">
                                                <ChevronUp class="size-3 -mb-0.5"
                                                    :class="chevronClass('volume', 'asc')" />
                                                <ChevronDown class="size-3 -mt-0.5"
                                                    :class="chevronClass('volume', 'desc')" />
                                            </span>
                                        </button>
                                    </th>

                                    <th class="px-4 py-3 text-left">
                                        <button
                                            class="flex items-center gap-1 text-xs font-semibold uppercase tracking-wider transition"
                                            :class="sortClass('purchase_price')" @click="handleSort('purchase_price')">
                                            Harga Beli
                                            <span class="flex flex-col">
                                                <ChevronUp class="size-3 -mb-0.5"
                                                    :class="chevronClass('purchase_price', 'asc')" />
                                                <ChevronDown class="size-3 -mt-0.5"
                                                    :class="chevronClass('purchase_price', 'desc')" />
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
                                <tr v-for="item in goodsReceipts.data" :key="item.id"
                                    class="transition hover:bg-gray-50/60">

                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="font-mono text-xs text-gray-700 font-medium">{{ item.transaction_number
                                            }}</span>
                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap text-gray-500">
                                        {{ formatTanggal(item.date) }}
                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap text-gray-700">
                                        {{ item.supplier_name }}
                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap text-gray-500">
                                        {{ item.warehouse_name }}
                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap text-gray-700">
                                        {{ formatVolume(item.volume) }}
                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap text-gray-700">
                                        {{ formatRupiah(item.purchase_price) }}
                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex flex-col gap-0.5">
                                            <span
                                                class="inline-flex w-fit items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                                :class="item.status === 'lunas'
                                                    ? 'bg-emerald-50 text-emerald-700'
                                                    : 'bg-amber-50 text-amber-600'">
                                                {{ item.status === 'lunas' ? 'Lunas' : 'Belum Lunas' }}
                                            </span>
                                            <span v-if="item.status === 'belum_lunas' && item.due_date"
                                                class="text-[11px] text-amber-500">
                                                {{ formatTanggal(item.due_date) }}
                                            </span>
                                        </div>
                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap w-[50px]">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <button
                                                    class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                                                    <EllipsisVertical class="size-4" />
                                                </button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end" class="w-48">
                                                <DropdownMenuItem class="gap-2 text-sm"
                                                    @click="router.get(`/goods-receipt/${item.id}`)">
                                                    <Eye class="size-3.5" />
                                                    Lihat Detail
                                                </DropdownMenuItem>
                                                <DropdownMenuItem class="gap-2 text-sm" @click="openEdit(item)">
                                                    <Pencil class="size-3.5" />
                                                    Ubah Data
                                                </DropdownMenuItem>
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem class="gap-2 text-sm" :class="item.status === 'belum_lunas'
                                                    ? 'text-emerald-600 focus:text-emerald-600'
                                                    : 'text-amber-600 focus:text-amber-600'"
                                                    @click="toggleStatus(item)">
                                                    <component
                                                        :is="item.status === 'belum_lunas' ? CheckCircle2 : XCircle"
                                                        class="size-3.5" />
                                                    {{
                                                        item.status === 'belum_lunas' ? 'Tandai Lunas' :
                                                            'Tandai Belum Lunas'
                                                    }}
                                                </DropdownMenuItem>
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem class="gap-2 text-sm text-red-600 focus:text-red-600"
                                                    @click="openDelete(item)">
                                                    <Trash2 class="size-3.5" />
                                                    Hapus
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </td>
                                </tr>

                                <!-- Empty State -->
                                <tr v-if="goodsReceipts.data.length === 0">
                                    <td colspan="8" class="px-5 py-10 text-center">
                                        <div class="flex flex-col items-center gap-2">
                                            <Vue3Lottie :animationData="emptyAnimation" :height="160" :width="160"
                                                :loop="true" />
                                            <p class="text-sm font-medium text-gray-600">Tidak ada data Barang Masuk</p>
                                            <p class="text-xs text-gray-400">Coba ubah kata kunci pencarian atau filter
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <TablePagination :paginator="goodsReceipts" @navigate="goToPage" />
            </div>

        </div>

        <!-- ── Modals ─────────────────────────────────────────── -->

        <GoodsReceiptFormModal v-model:open="isFormOpen" :editing-goods-receipt="editingGoodsReceipt"
            post-url="/goods-receipt" :suppliers="allSuppliers" :warehouses="allWarehouses" @success="toast.success('Berhasil!', {
                description: editingGoodsReceipt
                    ? 'Data barang masuk berhasil diperbarui'
                    : 'Barang masuk berhasil dicatat'
            })" />

        <!-- Delete Confirmation -->
        <AlertDialog v-model:open="isDeleteOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Hapus Data Barang Masuk</AlertDialogTitle>
                    <AlertDialogDescription>
                        Apakah Anda yakin ingin menghapus transaksi
                        <strong>{{ deletingGr?.transaction_number }}</strong>?
                        Tindakan ini tidak dapat dibatalkan.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Batal</AlertDialogCancel>
                    <AlertDialogAction class="bg-red-600 hover:bg-red-700" :disabled="isDeleting"
                        @click="confirmDelete">
                        {{ isDeleting ? 'Menghapus...' : 'Ya, Hapus' }}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

    </AppLayout>
</template>