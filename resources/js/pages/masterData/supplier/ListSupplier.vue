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
    Users,
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
import { usePermission } from '@/composables/usePermission';
import { PermissionEnum } from '@/enums/PermissionEnum';
import type { BreadcrumbItem } from '@/types';

import SupplierFormModal from '@/components/Supplier/SupplierFormModal.vue';
import type { Supplier, City } from '@/components/Supplier/SupplierFormModal.vue';
import SupplierStatusModal from '@/components/Supplier/SupplierStatusModal.vue';

const { can } = usePermission();

// ── Types ──────────────────────────────────────────────────────

interface PaginatedSuppliers {
    data: Supplier[];
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
    active: number;
    inactive: number;
}

// ── Props ──────────────────────────────────────────────────────

const props = defineProps<{
    suppliers: PaginatedSuppliers;
    stats: Stats;
    cities: City[];      // kota yang dipakai supplier (untuk filter)
    allCities: City[];   // semua kota (untuk form)
    filters: {
        search?: string;
        perPage?: number;
        status?: string;
        payment_term?: string;
        city_id?: string;
        sort?: string;
        direction?: 'asc' | 'desc';
    };
}>();

// ── Breadcrumbs ────────────────────────────────────────────────

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Master Data', href: '#' },
    { title: 'Supplier', href: '/master-data/supplier' },
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
        key: 'payment_term',
        label: 'Termin',
        type: 'select' as const,
        options: [
            { label: 'Cash', value: 'cash' },
            { label: 'Tempo', value: 'tempo' },
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
    payment_term: props.filters.payment_term ?? undefined,
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
    router.get('/master-data/supplier', buildParams(filterValues.value), { preserveState: true, replace: true });
};

const handleFilter = (values: FilterValues) => {
    filterValues.value = values;
    router.get('/master-data/supplier', buildParams(values), { preserveState: true, replace: true });
};

const handleFilterReset = () => {
    filterValues.value = {};
    router.get('/master-data/supplier', buildParams({}), { preserveState: true, replace: true });
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
            router.get('/master-data/supplier', buildParams(filterValues.value), { preserveState: true, replace: true });
        }, 400);
    }
});

watch(perPage, () => {
    router.get('/master-data/supplier', buildParams(filterValues.value), { preserveState: true, replace: true });
});

const statCards = computed(() => [
    // {
    //     label: 'Total UCO Masuk',
    //     value: props.totalUcoMasuk.value,
    //     trend: props.totalUcoMasuk.trend,
    //     up: props.totalUcoMasuk.up,
    //     iconType: 'box',
    // },

]);

// ── Form Modal ─────────────────────────────────────────────────

const isFormOpen = ref(false);
const editingSupplier = ref<Supplier | null>(null);

function openCreate() {
    editingSupplier.value = null;
    isFormOpen.value = true;
}

function openEdit(supplier: Supplier) {
    editingSupplier.value = supplier;
    isFormOpen.value = true;
}

// ── Status Modal ───────────────────────────────────────────────

const isStatusOpen = ref(false);
const statusSupplier = ref<Supplier | null>(null);

function openStatus(supplier: Supplier) {
    statusSupplier.value = supplier;
    isStatusOpen.value = true;
}

// ── Delete ─────────────────────────────────────────────────────

const isDeleteOpen = ref(false);
const deletingSupplier = ref<Supplier | null>(null);
const isDeleting = ref(false);

function openDelete(supplier: Supplier) {
    deletingSupplier.value = supplier;
    isDeleteOpen.value = true;
}

function confirmDelete() {
    if (!deletingSupplier.value) return;
    isDeleting.value = true;
    router.delete(`/master-data/supplier/${deletingSupplier.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Berhasil!', { description: 'Supplier berhasil dihapus' });
            isDeleteOpen.value = false;
        },
        onError: () => toast.error('Gagal!', { description: 'Gagal menghapus supplier' }),
        onFinish: () => { isDeleting.value = false; },
    });
}

// ── Helpers ────────────────────────────────────────────────────

const getInitials = (nama: string) =>
    nama.split(' ').slice(0, 2).map((w) => w[0]?.toUpperCase() ?? '').join('');

const formatKapasitas = (val: number | null | undefined) => {
    if (val == null) return '—';
    return new Intl.NumberFormat('id-ID').format(val) + ' kg';
};

// Column sort helper
const sortClass = (col: string) =>
    sortColumn.value === col ? 'text-[#007C95]' : 'text-gray-500 hover:text-gray-700';

const chevronClass = (col: string, dir: 'asc' | 'desc') =>
    sortColumn.value === col && sortDirection.value === dir ? 'text-[#007C95]' : 'text-gray-300';
</script>

<template>

    <Head title="Supplier" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 pb-10 md:p-6">

            <!-- ── Header ─────────────────────────────────────── -->
            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div class="flex-1 min-w-0">
                    <h1 class="text-[24px] font-bold text-gray-900">Supplier</h1>
                    <p class="mt-0.5 text-[16px] text-gray-500">Kelola data supplier minyak jelantah</p>
                </div>

                <div class="flex w-full justify-end md:w-auto md:flex-shrink-0">
                    <div class="flex flex-col gap-2 md:flex-row">
                        <Button variant="outline"
                            class="flex w-fit items-center justify-center gap-1.5 rounded-lg px-4 py-2.5 text-sm font-medium">
                            <Upload class="size-4" />
                            Import Excel
                        </Button>
                        <Button
                            class="flex w-fit items-center justify-center gap-1.5 rounded-lg bg-[#007C95] px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-[#006b80]"
                            @click="openCreate">
                            <Plus class="size-4" />
                            Tambah Supplier
                        </Button>
                    </div>
                </div>
            </div>


            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="bg-white rounded-xl border border-[#EDEDED] shadow-sm p-4 flex flex-col">
                    <div class="flex items-center justify-between">
                        <span class="text-[14px] text-[#101010] font-semibold">Total Supplier</span>
                        <span class="text-gray-300">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.2"
                                    d="M15 7.5V11.5C15 11.6326 14.9473 11.7598 14.8536 11.8536C14.7598 11.9473 14.6326 12 14.5 12H13C13 11.76 12.9425 11.5235 12.8322 11.3104C12.7219 11.0973 12.562 10.9137 12.3661 10.7751C12.1702 10.6366 11.9438 10.547 11.7061 10.5141C11.4684 10.4811 11.2263 10.5056 11 10.5856C10.7075 10.689 10.4543 10.8806 10.2752 11.1339C10.0961 11.3872 9.99997 11.6898 10 12H6C6 11.6022 5.84196 11.2206 5.56066 10.9393C5.27936 10.658 4.89782 10.5 4.5 10.5C4.10218 10.5 3.72064 10.658 3.43934 10.9393C3.15804 11.2206 3 11.6022 3 12H1.5C1.36739 12 1.24021 11.9473 1.14645 11.8536C1.05268 11.7598 1 11.6326 1 11.5V9H11V7.5H15Z"
                                    fill="#50CD89" />
                                <path
                                    d="M15.4637 7.3125L14.5887 5.125C14.5145 4.93992 14.3864 4.78139 14.2211 4.66996C14.0557 4.55852 13.8607 4.49931 13.6613 4.5H11.5V4C11.5 3.86739 11.4473 3.74021 11.3536 3.64645C11.2598 3.55268 11.1326 3.5 11 3.5H1.5C1.23478 3.5 0.98043 3.60536 0.792893 3.79289C0.605357 3.98043 0.5 4.23478 0.5 4.5V11.5C0.5 11.7652 0.605357 12.0196 0.792893 12.2071C0.98043 12.3946 1.23478 12.5 1.5 12.5H2.5625C2.67265 12.9302 2.92285 13.3115 3.27366 13.5838C3.62446 13.8561 4.05591 14.0039 4.5 14.0039C4.94409 14.0039 5.37554 13.8561 5.72635 13.5838C6.07715 13.3115 6.32735 12.9302 6.4375 12.5H9.5625C9.67265 12.9302 9.92285 13.3115 10.2737 13.5838C10.6245 13.8561 11.0559 14.0039 11.5 14.0039C11.9441 14.0039 12.3755 13.8561 12.7263 13.5838C13.0771 13.3115 13.3273 12.9302 13.4375 12.5H14.5C14.7652 12.5 15.0196 12.3946 15.2071 12.2071C15.3946 12.0196 15.5 11.7652 15.5 11.5V7.5C15.5002 7.43574 15.4879 7.37206 15.4637 7.3125ZM11.5 5.5H13.6613L14.2612 7H11.5V5.5ZM1.5 4.5H10.5V8.5H1.5V4.5ZM4.5 13C4.30222 13 4.10888 12.9414 3.94443 12.8315C3.77998 12.7216 3.65181 12.5654 3.57612 12.3827C3.50043 12.2 3.48063 11.9989 3.51921 11.8049C3.5578 11.6109 3.65304 11.4327 3.79289 11.2929C3.93275 11.153 4.11093 11.0578 4.30491 11.0192C4.49889 10.9806 4.69996 11.0004 4.88268 11.0761C5.06541 11.1518 5.22159 11.28 5.33147 11.4444C5.44135 11.6089 5.5 11.8022 5.5 12C5.5 12.2652 5.39464 12.5196 5.20711 12.7071C5.01957 12.8946 4.76522 13 4.5 13ZM9.5625 11.5H6.4375C6.32735 11.0698 6.07715 10.6885 5.72635 10.4162C5.37554 10.1439 4.94409 9.99608 4.5 9.99608C4.05591 9.99608 3.62446 10.1439 3.27366 10.4162C2.92285 10.6885 2.67265 11.0698 2.5625 11.5H1.5V9.5H10.5V10.2694C10.2701 10.4023 10.0688 10.5795 9.9079 10.7907C9.74697 11.002 9.62957 11.243 9.5625 11.5ZM11.5 13C11.3022 13 11.1089 12.9414 10.9444 12.8315C10.78 12.7216 10.6518 12.5654 10.5761 12.3827C10.5004 12.2 10.4806 11.9989 10.5192 11.8049C10.5578 11.6109 10.653 11.4327 10.7929 11.2929C10.9327 11.153 11.1109 11.0578 11.3049 11.0192C11.4989 10.9806 11.7 11.0004 11.8827 11.0761C12.0654 11.1518 12.2216 11.28 12.3315 11.4444C12.4414 11.6089 12.5 11.8022 12.5 12C12.5 12.2652 12.3946 12.5196 12.2071 12.7071C12.0196 12.8946 11.7652 13 11.5 13ZM14.5 11.5H13.4375C13.326 11.0708 13.0754 10.6908 12.7247 10.4193C12.3741 10.1479 11.9434 10.0004 11.5 10V8H14.5V11.5Z"
                                    fill="#101010" />
                            </svg>
                        </span>
                    </div>
                    <p class="text-[24px] font-bold tracking-tight text-[#101010]">{{ stats.total }}</p>
                </div>

                <div class="bg-white rounded-xl border border-[#EDEDED] shadow-sm p-4 flex flex-col">
                    <div class="flex items-center justify-between">
                        <span class="text-[14px] text-[#101010] font-semibold">Aktif</span>
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path opacity="0.2"
                                d="M14 8C14 9.18669 13.6481 10.3467 12.9888 11.3334C12.3295 12.3201 11.3925 13.0892 10.2961 13.5433C9.19975 13.9974 7.99335 14.1162 6.82946 13.8847C5.66558 13.6532 4.59648 13.0818 3.75736 12.2426C2.91825 11.4035 2.3468 10.3344 2.11529 9.17054C1.88378 8.00666 2.0026 6.80026 2.45673 5.7039C2.91085 4.60754 3.67989 3.67047 4.66658 3.01118C5.65328 2.35189 6.81331 2 8 2C9.5913 2 11.1174 2.63214 12.2426 3.75736C13.3679 4.88258 14 6.4087 14 8Z"
                                fill="#50CD89" />
                            <path
                                d="M10.8538 6.14625C10.9002 6.19269 10.9371 6.24783 10.9623 6.30853C10.9874 6.36923 11.0004 6.43429 11.0004 6.5C11.0004 6.56571 10.9874 6.63077 10.9623 6.69147C10.9371 6.75217 10.9002 6.80731 10.8538 6.85375L7.35375 10.3538C7.30732 10.4002 7.25217 10.4371 7.19147 10.4623C7.13077 10.4874 7.06571 10.5004 7 10.5004C6.9343 10.5004 6.86923 10.4874 6.80853 10.4623C6.74783 10.4371 6.69269 10.4002 6.64625 10.3538L5.14625 8.85375C5.05243 8.75993 4.99972 8.63268 4.99972 8.5C4.99972 8.36732 5.05243 8.24007 5.14625 8.14625C5.24007 8.05243 5.36732 7.99972 5.5 7.99972C5.63268 7.99972 5.75993 8.05243 5.85375 8.14625L7 9.29313L10.1463 6.14625C10.1927 6.09976 10.2478 6.06288 10.3085 6.03772C10.3692 6.01256 10.4343 5.99961 10.5 5.99961C10.5657 5.99961 10.6308 6.01256 10.6915 6.03772C10.7522 6.06288 10.8073 6.09976 10.8538 6.14625ZM14.5 8C14.5 9.28558 14.1188 10.5423 13.4046 11.6112C12.6903 12.6801 11.6752 13.5132 10.4874 14.0052C9.29973 14.4972 7.99279 14.6259 6.73192 14.3751C5.47104 14.1243 4.31285 13.5052 3.40381 12.5962C2.49477 11.6872 1.8757 10.529 1.6249 9.26809C1.37409 8.00721 1.50282 6.70028 1.99479 5.51256C2.48676 4.32484 3.31988 3.30968 4.3888 2.59545C5.45772 1.88122 6.71442 1.5 8 1.5C9.72335 1.50182 11.3756 2.18722 12.5942 3.40582C13.8128 4.62441 14.4982 6.27665 14.5 8ZM13.5 8C13.5 6.9122 13.1774 5.84883 12.5731 4.94436C11.9687 4.03989 11.1098 3.33494 10.1048 2.91866C9.09977 2.50238 7.9939 2.39346 6.92701 2.60568C5.86011 2.8179 4.8801 3.34172 4.11092 4.11091C3.34173 4.8801 2.8179 5.86011 2.60568 6.927C2.39347 7.9939 2.50238 9.09977 2.91867 10.1048C3.33495 11.1098 4.0399 11.9687 4.94437 12.5731C5.84884 13.1774 6.91221 13.5 8 13.5C9.45819 13.4983 10.8562 12.9184 11.8873 11.8873C12.9184 10.8562 13.4983 9.45818 13.5 8Z"
                                fill="#101010" />
                        </svg>

                    </div>
                    <p class="text-[24px] font-bold tracking-tight text-[#101010]">{{ stats.active }}</p>
                </div>

                <div class="bg-white rounded-xl border border-[#EDEDED] shadow-sm p-4 flex flex-col">
                    <div class="flex items-center justify-between">
                        <span class="text-[14px] text-[#101010] font-semibold">Non-Aktif</span>
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path opacity="0.2"
                                d="M14 8C14 9.18669 13.6481 10.3467 12.9888 11.3334C12.3295 12.3201 11.3925 13.0892 10.2961 13.5433C9.19975 13.9974 7.99335 14.1162 6.82946 13.8847C5.66558 13.6532 4.59648 13.0818 3.75736 12.2426C2.91825 11.4035 2.3468 10.3344 2.11529 9.17054C1.88378 8.00666 2.0026 6.80026 2.45673 5.7039C2.91085 4.60754 3.67989 3.67047 4.66658 3.01118C5.65328 2.35189 6.81331 2 8 2C9.5913 2 11.1174 2.63214 12.2426 3.75736C13.3679 4.88258 14 6.4087 14 8Z"
                                fill="#50CD89" />
                            <path
                                d="M8 1.5C6.71442 1.5 5.45772 1.88122 4.3888 2.59545C3.31988 3.30968 2.48676 4.32484 1.99479 5.51256C1.50282 6.70028 1.37409 8.00721 1.6249 9.26809C1.8757 10.529 2.49477 11.6872 3.40381 12.5962C4.31285 13.5052 5.47104 14.1243 6.73192 14.3751C7.99279 14.6259 9.29973 14.4972 10.4874 14.0052C11.6752 13.5132 12.6903 12.6801 13.4046 11.6112C14.1188 10.5423 14.5 9.28558 14.5 8C14.4982 6.27665 13.8128 4.62441 12.5942 3.40582C11.3756 2.18722 9.72335 1.50182 8 1.5ZM8 13.5C6.91221 13.5 5.84884 13.1774 4.94437 12.5731C4.0399 11.9687 3.33495 11.1098 2.91867 10.1048C2.50238 9.09977 2.39347 7.9939 2.60568 6.927C2.8179 5.86011 3.34173 4.8801 4.11092 4.11091C4.8801 3.34172 5.86011 2.8179 6.92701 2.60568C7.9939 2.39346 9.09977 2.50238 10.1048 2.91866C11.1098 3.33494 11.9687 4.03989 12.5731 4.94436C13.1774 5.84883 13.5 6.9122 13.5 8C13.4983 9.45818 12.9184 10.8562 11.8873 11.8873C10.8562 12.9184 9.45819 13.4983 8 13.5ZM7 6V10C7 10.1326 6.94732 10.2598 6.85356 10.3536C6.75979 10.4473 6.63261 10.5 6.5 10.5C6.36739 10.5 6.24022 10.4473 6.14645 10.3536C6.05268 10.2598 6 10.1326 6 10V6C6 5.86739 6.05268 5.74021 6.14645 5.64645C6.24022 5.55268 6.36739 5.5 6.5 5.5C6.63261 5.5 6.75979 5.55268 6.85356 5.64645C6.94732 5.74021 7 5.86739 7 6ZM10 6V10C10 10.1326 9.94732 10.2598 9.85356 10.3536C9.75979 10.4473 9.63261 10.5 9.5 10.5C9.36739 10.5 9.24022 10.4473 9.14645 10.3536C9.05268 10.2598 9 10.1326 9 10V6C9 5.86739 9.05268 5.74021 9.14645 5.64645C9.24022 5.55268 9.36739 5.5 9.5 5.5C9.63261 5.5 9.75979 5.55268 9.85356 5.64645C9.94732 5.74021 10 5.86739 10 6Z"
                                fill="#101010" />
                        </svg>

                    </div>
                    <p class="text-[24px] font-bold tracking-tight text-[#101010]">{{ stats.inactive }}</p>
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
                        <input v-model="searchQuery" type="text" placeholder="Cari supplier..."
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

                                    <!-- Kode -->
                                    <th class="px-4 py-3 text-left">
                                        <button
                                            class="flex items-center gap-1 text-xs font-semibold uppercase tracking-wider transition"
                                            :class="sortClass('code')" @click="handleSort('code')">
                                            Kode
                                            <span class="flex flex-col">
                                                <ChevronUp class="size-3 -mb-0.5"
                                                    :class="chevronClass('code', 'asc')" />
                                                <ChevronDown class="size-3 -mt-0.5"
                                                    :class="chevronClass('code', 'desc')" />
                                            </span>
                                        </button>
                                    </th>

                                    <!-- Nama -->
                                    <th class="px-4 py-3 text-left">
                                        <button
                                            class="flex items-center gap-1 text-xs font-semibold uppercase tracking-wider transition"
                                            :class="sortClass('name')" @click="handleSort('name')">
                                            Nama Supplier
                                            <span class="flex flex-col">
                                                <ChevronUp class="size-3 -mb-0.5"
                                                    :class="chevronClass('name', 'asc')" />
                                                <ChevronDown class="size-3 -mt-0.5"
                                                    :class="chevronClass('name', 'desc')" />
                                            </span>
                                        </button>
                                    </th>

                                    <!-- Kontak -->
                                    <th class="px-4 py-3 text-left">
                                        <span
                                            class="text-xs font-semibold uppercase tracking-wider text-gray-500">Kontak</span>
                                    </th>

                                    <!-- Lokasi -->
                                    <th class="px-4 py-3 text-left">
                                        <span
                                            class="text-xs font-semibold uppercase tracking-wider text-gray-500">Lokasi</span>
                                    </th>

                                    <!-- Kapasitas -->
                                    <th class="px-4 py-3 text-left">
                                        <button
                                            class="flex items-center gap-1 text-xs font-semibold uppercase tracking-wider transition"
                                            :class="sortClass('monthly_capacity')"
                                            @click="handleSort('monthly_capacity')">
                                            Kapasitas
                                            <span class="flex flex-col">
                                                <ChevronUp class="size-3 -mb-0.5"
                                                    :class="chevronClass('monthly_capacity', 'asc')" />
                                                <ChevronDown class="size-3 -mt-0.5"
                                                    :class="chevronClass('monthly_capacity', 'desc')" />
                                            </span>
                                        </button>
                                    </th>

                                    <!-- Termin -->
                                    <th class="px-4 py-3 text-left">
                                        <span
                                            class="text-xs font-semibold uppercase tracking-wider text-gray-500">Termin</span>
                                    </th>

                                    <!-- Status -->
                                    <th class="px-4 py-3 text-left">
                                        <span
                                            class="text-xs font-semibold uppercase tracking-wider text-gray-500">Status</span>
                                    </th>

                                    <!-- Aksi -->
                                    <th class="px-4 py-3 text-left w-[50px]">
                                        <span
                                            class="text-xs font-semibold uppercase tracking-wider text-gray-500">Aksi</span>
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="supplier in suppliers.data" :key="supplier.id"
                                    class="transition hover:bg-gray-50/60">

                                    <!-- Kode -->
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="font-mono text-xs text-gray-500">{{ supplier.code }}</span>
                                    </td>

                                    <!-- Nama -->
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <img v-if="supplier.photo_url" :src="supplier.photo_url" :alt="supplier.name"
                                                class="size-8 shrink-0 rounded-full border border-gray-200 object-cover" />
                                            <div v-else
                                                class="flex size-8 shrink-0 items-center justify-center rounded-full bg-[#007C95]/10">
                                                <span class="text-xs font-semibold text-[#007C95]">
                                                    {{ getInitials(supplier.name) }}
                                                </span>
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-900">{{ supplier.name }}</p>
                                                <p v-if="supplier.email" class="text-xs text-gray-400">{{ supplier.email
                                                }}</p>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Kontak -->
                                    <td class="px-4 py-3 whitespace-nowrap text-gray-500">
                                        {{ supplier.phone ?? '—' }}
                                    </td>

                                    <!-- Lokasi -->
                                    <td class="px-4 py-3 whitespace-nowrap text-gray-500">
                                        {{ supplier.city_name ?? '—' }}
                                    </td>

                                    <!-- Kapasitas -->
                                    <td class="px-4 py-3 whitespace-nowrap text-gray-700">
                                        {{ formatKapasitas(supplier.monthly_capacity) }}
                                    </td>

                                    <!-- Termin -->
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span
                                            class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-medium"
                                            :class="supplier.payment_term === 'cash'
                                                ? 'bg-[#101010] text-white'
                                                : 'bg-[#EDEDED] text-[#101010]'">
                                            {{ supplier.payment_term_label ?? (supplier.payment_term === 'cash' ? 'Cash' : `Tempo
                                            (${supplier.payment_term_days} hari)`) }}
                                        </span>
                                    </td>

                                    <!-- Status -->
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span
                                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                            :class="supplier.is_active
                                                ? 'bg-emerald-50 text-emerald-700'
                                                : 'bg-rose-50 text-rose-600'">
                                            {{ supplier.is_active ? 'Aktif' : 'Non Aktif' }}
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
                                                    @click="router.get(`/master-data/supplier/${supplier.id}`)">
                                                    <Eye class="size-3.5" />
                                                    Lihat Detail
                                                </DropdownMenuItem>
                                                <DropdownMenuItem class="gap-2 text-sm" @click="openEdit(supplier)">
                                                    <Pencil class="size-3.5" />
                                                    Ubah Data
                                                </DropdownMenuItem>
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem class="gap-2 text-sm" :class="supplier.is_active
                                                    ? 'text-amber-600 focus:text-amber-600'
                                                    : 'text-emerald-600 focus:text-emerald-600'"
                                                    @click="openStatus(supplier)">
                                                    <component :is="supplier.is_active ? XCircle : CheckCircle2"
                                                        class="size-3.5" />
                                                    {{ supplier.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                </DropdownMenuItem>
                                                <!-- <DropdownMenuItem class="gap-2 text-sm text-red-600 focus:text-red-600"
                                                    @click="openDelete(supplier)">
                                                    <Trash2 class="size-3.5" />
                                                    Hapus
                                                </DropdownMenuItem> -->
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </td>
                                </tr>

                                <!-- Empty State -->
                                <tr v-if="suppliers.data.length === 0">
                                    <td colspan="8" class="px-5 py-10 text-center">
                                        <div class="flex flex-col items-center gap-2">
                                            <Vue3Lottie :animationData="emptyAnimation" :height="160" :width="160"
                                                :loop="true" />
                                            <p class="text-sm font-medium text-gray-600">Tidak ada data Supplier</p>
                                            <p class="text-xs text-gray-400">Coba ubah kata kunci pencarian atau filter
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <TablePagination :paginator="suppliers" @navigate="goToPage" />
            </div>

        </div>

        <!-- ── Modals ─────────────────────────────────────────── -->

        <SupplierFormModal v-model:open="isFormOpen" :editing-supplier="editingSupplier" :cities="allCities"
            post-url="/master-data/supplier" @success="toast.success('Berhasil!', {
                description: editingSupplier
                    ? 'Data supplier berhasil diperbarui'
                    : 'Supplier baru berhasil ditambahkan'
            })" />

        <SupplierStatusModal v-model:open="isStatusOpen" :supplier="statusSupplier" toggle-url="/master-data/supplier"
            @success="toast.success('Berhasil!', {
                description: statusSupplier?.is_active
                    ? 'Supplier berhasil dinonaktifkan'
                    : 'Supplier berhasil diaktifkan'
            })" />

        <AlertDialog v-model:open="isDeleteOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Hapus Supplier</AlertDialogTitle>
                    <AlertDialogDescription>
                        Apakah Anda yakin ingin menghapus supplier
                        <strong>{{ deletingSupplier?.nama }}</strong>?
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