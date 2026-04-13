<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted } from 'vue';
import { Search } from 'lucide-vue-next';
import { Line } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler,
} from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';

import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import TablePagination from '@/components/TablePagination.vue';
import TableFilter from '@/components/TableFilter.vue';
import type { FilterValues } from '@/components/TableFilter.vue';

import WarehouseFormModal from '@/components/Warehouse/WarehouseFormModal.vue';
import type { Warehouse, City } from '@/components/Warehouse/WarehouseFormModal.vue';
import WarehouseStatusModal from '@/components/Warehouse/WarehouseStatusModal.vue';
import WarehouseTransferModal from '@/components/Warehouse/WarehouseTransferModal.vue';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler, ChartDataLabels);

// ── Types ──────────────────────────────────────────────────────

interface ActivityRow {
    id: string;
    document_number: string;
    date: string;
    party: string;
    party_email: string;
    total: number;
    status: string;
}

interface ActivityLog {
    id: string;
    message: string;
    user: string;
    time: string;
    type: 'success' | 'danger' | 'warning' | 'info';
}

interface PartyItem {
    initials: string;
    name: string;
    email: string;
}

// ── Props ──────────────────────────────────────────────────────

const props = defineProps<{
    warehouse: Warehouse;
    stats: {
        current_stock: string;
        stock_label: string;
        capacity_max: string;
        capacity_max_sub: string;
        available: string;
        available_sub: string;
        stock_value: string;
        stock_value_sub: string;
        occupancy: number;
        occupancy_label: string;
        min_stock: string;
    };
    analyticsChart: { label: string; stock: number; volume_in: number; value: number }[];
    activityHistory: { tab: string; data: ActivityRow[] }[];
    activeSuppliers: PartyItem[];
    activeBuyers: PartyItem[];
    activityLogs: ActivityLog[];
    allWarehouses: { id: string | number; label: string }[];
    toggleUrl: string;
    editUrl: string;
    openEditModal?: boolean;
    allCities?: City[];
}>();

// ── Breadcrumbs ────────────────────────────────────────────────

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Master Data', href: '#' },
    { title: 'Warehouse', href: '/master-data/warehouse' },
    { title: props.warehouse.name, href: '#' },
];

// ── Modals ─────────────────────────────────────────────────────

const isEditOpen = ref(false);
const isStatusOpen = ref(false);
const isTransferOpen = ref(false);

onMounted(() => {
    if (props.openEditModal) isEditOpen.value = true;
});

// ── Stat cards ─────────────────────────────────────────────────

const statCards = computed(() => [
    { label: 'Stok Saat Ini', value: props.stats.current_stock, sub: props.stats.stock_label, iconType: 'stok' },
    { label: 'Kapasitas Maks', value: props.stats.capacity_max, sub: ` ${props.stats.capacity_max_sub}`, iconType: 'kapasitas' },
    { label: 'Tersedia', value: props.stats.available, sub: props.stats.available_sub, iconType: 'tersedia' },
    { label: 'Total Nilai Stok', value: props.stats.stock_value, sub: props.stats.stock_value_sub, iconType: 'nilai' },
    { label: 'Utilisasi', value: `${props.stats.occupancy}%`, sub: props.stats.occupancy_label, iconType: 'utilisasi' },
]);

// ── Chart ──────────────────────────────────────────────────────

const activeChartTab = ref<'Stok' | 'Volume' | 'Nilai'>('Stok');

const chartData = computed(() => {
    const key = activeChartTab.value === 'Stok' ? 'stock'
        : activeChartTab.value === 'Volume' ? 'volume_in'
            : 'value';

    return {
        labels: props.analyticsChart.map(d => d.label),
        datasets: [{
            label: activeChartTab.value,
            data: props.analyticsChart.map(d => (d as any)[key]),
            borderColor: '#007C95',
            backgroundColor: 'rgba(0,124,149,0.12)',
            fill: true,
            tension: 0.4,
            pointRadius: 3,
            pointBackgroundColor: '#007C95',
            borderWidth: 2,
        }],
    };
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: { mode: 'index' as const, intersect: false },
        datalabels: { display: false },
    },
    scales: {
        x: { grid: { display: false }, ticks: { font: { size: 10 } } },
        y: {
            grid: { color: '#f3f4f6' },
            ticks: {
                font: { size: 10 },
                callback: (v: any) => (v >= 1000000 ? (v / 1000000).toFixed(0) + 'M' : v >= 1000 ? v / 1000 + 'k' : v),
            },
        },
    },
};

// ── Activity History Table ──────────────────────────────────────

const activeActivityTab = ref(props.activityHistory[0]?.tab ?? 'Barang Masuk');
const txSearch = ref('');
const txPerPage = ref(10);
const txCurrentPage = ref(1);
const txFilterValues = ref<FilterValues>({});

const currentTabData = computed(() =>
    props.activityHistory.find(t => t.tab === activeActivityTab.value)?.data ?? []
);

const filteredTx = computed(() => {
    let data = currentTabData.value;
    if (txSearch.value) {
        const q = txSearch.value.toLowerCase();
        data = data.filter(t =>
            t.document_number.toLowerCase().includes(q) ||
            t.party.toLowerCase().includes(q)
        );
    }
    if (txFilterValues.value.status) data = data.filter(t => t.status === txFilterValues.value.status);
    return data;
});

const txLastPage = computed(() => Math.max(1, Math.ceil(filteredTx.value.length / txPerPage.value)));
const pagedTx = computed(() => {
    const start = (txCurrentPage.value - 1) * txPerPage.value;
    return filteredTx.value.slice(start, start + txPerPage.value);
});
watch([txSearch, txPerPage, txFilterValues, activeActivityTab], () => { txCurrentPage.value = 1; }, { deep: true });

const txPaginator = computed(() => {
    const total = filteredTx.value.length;
    const current = txCurrentPage.value;
    const last = txLastPage.value;
    const from = total === 0 ? null : (current - 1) * txPerPage.value + 1;
    const to = total === 0 ? null : Math.min(current * txPerPage.value, total);
    const links = [
        { url: current > 1 ? `#p=${current - 1}` : null, label: '&laquo; Previous', active: false },
        ...Array.from({ length: last }, (_, i) => ({
            url: `#p=${i + 1}`, label: String(i + 1), active: i + 1 === current,
        })),
        { url: current < last ? `#p=${current + 1}` : null, label: 'Next &raquo;', active: false },
    ];
    return { current_page: current, last_page: last, from, to, total, links };
});

function handleTxNavigate(url: string) {
    const m = url.match(/p=(\d+)/);
    if (m) txCurrentPage.value = parseInt(m[1]);
}

const txFilterFields = computed(() => [
    {
        key: 'status', label: 'Status', type: 'select' as const,
        options: [
            { label: 'Lunas', value: 'Lunas' },
            { label: 'Hutang', value: 'Hutang' },
            { label: 'Pending', value: 'Pending' },
        ],
    },
]);

// ── Helpers ────────────────────────────────────────────────────

const occupancyColor = (p: number) => p >= 90 ? 'bg-red-500' : p >= 70 ? 'bg-amber-400' : 'bg-[#007C95]';
const occupancyText = (p: number) => p >= 90 ? 'text-red-600' : p >= 70 ? 'text-amber-600' : 'text-[#007C95]';

const logDotColor: Record<string, string> = {
    success: 'bg-emerald-500',
    danger: 'bg-red-500',
    warning: 'bg-amber-400',
    info: 'bg-sky-400',
};

const typeColor: Record<string, string> = {
    Utama: 'bg-blue-50 text-blue-700',
    Cabang: 'bg-purple-50 text-purple-700',
    Transit: 'bg-orange-50 text-orange-700',
    Sementara: 'bg-gray-100 text-gray-600',
};

function formatRp(val: number) {
    return 'Rp ' + val.toLocaleString('id-ID');
}
</script>

<template>

    <Head :title="warehouse.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-5 p-6 bg-gray-50 pb-12">

            <!-- ── Page Header ──────────────────────────────── -->
            <div class="flex items-center gap-3">
                <button
                    class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 hover:bg-gray-50"
                    @click="router.get('/master-data/warehouse')">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                        <path d="M10 12L6 8l4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </button>

                <div class="flex-1">
                    <h1 class="text-[22px] font-bold text-[#101010]">{{ warehouse.name }}</h1>
                    <div class="flex items-center gap-2 flex-wrap">
                        <p class="text-sm text-gray-400">{{ warehouse.code }}</p>
                        <span class="inline-flex items-center rounded px-2 py-0.5 text-xs font-medium"
                            :class="warehouse.is_active ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-500'">
                            {{ warehouse.is_active ? 'Aktif' : 'Non-Aktif' }}
                        </span>
                        <span v-if="warehouse.type"
                            class="inline-flex items-center rounded px-2 py-0.5 text-xs font-semibold"
                            :class="typeColor[warehouse.type] ?? 'bg-gray-100 text-gray-700'">
                            {{ warehouse.type }}
                        </span>
                        <span class="text-xs text-gray-400">{{ warehouse.city_name }}</span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-2 flex-wrap">
                    <button
                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-600 hover:bg-gray-50 transition"
                        @click="router.get(`/master-data/warehouse/${warehouse.id}/cetak`)">
                        <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                            <path d="M4 4V2h8v2M4 12H2V7h12v5h-2M4 9h8v5H4V9z" stroke="currentColor" stroke-width="1.3"
                                stroke-linejoin="round" />
                        </svg>
                        Cetak Laporan
                    </button>
                    <button
                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm transition"
                        :class="warehouse.is_active ? 'text-amber-600 hover:bg-amber-50' : 'text-emerald-600 hover:bg-emerald-50'"
                        @click="isStatusOpen = true">
                        <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                            <rect x="2" y="5" width="12" height="8" rx="1.5" stroke="currentColor" stroke-width="1.3" />
                            <path d="M5 5V3.5a3 3 0 0 1 6 0V5" stroke="currentColor" stroke-width="1.3" />
                        </svg>
                        {{ warehouse.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                    </button>
                    <button
                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-600 hover:bg-gray-50 transition"
                        @click="isEditOpen = true">
                        <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                            <path d="M11.5 2.5a1.414 1.414 0 0 1 2 2L5 13H3v-2L11.5 2.5z" stroke="currentColor"
                                stroke-width="1.3" stroke-linejoin="round" />
                        </svg>
                        Ubah Data
                    </button>
                    <button
                        class="inline-flex items-center gap-1.5 rounded-lg bg-[#007C95] px-3 py-2 text-sm text-white hover:bg-[#006b80] transition"
                        @click="isTransferOpen = true">
                        <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                            <path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.3"
                                stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        Buat Transfer
                    </button>
                </div>
            </div>

            <!-- ── Stat Cards ──────────────────────────────── -->
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-5">
                <div v-for="card in statCards" :key="card.label"
                    class="bg-white rounded-xl border border-[#EDEDED] shadow-sm p-4 flex flex-col">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[13px] text-[#878787] font-medium">{{ card.label }}</span>
                        <span class="text-gray-300">
                            <!-- icon stok -->
                            <template v-if="card.iconType === 'stok'">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.2" d="M14 5L8 8.5L2 5L8 1.5L14 5Z" fill="#50CD89" />
                                    <path
                                        d="M14.4319 10.75C14.4979 10.8646 14.5159 11.0007 14.4818 11.1285C14.4477 11.2562 14.3643 11.3653 14.25 11.4318L8.25 14.9318C8.17355 14.9764 8.08663 14.9999 7.99813 14.9999C7.90962 14.9999 7.8227 14.9764 7.74625 14.9318L1.74625 11.4318C1.63356 11.3641 1.55207 11.2547 1.51944 11.1274C1.4868 11 1.50564 10.8649 1.57188 10.7514C1.63811 10.6378 1.74641 10.5549 1.87333 10.5206C2.00025 10.4863 2.13557 10.5033 2.25 10.5681L8 13.9212L13.75 10.5681C13.8646 10.502 14.0007 10.4841 14.1285 10.5182C14.2563 10.5523 14.3654 10.6356 14.4319 10.75ZM13.75 7.56808L8 10.9212L2.25 7.56808C2.13615 7.51139 2.00498 7.50022 1.88319 7.53684C1.7614 7.57345 1.65814 7.65511 1.59442 7.76517C1.53071 7.87524 1.51133 8.00545 1.54023 8.1293C1.56914 8.25315 1.64415 8.36133 1.75 8.43183L7.75 11.9318C7.82645 11.9764 7.91337 11.9999 8.00187 11.9999C8.09038 11.9999 8.1773 11.9764 8.25375 11.9318L14.2537 8.43183C14.3114 8.39922 14.362 8.35549 14.4025 8.30318C14.4431 8.25087 14.4729 8.19102 14.4902 8.1271C14.5075 8.06319 14.5119 7.99647 14.5032 7.93084C14.4945 7.8652 14.4728 7.80195 14.4394 7.74475C14.4061 7.68756 14.3617 7.63755 14.3089 7.59765C14.256 7.55775 14.1958 7.52873 14.1317 7.5123C14.0675 7.49586 14.0007 7.49233 13.9352 7.5019C13.8697 7.51148 13.8068 7.53397 13.75 7.56808ZM1.5 4.99995C1.5002 4.91237 1.5234 4.82639 1.56727 4.7506C1.61115 4.6748 1.67416 4.61186 1.75 4.56808L7.75 1.06808C7.82645 1.02349 7.91337 1 8.00187 1C8.09038 1 8.1773 1.02349 8.25375 1.06808L14.2537 4.56808C14.3292 4.61211 14.3918 4.67516 14.4354 4.75093C14.4789 4.82671 14.5018 4.91257 14.5018 4.99995C14.5018 5.08733 14.4789 5.17319 14.4354 5.24897C14.3918 5.32474 14.3292 5.38779 14.2537 5.43183L8.25375 8.93183C8.1773 8.97641 8.09038 8.9999 8.00187 8.9999C7.91337 8.9999 7.82645 8.97641 7.75 8.93183L1.75 5.43183C1.67416 5.38804 1.61115 5.3251 1.56727 5.24931C1.5234 5.17351 1.5002 5.08753 1.5 4.99995ZM2.9925 4.99995L8 7.9212L13.0075 4.99995L8 2.0787L2.9925 4.99995Z"
                                        fill="#101010" />
                                </svg>
                            </template>
                            <template v-if="card.iconType === 'kapasitas'">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.2"
                                        d="M13 3V5.5L10.5 3H13ZM3 13H5.5L3 10.5V13ZM13 13V10.5L10.5 13H13ZM3 5.5L5.5 3H3V5.5Z"
                                        fill="#50CD89" />
                                    <path
                                        d="M13 2.5H10.5C10.4011 2.49992 10.3043 2.5292 10.222 2.58414C10.1397 2.63908 10.0756 2.71719 10.0377 2.80861C9.99981 2.90002 9.98991 3.00061 10.0092 3.09765C10.0286 3.1947 10.0762 3.28382 10.1462 3.35375L12.6462 5.85375C12.7162 5.92376 12.8053 5.97144 12.9023 5.99076C12.9994 6.01009 13.1 6.00019 13.1914 5.96231C13.2828 5.92444 13.3609 5.86029 13.4159 5.77799C13.4708 5.69569 13.5001 5.59895 13.5 5.5V3C13.5 2.86739 13.4473 2.74022 13.3536 2.64645C13.2598 2.55268 13.1326 2.5 13 2.5ZM12.5 4.29313L11.7069 3.5H12.5V4.29313ZM3.35375 10.1462C3.28382 10.0762 3.1947 10.0286 3.09765 10.0092C3.00061 9.98991 2.90002 9.99981 2.80861 10.0377C2.71719 10.0756 2.63908 10.1397 2.58414 10.222C2.5292 10.3043 2.49992 10.4011 2.5 10.5V13C2.5 13.1326 2.55268 13.2598 2.64645 13.3536C2.74022 13.4473 2.86739 13.5 3 13.5H5.5C5.59895 13.5001 5.69569 13.4708 5.77799 13.4159C5.86029 13.3609 5.92444 13.2828 5.96231 13.1914C6.00019 13.1 6.01009 12.9994 5.99076 12.9023C5.97144 12.8053 5.92376 12.7162 5.85375 12.6462L3.35375 10.1462ZM3.5 12.5V11.7069L4.29313 12.5H3.5ZM13.1912 10.0381C13.0999 10.0002 12.9994 9.99027 12.9023 10.0095C12.8053 10.0288 12.7162 10.0763 12.6462 10.1462L10.1462 12.6462C10.0762 12.7162 10.0286 12.8053 10.0092 12.9023C9.98991 12.9994 9.99981 13.1 10.0377 13.1914C10.0756 13.2828 10.1397 13.3609 10.222 13.4159C10.3043 13.4708 10.4011 13.5001 10.5 13.5H13C13.1326 13.5 13.2598 13.4473 13.3536 13.3536C13.4473 13.2598 13.5 13.1326 13.5 13V10.5C13.5 10.4011 13.4706 10.3044 13.4157 10.2222C13.3607 10.14 13.2826 10.076 13.1912 10.0381ZM12.5 12.5H11.7069L12.5 11.7069V12.5ZM5.5 2.5H3C2.86739 2.5 2.74022 2.55268 2.64645 2.64645C2.55268 2.74022 2.5 2.86739 2.5 3V5.5C2.49992 5.59895 2.5292 5.69569 2.58414 5.77799C2.63908 5.86029 2.71719 5.92444 2.80861 5.96231C2.90002 6.00019 3.00061 6.01009 3.09765 5.99076C3.1947 5.97144 3.28382 5.92376 3.35375 5.85375L5.85375 3.35375C5.92376 3.28382 5.97144 3.1947 5.99076 3.09765C6.01009 3.00061 6.00019 2.90002 5.96231 2.80861C5.92444 2.71719 5.86029 2.63908 5.77799 2.58414C5.69569 2.5292 5.59895 2.49992 5.5 2.5ZM3.5 4.29313V3.5H4.29313L3.5 4.29313Z"
                                        fill="#101010" />
                                </svg>
                            </template>
                            <template v-if="card.iconType === 'tersedia'">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.2" d="M15 6.5L8 10.5L1 6.5L8 2.5L15 6.5Z" fill="#50CD89" />
                                    <path
                                        d="M0.750094 6.93759L7.75009 10.9376C7.82566 10.9808 7.91118 11.0035 7.99822 11.0035C8.08525 11.0035 8.17078 10.9808 8.24634 10.9376L15.2463 6.93759C15.323 6.89389 15.3867 6.83069 15.431 6.7544C15.4754 6.67811 15.4987 6.59145 15.4987 6.50321C15.4987 6.41498 15.4754 6.32832 15.431 6.25203C15.3867 6.17574 15.323 6.11254 15.2463 6.06884L8.24634 2.06884C8.17078 2.02565 8.08525 2.00293 7.99822 2.00293C7.91118 2.00293 7.82566 2.02565 7.75009 2.06884L0.750094 6.06884C0.673442 6.11254 0.609718 6.17574 0.565392 6.25203C0.521067 6.32832 0.497719 6.41498 0.497719 6.50321C0.497719 6.59145 0.521067 6.67811 0.565392 6.7544C0.609718 6.83069 0.673442 6.89389 0.750094 6.93759ZM8.00009 3.07571L13.992 6.50009L8.00009 9.92446L2.00822 6.50009L8.00009 3.07571ZM15.4376 8.75009C15.4709 8.80741 15.4926 8.8708 15.5012 8.93655C15.5098 9.00231 15.5053 9.06912 15.4878 9.1331C15.4704 9.19708 15.4404 9.25695 15.3995 9.30923C15.3587 9.3615 15.3079 9.40514 15.2501 9.43759L8.25009 13.4376C8.17453 13.4808 8.089 13.5035 8.00197 13.5035C7.91493 13.5035 7.82941 13.4808 7.75384 13.4376L0.750094 9.43759C0.692846 9.40476 0.642624 9.36097 0.602298 9.30873C0.561971 9.2565 0.532329 9.19683 0.515063 9.13313C0.497797 9.06944 0.493246 9.00296 0.501669 8.93751C0.510093 8.87205 0.531326 8.8089 0.564156 8.75165C0.596987 8.6944 0.640772 8.64418 0.693011 8.60386C0.74525 8.56353 0.804921 8.53389 0.868616 8.51662C0.997254 8.48175 1.13448 8.49941 1.25009 8.56571L8.00009 12.4245L14.7501 8.56571C14.8072 8.53224 14.8704 8.51044 14.9361 8.50159C15.0017 8.49274 15.0684 8.49702 15.1324 8.51418C15.1963 8.53133 15.2563 8.56102 15.3087 8.60151C15.3611 8.64201 15.4049 8.69251 15.4376 8.75009Z"
                                        fill="#101010" />
                                </svg>
                            </template>
                            <template v-if="card.iconType === 'nilai'">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.2"
                                        d="M10 8C10 8.39556 9.8827 8.78224 9.66294 9.11114C9.44318 9.44004 9.13082 9.69638 8.76537 9.84776C8.39991 9.99913 7.99778 10.0387 7.60982 9.96157C7.22186 9.8844 6.86549 9.69392 6.58579 9.41421C6.30608 9.13451 6.1156 8.77814 6.03843 8.39018C5.96126 8.00222 6.00087 7.60009 6.15224 7.23463C6.30362 6.86918 6.55996 6.55682 6.88886 6.33706C7.21776 6.1173 7.60444 6 8 6C8.53043 6 9.03914 6.21071 9.41421 6.58579C9.78929 6.96086 10 7.46957 10 8ZM12.5 4C12.6059 4.62457 12.9034 5.20075 13.3513 5.64869C13.7992 6.09663 14.3754 6.39414 15 6.5V4H12.5ZM12.5 12H15V9.5C14.3754 9.60586 13.7992 9.90337 13.3513 10.3513C12.9034 10.7992 12.6059 11.3754 12.5 12ZM1 9.5V12H3.5C3.39414 11.3754 3.09663 10.7992 2.64869 10.3513C2.20075 9.90337 1.62457 9.60586 1 9.5ZM1 6.5C1.62457 6.39414 2.20075 6.09663 2.64869 5.64869C3.09663 5.20075 3.39414 4.62457 3.5 4H1V6.5Z"
                                        fill="#50CD89" />
                                    <path
                                        d="M8 5.5C7.50555 5.5 7.0222 5.64662 6.61107 5.92133C6.19995 6.19603 5.87952 6.58648 5.6903 7.04329C5.50108 7.50011 5.45157 8.00277 5.54804 8.48773C5.6445 8.97268 5.8826 9.41814 6.23223 9.76777C6.58186 10.1174 7.02732 10.3555 7.51227 10.452C7.99723 10.5484 8.49989 10.4989 8.95671 10.3097C9.41352 10.1205 9.80397 9.80005 10.0787 9.38893C10.3534 8.9778 10.5 8.49445 10.5 8C10.5 7.33696 10.2366 6.70107 9.76777 6.23223C9.29893 5.76339 8.66304 5.5 8 5.5ZM8 9.5C7.70333 9.5 7.41332 9.41203 7.16664 9.2472C6.91997 9.08238 6.72771 8.84811 6.61418 8.57403C6.50065 8.29994 6.47094 7.99834 6.52882 7.70736C6.5867 7.41639 6.72956 7.14912 6.93934 6.93934C7.14912 6.72956 7.41639 6.5867 7.70736 6.52882C7.99834 6.47094 8.29994 6.50065 8.57403 6.61418C8.84811 6.72771 9.08238 6.91997 9.2472 7.16664C9.41203 7.41332 9.5 7.70333 9.5 8C9.5 8.39782 9.34196 8.77936 9.06066 9.06066C8.77936 9.34196 8.39782 9.5 8 9.5ZM15 3.5H1C0.867392 3.5 0.740215 3.55268 0.646447 3.64645C0.552678 3.74021 0.5 3.86739 0.5 4V12C0.5 12.1326 0.552678 12.2598 0.646447 12.3536C0.740215 12.4473 0.867392 12.5 1 12.5H15C15.1326 12.5 15.2598 12.4473 15.3536 12.3536C15.4473 12.2598 15.5 12.1326 15.5 12V4C15.5 3.86739 15.4473 3.74021 15.3536 3.64645C15.2598 3.55268 15.1326 3.5 15 3.5ZM1.5 4.5H2.83562C2.57774 5.09973 2.09973 5.57775 1.5 5.83563V4.5ZM1.5 11.5V10.1644C2.09973 10.4223 2.57774 10.9003 2.83562 11.5H1.5ZM14.5 11.5H13.1644C13.4223 10.9003 13.9003 10.4223 14.5 10.1644V11.5ZM14.5 9.10312C13.9323 9.271 13.4155 9.57825 12.9969 9.99689C12.5782 10.4155 12.271 10.9323 12.1031 11.5H3.89687C3.729 10.9323 3.42175 10.4155 3.00311 9.99689C2.58447 9.57825 2.06775 9.271 1.5 9.10312V6.89687C2.06775 6.729 2.58447 6.42175 3.00311 6.00311C3.42175 5.58447 3.729 5.06775 3.89687 4.5H12.1031C12.271 5.06775 12.5782 5.58447 12.9969 6.00311C13.4155 6.42175 13.9323 6.729 14.5 6.89687V9.10312ZM14.5 5.83563C13.9003 5.57775 13.4223 5.09973 13.1644 4.5H14.5V5.83563Z"
                                        fill="#101010" />
                                </svg>
                            </template>
                            <template v-if="card.iconType === 'utilisasi'">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.2" d="M1 8L3 6V10L1 8ZM13 6V10L15 8L13 6Z" fill="#50CD89" />
                                    <path
                                        d="M8.4999 2.5V13.5C8.4999 13.6326 8.44723 13.7598 8.35346 13.8536C8.25969 13.9473 8.13251 14 7.99991 14C7.8673 14 7.74012 13.9473 7.64635 13.8536C7.55258 13.7598 7.49991 13.6326 7.49991 13.5V2.5C7.49991 2.36739 7.55258 2.24021 7.64635 2.14645C7.74012 2.05268 7.8673 2 7.99991 2C8.13251 2 8.25969 2.05268 8.35346 2.14645C8.44723 2.24021 8.4999 2.36739 8.4999 2.5ZM6.49991 8C6.49991 8.13261 6.44723 8.25979 6.35346 8.35355C6.25969 8.44732 6.13251 8.5 5.99991 8.5H3.49991V10C3.49998 10.0989 3.4707 10.1957 3.41577 10.278C3.36083 10.3603 3.28271 10.4244 3.1913 10.4623C3.09989 10.5002 2.99929 10.5101 2.90225 10.4908C2.80521 10.4714 2.71608 10.4238 2.64616 10.3538L0.646155 8.35375C0.599667 8.30731 0.562787 8.25217 0.537625 8.19147C0.512463 8.13077 0.499512 8.06571 0.499512 8C0.499512 7.93429 0.512463 7.86923 0.537625 7.80853C0.562787 7.74783 0.599667 7.69269 0.646155 7.64625L2.64616 5.64625C2.71608 5.57624 2.80521 5.52856 2.90225 5.50924C2.99929 5.48991 3.09989 5.49981 3.1913 5.53769C3.28271 5.57556 3.36083 5.63971 3.41577 5.72201C3.4707 5.80431 3.49998 5.90105 3.49991 6V7.5H5.99991C6.13251 7.5 6.25969 7.55268 6.35346 7.64645C6.44723 7.74021 6.49991 7.86739 6.49991 8ZM2.49991 7.20687L1.70678 8L2.49991 8.79313V7.20687ZM15.3537 8.35375L13.3537 10.3538C13.2837 10.4238 13.1946 10.4714 13.0976 10.4908C13.0005 10.5101 12.8999 10.5002 12.8085 10.4623C12.7171 10.4244 12.639 10.3603 12.584 10.278C12.5291 10.1957 12.4998 10.0989 12.4999 10V8.5H9.9999C9.8673 8.5 9.74012 8.44732 9.64635 8.35355C9.55258 8.25979 9.4999 8.13261 9.4999 8C9.4999 7.86739 9.55258 7.74021 9.64635 7.64645C9.74012 7.55268 9.8673 7.5 9.9999 7.5H12.4999V6C12.4998 5.90105 12.5291 5.80431 12.584 5.72201C12.639 5.63971 12.7171 5.57556 12.8085 5.53769C12.8999 5.49981 13.0005 5.48991 13.0976 5.50924C13.1946 5.52856 13.2837 5.57624 13.3537 5.64625L15.3537 7.64625C15.4001 7.69269 15.437 7.74783 15.4622 7.80853C15.4873 7.86923 15.5003 7.93429 15.5003 8C15.5003 8.06571 15.4873 8.13077 15.4622 8.19147C15.437 8.25217 15.4001 8.30731 15.3537 8.35375ZM14.2912 8L13.4999 7.20687V8.79313L14.2912 8Z"
                                        fill="#101010" />
                                </svg>
                            </template>
                        </span>
                    </div>
                    <p class="text-[20px] font-bold tracking-tight text-[#101010] leading-tight">{{ card.value }}</p>
                    <p class="mt-0.5 text-[12px] text-[#878787]">{{ card.sub }}</p>
                </div>
            </div>

            <!-- ── Kapasitas Progress Bar ──────────────────── -->
            <div class="bg-white rounded-xl border border-[#EDEDED] shadow-sm p-5">
                <div class="flex items-center gap-3 mb-2">
                    <span class="text-[14px] font-semibold text-[#101010]">Kapasitas Penyimpanan</span>
                    <span class="text-[14px] font-bold text-emerald-500">{{ stats.occupancy }}%</span>
                </div>
                <div class="relative h-[24px] w-full rounded-[8px] bg-gray-100 overflow-hidden">
                    <div class="absolute inset-y-0 left-0 rounded-[8px] bg-emerald-400 transition-all"
                        :style="{ width: stats.occupancy + '%' }" />
                    <!-- Min line marker -->
                    <div class="absolute inset-y-0 w-0.5 bg-red-500 z-10"
                        :style="{ left: ((parseFloat(stats.min_stock.replace(/\./g, '')) / parseFloat(stats.capacity_max.replace(/\./g, ''))) * 100) + '%' }" />
                </div>
                <div class="flex justify-between mt-1.5 text-[11px] text-[#101010]">
                    <span>0 kg</span>
                    <span class="text-red-500 font-medium">Min: {{ stats.min_stock }} kg</span>
                    <span>{{ stats.capacity_max }} kg</span>
                </div>
            </div>

            <!-- ── Main 2-col layout ───────────────────────── -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

                <!-- LEFT col -->
                <div class="xl:col-span-2 flex flex-col gap-5">

                    <!-- Analitik Aktivitas Warehouse -->
                    <div class="bg-white rounded-xl border border-[#EDEDED] shadow-sm p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-[18px] font-semibold text-[#101010]">Analitik Aktivitas Warehouse</h2>
                            <div class="flex items-center gap-1 rounded-lg p-0.5">
                                <button v-for="tab in ['Stok', 'Volume', 'Nilai']" :key="tab"
                                    class="px-3 py-2 h-[34px] border rounded-md text-xs font-medium transition text-[#101010]"
                                    :class="activeChartTab === tab
                                        ? 'bg-[#EBFFFA] border-[#AAEADB]'
                                        : ''" @click="activeChartTab = (tab as any)">
                                    {{ tab }}
                                </button>
                            </div>
                        </div>
                        <div class="h-56">
                            <Line :data="chartData" :options="chartOptions" />
                        </div>
                    </div>

                    <!-- Riwayat Aktivitas Lengkap -->
                    <div class="bg-white rounded-xl border border-[#EDEDED] shadow-sm">
                        <div class="px-5 pt-4 pb-3 border-b border-gray-100">
                            <div class="flex items-center justify-between mb-3">
                                <h2 class="text-[18px] font-semibold text-[#101010]">Riwayat Aktivitas Lengkap</h2>
                                <button
                                    class="px-3 py-2 h-[34px] text-[12px] rounded-core text-primary border border-primary transition"
                                    @click="isTransferOpen = true">
                                    Transfer
                                </button>
                            </div>

                            <!-- Tabs -->
                            <div class="flex gap-1">
                                <button v-for="t in activityHistory" :key="t.tab"
                                    class="px-3 py-2 h-[34px] border rounded-md text-xs font-medium transition text-[#101010]"
                                    @click="activeActivityTab = t.tab" :class="activeActivityTab === t.tab
                                        ? 'bg-[#EBFFFA] border-[#AAEADB]'
                                        : ''">
                                    {{ t.tab }}
                                </button>
                            </div>

                            <!-- Toolbar -->
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mt-3">
                                <div class="flex items-center gap-2">
                                    <select v-model="txPerPage"
                                        class="h-[40px] w-16 rounded-lg border border-gray-300 bg-white px-2 text-sm text-gray-700 focus:border-[#007C95] focus:outline-none">
                                        <option :value="5">5</option>
                                        <option :value="10">10</option>
                                        <option :value="25">25</option>
                                    </select>
                                    <span class="text-sm text-gray-500">Entri per halaman</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <TableFilter :filters="txFilterFields" :model-value="txFilterValues"
                                        @update:model-value="v => { txFilterValues = v }"
                                        @reset="txFilterValues = {}" />
                                    <div class="relative">
                                        <Search class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-gray-400" />
                                        <input v-model="txSearch" type="text" placeholder="Cari..."
                                            class="h-[45px] w-48 rounded-lg border border-gray-300 bg-white py-2 pr-3 pl-9 text-sm placeholder-gray-400 focus:border-[#007C95] focus:outline-none" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-100 bg-gray-50/60">
                                        <th
                                            class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 whitespace-nowrap">
                                            No. Dokumen</th>
                                        <th
                                            class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 whitespace-nowrap">
                                            Tanggal</th>
                                        <th
                                            class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 whitespace-nowrap">
                                            Supplier</th>
                                        <th
                                            class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 whitespace-nowrap">
                                            Total</th>
                                        <th
                                            class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 whitespace-nowrap">
                                            Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr v-for="tx in pagedTx" :key="tx.id" class="hover:bg-gray-50/50">
                                        <td class="px-4 py-3 whitespace-nowrap font-medium text-[#101010] text-[13px]">
                                            {{ tx.document_number }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-500 text-[13px]">{{ tx.date }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-[13px]">
                                            <div class="flex items-center gap-2">
                                                <span
                                                    class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#007C95]/10 text-[10px] font-bold text-[#007C95]">
                                                    {{ tx.party.substring(0, 2).toUpperCase() }}
                                                </span>
                                                <div>
                                                    <p class="text-[#101010] font-medium leading-none">{{ tx.party }}
                                                    </p>
                                                    <p class="text-[11px] text-gray-400">{{ tx.party_email }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-700 text-[13px]">{{
                                            formatRp(tx.total) }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span
                                                class="inline-flex items-center rounded px-2 py-0.5 text-xs font-medium"
                                                :class="{
                                                    'bg-emerald-50 text-emerald-600': tx.status === 'Lunas',
                                                    'bg-red-50 text-red-500': tx.status === 'Hutang',
                                                    'bg-amber-50 text-amber-600': tx.status === 'Pending',
                                                }">
                                                {{ tx.status }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr v-if="filteredTx.length === 0">
                                        <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-400">
                                            Belum ada aktivitas
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <TablePagination :paginator="txPaginator" type="centerPaginate" @navigate="handleTxNavigate" />
                    </div>

                    <!-- Log Aktivitas -->
                    <div class="bg-white rounded-xl border border-[#EDEDED] shadow-sm p-5">
                        <h2 class="text-[18px] font-semibold text-[#101010] mb-4">Log Aktivitas</h2>
                        <div class="flex flex-col divide-y divide-gray-50">
                            <div v-for="log in activityLogs" :key="log.id" class="flex items-start gap-3 py-3">
                                <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full"
                                    :class="logDotColor[log.type] ?? 'bg-gray-300'" />
                                <div class="flex-1 min-w-0">
                                    <p class="text-[13px] text-[#101010]">{{ log.message }}</p>
                                    <p class="text-[12px] text-gray-400">{{ log.user }}</p>
                                </div>
                                <span class="shrink-0 text-[12px] text-gray-400 whitespace-nowrap">{{ log.time }}</span>
                            </div>
                            <div v-if="activityLogs.length === 0" class="py-6 text-center text-sm text-gray-400">
                                Belum ada aktivitas
                            </div>
                        </div>
                    </div>

                </div>

                <!-- RIGHT col -->
                <div class="flex flex-col gap-5">

                    <!-- Profil Warehouse -->
                    <div class="bg-white rounded-xl border border-[#EDEDED] shadow-sm p-5">
                        <h2 class="text-[18px] font-semibold text-[#101010] mb-4">Profil Warehouse</h2>
                        <div class="flex items-center gap-3 mb-4">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#007C95]/10 text-sm font-bold text-[#007C95]">
                                {{ warehouse.name.substring(0, 2).toUpperCase() }}
                            </div>
                            <div>
                                <p class="text-[14px] font-semibold text-[#101010]">{{ warehouse.name }}</p>
                                <p class="text-[12px] text-gray-400">{{ warehouse.code }} · {{ warehouse.city_name ??
                                    '—' }}</p>
                            </div>
                        </div>
                        <div class="flex flex-col gap-2.5 text-[13px]">
                            <div class="flex items-start justify-between gap-2">
                                <span class="text-gray-400 shrink-0">Alamat:</span>
                                <span class="text-right text-[#101010]">{{ warehouse.address ?? '—' }}</span>
                            </div>
                            <div class="flex items-start justify-between gap-2">
                                <span class="text-gray-400 shrink-0">Kota:</span>
                                <span class="text-right text-[#101010]">{{ warehouse.city_name ?? '—' }}</span>
                            </div>
                            <div class="flex items-start justify-between gap-2">
                                <span class="text-gray-400 shrink-0">Tipe:</span>
                                <span class="text-right text-[#101010]">{{ warehouse.type ?? '—' }}</span>
                            </div>
                            <div class="flex items-start justify-between gap-2">
                                <span class="text-gray-400 shrink-0">PIC:</span>
                                <span class="text-right text-[#101010]">{{ warehouse.pic ?? '—' }}</span>
                            </div>
                            <div class="flex items-start justify-between gap-2">
                                <span class="text-gray-400 shrink-0">Telepon:</span>
                                <span class="text-right text-[#101010]">{{ warehouse.pic_phone ? '+62' +
                                    warehouse.pic_phone : '—'
                                    }}</span>
                            </div>
                            <div class="flex items-start justify-between gap-2">
                                <span class="text-gray-400 shrink-0">Status:</span>
                                <span class="font-medium"
                                    :class="warehouse.is_active ? 'text-emerald-600' : 'text-red-500'">
                                    {{ warehouse.is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </div>
                            <div class="flex items-start justify-between gap-2">
                                <span class="text-gray-400 shrink-0">Biaya Ops:</span>
                                <span class="text-right text-[#101010]">
                                    {{ warehouse.operating_cost ? 'Rp ' +
                                        Number(warehouse.operating_cost).toLocaleString('id-ID') + ' / bulan' : '—' }}
                                </span>
                            </div>
                            <div class="flex items-start justify-between gap-2">
                                <span class="text-gray-400 shrink-0">Stok Minimum:</span>
                                <span class="text-right text-[#101010]">
                                    {{ warehouse.min_stock ? Number(warehouse.min_stock).toLocaleString('id-ID') + ' kg'
                                    : '—' }}
                                </span>
                            </div>
                            <div class="flex items-start justify-between gap-2">
                                <span class="text-gray-400 shrink-0">Harga Estimasi:</span>
                                <span class="text-right text-[#101010]">
                                    {{ warehouse.price_estimate ? 'Rp ' +
                                        Number(warehouse.price_estimate).toLocaleString('id-ID') + '/kg' : '—' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Supplier Aktif -->
                    <div class="bg-white rounded-xl border border-[#EDEDED] shadow-sm p-5">
                        <div class="flex items-center justify-between mb-3">
                            <h2 class="text-[15px] font-semibold text-[#101010]">Supplier Aktif</h2>
                            <div class="text-xs text-[#50CD89] bg-[#F5FFFA] py-0.5 px-1 rounded-core">
                                {{ activeSuppliers.length }} aktif
                            </div>
                        </div>
                        <div class="flex flex-col gap-2">
                            <div v-for="(s, i) in activeSuppliers" :key="i"
                                class="flex items-center justify-between gap-3 rounded-lg border border-gray-100 px-3 py-2 hover:bg-gray-50 cursor-pointer transition">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#007C95]/10 text-[11px] font-bold text-[#007C95]">
                                        {{ s.initials }}
                                    </span>
                                    <div>
                                        <p class="text-[13px] font-medium text-[#101010]">{{ s.name }}</p>
                                        <p class="text-[11px] text-gray-400">{{ s.email }}</p>
                                    </div>
                                </div>
                                <svg width="14" height="14" viewBox="0 0 16 16" fill="none" class="text-gray-400">
                                    <path d="M6 4l4 4-4 4" stroke="currentColor" stroke-width="1.5"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                            <div v-if="activeSuppliers.length === 0" class="text-xs text-gray-400 text-center py-2">
                                Belum ada data
                            </div>
                        </div>
                    </div>

                    <!-- Buyer Aktif -->
                    <div class="bg-white rounded-xl border border-[#EDEDED] shadow-sm p-5">
                        <div class="flex items-center justify-between mb-3">
                            <h2 class="text-[15px] font-semibold text-[#101010]">Buyer Aktif</h2>
                            <span class="text-xs text-[#7239EA] bg-[#F6F2FF] py-0.5 px-1 rounded-core">
                                {{ activeBuyers.length }} aktif
                            </span>
                        </div>
                        <div class="flex flex-col gap-2">
                            <div v-for="(b, i) in activeBuyers" :key="i"
                                class="flex items-center justify-between gap-3 rounded-lg border border-gray-100 px-3 py-2 hover:bg-gray-50 cursor-pointer transition">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-teal-50 text-[11px] font-bold text-teal-700">
                                        {{ b.initials }}
                                    </span>
                                    <div>
                                        <p class="text-[13px] font-medium text-[#101010]">{{ b.name }}</p>
                                        <p class="text-[11px] text-gray-400">{{ b.email }}</p>
                                    </div>
                                </div>
                                <svg width="14" height="14" viewBox="0 0 16 16" fill="none" class="text-gray-400">
                                    <path d="M6 4l4 4-4 4" stroke="currentColor" stroke-width="1.5"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                            <div v-if="activeBuyers.length === 0" class="text-xs text-gray-400 text-center py-2">Belum
                                ada data
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>

        <!-- Modals -->
        <WarehouseFormModal v-model:open="isEditOpen" :editing-warehouse="(warehouse as any)" :cities="allCities ?? []"
            post-url="/master-data/warehouse" @success="router.reload()" />

        <WarehouseStatusModal v-model:open="isStatusOpen" :warehouse="(warehouse as any)"
            toggle-url="/master-data/warehouse" @success="router.reload()" />

        <WarehouseTransferModal v-model:open="isTransferOpen" :source-warehouse="(warehouse as any)"
            :all-warehouses="allWarehouses" transfer-url="/master-data/warehouse/transfer" @success="router.reload()" />

    </AppLayout>
</template>