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
    BarChart3,
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

import GoodsIssueFormModal from '@/components/GoodsIssue/GoodsIssueFormModal.vue';
import type { GoodsIssue, BuyerOption, WarehouseOption } from '@/components/GoodsIssue/GoodsIssueFormModal.vue';

// ── Types ──────────────────────────────────────────────────────

interface PaginatedGoodsIssues {
    data: GoodsIssue[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: { url: string | null; label: string; active: boolean }[];
}

interface Stats {
    total_uco_out: number;
    total_uco_out_trend: number;
    total_uco_out_up: boolean;
    total_sales: number;
    total_transactions: number;
}

// ── Props ──────────────────────────────────────────────────────

const props = defineProps<{
    goodsIssues: PaginatedGoodsIssues;
    stats: Stats;
    buyers: { id: string; name: string }[];
    warehouses: { id: number; name: string }[];
    allBuyers: BuyerOption[];
    allWarehouses: WarehouseOption[];
    filters: {
        search?: string;
        perPage?: number;
        status?: string;
        buyer_id?: string;
        warehouse_id?: string;
        date_from?: string;
        date_to?: string;
        sort?: string;
        direction?: 'asc' | 'desc';
    };
}>();

// ── Breadcrumbs ────────────────────────────────────────────────

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Barang Keluar', href: '/goods-issue' },
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
            { label: 'Pending', value: 'pending' },
            { label: 'Shipped', value: 'shipped' },
            { label: 'Delivered', value: 'delivered' },
            { label: 'Cancelled', value: 'cancelled' },
        ],
    },
    {
        key: 'buyer_id',
        label: 'Buyer',
        type: 'select' as const,
        options: props.buyers.map((b) => ({ label: b.name, value: String(b.id) })),
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
    buyer_id: props.filters.buyer_id ?? undefined,
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
    router.get('/goods-issue', buildParams(filterValues.value), { preserveState: true, replace: true });
};

const handleFilter = (values: FilterValues) => {
    filterValues.value = values;
    router.get('/goods-issue', buildParams(values), { preserveState: true, replace: true });
};

const handleFilterReset = () => {
    filterValues.value = {};
    router.get('/goods-issue', buildParams({}), { preserveState: true, replace: true });
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
            router.get('/goods-issue', buildParams(filterValues.value), { preserveState: true, replace: true });
        }, 400);
    }
});

watch(perPage, () => {
    router.get('/goods-issue', buildParams(filterValues.value), { preserveState: true, replace: true });
});

// ── Form Modal ─────────────────────────────────────────────────

const isFormOpen = ref(false);
const editingGoodsIssue = ref<GoodsIssue | null>(null);

function openCreate() {
    editingGoodsIssue.value = null;
    isFormOpen.value = true;
}

function openEdit(gi: GoodsIssue) {
    editingGoodsIssue.value = gi;
    isFormOpen.value = true;
}

// ── Update Status ──────────────────────────────────────────────

type GoodsIssueStatus = GoodsIssue['status'];

const STATUS_NEXT: Record<GoodsIssueStatus, GoodsIssueStatus> = {
    pending: 'shipped',
    shipped: 'delivered',
    delivered: 'delivered',
    cancelled: 'cancelled',
};

const STATUS_NEXT_LABEL: Record<GoodsIssueStatus, string> = {
    pending: 'Tandai Shipped',
    shipped: 'Tandai Delivered',
    delivered: '—',
    cancelled: '—',
};

function updateStatus(gi: GoodsIssue, status: GoodsIssueStatus) {
    router.patch(
        `/goods-issue/${gi.id}/update-status`,
        { status },
        {
            preserveScroll: true,
            onSuccess: () => toast.success('Berhasil!', { description: `Status diubah menjadi ${status}` }),
            onError: () => toast.error('Gagal!', { description: 'Gagal mengubah status' }),
        },
    );
}

// ── Delete ─────────────────────────────────────────────────────

const isDeleteOpen = ref(false);
const deletingGi = ref<GoodsIssue | null>(null);
const isDeleting = ref(false);

function openDelete(gi: GoodsIssue) {
    deletingGi.value = gi;
    isDeleteOpen.value = true;
}

function confirmDelete() {
    if (!deletingGi.value) return;
    isDeleting.value = true;
    router.delete(`/goods-issue/${deletingGi.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Berhasil!', { description: 'Data barang keluar berhasil dihapus' });
            isDeleteOpen.value = false;
        },
        onError: () => toast.error('Gagal!', { description: 'Gagal menghapus data' }),
        onFinish: () => { isDeleting.value = false; },
    });
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

const statusBadgeClass: Record<string, string> = {
    pending: 'bg-amber-50 text-amber-600',
    shipped: 'bg-blue-50 text-blue-600',
    delivered: 'bg-emerald-50 text-emerald-700',
    cancelled: 'bg-red-50 text-red-600',
};
</script>

<template>

    <Head title="Barang Keluar" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 pb-10 md:p-6">

            <!-- ── Header ─────────────────────────────────────── -->
            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div class="flex-1 min-w-0">
                    <h1 class="text-[24px] font-bold text-gray-900">Barang Keluar</h1>
                    <p class="mt-0.5 text-[16px] text-gray-500">Pencatatan penjualan minyak jelantah ke buyer</p>
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
                            Tambah Barang Keluar
                        </Button>
                    </div>
                </div>
            </div>

            <!-- ── Stats Cards ────────────────────────────────── -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">

                <div class="bg-white rounded-xl border border-[#EDEDED] shadow-sm p-4 flex flex-col gap-1">
                    <div class="flex items-center justify-between">
                        <span class="text-[14px] text-[#101010] font-semibold">Total UCO Keluar</span>
                        <PackageOpen class="size-4 text-gray-300" />
                    </div>
                    <p class="text-[24px] font-bold tracking-tight text-[#101010]">
                        {{ formatVolume(stats.total_uco_out) }}
                    </p>
                    <p class="text-xs" :class="stats.total_uco_out_up ? 'text-emerald-600' : 'text-rose-500'">
                        <span>{{ stats.total_uco_out_up ? '▲' : '▼' }}</span>
                        {{ stats.total_uco_out_trend }}% from last month
                    </p>
                </div>

                <div class="bg-white rounded-xl border border-[#EDEDED] shadow-sm p-4 flex flex-col gap-1">
                    <div class="flex items-center justify-between">
                        <span class="text-[14px] text-[#101010] font-semibold">Total Penjualan</span>
                        <DollarSign class="size-4 text-gray-300" />
                    </div>
                    <p class="text-[24px] font-bold tracking-tight text-[#101010]">
                        {{ formatRupiah(stats.total_sales) }}
                    </p>
                    <p class="text-xs text-gray-400">Total value</p>
                </div>

                <div class="bg-white rounded-xl border border-[#EDEDED] shadow-sm p-4 flex flex-col gap-1">
                    <div class="flex items-center justify-between">
                        <span class="text-[14px] text-[#101010] font-semibold">Total Transaksi</span>
                        <BarChart3 class="size-4 text-gray-300" />
                    </div>
                    <p class="text-[24px] font-bold tracking-tight text-[#101010]">
                        {{ stats.total_transactions }}
                    </p>
                    <p class="text-xs text-gray-400">Semua transaksi</p>
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
                        <input v-model="searchQuery" type="text" placeholder="Cari no. transaksi / buyer..."
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
                                            class="text-xs font-semibold uppercase tracking-wider text-gray-500">Buyer</span>
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
                                            :class="sortClass('selling_price')" @click="handleSort('selling_price')">
                                            Harga Jual
                                            <span class="flex flex-col">
                                                <ChevronUp class="size-3 -mb-0.5"
                                                    :class="chevronClass('selling_price', 'asc')" />
                                                <ChevronDown class="size-3 -mt-0.5"
                                                    :class="chevronClass('selling_price', 'desc')" />
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
                                <tr v-for="item in goodsIssues.data" :key="item.id"
                                    class="transition hover:bg-gray-50/60">

                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="font-mono text-xs text-gray-700 font-medium">{{ item.transaction_number
                                            }}</span>
                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap text-gray-500">
                                        {{ formatTanggal(item.date) }}
                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap text-gray-700">
                                        {{ item.buyer_name }}
                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap text-gray-500">
                                        {{ item.warehouse_name }}
                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap text-gray-700">
                                        {{ formatVolume(item.volume) }}
                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap text-gray-700">
                                        {{ formatRupiah(item.selling_price) }}
                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span
                                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                            :class="statusBadgeClass[item.status] ?? 'bg-gray-100 text-gray-600'">
                                            {{ item.status_label }}
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
                                            <DropdownMenuContent align="end" class="w-48">
                                                <DropdownMenuItem class="gap-2 text-sm"
                                                    @click="router.get(`/goods-issue/${item.id}`)">
                                                    <Eye class="size-3.5" />
                                                    Lihat Detail
                                                </DropdownMenuItem>
                                                <DropdownMenuItem class="gap-2 text-sm" @click="openEdit(item)">
                                                    <Pencil class="size-3.5" />
                                                    Ubah Data
                                                </DropdownMenuItem>

                                                <!-- Status progression -->
                                                <template v-if="item.status === 'pending' || item.status === 'shipped'">
                                                    <DropdownMenuSeparator />
                                                    <DropdownMenuItem
                                                        class="gap-2 text-sm text-blue-600 focus:text-blue-600"
                                                        @click="updateStatus(item, STATUS_NEXT[item.status])">
                                                        {{ STATUS_NEXT_LABEL[item.status] }}
                                                    </DropdownMenuItem>
                                                </template>

                                                <!-- Cancel (only from pending) -->
                                                <template v-if="item.status === 'pending'">
                                                    <DropdownMenuItem
                                                        class="gap-2 text-sm text-red-500 focus:text-red-500"
                                                        @click="updateStatus(item, 'cancelled')">
                                                        Batalkan
                                                    </DropdownMenuItem>
                                                </template>

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
                                <tr v-if="goodsIssues.data.length === 0">
                                    <td colspan="8" class="px-5 py-10 text-center">
                                        <div class="flex flex-col items-center gap-2">
                                            <Vue3Lottie :animationData="emptyAnimation" :height="160" :width="160"
                                                :loop="true" />
                                            <p class="text-sm font-medium text-gray-600">Tidak ada data Barang Keluar
                                            </p>
                                            <p class="text-xs text-gray-400">Coba ubah kata kunci pencarian atau filter
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <TablePagination :paginator="goodsIssues" @navigate="goToPage" />
            </div>

        </div>

        <!-- ── Modals ─────────────────────────────────────────── -->

        <GoodsIssueFormModal v-model:open="isFormOpen" :editing-goods-issue="editingGoodsIssue" post-url="/goods-issue"
            :buyers="allBuyers" :warehouses="allWarehouses" @success="toast.success('Berhasil!', {
                description: editingGoodsIssue
                    ? 'Data barang keluar berhasil diperbarui'
                    : 'Barang keluar berhasil dicatat'
            })" />

        <!-- Delete Confirmation -->
        <AlertDialog v-model:open="isDeleteOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Hapus Data Barang Keluar</AlertDialogTitle>
                    <AlertDialogDescription>
                        Apakah Anda yakin ingin menghapus transaksi
                        <strong>{{ deletingGi?.transaction_number }}</strong>?
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