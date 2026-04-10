<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Search, Plus, Filter, SlidersHorizontal } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { toast } from 'vue-sonner';

import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import TablePagination from '@/components/TablePagination.vue';
import TableFilter from '@/components/TableFilter.vue';
import type { FilterValues } from '@/components/TableFilter.vue';

import WarehouseFormModal from '@/components/Warehouse/WarehouseFormModal.vue';
import type { Warehouse, City } from '@/components/Warehouse/WarehouseFormModal.vue';
import WarehouseStatusModal from '@/components/Warehouse/WarehouseStatusModal.vue';
import WarehouseTransferModal from '@/components/Warehouse/WarehouseTransferModal.vue';

// ── Types ──────────────────────────────────────────────────────

interface PaginatedWarehouses {
    data: Warehouse[];
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
    aktif: number;
    nonaktif: number;
}

// ── Props ──────────────────────────────────────────────────────

const props = defineProps<{
    warehouses: PaginatedWarehouses;
    stats: Stats;
    cities: City[];
    allCities: City[];
    filters: {
        search?: string;
        perPage?: number;
        status?: string;
        tipe?: string;
        city_id?: string;
    };
}>();

// ── Breadcrumbs ────────────────────────────────────────────────

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Master Data', href: '#' },
    { title: 'Warehouse', href: '/master-data/warehouse' },
];

// ── State ──────────────────────────────────────────────────────

const searchQuery = ref(props.filters.search ?? '');
const perPage = ref(props.filters.perPage ?? 12);
const filterValues = ref<FilterValues>({
    status: props.filters.status ?? undefined,
    tipe: props.filters.tipe ?? undefined,
    city_id: props.filters.city_id ?? undefined,
});

const filterFields = [
    {
        key: 'status', label: 'Status', type: 'select' as const,
        options: [
            { label: 'Aktif', value: 'active' },
            { label: 'Non Aktif', value: 'inactive' },
        ],
    },
    {
        key: 'tipe', label: 'Tipe', type: 'select' as const,
        options: [
            { label: 'Utama', value: 'Utama' },
            { label: 'Cabang', value: 'Cabang' },
            { label: 'Transit', value: 'Transit' },
            { label: 'Sementara', value: 'Sementara' },
        ],
    },
    {
        key: 'city_id', label: 'Kota', type: 'select' as const,
        options: props.cities.map(c => ({ label: c.name, value: String(c.id) })),
    },
];

const buildParams = (extra: FilterValues = {}) => ({
    search: searchQuery.value || undefined,
    perPage: perPage.value,
    ...extra,
});

const navigate = (url: string | null) => {
    if (!url) return;
    router.get(url, buildParams(filterValues.value), { preserveState: true });
};

const handleFilter = (values: FilterValues) => {
    filterValues.value = values;
    router.get('/master-data/warehouse', buildParams(values), { preserveState: true, replace: true });
};

const handleFilterReset = () => {
    filterValues.value = {};
    router.get('/master-data/warehouse', buildParams({}), { preserveState: true, replace: true });
};

let searchTimeout: ReturnType<typeof setTimeout>;
watch(searchQuery, (val) => {
    clearTimeout(searchTimeout);
    if (val.length === 0 || val.length >= 3) {
        searchTimeout = setTimeout(() => {
            router.get('/master-data/warehouse', buildParams(filterValues.value), { preserveState: true, replace: true });
        }, 400);
    }
});

// ── Modals ─────────────────────────────────────────────────────

const isFormOpen = ref(false);
const editingWarehouse = ref<Warehouse | null>(null);
const isStatusOpen = ref(false);
const statusWarehouse = ref<Warehouse | null>(null);
const isTransferOpen = ref(false);
const transferWarehouse = ref<Warehouse | null>(null);

function openCreate() { editingWarehouse.value = null; isFormOpen.value = true; }
function openEdit(g: Warehouse) { editingWarehouse.value = g; isFormOpen.value = true; }
function openStatus(g: Warehouse) { statusWarehouse.value = g; isStatusOpen.value = true; }
function openTransfer(g: Warehouse) { transferWarehouse.value = g; isTransferOpen.value = true; }

// ── Helpers ────────────────────────────────────────────────────

function utilisasiColor(persen: number) {
    if (persen >= 90) return 'bg-red-500';
    if (persen >= 70) return 'bg-amber-400';
    return 'bg-[#007C95]';
}

function utilisasiTextColor(persen: number) {
    if (persen >= 90) return 'text-red-600';
    if (persen >= 70) return 'text-amber-600';
    return 'text-[#007C95]';
}

const tipeColor: Record<string, string> = {
    Utama: 'bg-blue-50 text-blue-700',
    Cabang: 'bg-purple-50 text-purple-700',
    Transit: 'bg-orange-50 text-orange-700',
    Sementara: 'bg-gray-100 text-gray-600',
};

function formatKg(val: number | null | undefined) {
    if (val == null) return '0';
    return new Intl.NumberFormat('id-ID').format(val);
}

// All warehouses for transfer (simplified)
const allWarehousesForTransfer = props.warehouses.data.map(g => ({
    id: g.id,
    label: `${g.nama} (${g.kode})`,
}));
</script>

<template>

    <Head title="Warehouse" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 pb-10 md:p-6">

            <!-- Header -->
            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div>
                    <h1 class="text-[24px] font-bold text-gray-900">Gudang</h1>
                    <p class="mt-0.5 text-[16px] text-gray-500">Kelola lokasi penyimpanan minyak jelantah</p>
                </div>
                <button
                    class="flex w-fit items-center gap-1.5 rounded-lg bg-[#007C95] px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-[#006b80] transition"
                    @click="openCreate">
                    <Plus class="size-4" />
                    Tambah Gudang
                </button>
            </div>

            <!-- Toolbar -->
            <div class="flex flex-col gap-3 sm:flex-row s">
                <div class="relative">
                    <Search class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-gray-400" />
                    <input v-model="searchQuery" type="text" placeholder="Cari warehouse..."
                        class="h-[45px] w-full rounded-lg border border-gray-300 bg-white py-2 pr-3 pl-9 text-sm placeholder-gray-400 focus:border-[#007C95] focus:ring-1 focus:ring-[#007C95] focus:outline-none sm:w-56" />
                </div>

                <div class="flex items-center gap-2">
                    <TableFilter :filters="filterFields" :model-value="filterValues" @update:model-value="handleFilter"
                        @reset="handleFilterReset" />
                </div>
            </div>

            <!-- Card Grid -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <div v-for="warehouse in warehouses.data" :key="warehouse.id"
                    class="group relative flex flex-col rounded-xl border border-gray-200 bg-white shadow-sm transition hover:shadow-md hover:border-[#007C95]/30 cursor-pointer"
                    @click="router.get(`/master-data/warehouse/${warehouse.id}`)">

                    <!-- Card Header -->
                    <div class="flex items-start justify-between px-4 pt-4 pb-2">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-0.5">
                                <h3 class="text-[14px] font-semibold text-gray-900 truncate">{{ warehouse.nama }}</h3>
                            </div>
                            <p class="text-[12px] text-gray-400 truncate">{{ warehouse.alamat ?? warehouse.city_name ?? '—' }}
                            </p>
                        </div>
                        <span
                            class="ml-2 shrink-0 inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium"
                            :class="warehouse.is_active ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-500'">
                            {{ warehouse.is_active ? 'Aktif' : 'Non Aktif' }}
                        </span>
                    </div>

                    <!-- Tipe badge (optional) -->
                    <div v-if="warehouse.tipe" class="px-4 pb-2">
                        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-semibold"
                            :class="tipeColor[warehouse.tipe] ?? 'bg-gray-100 text-gray-600'">
                            {{ warehouse.tipe }}
                        </span>
                    </div>

                    <!-- Progress Bar -->
                    <div class="px-4 pb-1">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[13px] font-bold text-gray-800">
                                {{ formatKg(warehouse.stok_saat_ini) }} kg
                                <span :class="utilisasiTextColor(warehouse.utilisasi ?? 0)"
                                    class="text-[11px] font-semibold ml-0.5">
                                    ({{ warehouse.utilisasi ?? 0 }}%)
                                </span>
                            </span>
                            <span class="text-[12px] text-gray-400">{{ formatKg(warehouse.kapasitas_maks)
                                }}&thinsp;kg</span>
                        </div>
                        <div class="h-1.5 w-full rounded-full bg-gray-100 overflow-hidden">
                            <div class="h-1.5 rounded-full transition-all"
                                :class="utilisasiColor(warehouse.utilisasi ?? 0)"
                                :style="{ width: (warehouse.utilisasi ?? 0) + '%' }" />
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="mt-auto flex items-center gap-2 px-4 py-3 border-t border-gray-100" @click.stop>
                        <button
                            class="flex-1 flex items-center justify-center gap-1.5 rounded-lg bg-[#007C95] px-3 py-1.5 text-[12px] font-medium text-white hover:bg-[#006b80] transition"
                            @click.stop="openTransfer(warehouse)">
                            Transfer
                        </button>
                        <button
                            class="flex-1 flex items-center justify-center gap-1.5 rounded-lg border border-gray-200 px-3 py-1.5 text-[12px] font-medium text-gray-600 hover:bg-gray-50 transition"
                            @click.stop="openEdit(warehouse)">
                            Edit
                        </button>
                    </div>

                </div>

                <!-- Empty State -->
                <div v-if="warehouses.data.length === 0" class="col-span-full py-16 text-center">
                    <p class="text-sm font-medium text-gray-500">Tidak ada data warehouse</p>
                    <p class="text-xs text-gray-400 mt-1">Coba ubah kata kunci pencarian atau filter</p>
                </div>
            </div>

            <!-- Pagination -->
            <TablePagination :paginator="warehouses" type="centerPaginate" @navigate="navigate" />

        </div>

        <!-- Modals -->
        <WarehouseFormModal v-model:open="isFormOpen" :editing-warehouse="editingWarehouse" :cities="allCities"
            post-url="/master-data/warehouse" @success="toast.success('Berhasil!', {
                description: editingWarehouse ? 'Data warehouse berhasil diperbarui' : 'Warehouse baru berhasil ditambahkan'
            })" />

        <WarehouseStatusModal v-model:open="isStatusOpen" :warehouse="statusWarehouse" toggle-url="/master-data/warehouse" @success="toast.success('Berhasil!', {
            description: statusWarehouse?.is_active ? 'Warehouse dinonaktifkan' : 'Warehouse diaktifkan'
        })" />

        <WarehouseTransferModal v-model:open="isTransferOpen" :source-warehouse="transferWarehouse"
            :all-warehouses="allWarehousesForTransfer" transfer-url="/master-data/warehouse/transfer"
            @success="toast.success('Transfer berhasil dicatat!')" />

    </AppLayout>
</template>