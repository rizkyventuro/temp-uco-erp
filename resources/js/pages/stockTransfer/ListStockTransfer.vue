<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    Search,
    Plus,
    ChevronUp,
    ChevronDown,
    ArrowRightLeft,
    EllipsisVertical,
    CheckCircle2,
    XCircle,
    Clock,
    Truck,
} from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { toast } from 'vue-sonner';

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
import StockTransferFormModal from '@/components/StockTransfer/StockTransferFormModal.vue';

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
    { title: 'Stock Transfer', href: '/stock-transfer' },
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
        key: 'from_warehouse_id', label: 'From Warehouse', type: 'select' as const,
        options: props.allWarehouses.map(w => ({ label: w.label, value: String(w.id) })),
    },
    {
        key: 'to_warehouse_id', label: 'To Warehouse', type: 'select' as const,
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

// ── Status Update ──────────────────────────────────────────────

function updateStatus(transfer: Transfer, status: string) {
    router.patch(`/stock-transfer/${transfer.id}/status`, { status }, {
        preserveScroll: true,
        onSuccess: () => toast.success('Status updated successfully.'),
        onError: () => toast.error('Failed to update status.'),
    });
}

function deleteTransfer(transfer: Transfer) {
    if (!confirm(`Delete transfer ${transfer.transfer_number}?`)) return;
    router.delete(`/stock-transfer/${transfer.id}`, {
        preserveScroll: true,
        onSuccess: () => toast.success('Transfer deleted.'),
        onError: () => toast.error('Failed to delete transfer.'),
    });
}

// ── Helpers ────────────────────────────────────────────────────

function occupancyBarColor(pct: number) {
    if (pct >= 90) return 'bg-red-500';
    if (pct >= 70) return 'bg-amber-400';
    return 'bg-[#007C95]';
}

function occupancyTextColor(pct: number) {
    if (pct >= 90) return 'text-red-600';
    if (pct >= 70) return 'text-amber-600';
    return 'text-[#007C95]';
}

const statusBadge: Record<string, string> = {
    'Completed': 'bg-emerald-50 text-emerald-700',
    'In Transit': 'bg-blue-50 text-blue-700',
    'Pending': 'bg-amber-50 text-amber-700',
    'Cancelled': 'bg-rose-50 text-rose-600',
};

function formatVolume(val: number) {
    return new Intl.NumberFormat('id-ID').format(val) + ' kg';
}

const sortClass = (col: string) =>
    sortColumn.value === col ? 'text-[#007C95]' : 'text-gray-500 hover:text-gray-700';

const chevronClass = (col: string, dir: 'asc' | 'desc') =>
    sortColumn.value === col && sortDirection.value === dir ? 'text-[#007C95]' : 'text-gray-300';
</script>

<template>

    <Head title="Stock Transfer" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 pb-10 md:p-6">

            <!-- ── Header ──────────────────────────────────── -->
            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div>
                    <h1 class="text-[24px] font-bold text-gray-900">Stock Transfer</h1>
                    <p class="mt-0.5 text-[16px] text-gray-500">Move stock between warehouses</p>
                </div>
                <button
                    class="flex w-fit items-center gap-1.5 rounded-lg bg-[#007C95] px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-[#006b80] transition"
                    @click="isFormOpen = true">
                    <Plus class="size-4" />
                    Transfer Stock
                </button>
            </div>

            <!-- ── Stat Cards ──────────────────────────────── -->
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="bg-white rounded-xl border border-[#EDEDED] shadow-sm p-4 flex flex-col gap-1">
                    <span class="text-[13px] font-semibold text-[#101010]">Total Transfers</span>
                    <p class="text-[24px] font-bold tracking-tight text-[#101010]">{{ stats.total }}</p>
                </div>
                <div class="bg-white rounded-xl border border-[#EDEDED] shadow-sm p-4 flex flex-col gap-1">
                    <div class="flex items-center gap-1.5">
                        <Clock class="size-3.5 text-amber-500" />
                        <span class="text-[13px] font-semibold text-[#101010]">Pending</span>
                    </div>
                    <p class="text-[24px] font-bold tracking-tight text-amber-600">{{ stats.pending }}</p>
                </div>
                <div class="bg-white rounded-xl border border-[#EDEDED] shadow-sm p-4 flex flex-col gap-1">
                    <div class="flex items-center gap-1.5">
                        <Truck class="size-3.5 text-blue-500" />
                        <span class="text-[13px] font-semibold text-[#101010]">In Transit</span>
                    </div>
                    <p class="text-[24px] font-bold tracking-tight text-blue-600">{{ stats.in_transit }}</p>
                </div>
                <div class="bg-white rounded-xl border border-[#EDEDED] shadow-sm p-4 flex flex-col gap-1">
                    <div class="flex items-center gap-1.5">
                        <CheckCircle2 class="size-3.5 text-emerald-500" />
                        <span class="text-[13px] font-semibold text-[#101010]">Completed</span>
                    </div>
                    <p class="text-[24px] font-bold tracking-tight text-emerald-600">{{ stats.completed }}</p>
                </div>
            </div>

            <!-- ── Warehouse Cards ─────────────────────────── -->
            <div>
                <h2 class="text-[16px] font-semibold text-gray-800 mb-3">Warehouse List</h2>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <div v-for="warehouse in warehouses.data" :key="warehouse.id"
                        class="flex flex-col rounded-xl border border-gray-200 bg-white shadow-sm p-4 gap-2">

                        <!-- Name & Location -->
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-[13px] font-semibold text-gray-900 truncate">{{ warehouse.name }}</p>
                                <p class="text-[11px] text-gray-400 truncate mt-0.5">
                                    {{ warehouse.address ?? warehouse.city_name ?? '—' }}
                                </p>
                            </div>
                        </div>

                        <!-- Status Badge -->
                        <span class="w-fit inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-semibold"
                            :class="{
                                'bg-rose-50 text-rose-600': warehouse.status_color === 'rose' || warehouse.status_color === 'red',
                                'bg-amber-50 text-amber-700': warehouse.status_color === 'amber',
                                'bg-emerald-50 text-emerald-700': warehouse.status_color === 'emerald',
                            }">
                            {{ warehouse.status_label }}
                        </span>

                        <!-- Progress Bar -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-[12px] font-bold text-gray-800">
                                    {{ new Intl.NumberFormat('id-ID').format(warehouse.current_stock) }} kg
                                    <span :class="occupancyTextColor(warehouse.occupancy)"
                                        class="text-[11px] font-semibold ml-0.5">
                                        ({{ warehouse.occupancy }}%)
                                    </span>
                                </span>
                                <span class="text-[11px] text-gray-400">
                                    {{ new Intl.NumberFormat('id-ID').format(warehouse.capacity_max) }}&thinsp;kg
                                </span>
                            </div>
                            <div class="h-1.5 w-full rounded-full bg-gray-100 overflow-hidden">
                                <div class="h-1.5 rounded-full transition-all"
                                    :class="occupancyBarColor(warehouse.occupancy)"
                                    :style="{ width: warehouse.occupancy + '%' }" />
                            </div>
                        </div>
                    </div>

                    <div v-if="warehouses.data.length === 0" class="col-span-full py-10 text-center">
                        <p class="text-sm text-gray-400">No warehouses found.</p>
                    </div>
                </div>

                <!-- Warehouse Pagination -->
                <TablePagination :paginator="warehouses" type="centerPaginate" @navigate="navigateWarehouse" />
            </div>

            <!-- ── Transfer History Table ──────────────────── -->
            <div>
                <h2 class="text-[16px] font-semibold text-gray-800 mb-3">Stock Transfer History</h2>

                <!-- Toolbar -->
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <select v-model="perPage"
                            class="h-[40px] w-16 rounded-lg border border-gray-300 bg-white px-2 text-sm text-gray-700 focus:border-[#007C95] focus:ring-1 focus:ring-[#007C95] focus:outline-none">
                            <option :value="10">10</option>
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                        </select>
                        <span class="text-sm text-gray-500">Entries per page</span>
                    </div>

                    <div class="flex items-center gap-2">
                        <TableFilter :filters="filterFields" :model-value="filterValues"
                            @update:model-value="handleFilter" @reset="handleFilterReset" />
                        <div class="relative">
                            <Search class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-gray-400" />
                            <input v-model="searchQuery" type="text" placeholder="Search..."
                                class="h-[40px] w-full rounded-lg border border-gray-300 bg-white py-2 pr-3 pl-9 text-sm placeholder-gray-400 focus:border-[#007C95] focus:ring-1 focus:ring-[#007C95] focus:outline-none sm:w-52" />
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 bg-[#F9F9F9]">

                                    <!-- Transfer No -->
                                    <th class="px-4 py-3 text-left">
                                        <button
                                            class="flex items-center gap-1 text-xs font-semibold uppercase tracking-wider transition"
                                            :class="sortClass('transfer_number')"
                                            @click="handleSort('transfer_number')">
                                            Transfer No
                                            <span class="flex flex-col">
                                                <ChevronUp class="size-3 -mb-0.5"
                                                    :class="chevronClass('transfer_number', 'asc')" />
                                                <ChevronDown class="size-3 -mt-0.5"
                                                    :class="chevronClass('transfer_number', 'desc')" />
                                            </span>
                                        </button>
                                    </th>

                                    <!-- Date -->
                                    <th class="px-4 py-3 text-left">
                                        <button
                                            class="flex items-center gap-1 text-xs font-semibold uppercase tracking-wider transition"
                                            :class="sortClass('transferred_at')" @click="handleSort('transferred_at')">
                                            Date
                                            <span class="flex flex-col">
                                                <ChevronUp class="size-3 -mb-0.5"
                                                    :class="chevronClass('transferred_at', 'asc')" />
                                                <ChevronDown class="size-3 -mt-0.5"
                                                    :class="chevronClass('transferred_at', 'desc')" />
                                            </span>
                                        </button>
                                    </th>

                                    <!-- From Warehouse -->
                                    <th class="px-4 py-3 text-left">
                                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">From
                                            Warehouse</span>
                                    </th>

                                    <!-- To Warehouse -->
                                    <th class="px-4 py-3 text-left">
                                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-500">To
                                            Warehouse</span>
                                    </th>

                                    <!-- Volume -->
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

                                    <!-- Status -->
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

                                    <!-- Actions -->
                                    <th class="px-4 py-3 w-[50px]"></th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="transfer in transfers.data" :key="transfer.id"
                                    class="transition hover:bg-gray-50/60">

                                    <!-- Transfer No -->
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="font-mono text-xs font-semibold text-gray-700">
                                            {{ transfer.transfer_number }}
                                        </span>
                                    </td>

                                    <!-- Date -->
                                    <td class="px-4 py-3 whitespace-nowrap text-gray-500 text-[13px]">
                                        {{ transfer.transferred_at }}
                                    </td>

                                    <!-- From Warehouse -->
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <p class="text-[13px] font-medium text-gray-900">{{ transfer.from_warehouse_name
                                            }}</p>
                                    </td>

                                    <!-- To Warehouse -->
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <p class="text-[13px] font-medium text-gray-900">{{ transfer.to_warehouse_name
                                            }}</p>
                                    </td>

                                    <!-- Volume -->
                                    <td class="px-4 py-3 whitespace-nowrap text-gray-700 text-[13px] font-medium">
                                        {{ formatVolume(transfer.volume) }}
                                    </td>

                                    <!-- Status -->
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span
                                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                            :class="statusBadge[transfer.status] ?? 'bg-gray-100 text-gray-600'">
                                            {{ transfer.status }}
                                        </span>
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-4 py-3 whitespace-nowrap">
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
                                                        <Truck class="size-3.5" />
                                                        Mark as In Transit
                                                    </DropdownMenuItem>
                                                </template>
                                                <template v-if="transfer.status === 'In Transit'">
                                                    <DropdownMenuItem
                                                        class="gap-2 text-sm text-emerald-600 focus:text-emerald-600"
                                                        @click="updateStatus(transfer, 'Completed')">
                                                        <CheckCircle2 class="size-3.5" />
                                                        Mark as Completed
                                                    </DropdownMenuItem>
                                                </template>
                                                <template
                                                    v-if="transfer.status !== 'Completed' && transfer.status !== 'Cancelled'">
                                                    <DropdownMenuItem
                                                        class="gap-2 text-sm text-rose-600 focus:text-rose-600"
                                                        @click="updateStatus(transfer, 'Cancelled')">
                                                        <XCircle class="size-3.5" />
                                                        Cancel Transfer
                                                    </DropdownMenuItem>
                                                    <DropdownMenuSeparator />
                                                    <DropdownMenuItem
                                                        class="gap-2 text-sm text-red-600 focus:text-red-600"
                                                        @click="deleteTransfer(transfer)">
                                                        Delete
                                                    </DropdownMenuItem>
                                                </template>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </td>
                                </tr>

                                <!-- Empty State -->
                                <tr v-if="transfers.data.length === 0">
                                    <td colspan="7" class="px-5 py-12 text-center">
                                        <div class="flex flex-col items-center gap-2">
                                            <ArrowRightLeft class="size-10 text-gray-200" />
                                            <p class="text-sm font-medium text-gray-500">No transfer records found</p>
                                            <p class="text-xs text-gray-400">Try adjusting your search or filters</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Transfer Pagination -->
                <TablePagination :paginator="transfers" @navigate="navigateTransfer" />

            </div>
        </div>

        <!-- ── Transfer Form Modal ────────────────────────── -->
        <StockTransferFormMoebdal v-model:open="isFormOpen" :all-warehouses="allWarehouses" post-url="/stock-transfer"
            @success="toast.success('Transfer recorded successfully!')" />

    </AppLayout>
</template>