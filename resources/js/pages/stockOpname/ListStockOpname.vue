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
    ClipboardCheck,
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

// ── Types ──────────────────────────────────────────────────────

type OpnameStatus = 'completed' | 'in_transit' | 'draft' | 'cancelled';

interface Gudang {
    id: number;
    name: string;
}

interface StokOpname {
    id: string;
    date: string;
    gudang_id: number;
    gudang_name: string;
    stok_sistem: number;
    stok_fisik: number;
    selisih: number;
    status: OpnameStatus;
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
    opnames?: Paginated<StokOpname>;
    gudangs?: Gudang[];
    allGudangs?: Gudang[];
    filters: {
        search?: string;
        perPage?: number;
        status?: string;
        gudang_id?: string;
        sort?: string;
        direction?: 'asc' | 'desc';
    };
}>();

// ── Breadcrumbs ────────────────────────────────────────────────

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Stok / Opname', href: '/stok-opname' },
];

// ── Static dummy (sementara) ───────────────────────────────────

const dummyOpnames: StokOpname[] = Array.from({ length: 57 }, (_, i) => {
    const stokSistem = [8000, 8000, 7500, 8000, 8000, 8000, 8000, 8000, 8000, 8000][i % 10];
    const stokFisik = [8000, 9000, 8000, 8000, 8000, 8000, 8000, 8000, 8000, 8000][i % 10];
    const selisih = stokFisik - stokSistem;
    const statuses: OpnameStatus[] = ['completed', 'in_transit', 'in_transit', 'in_transit', 'completed', 'completed', 'completed', 'completed', 'completed', 'completed'];
    return {
        id: String(i + 1),
        date: '03 Apr 2026',
        gudang_id: 1,
        gudang_name: 'Gudang Surabaya',
        stok_sistem: stokSistem,
        stok_fisik: stokFisik,
        selisih,
        status: statuses[i % 10],
    };
});

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
            { label: 'Completed', value: 'completed' },
            { label: 'In Transit', value: 'in_transit' },
            { label: 'Draft', value: 'draft' },
            { label: 'Cancelled', value: 'cancelled' },
        ],
    },
    {
        key: 'gudang_id',
        label: 'Gudang',
        type: 'select' as const,
        options: (props.gudangs ?? [{ id: 1, name: 'Gudang Surabaya' }]).map(g => ({
            label: g.name,
            value: String(g.id),
        })),
    },
]);

const filterValues = ref<FilterValues>({
    status: props.filters.status ?? undefined,
    gudang_id: props.filters.gudang_id ?? undefined,
});

// ── Client-side filtering (static dummy) ──────────────────────

const filteredData = computed(() => {
    let data = props.opnames?.data?.length ? props.opnames.data : dummyOpnames;
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        data = data.filter(o =>
            o.gudang_name.toLowerCase().includes(q) ||
            o.date.toLowerCase().includes(q)
        );
    }
    if (filterValues.value.status)
        data = data.filter(o => o.status === filterValues.value.status);
    if (filterValues.value.gudang_id)
        data = data.filter(o => String(o.gudang_id) === filterValues.value.gudang_id);
    return data;
});

const lastPage = computed(() => Math.max(1, Math.ceil(filteredData.value.length / perPage.value)));
const currentPage = ref(1);

const pagedData = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return filteredData.value.slice(start, start + perPage.value);
});

const paginator = computed(() => {
    const total = filteredData.value.length;
    const current = currentPage.value;
    const last = lastPage.value;
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

function handleNavigate(url: string) {
    const m = url.match(/p=(\d+)/);
    if (m) currentPage.value = parseInt(m[1]);
}

// ── Sort ───────────────────────────────────────────────────────

const handleSort = (column: string) => {
    if (sortColumn.value === column) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortColumn.value = column;
        sortDirection.value = 'asc';
    }
    // Kalau sudah pakai backend:
    // router.get('/stok-opname', buildParams(filterValues.value), { preserveState: true, replace: true });
};

// ── Helpers ────────────────────────────────────────────────────

function formatKg(val: number | null | undefined) {
    if (val == null) return '—';
    return new Intl.NumberFormat('id-ID').format(val) + ' kg';
}

function formatSelisih(val: number) {
    if (val === 0) return '0 kg';
    const formatted = new Intl.NumberFormat('id-ID').format(Math.abs(val)) + ' kg';
    return val > 0 ? `+ ${formatted}` : `-${formatted}`;
}

function selisihClass(val: number) {
    if (val > 0) return 'text-emerald-600';
    if (val < 0) return 'text-red-500';
    return 'text-gray-500';
}

const statusMap: Record<OpnameStatus, { label: string; bg: string; text: string }> = {
    completed: { label: 'Completed', bg: 'bg-emerald-50', text: 'text-emerald-700' },
    in_transit: { label: 'In Transit', bg: 'bg-amber-50', text: 'text-amber-700' },
    draft: { label: 'Draft', bg: 'bg-gray-100', text: 'text-gray-600' },
    cancelled: { label: 'Cancelled', bg: 'bg-rose-50', text: 'text-rose-600' },
};

const sortClass = (col: string) =>
    sortColumn.value === col ? 'text-[#007C95]' : 'text-gray-500 hover:text-gray-700';

const chevronClass = (col: string, dir: 'asc' | 'desc') =>
    sortColumn.value === col && sortDirection.value === dir ? 'text-[#007C95]' : 'text-gray-300';

// ── Delete ─────────────────────────────────────────────────────

const isDeleteOpen = ref(false);
const deletingOpname = ref<StokOpname | null>(null);
const isDeleting = ref(false);

function openDelete(opname: StokOpname) {
    deletingOpname.value = opname;
    isDeleteOpen.value = true;
}

function confirmDelete() {
    if (!deletingOpname.value) return;
    isDeleting.value = true;
    router.delete(`/stok-opname/${deletingOpname.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Berhasil!', { description: 'Data opname berhasil dihapus' });
            isDeleteOpen.value = false;
        },
        onError: () => toast.error('Gagal!', { description: 'Gagal menghapus data opname' }),
        onFinish: () => { isDeleting.value = false; },
    });
}
</script>

<template>

    <Head title="Stok / Opname" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 pb-10 md:p-6">

            <!-- ── Header ─────────────────────────────────────── -->
            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div class="flex-1 min-w-0">
                    <h1 class="text-[24px] font-bold text-gray-900">Stok / Opname</h1>
                    <p class="mt-0.5 text-[16px] text-gray-500">Monitor & rekonsiliasi stok fisik dengan sistem</p>
                </div>

                <div class="flex w-full justify-end md:w-auto md:flex-shrink-0">
                    <Button
                        class="flex w-fit items-center justify-center gap-1.5 rounded-lg bg-[#007C95] px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-[#006b80]"
                        @click="router.get('/stok-opname/create')">
                        <ClipboardCheck class="size-4" />
                        Mulai Opname
                    </Button>
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
                    <TableFilter :filters="filterFields" :model-value="filterValues"
                        @update:model-value="v => { filterValues = v; }" @reset="filterValues = {}" />
                    <div class="relative flex-1 sm:flex-none">
                        <Search class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-gray-400" />
                        <input v-model="searchQuery" type="text" placeholder="Cari..."
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

                                    <!-- Tanggal -->
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

                                    <!-- Gudang -->
                                    <th class="px-4 py-3 text-left">
                                        <button
                                            class="flex items-center gap-1 text-xs font-semibold uppercase tracking-wider transition"
                                            :class="sortClass('gudang_name')" @click="handleSort('gudang_name')">
                                            Gudang
                                            <span class="flex flex-col">
                                                <ChevronUp class="size-3 -mb-0.5"
                                                    :class="chevronClass('gudang_name', 'asc')" />
                                                <ChevronDown class="size-3 -mt-0.5"
                                                    :class="chevronClass('gudang_name', 'desc')" />
                                            </span>
                                        </button>
                                    </th>

                                    <!-- Stok Sistem -->
                                    <th class="px-4 py-3 text-left">
                                        <button
                                            class="flex items-center gap-1 text-xs font-semibold uppercase tracking-wider transition"
                                            :class="sortClass('stok_sistem')" @click="handleSort('stok_sistem')">
                                            Stok Sistem
                                            <span class="flex flex-col">
                                                <ChevronUp class="size-3 -mb-0.5"
                                                    :class="chevronClass('stok_sistem', 'asc')" />
                                                <ChevronDown class="size-3 -mt-0.5"
                                                    :class="chevronClass('stok_sistem', 'desc')" />
                                            </span>
                                        </button>
                                    </th>

                                    <!-- Stok Fisik -->
                                    <th class="px-4 py-3 text-left">
                                        <button
                                            class="flex items-center gap-1 text-xs font-semibold uppercase tracking-wider transition"
                                            :class="sortClass('stok_fisik')" @click="handleSort('stok_fisik')">
                                            Stok Fisik
                                            <span class="flex flex-col">
                                                <ChevronUp class="size-3 -mb-0.5"
                                                    :class="chevronClass('stok_fisik', 'asc')" />
                                                <ChevronDown class="size-3 -mt-0.5"
                                                    :class="chevronClass('stok_fisik', 'desc')" />
                                            </span>
                                        </button>
                                    </th>

                                    <!-- Selisih -->
                                    <th class="px-4 py-3 text-left">
                                        <button
                                            class="flex items-center gap-1 text-xs font-semibold uppercase tracking-wider transition"
                                            :class="sortClass('selisih')" @click="handleSort('selisih')">
                                            Selisih
                                            <span class="flex flex-col">
                                                <ChevronUp class="size-3 -mb-0.5"
                                                    :class="chevronClass('selisih', 'asc')" />
                                                <ChevronDown class="size-3 -mt-0.5"
                                                    :class="chevronClass('selisih', 'desc')" />
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

                                    <!-- Aksi -->
                                    <th class="px-4 py-3 text-left w-[50px]">
                                        <span
                                            class="text-xs font-semibold uppercase tracking-wider text-gray-500">Aksi</span>
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="opname in pagedData" :key="opname.id" class="transition hover:bg-gray-50/60">

                                    <!-- Tanggal -->
                                    <td class="px-4 py-3 whitespace-nowrap text-[13px] text-gray-500">
                                        {{ opname.date }}
                                    </td>

                                    <!-- Gudang -->
                                    <td class="px-4 py-3 whitespace-nowrap text-[13px] text-gray-900">
                                        {{ opname.gudang_name }}
                                    </td>

                                    <!-- Stok Sistem -->
                                    <td class="px-4 py-3 whitespace-nowrap text-[13px] text-gray-700">
                                        {{ formatKg(opname.stok_sistem) }}
                                    </td>

                                    <!-- Stok Fisik -->
                                    <td class="px-4 py-3 whitespace-nowrap text-[13px] text-gray-700">
                                        {{ formatKg(opname.stok_fisik) }}
                                    </td>

                                    <!-- Selisih -->
                                    <td class="px-4 py-3 whitespace-nowrap text-[13px] font-medium"
                                        :class="selisihClass(opname.selisih)">
                                        {{ formatSelisih(opname.selisih) }}
                                    </td>

                                    <!-- Status -->
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span
                                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                            :class="[
                                                statusMap[opname.status]?.bg ?? 'bg-gray-100',
                                                statusMap[opname.status]?.text ?? 'text-gray-600',
                                            ]">
                                            {{ statusMap[opname.status]?.label ?? opname.status }}
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
                                                    @click="router.get(`/stok-opname/${opname.id}`)">
                                                    <Eye class="size-3.5" />
                                                    Lihat Detail
                                                </DropdownMenuItem>
                                                <DropdownMenuItem
                                                    v-if="opname.status === 'draft' || opname.status === 'in_transit'"
                                                    class="gap-2 text-sm"
                                                    @click="router.get(`/stok-opname/${opname.id}/edit`)">
                                                    <Pencil class="size-3.5" />
                                                    Edit
                                                </DropdownMenuItem>
                                                <DropdownMenuSeparator v-if="opname.status === 'draft'" />
                                                <DropdownMenuItem v-if="opname.status === 'draft'"
                                                    class="gap-2 text-sm text-red-600 focus:text-red-600"
                                                    @click="openDelete(opname)">
                                                    <Trash2 class="size-3.5" />
                                                    Hapus
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </td>
                                </tr>

                                <!-- Empty State -->
                                <tr v-if="pagedData.length === 0">
                                    <td colspan="7" class="px-5 py-10 text-center">
                                        <div class="flex flex-col items-center gap-2">
                                            <Vue3Lottie :animationData="emptyAnimation" :height="160" :width="160"
                                                :loop="true" />
                                            <p class="text-sm font-medium text-gray-600">Tidak ada data opname</p>
                                            <p class="text-xs text-gray-400">Coba ubah kata kunci pencarian atau filter
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <TablePagination :paginator="paginator" @navigate="handleNavigate" />
            </div>

        </div>

        <!-- ── Delete Dialog ──────────────────────────────────── -->
        <AlertDialog v-model:open="isDeleteOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Hapus Data Opname</AlertDialogTitle>
                    <AlertDialogDescription>
                        Apakah Anda yakin ingin menghapus data opname ini?
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