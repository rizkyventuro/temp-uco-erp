<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    Search,
    Plus,
    ChevronUp,
    ChevronDown,
    EllipsisVertical,
    CheckCircle2,
    XCircle,
    Truck,
} from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import { Vue3Lottie } from 'vue3-lottie';
import emptyAnimation from '@/../../public/assets/animations/Pencarian Tidak Ditemukan.json';

import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import TablePagination from '@/components/TablePagination.vue';
import TableFilter from '@/components/TableFilter.vue';
import type { FilterValues } from '@/components/TableFilter.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import WarehouseTransferModal from '@/components/Warehouse/WarehouseTransferModal.vue';

// ── Types ──────────────────────────────────────────────────────

interface Transfer {
    id: number;
    transfer_number: string;
    from_warehouse_id: number;
    from_warehouse_name: string;
    from_warehouse_code: string;
    to_warehouse_id: number;
    to_warehouse_name: string;
    to_warehouse_code: string;
    volume: number;
    status: 'Pending' | 'In Transit' | 'Completed' | 'Cancelled';
    notes: string | null;
    transferred_at: string;
    completed_at: string | null;
    created_by_name: string | null;
}

interface WarehouseCard {
    id: number;
    code: string;
    name: string;
    address: string | null;
    city_name: string | null;
    type: string | null;
    is_active: boolean;
    capacity_max: number;
    current_stock: number;
    occupancy: number;
    status_label: string;
    status_color: 'emerald' | 'amber' | 'rose' | 'red';
}

interface WarehouseOption {
    id: number;
    label: string;
    name?: string;
    code?: string;
    current_stock?: number;
    capacity_max?: number;
    occupancy?: number;
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

interface Stats {
    total: number;
    pending: number;
    in_transit: number;
    completed: number;
}

// ── Props ──────────────────────────────────────────────────────

const props = defineProps<{
    transfers: Paginated<Transfer>;
    warehouses: Paginated<WarehouseCard>;
    allWarehouses: WarehouseOption[];
    stats: Stats;
    filters: {
        search?: string;
        per_page?: number;
        status?: string;
        from_warehouse_id?: string;
        to_warehouse_id?: string;
        sort?: string;
        direction?: 'asc' | 'desc';
        warehouse_page?: number;
    };
}>();

// ── Breadcrumbs ────────────────────────────────────────────────

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Transfer Stok', href: '/stock-transfer' },
];

// ── State ──────────────────────────────────────────────────────

const searchQuery = ref(props.filters.search ?? '');
const perPage = ref(props.filters.per_page ?? 10);
const sortColumn = ref(props.filters.sort ?? 'created_at');
const sortDirection = ref<'asc' | 'desc'>(props.filters.direction ?? 'desc');

const filterFields = [
    {
        key: 'status', label: 'Status', type: 'select' as const,
        options: [
            { label: 'Pending', value: 'Pending' },
            { label: 'In Transit', value: 'In Transit' },
            { label: 'Completed', value: 'Completed' },
            { label: 'Cancelled', value: 'Cancelled' },
        ],
    },
    {
        key: 'from_warehouse_id', label: 'Dari Gudang', type: 'select' as const,
        options: props.allWarehouses.map(w => ({ label: w.label, value: String(w.id) })),
    },
    {
        key: 'to_warehouse_id', label: 'Ke Gudang', type: 'select' as const,
        options: props.allWarehouses.map(w => ({ label: w.label, value: String(w.id) })),
    },
];

const filterValues = ref<FilterValues>({
    status: props.filters.status ?? undefined,
    from_warehouse_id: props.filters.from_warehouse_id ?? undefined,
    to_warehouse_id: props.filters.to_warehouse_id ?? undefined,
});

const buildParams = (extra: FilterValues = {}) => ({
    search: searchQuery.value || undefined,
    per_page: perPage.value,
    sort: sortColumn.value,
    direction: sortDirection.value,
    ...extra,
});

const handleSort = (col: string) => {
    if (sortColumn.value === col) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortColumn.value = col;
        sortDirection.value = 'asc';
    }
    router.get('/stock-transfer', buildParams(filterValues.value), { preserveState: true, replace: true });
};

const handleFilter = (values: FilterValues) => {
    filterValues.value = values;
    router.get('/stock-transfer', buildParams(values), { preserveState: true, replace: true });
};

const handleFilterReset = () => {
    filterValues.value = {};
    router.get('/stock-transfer', buildParams({}), { preserveState: true, replace: true });
};

const navigateTransfer = (url: string | null) => {
    if (!url) return;
    router.get(url, buildParams(filterValues.value), { preserveState: true });
};

const navigateWarehouse = (url: string | null) => {
    if (!url) return;
    router.get(url, { ...buildParams(filterValues.value), warehouse_page: undefined }, { preserveState: true });
};

let searchTimeout: ReturnType<typeof setTimeout>;
watch(searchQuery, (val) => {
    clearTimeout(searchTimeout);
    if (val.length === 0 || val.length >= 3) {
        searchTimeout = setTimeout(() => {
            router.get('/stock-transfer', buildParams(filterValues.value), { preserveState: true, replace: true });
        }, 400);
    }
});

watch(perPage, () => {
    router.get('/stock-transfer', buildParams(filterValues.value), { preserveState: true, replace: true });
});

// ── Transfer Form Modal ─────────────────────────────────────────

const isFormOpen = ref(false);
const transferSourceWarehouse = ref<WarehouseCard | null>(null);

/** Buka modal TANPA pre-select gudang asal (tombol header) */
function openTransferNew() {
    transferSourceWarehouse.value = null;
    isFormOpen.value = true;
}

/** Buka modal DENGAN pre-select gudang asal (klik card gudang) */
function openTransferFrom(warehouse: WarehouseCard) {
    transferSourceWarehouse.value = warehouse;
    isFormOpen.value = true;
}

// ── Status Update ──────────────────────────────────────────────

function updateStatus(transfer: Transfer, status: string) {
    router.patch(`/stock-transfer/${transfer.id}/status`, { status }, {
        preserveScroll: true,
        onSuccess: () => toast.success('Berhasil!', { description: 'Status transfer berhasil diperbarui' }),
        onError: () => toast.error('Gagal!', { description: 'Gagal memperbarui status transfer' }),
    });
}

function deleteTransfer(transfer: Transfer) {
    if (!confirm(`Hapus transfer ${transfer.transfer_number}?`)) return;
    router.delete(`/stock-transfer/${transfer.id}`, {
        preserveScroll: true,
        onSuccess: () => toast.success('Berhasil!', { description: 'Transfer berhasil dihapus' }),
        onError: () => toast.error('Gagal!', { description: 'Gagal menghapus transfer' }),
    });
}

// ── Helpers ────────────────────────────────────────────────────

function occupancyBarColor(pct: number) {
    if (pct >= 90) return 'bg-red-500';
    if (pct >= 70) return 'bg-amber-400';
    return 'bg-emerald-400';
}

function statusBadgeColor(status_color: string) {
    const map: Record<string, string> = {
        rose: 'bg-rose-50 text-rose-600 border border-rose-200',
        red: 'bg-rose-50 text-rose-600 border border-rose-200',
        amber: 'bg-amber-50 text-amber-700 border border-amber-200',
        emerald: 'bg-emerald-50 text-emerald-700 border border-emerald-200',
    };
    return map[status_color] ?? 'bg-gray-100 text-gray-600 border border-gray-200';
}

const transferStatusBadge: Record<string, string> = {
    'Completed': 'text-emerald-600',
    'In Transit': 'text-amber-600',
    'Pending': 'text-blue-600',
    'Cancelled': 'text-rose-600',
};

function formatVolume(val: number) {
    return new Intl.NumberFormat('id-ID').format(val) + ' kg';
}

function formatNumber(val: number) {
    return new Intl.NumberFormat('id-ID').format(val);
}

const sortClass = (col: string) =>
    sortColumn.value === col ? 'text-[#007C95]' : 'text-gray-500 hover:text-gray-700';

const chevronClass = (col: string, dir: 'asc' | 'desc') =>
    sortColumn.value === col && sortDirection.value === dir ? 'text-[#007C95]' : 'text-gray-300';
</script>

<template>

    <Head title="Transfer Stok" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 pb-10 md:p-6">

            <!-- ── Header ──────────────────────────────────── -->
            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div class="flex-1 min-w-0">
                    <h1 class="text-[24px] font-bold text-gray-900">Transfer Stok</h1>
                    <p class="mt-0.5 text-[16px] text-gray-500">Pindahkan stok antar gudang</p>
                </div>

                <div class="flex w-full justify-end md:w-auto md:flex-shrink-0">
                    <Button
                        class="flex w-fit items-center justify-center gap-1.5 rounded-lg bg-[#007C95] px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-[#006b80]"
                        @click="openTransferNew">
                        <Plus class="size-4" />
                        Transfer Stok
                    </Button>
                </div>
            </div>

            <!-- ── List Gudang ─────────────────────────────── -->
            <div>
                <h2 class="text-[16px] font-semibold text-gray-900 mb-3">List Gudang</h2>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <div v-for="warehouse in warehouses.data" :key="warehouse.id"
                        class="flex flex-col rounded-xl border border-[#EDEDED] bg-white shadow-sm p-4 gap-3 cursor-pointer hover:shadow-md hover:border-[#007C95]/30 transition"
                        @click="openTransferFrom(warehouse)">

                        <!-- Name & Address -->
                        <div class="min-w-0">
                            <p class="text-[14px] font-semibold text-[#101010] truncate">{{ warehouse.name }}</p>
                            <p class="text-[12px] text-gray-400 truncate mt-0.5">
                                {{ warehouse.address ?? warehouse.city_name ?? '—' }}
                            </p>
                        </div>

                        <!-- Status Badge -->
                        <span class="w-fit inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-semibold"
                            :class="statusBadgeColor(warehouse.status_color)">
                            {{ warehouse.status_label }}
                        </span>

                        <!-- Progress Bar -->
                        <div>
                            <div class="h-2 w-full rounded-full bg-gray-100 overflow-hidden">
                                <div class="h-2 rounded-full transition-all"
                                    :class="occupancyBarColor(warehouse.occupancy)"
                                    :style="{ width: Math.min(warehouse.occupancy, 100) + '%' }" />
                            </div>
                            <div class="flex items-center justify-between mt-1.5">
                                <span class="text-[12px] font-medium text-[#101010]">
                                    {{ formatNumber(warehouse.current_stock) }} kg ({{ warehouse.occupancy }}%)
                                </span>
                                <span class="text-[12px] text-gray-400">
                                    {{ formatNumber(warehouse.capacity_max) }} kg
                                </span>
                            </div>
                        </div>
                    </div>

                    <div v-if="warehouses.data.length === 0" class="col-span-full py-10 text-center">
                        <p class="text-sm text-gray-400">Tidak ada data gudang</p>
                    </div>
                </div>

                <TablePagination :paginator="warehouses" type="centerPaginate" @navigate="navigateWarehouse" />
            </div>

            <!-- ── Riwayat Transfer Stok ───────────────────── -->
            <div>
                <h2 class="text-[16px] font-semibold text-gray-900 mb-3">Riwayat Transfer Stok</h2>

                <!-- Toolbar -->
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-3">
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
                            @update:model-value="handleFilter" @reset="handleFilterReset" />
                        <div class="relative flex-1 sm:flex-none">
                            <Search class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-gray-400" />
                            <input v-model="searchQuery" type="text" placeholder="Cari..."
                                class="h-[45px] w-full rounded-lg border border-gray-300 bg-white py-2 pr-3 pl-9 text-sm placeholder-gray-400 focus:border-[#007C95] focus:ring-1 focus:ring-[#007C95] focus:outline-none sm:w-56" />
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-hidden rounded-xl border-gray-200 bg-white">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 bg-[#F9F9F9]">
                                    <th class="px-4 py-3 text-left">
                                        <button
                                            class="flex items-center gap-1 text-xs font-semibold uppercase tracking-wider transition"
                                            :class="sortClass('transfer_number')"
                                            @click="handleSort('transfer_number')">
                                            No Transfer
                                            <span class="flex flex-col">
                                                <ChevronUp class="size-3 -mb-0.5"
                                                    :class="chevronClass('transfer_number', 'asc')" />
                                                <ChevronDown class="size-3 -mt-0.5"
                                                    :class="chevronClass('transfer_number', 'desc')" />
                                            </span>
                                        </button>
                                    </th>
                                    <th class="px-4 py-3 text-left">
                                        <button
                                            class="flex items-center gap-1 text-xs font-semibold uppercase tracking-wider transition"
                                            :class="sortClass('transferred_at')" @click="handleSort('transferred_at')">
                                            Tanggal
                                            <span class="flex flex-col">
                                                <ChevronUp class="size-3 -mb-0.5"
                                                    :class="chevronClass('transferred_at', 'asc')" />
                                                <ChevronDown class="size-3 -mt-0.5"
                                                    :class="chevronClass('transferred_at', 'desc')" />
                                            </span>
                                        </button>
                                    </th>
                                    <th class="px-4 py-3 text-left">
                                        <button
                                            class="flex items-center gap-1 text-xs font-semibold uppercase tracking-wider transition"
                                            :class="sortClass('from_warehouse_name')"
                                            @click="handleSort('from_warehouse_name')">
                                            Dari Gudang
                                            <span class="flex flex-col">
                                                <ChevronUp class="size-3 -mb-0.5"
                                                    :class="chevronClass('from_warehouse_name', 'asc')" />
                                                <ChevronDown class="size-3 -mt-0.5"
                                                    :class="chevronClass('from_warehouse_name', 'desc')" />
                                            </span>
                                        </button>
                                    </th>
                                    <th class="px-4 py-3 text-left">
                                        <button
                                            class="flex items-center gap-1 text-xs font-semibold uppercase tracking-wider transition"
                                            :class="sortClass('to_warehouse_name')"
                                            @click="handleSort('to_warehouse_name')">
                                            Ke Gudang
                                            <span class="flex flex-col">
                                                <ChevronUp class="size-3 -mb-0.5"
                                                    :class="chevronClass('to_warehouse_name', 'asc')" />
                                                <ChevronDown class="size-3 -mt-0.5"
                                                    :class="chevronClass('to_warehouse_name', 'desc')" />
                                            </span>
                                        </button>
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
                                            :class="sortClass('status')" @click="handleSort('status')">
                                            Status
                                            <span class="flex flex-col">
                                                <ChevronUp class="size-3 -mb-0.5"
                                                    :class="chevronClass('status', 'asc')" />
                                                <ChevronDown class="size-3 -mt-0.5"
                                                    :class="chevronClass('status', 'desc')" />
                                            </span>
                                        </button>
                                    </th>
                                    <th class="px-4 py-3 text-left w-[50px]">
                                        <span
                                            class="text-xs font-semibold uppercase tracking-wider text-gray-500">Aksi</span>
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="transfer in transfers.data" :key="transfer.id"
                                    class="transition hover:bg-gray-50/60">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="font-mono text-xs text-gray-500">{{ transfer.transfer_number
                                            }}</span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-[13px] text-gray-500">
                                        {{ transfer.transferred_at }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-[13px] text-gray-900">
                                        {{ transfer.from_warehouse_name }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-[13px] text-gray-900">
                                        {{ transfer.to_warehouse_name }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-[13px] text-gray-700">
                                        {{ formatVolume(transfer.volume) }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="text-[13px] font-medium"
                                            :class="transferStatusBadge[transfer.status] ?? 'text-gray-600'">
                                            {{ transfer.status }}
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
                                                <template v-if="transfer.status === 'Pending'">
                                                    <DropdownMenuItem
                                                        class="gap-2 text-sm text-blue-600 focus:text-blue-600"
                                                        @click="updateStatus(transfer, 'In Transit')">
                                                        <Truck class="size-3.5" /> Tandai In Transit
                                                    </DropdownMenuItem>
                                                </template>
                                                <template v-if="transfer.status === 'In Transit'">
                                                    <DropdownMenuItem
                                                        class="gap-2 text-sm text-emerald-600 focus:text-emerald-600"
                                                        @click="updateStatus(transfer, 'Completed')">
                                                        <CheckCircle2 class="size-3.5" /> Tandai Completed
                                                    </DropdownMenuItem>
                                                </template>
                                                <template
                                                    v-if="transfer.status !== 'Completed' && transfer.status !== 'Cancelled'">
                                                    <DropdownMenuItem
                                                        class="gap-2 text-sm text-rose-600 focus:text-rose-600"
                                                        @click="updateStatus(transfer, 'Cancelled')">
                                                        <XCircle class="size-3.5" /> Batalkan Transfer
                                                    </DropdownMenuItem>
                                                    <DropdownMenuSeparator />
                                                    <DropdownMenuItem
                                                        class="gap-2 text-sm text-red-600 focus:text-red-600"
                                                        @click="deleteTransfer(transfer)">
                                                        Hapus
                                                    </DropdownMenuItem>
                                                </template>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </td>
                                </tr>

                                <tr v-if="transfers.data.length === 0">
                                    <td colspan="7" class="px-5 py-10 text-center">
                                        <div class="flex flex-col items-center gap-2">
                                            <Vue3Lottie :animationData="emptyAnimation" :height="160" :width="160"
                                                :loop="true" />
                                            <p class="text-sm font-medium text-gray-600">Tidak ada data transfer</p>
                                            <p class="text-xs text-gray-400">Coba ubah kata kunci pencarian atau filter
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <TablePagination :paginator="transfers" @navigate="navigateTransfer" />
            </div>

        </div>

        <!-- ── Transfer Form Modal ────────────────────────── -->
        <WarehouseTransferModal v-model:open="isFormOpen" :source-warehouse="transferSourceWarehouse"
            :all-warehouses="allWarehouses" transfer-url="/stock-transfer"
            @success="toast.success('Berhasil!', { description: 'Transfer stok berhasil dicatat' })" />

    </AppLayout>
</template>