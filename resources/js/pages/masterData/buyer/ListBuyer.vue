<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    Search,
    Pencil,
    Trash2,
    ChevronUp,
    ChevronDown,
    Plus,
    EllipsisVertical,
    Eye,
    Upload,
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

import BuyerFormModal from '@/components/Buyer/BuyerFormModal.vue';
import type { Buyer, City } from '@/components/Buyer/BuyerFormModal.vue';
import BuyerStatusModal from '@/components/Buyer/BuyerStatusModal.vue';

// ── Types ──────────────────────────────────────────────────────

interface PaginatedBuyers {
    data: Buyer[];
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
    buyers: PaginatedBuyers;
    stats: Stats;
    cities: City[];
    allCities: City[];
    filters: {
        search?: string;
        perPage?: number;
        status?: string;
        tipe?: string;
        city_id?: string;
        sort?: string;
        direction?: 'asc' | 'desc';
    };
}>();

// ── Breadcrumbs ────────────────────────────────────────────────

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Master Data', href: '#' },
    { title: 'Buyer (Customer)', href: '/master-data/buyer' },
];

// ── Table State ────────────────────────────────────────────────

const searchQuery = ref(props.filters.search ?? '');
const perPage = ref(props.filters.perPage ?? 10);
const sortColumn = ref(props.filters.sort ?? 'created_at');
const sortDirection = ref<'asc' | 'desc'>(props.filters.direction ?? 'desc');

const filterFields = computed(() => [
    {
        key: 'status',
        label: 'Status',
        type: 'select' as const,
        options: [
            { label: 'Aktif', value: 'active' },
            { label: 'Non Aktif', value: 'inactive' },
        ],
    },
    {
        key: 'tipe',
        label: 'Tipe',
        type: 'select' as const,
        options: [
            { label: 'PT', value: 'PT' },
            { label: 'CV', value: 'CV' },
            { label: 'UD', value: 'UD' },
            { label: 'Perorangan', value: 'Perorangan' },
        ],
    },
    {
        key: 'city_id',
        label: 'Kota',
        type: 'select' as const,
        options: props.cities.map((c) => ({ label: c.name, value: String(c.id) })),
    },
]);

const filterValues = ref<FilterValues>({
    status: props.filters.status ?? undefined,
    tipe: props.filters.tipe ?? undefined,
    city_id: props.filters.city_id ?? undefined,
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
    router.get('/master-data/buyer', buildParams(filterValues.value), { preserveState: true, replace: true });
};

const handleFilter = (values: FilterValues) => {
    filterValues.value = values;
    router.get('/master-data/buyer', buildParams(values), { preserveState: true, replace: true });
};

const handleFilterReset = () => {
    filterValues.value = {};
    router.get('/master-data/buyer', buildParams({}), { preserveState: true, replace: true });
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
            router.get('/master-data/buyer', buildParams(filterValues.value), { preserveState: true, replace: true });
        }, 400);
    }
});

watch(perPage, () => {
    router.get('/master-data/buyer', buildParams(filterValues.value), { preserveState: true, replace: true });
});

// ── Form Modal ─────────────────────────────────────────────────

const isFormOpen = ref(false);
const editingBuyer = ref<Buyer | null>(null);

function openCreate() {
    editingBuyer.value = null;
    isFormOpen.value = true;
}

function openEdit(buyer: Buyer) {
    editingBuyer.value = buyer;
    isFormOpen.value = true;
}

// ── Status Modal ───────────────────────────────────────────────

const isStatusOpen = ref(false);
const statusBuyer = ref<Buyer | null>(null);

function openStatus(buyer: Buyer) {
    statusBuyer.value = buyer;
    isStatusOpen.value = true;
}

// ── Delete ─────────────────────────────────────────────────────

const isDeleteOpen = ref(false);
const deletingBuyer = ref<Buyer | null>(null);
const isDeleting = ref(false);

function openDelete(buyer: Buyer) {
    deletingBuyer.value = buyer;
    isDeleteOpen.value = true;
}

function confirmDelete() {
    if (!deletingBuyer.value) return;
    isDeleting.value = true;
    router.delete(`/master-data/buyer/${deletingBuyer.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Berhasil!', { description: 'Buyer berhasil dihapus' });
            isDeleteOpen.value = false;
        },
        onError: () => toast.error('Gagal!', { description: 'Gagal menghapus buyer' }),
        onFinish: () => { isDeleting.value = false; },
    });
}

// ── Helpers ────────────────────────────────────────────────────

const getInitials = (nama: string) =>
    nama.split(' ').slice(0, 2).map((w) => w[0]?.toUpperCase() ?? '').join('');

const formatCurrency = (val: number | null | undefined) => {
    if (val == null) return '—';
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(val);
};

const sortClass = (col: string) =>
    sortColumn.value === col ? 'text-[#007C95]' : 'text-gray-500 hover:text-gray-700';

const chevronClass = (col: string, dir: 'asc' | 'desc') =>
    sortColumn.value === col && sortDirection.value === dir ? 'text-[#007C95]' : 'text-gray-300';

const tipeColor: Record<string, string> = {
    PT: 'bg-blue-50 text-blue-700',
    CV: 'bg-purple-50 text-purple-700',
    UD: 'bg-orange-50 text-orange-700',
    Perorangan: 'bg-gray-100 text-gray-700',
};
</script>

<template>

    <Head title="Buyer" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 pb-10 md:p-6">

            <!-- Header -->
            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div class="flex-1 min-w-0">
                    <h1 class="text-[24px] font-bold text-gray-900">Buyer / Pembeli</h1>
                    <p class="mt-0.5 text-[16px] text-gray-500">Kelola data pembeli minyak jelantah</p>
                </div>
                <div class="flex w-full justify-end md:w-auto md:flex-shrink-0">
                    <div class="flex flex-col gap-2 md:flex-row">
                        <Button
                            class="flex w-fit items-center justify-center gap-1.5 rounded-lg bg-[#007C95] px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-[#006b80]"
                            @click="openCreate">
                            <Plus class="size-4" />
                            Tambah Buyer
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Toolbar -->
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
                        <input v-model="searchQuery" type="text" placeholder="Cari buyer..."
                            class="h-[45px] w-full rounded-lg border border-gray-300 bg-white py-2 pr-3 pl-9 text-sm placeholder-gray-400 focus:border-[#007C95] focus:ring-1 focus:ring-[#007C95] focus:outline-none sm:w-56" />
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div>
                <div class="overflow-hidden rounded-xl border-gray-200 bg-white">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 bg-[#F9F9F9]">
                                    <th class="px-4 py-3 text-left">
                                        <button
                                            class="flex items-center gap-1 text-xs font-semibold uppercase tracking-wider transition"
                                            :class="sortClass('kode')" @click="handleSort('kode')">
                                            Kode
                                            <span class="flex flex-col">
                                                <ChevronUp class="size-3 -mb-0.5"
                                                    :class="chevronClass('kode', 'asc')" />
                                                <ChevronDown class="size-3 -mt-0.5"
                                                    :class="chevronClass('kode', 'desc')" />
                                            </span>
                                        </button>
                                    </th>
                                    <th class="px-4 py-3 text-left">
                                        <button
                                            class="flex items-center gap-1 text-xs font-semibold uppercase tracking-wider transition"
                                            :class="sortClass('nama')" @click="handleSort('nama')">
                                            Nama Buyer
                                            <span class="flex flex-col">
                                                <ChevronUp class="size-3 -mb-0.5"
                                                    :class="chevronClass('nama', 'asc')" />
                                                <ChevronDown class="size-3 -mt-0.5"
                                                    :class="chevronClass('nama', 'desc')" />
                                            </span>
                                        </button>
                                    </th>
                                    <th class="px-4 py-3 text-left">
                                        <span
                                            class="text-xs font-semibold uppercase tracking-wider text-gray-500">Tipe</span>
                                    </th>
                                    <th class="px-4 py-3 text-left">
                                        <span
                                            class="text-xs font-semibold uppercase tracking-wider text-gray-500">Kontak</span>
                                    </th>
                                    <th class="px-4 py-3 text-left">
                                        <span
                                            class="text-xs font-semibold uppercase tracking-wider text-gray-500">Lokasi</span>
                                    </th>
                                    <th class="px-4 py-3 text-left">
                                        <button
                                            class="flex items-center gap-1 text-xs font-semibold uppercase tracking-wider transition"
                                            :class="sortClass('total_penjualan')"
                                            @click="handleSort('total_penjualan')">
                                            Total Penjualan
                                            <span class="flex flex-col">
                                                <ChevronUp class="size-3 -mb-0.5"
                                                    :class="chevronClass('total_penjualan', 'asc')" />
                                                <ChevronDown class="size-3 -mt-0.5"
                                                    :class="chevronClass('total_penjualan', 'desc')" />
                                            </span>
                                        </button>
                                    </th>
                                    <th class="px-4 py-3 text-left">
                                        <button
                                            class="flex items-center gap-1 text-xs font-semibold uppercase tracking-wider transition"
                                            :class="sortClass('total_piutang')" @click="handleSort('total_piutang')">
                                            Piutang
                                            <span class="flex flex-col">
                                                <ChevronUp class="size-3 -mb-0.5"
                                                    :class="chevronClass('total_piutang', 'asc')" />
                                                <ChevronDown class="size-3 -mt-0.5"
                                                    :class="chevronClass('total_piutang', 'desc')" />
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
                                <tr v-for="buyer in buyers.data" :key="buyer.id" class="transition hover:bg-gray-50/60">

                                    <!-- Kode -->
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="font-mono text-xs text-gray-500">{{ buyer.kode }}</span>
                                    </td>

                                    <!-- Nama -->
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <img v-if="buyer.foto_url" :src="buyer.foto_url" :alt="buyer.nama"
                                                class="size-8 shrink-0 rounded-full border border-gray-200 object-cover" />
                                            <div v-else
                                                class="flex size-8 shrink-0 items-center justify-center rounded-full bg-[#007C95]/10">
                                                <span class="text-xs font-semibold text-[#007C95]">
                                                    {{ getInitials(buyer.nama) }}
                                                </span>
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-900">{{ buyer.nama }}</p>
                                                <p v-if="buyer.email" class="text-xs text-gray-400">{{ buyer.email }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Tipe -->
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span v-if="buyer.tipe"
                                            class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold"
                                            :class="tipeColor[buyer.tipe] ?? 'bg-gray-100 text-gray-700'">
                                            {{ buyer.tipe }}
                                        </span>
                                        <span v-else class="text-gray-400">—</span>
                                    </td>

                                    <!-- Kontak -->
                                    <td class="px-4 py-3 whitespace-nowrap text-gray-500">
                                        {{ buyer.telepon ?? '—' }}
                                    </td>

                                    <!-- Lokasi -->
                                    <td class="px-4 py-3 whitespace-nowrap text-gray-500">
                                        {{ buyer.city_name ?? '—' }}
                                    </td>

                                    <!-- Total Penjualan -->
                                    <td class="px-4 py-3 whitespace-nowrap text-gray-700">
                                        {{ formatCurrency(buyer.total_penjualan) }}
                                    </td>

                                    <!-- Piutang -->
                                    <td class="px-4 py-3 whitespace-nowrap text-gray-700">
                                        {{ formatCurrency(buyer.total_piutang) }}
                                    </td>

                                    <!-- Status -->
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span
                                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                            :class="buyer.is_active
                                                ? 'bg-emerald-50 text-emerald-700'
                                                : 'bg-rose-50 text-rose-600'">
                                            {{ buyer.is_active ? 'Aktif' : 'Non Aktif' }}
                                        </span>
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
                                                <DropdownMenuItem class="gap-2 text-sm"
                                                    @click="router.get(`/master-data/buyer/${buyer.id}`)">
                                                    <Eye class="size-3.5" />
                                                    Lihat Detail
                                                </DropdownMenuItem>
                                                <DropdownMenuItem class="gap-2 text-sm" @click="openEdit(buyer)">
                                                    <Pencil class="size-3.5" />
                                                    Ubah Data
                                                </DropdownMenuItem>
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem class="gap-2 text-sm" :class="buyer.is_active
                                                    ? 'text-amber-600 focus:text-amber-600'
                                                    : 'text-emerald-600 focus:text-emerald-600'"
                                                    @click="openStatus(buyer)">
                                                    <component :is="buyer.is_active ? XCircle : CheckCircle2"
                                                        class="size-3.5" />
                                                    {{ buyer.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                </DropdownMenuItem>
                                                <!-- <DropdownMenuItem class="gap-2 text-sm text-red-600 focus:text-red-600"
                                                    @click="openDelete(buyer)">
                                                    <Trash2 class="size-3.5" />
                                                    Hapus
                                                </DropdownMenuItem> -->
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </td>
                                </tr>

                                <!-- Empty State -->
                                <tr v-if="buyers.data.length === 0">
                                    <td colspan="9" class="px-5 py-10 text-center">
                                        <div class="flex flex-col items-center gap-2">
                                            <Vue3Lottie :animationData="emptyAnimation" :height="160" :width="160"
                                                :loop="true" />
                                            <p class="text-sm font-medium text-gray-600">Tidak ada data Buyer</p>
                                            <p class="text-xs text-gray-400">Coba ubah kata kunci pencarian atau filter
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <TablePagination :paginator="buyers" @navigate="goToPage" />
            </div>

        </div>

        <!-- Modals -->
        <BuyerFormModal v-model:open="isFormOpen" :editing-buyer="editingBuyer" :cities="allCities"
            post-url="/master-data/buyer" @success="toast.success('Berhasil!', {
                description: editingBuyer
                    ? 'Data buyer berhasil diperbarui'
                    : 'Buyer baru berhasil ditambahkan'
            })" />

        <BuyerStatusModal v-model:open="isStatusOpen" :buyer="statusBuyer" toggle-url="/master-data/buyer" @success="toast.success('Berhasil!', {
            description: statusBuyer?.is_active
                ? 'Buyer berhasil dinonaktifkan'
                : 'Buyer berhasil diaktifkan'
        })" />

        <AlertDialog v-model:open="isDeleteOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Hapus Buyer</AlertDialogTitle>
                    <AlertDialogDescription>
                        Apakah Anda yakin ingin menghapus buyer
                        <strong>{{ deletingBuyer?.nama }}</strong>?
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