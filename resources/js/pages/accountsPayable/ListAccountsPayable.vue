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
                            <CreditCard class="size-4" />
                        </span>
                    </div>
                    <p class="text-[24px] font-bold tracking-tight text-[#101010]">{{ formatRp(stats.total_hutang) }}
                    </p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ stats.total_tagihan }} tagihan belum lunas</p>
                </div>

                <div class="bg-white rounded-xl border border-[#EDEDED] shadow-sm p-4 flex flex-col">
                    <div class="flex items-center justify-between">
                        <span class="text-[14px] text-[#101010] font-semibold">Jatuh Tempo &lt; 7 Hari</span>
                        <span class="text-amber-400">
                            <AlertTriangle class="size-4" />
                        </span>
                    </div>
                    <p class="text-[24px] font-bold tracking-tight text-[#101010]">{{ formatRp(stats.jatuh_tempo_7hari)
                        }}</p>
                    <p class="text-xs text-amber-500 mt-0.5">{{ stats.jatuh_tempo_count }} tagihan segera</p>
                </div>

                <div class="bg-white rounded-xl border border-[#EDEDED] shadow-sm p-4 flex flex-col">
                    <div class="flex items-center justify-between">
                        <span class="text-[14px] text-[#101010] font-semibold">Lunas Bulan Ini</span>
                        <span class="text-emerald-400">
                            <CheckCircle2 class="size-4" />
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