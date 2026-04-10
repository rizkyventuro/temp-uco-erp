<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref, watch, onMounted } from 'vue';
import { Search } from 'lucide-vue-next';
import BuyerFormModal from '@/components/Buyer/BuyerFormModal.vue';
import type { City } from '@/components/Buyer/BuyerFormModal.vue';
import BuyerStatusModal from '@/components/Buyer/BuyerStatusModal.vue';
import TableFilter from '@/components/TableFilter.vue';
import type { FilterValues } from '@/components/TableFilter.vue';
import TablePagination from '@/components/TablePagination.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Bar } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend,
} from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend, ChartDataLabels);

// ── Types ──────────────────────────────────────────────────────

interface Buyer {
    id: string;
    kode: string;
    nama: string;
    tipe?: 'PT' | 'CV' | 'UD' | 'Perorangan' | null;
    telepon?: string | null;
    email?: string | null;
    city_id?: number | null;
    city_name?: string | null;
    harga_jual_default?: number | null;
    limit_kredit?: number | null;
    termin_hari?: number | null;
    pic?: string | null;
    npwp?: string | null;
    website?: string | null;
    alamat?: string | null;
    catatan?: string | null;
    is_active: boolean;
    alasan_nonaktif?: string | null;
    foto_url?: string | null;
    inisials: string;
}

interface Transaction {
    id: string;
    no_dokumen: string;
    tanggal: string;
    gudang: string;
    volume: number;
    harga: number;
    total: number;
    status: string;
}

interface ProdukDibeli {
    nama: string;
    persen: number;
}

interface ActivityLog {
    id: string;
    message: string;
    user: string;
    waktu: string;
    type: 'success' | 'danger' | 'warning' | 'info';
}

// ── Props ──────────────────────────────────────────────────────

const props = defineProps<{
    buyer: Buyer;
    stats: {
        total_penjualan: string;
        total_penjualan_sub: string;
        piutang_aktif: string;
        piutang_aktif_sub: string;
        harga_rata_rata: string;
        harga_rata_rata_sub: string;
        total_volume: string;
        total_volume_sub: string;
        rating: string;
        rating_sub: string;
    };
    volumeChart: { label: string; volume: number }[];
    transactions: {
        data: Transaction[];
        current_page: number;
        last_page: number;
        total: number;
    };
    ringkasanPiutang: {
        total_penjualan: string;
        sudah_diterima: string;
        piutang_aktif: string;
        persen_diterima: number;
    };
    produkDibeli: ProdukDibeli[];
    activityLogs: ActivityLog[];
    toggleUrl: string;
    editUrl: string;
    openEditModal?: boolean;
    allCities?: City[];
}>();

// ── Edit modal ─────────────────────────────────────────────────

const isEditOpen = ref(false);
const isStatusOpen = ref(false);

onMounted(() => {
    if (props.openEditModal) isEditOpen.value = true;
});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Master Data', href: '#' },
    { title: 'Buyer (Customer)', href: '/master-data/buyer' },
    { title: props.buyer.nama, href: '#' },
];

// ── Stat cards ─────────────────────────────────────────────────

const statCards = computed(() => [
    { label: 'Total Penjualan', value: props.stats.total_penjualan, sub: props.stats.total_penjualan_sub, iconType: 'penjualan' },
    { label: 'Piutang Aktif', value: props.stats.piutang_aktif, sub: props.stats.piutang_aktif_sub, iconType: 'piutang' },
    { label: 'Harga Jual Rata-rata', value: props.stats.harga_rata_rata, sub: props.stats.harga_rata_rata_sub, iconType: 'avg_price' },
    { label: 'Total Volume', value: props.stats.total_volume, sub: props.stats.total_volume_sub, iconType: 'volume' },
    { label: 'Rating yer', value: props.stats.rating, sub: props.stats.rating_sub, iconType: 'star' },
]);

// ── Tipe badge color ───────────────────────────────────────────

const tipeColor: Record<string, string> = {
    PT: 'bg-blue-50 text-blue-700',
    CV: 'bg-purple-50 text-purple-700',
    UD: 'bg-orange-50 text-orange-700',
    Perorangan: 'bg-gray-100 text-gray-700',
};

// ── Bar chart ──────────────────────────────────────────────────

const barData = computed(() => ({
    labels: props.volumeChart.map(d => d.label),
    datasets: [{
        label: 'Volume (kg)',
        data: props.volumeChart.map(d => d.volume),
        backgroundColor: '#007C95',
        borderRadius: 4,
        barPercentage: 0.55,
    }],
}));

const barOptions = {
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
                callback: (v: any) => (v >= 1000 ? v / 1000 + 'k' : v),
            },
        },
    },
};

// ── Transaction toolbar ─────────────────────────────────────────

const txSearch = ref('');
const txPerPage = ref(10);
const txCurrentPage = ref(1);
const txFilterValues = ref<FilterValues>({});

const txFilterFields = computed(() => [
    {
        key: 'status', label: 'Status', type: 'select' as const,
        options: [
            { label: 'Lunas', value: 'Lunas' },
            { label: 'Hutang', value: 'Hutang' },
            { label: 'Pending', value: 'Pending' },
        ],
    },
    {
        key: 'gudang', label: 'Gudang', type: 'select' as const,
        options: [...new Set(props.transactions.data.map(t => t.gudang))]
            .map(g => ({ label: g, value: g })),
    },
]);

const filteredTx = computed(() => {
    let data = props.transactions.data;
    if (txSearch.value) {
        const q = txSearch.value.toLowerCase();
        data = data.filter(t =>
            t.no_dokumen.toLowerCase().includes(q) ||
            t.gudang.toLowerCase().includes(q),
        );
    }
    if (txFilterValues.value.status)
        data = data.filter(t => t.status === txFilterValues.value.status);
    if (txFilterValues.value.gudang)
        data = data.filter(t => t.gudang === txFilterValues.value.gudang);
    return data;
});

const txLastPage = computed(() => Math.max(1, Math.ceil(filteredTx.value.length / txPerPage.value)));

const pagedTx = computed(() => {
    const start = (txCurrentPage.value - 1) * txPerPage.value;
    return filteredTx.value.slice(start, start + txPerPage.value);
});

watch([txSearch, txPerPage, txFilterValues], () => { txCurrentPage.value = 1; }, { deep: true });

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

// ── Helpers ────────────────────────────────────────────────────

function formatRp(val: number) {
    return 'Rp ' + val.toLocaleString('id-ID');
}

const logDotColor: Record<string, string> = {
    success: 'bg-emerald-500',
    danger: 'bg-red-500',
    warning: 'bg-amber-400',
    info: 'bg-sky-400',
};
</script>

<template>

    <Head :title="buyer.nama" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-5 p-6 bg-gray-50 pb-12">

            <!-- ── Page Header ──────────────────────────────── -->
            <div class="flex items-center gap-3">
                <button
                    class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 hover:bg-gray-50"
                    @click="router.get('/master-data/buyer')">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                        <path d="M10 12L6 8l4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </button>

                <div class="flex-1">
                    <h1 class="text-[22px] font-bold text-[#101010]">{{ buyer.nama }}</h1>
                    <div class="flex items-center gap-2">
                        <p class="text-sm text-gray-400">{{ buyer.kode }}</p>
                        <span class="inline-flex items-center rounded px-2 py-0.5 text-xs font-medium"
                            :class="buyer.is_active ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-500'">
                            {{ buyer.is_active ? 'Aktif' : 'Non-Aktif' }}
                        </span>
                        <span v-if="buyer.tipe"
                            class="inline-flex items-center rounded px-2 py-0.5 text-xs font-semibold"
                            :class="tipeColor[buyer.tipe] ?? 'bg-gray-100 text-gray-700'">
                            {{ buyer.tipe }}
                        </span>
                    </div>
                </div>

                <!-- Action buttons -->
                <div class="flex items-center gap-2">
                    <button
                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-600 hover:bg-gray-50 transition"
                        @click="router.get(`/master-data/buyer/${buyer.id}/cetak-profil`)">
                        <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                            <path d="M4 4V2h8v2M4 12H2V7h12v5h-2M4 9h8v5H4V9z" stroke="currentColor" stroke-width="1.3"
                                stroke-linejoin="round" />
                        </svg>
                        Cetak Profil
                    </button>
                    <button
                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm transition"
                        :class="buyer.is_active
                            ? 'text-amber-600 hover:bg-amber-50'
                            : 'text-emerald-600 hover:bg-emerald-50'" @click="isStatusOpen = true">
                        <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                            <rect x="2" y="5" width="12" height="8" rx="1.5" stroke="currentColor" stroke-width="1.3" />
                            <path d="M5 5V3.5a3 3 0 0 1 6 0V5" stroke="currentColor" stroke-width="1.3" />
                        </svg>
                        {{ buyer.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                    </button>
                    <button
                        class="inline-flex items-center gap-1.5 rounded-lg bg-[#007C95] px-3 py-2 text-sm text-white hover:bg-[#006b80] transition"
                        @click="isEditOpen = true">
                        <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                            <path d="M11.5 2.5a1.414 1.414 0 0 1 2 2L5 13H3v-2L11.5 2.5z" stroke="currentColor"
                                stroke-width="1.3" stroke-linejoin="round" />
                        </svg>
                        Ubah Data
                    </button>
                </div>
            </div>

            <!-- ── Stat Cards ──────────────────────────────── -->
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-5">

                <!-- Total Penjualan -->
                <div class="bg-white rounded-xl border border-[#EDEDED] shadow-sm p-4 flex flex-col">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[13px] text-[#878787] font-medium">Total Penjualan</span>
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path opacity="0.2"
                                d="M15 7.5V11.5C15 11.6326 14.9473 11.7598 14.8536 11.8536C14.7598 11.9473 14.6326 12 14.5 12H13C13 11.76 12.9425 11.5235 12.8322 11.3104C12.7219 11.0973 12.562 10.9137 12.3661 10.7751C12.1702 10.6366 11.9438 10.547 11.7061 10.5141C11.4684 10.4811 11.2263 10.5056 11 10.5856C10.7075 10.689 10.4543 10.8806 10.2752 11.1339C10.0961 11.3872 9.99997 11.6898 10 12H6C6 11.6022 5.84196 11.2206 5.56066 10.9393C5.27936 10.658 4.89782 10.5 4.5 10.5C4.10218 10.5 3.72064 10.658 3.43934 10.9393C3.15804 11.2206 3 11.6022 3 12H1.5C1.36739 12 1.24021 11.9473 1.14645 11.8536C1.05268 11.7598 1 11.6326 1 11.5V9H11V7.5H15Z"
                                fill="#50CD89" />
                            <path
                                d="M15.4637 7.3125L14.5887 5.125C14.5145 4.93992 14.3864 4.78139 14.2211 4.66996C14.0557 4.55852 13.8607 4.49931 13.6613 4.5H11.5V4C11.5 3.86739 11.4473 3.74021 11.3536 3.64645C11.2598 3.55268 11.1326 3.5 11 3.5H1.5C1.23478 3.5 0.98043 3.60536 0.792893 3.79289C0.605357 3.98043 0.5 4.23478 0.5 4.5V11.5C0.5 11.7652 0.605357 12.0196 0.792893 12.2071C0.98043 12.3946 1.23478 12.5 1.5 12.5H2.5625C2.67265 12.9302 2.92285 13.3115 3.27366 13.5838C3.62446 13.8561 4.05591 14.0039 4.5 14.0039C4.94409 14.0039 5.37554 13.8561 5.72635 13.5838C6.07715 13.3115 6.32735 12.9302 6.4375 12.5H9.5625C9.67265 12.9302 9.92285 13.3115 10.2737 13.5838C10.6245 13.8561 11.0559 14.0039 11.5 14.0039C11.9441 14.0039 12.3755 13.8561 12.7263 13.5838C13.0771 13.3115 13.3273 12.9302 13.4375 12.5H14.5C14.7652 12.5 15.0196 12.3946 15.2071 12.2071C15.3946 12.0196 15.5 11.7652 15.5 11.5V7.5C15.5002 7.43574 15.4879 7.37206 15.4637 7.3125ZM11.5 5.5H13.6613L14.2612 7H11.5V5.5ZM1.5 4.5H10.5V8.5H1.5V4.5ZM4.5 13C4.30222 13 4.10888 12.9414 3.94443 12.8315C3.77998 12.7216 3.65181 12.5654 3.57612 12.3827C3.50043 12.2 3.48063 11.9989 3.51921 11.8049C3.5578 11.6109 3.65304 11.4327 3.79289 11.2929C3.93275 11.153 4.11093 11.0578 4.30491 11.0192C4.49889 10.9806 4.69996 11.0004 4.88268 11.0761C5.06541 11.1518 5.22159 11.28 5.33147 11.4444C5.44135 11.6089 5.5 11.8022 5.5 12C5.5 12.2652 5.39464 12.5196 5.20711 12.7071C5.01957 12.8946 4.76522 13 4.5 13ZM9.5625 11.5H6.4375C6.32735 11.0698 6.07715 10.6885 5.72635 10.4162C5.37554 10.1439 4.94409 9.99608 4.5 9.99608C4.05591 9.99608 3.62446 10.1439 3.27366 10.4162C2.92285 10.6885 2.67265 11.0698 2.5625 11.5H1.5V9.5H10.5V10.2694C10.2701 10.4023 10.0688 10.5795 9.9079 10.7907C9.74697 11.002 9.62957 11.243 9.5625 11.5ZM11.5 13C11.3022 13 11.1089 12.9414 10.9444 12.8315C10.78 12.7216 10.6518 12.5654 10.5761 12.3827C10.5004 12.2 10.4806 11.9989 10.5192 11.8049C10.5578 11.6109 10.653 11.4327 10.7929 11.2929C10.9327 11.153 11.1109 11.0578 11.3049 11.0192C11.4989 10.9806 11.7 11.0004 11.8827 11.0761C12.0654 11.1518 12.2216 11.28 12.3315 11.4444C12.4414 11.6089 12.5 11.8022 12.5 12C12.5 12.2652 12.3946 12.5196 12.2071 12.7071C12.0196 12.8946 11.7652 13 11.5 13ZM14.5 11.5H13.4375C13.326 11.0708 13.0754 10.6908 12.7247 10.4193C12.3741 10.1479 11.9434 10.0004 11.5 10V8H14.5V11.5Z"
                                fill="#101010" />
                        </svg>
                    </div>
                    <p class="text-[20px] font-bold tracking-tight text-[#101010] leading-tight">{{ stats.total_penjualan }}</p>
                    <p class="mt-0.5 text-[12px] text-[#878787]">{{ stats.total_penjualan_sub }}</p>
                </div>

                <!-- Piutang Aktif -->
                <div class="bg-white rounded-xl border border-[#EDEDED] shadow-sm p-4 flex flex-col">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[13px] text-[#878787] font-medium">Piutang Aktif</span>
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path opacity="0.2" d="M14.5 3.5V7.5L10.5 3.5H14.5Z" fill="#50CD89" />
                            <path
                                d="M14.4998 3H10.4998C10.4008 2.99992 10.3041 3.0292 10.2218 3.08414C10.1395 3.13908 10.0754 3.21719 10.0375 3.30861C9.9996 3.40002 9.9897 3.50061 10.009 3.59765C10.0284 3.6947 10.076 3.78382 10.146 3.85375L11.7929 5.5L8.49979 8.79313L6.35354 6.64625C6.3071 6.59976 6.25196 6.56288 6.19126 6.53772C6.13056 6.51256 6.0655 6.49961 5.99979 6.49961C5.93408 6.49961 5.86902 6.51256 5.80832 6.53772C5.74762 6.56288 5.69248 6.59976 5.64604 6.64625L1.14604 11.1462C1.05222 11.2401 0.999512 11.3673 0.999512 11.5C0.999512 11.6327 1.05222 11.7599 1.14604 11.8538C1.23986 11.9476 1.36711 12.0003 1.49979 12.0003C1.63247 12.0003 1.75972 11.9476 1.85354 11.8538L5.99979 7.70687L8.14604 9.85375C8.19248 9.90024 8.24762 9.93712 8.30832 9.96228C8.36902 9.98744 8.43408 10.0004 8.49979 10.0004C8.5655 10.0004 8.63056 9.98744 8.69126 9.96228C8.75196 9.93712 8.8071 9.90024 8.85354 9.85375L12.4998 6.20688L14.146 7.85375C14.216 7.92376 14.3051 7.97144 14.4021 7.99076C14.4992 8.01009 14.5998 8.00019 14.6912 7.96231C14.7826 7.92444 14.8607 7.86029 14.9157 7.77799C14.9706 7.69569 14.9999 7.59895 14.9998 7.5V3.5C14.9998 3.36739 14.9471 3.24022 14.8533 3.14645C14.7596 3.05268 14.6324 3 14.4998 3ZM13.9998 6.29313L11.7067 4H13.9998V6.29313Z"
                                fill="#101010" />
                        </svg>

                    </div>
                    <p class="text-[20px] font-bold tracking-tight text-[#101010] leading-tight">{{ stats.piutang_aktif }}</p>
                    <p class="mt-0.5 text-[12px] text-[#878787]">{{ stats.piutang_aktif_sub }}</p>
                </div>

                <!-- Harga Jual Rata-rata -->
                <div class="bg-white rounded-xl border border-[#EDEDED] shadow-sm p-4 flex flex-col">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[13px] text-[#878787] font-medium">Harga Jual Rata-rata</span>
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path opacity="0.2"
                                d="M10 8C10 8.39556 9.8827 8.78224 9.66294 9.11114C9.44318 9.44004 9.13082 9.69638 8.76537 9.84776C8.39991 9.99913 7.99778 10.0387 7.60982 9.96157C7.22186 9.8844 6.86549 9.69392 6.58579 9.41421C6.30608 9.13451 6.1156 8.77814 6.03843 8.39018C5.96126 8.00222 6.00087 7.60009 6.15224 7.23463C6.30362 6.86918 6.55996 6.55682 6.88886 6.33706C7.21776 6.1173 7.60444 6 8 6C8.53043 6 9.03914 6.21071 9.41421 6.58579C9.78929 6.96086 10 7.46957 10 8ZM12.5 4C12.6059 4.62457 12.9034 5.20075 13.3513 5.64869C13.7992 6.09663 14.3754 6.39414 15 6.5V4H12.5ZM12.5 12H15V9.5C14.3754 9.60586 13.7992 9.90337 13.3513 10.3513C12.9034 10.7992 12.6059 11.3754 12.5 12ZM1 9.5V12H3.5C3.39414 11.3754 3.09663 10.7992 2.64869 10.3513C2.20075 9.90337 1.62457 9.60586 1 9.5ZM1 6.5C1.62457 6.39414 2.20075 6.09663 2.64869 5.64869C3.09663 5.20075 3.39414 4.62457 3.5 4H1V6.5Z"
                                fill="#50CD89" />
                            <path
                                d="M8 5.5C7.50555 5.5 7.0222 5.64662 6.61107 5.92133C6.19995 6.19603 5.87952 6.58648 5.6903 7.04329C5.50108 7.50011 5.45157 8.00277 5.54804 8.48773C5.6445 8.97268 5.8826 9.41814 6.23223 9.76777C6.58186 10.1174 7.02732 10.3555 7.51227 10.452C7.99723 10.5484 8.49989 10.4989 8.95671 10.3097C9.41352 10.1205 9.80397 9.80005 10.0787 9.38893C10.3534 8.9778 10.5 8.49445 10.5 8C10.5 7.33696 10.2366 6.70107 9.76777 6.23223C9.29893 5.76339 8.66304 5.5 8 5.5ZM8 9.5C7.70333 9.5 7.41332 9.41203 7.16664 9.2472C6.91997 9.08238 6.72771 8.84811 6.61418 8.57403C6.50065 8.29994 6.47094 7.99834 6.52882 7.70736C6.5867 7.41639 6.72956 7.14912 6.93934 6.93934C7.14912 6.72956 7.41639 6.5867 7.70736 6.52882C7.99834 6.47094 8.29994 6.50065 8.57403 6.61418C8.84811 6.72771 9.08238 6.91997 9.2472 7.16664C9.41203 7.41332 9.5 7.70333 9.5 8C9.5 8.39782 9.34196 8.77936 9.06066 9.06066C8.77936 9.34196 8.39782 9.5 8 9.5ZM15 3.5H1C0.867392 3.5 0.740215 3.55268 0.646447 3.64645C0.552678 3.74021 0.5 3.86739 0.5 4V12C0.5 12.1326 0.552678 12.2598 0.646447 12.3536C0.740215 12.4473 0.867392 12.5 1 12.5H15C15.1326 12.5 15.2598 12.4473 15.3536 12.3536C15.4473 12.2598 15.5 12.1326 15.5 12V4C15.5 3.86739 15.4473 3.74021 15.3536 3.64645C15.2598 3.55268 15.1326 3.5 15 3.5ZM1.5 4.5H2.83562C2.57774 5.09973 2.09973 5.57775 1.5 5.83563V4.5ZM1.5 11.5V10.1644C2.09973 10.4223 2.57774 10.9003 2.83562 11.5H1.5ZM14.5 11.5H13.1644C13.4223 10.9003 13.9003 10.4223 14.5 10.1644V11.5ZM14.5 9.10312C13.9323 9.271 13.4155 9.57825 12.9969 9.99689C12.5782 10.4155 12.271 10.9323 12.1031 11.5H3.89687C3.729 10.9323 3.42175 10.4155 3.00311 9.99689C2.58447 9.57825 2.06775 9.271 1.5 9.10312V6.89687C2.06775 6.729 2.58447 6.42175 3.00311 6.00311C3.42175 5.58447 3.729 5.06775 3.89687 4.5H12.1031C12.271 5.06775 12.5782 5.58447 12.9969 6.00311C13.4155 6.42175 13.9323 6.729 14.5 6.89687V9.10312ZM14.5 5.83563C13.9003 5.57775 13.4223 5.09973 13.1644 4.5H14.5V5.83563Z"
                                fill="#101010" />
                        </svg>

                    </div>
                    <p class="text-[20px] font-bold tracking-tight text-[#101010] leading-tight">{{ stats.harga_rata_rata }}</p>
                    <p class="mt-0.5 text-[12px] text-[#878787]">{{ stats.harga_rata_rata_sub }}</p>
                </div>

                <!-- Total Volume -->
                <div class="bg-white rounded-xl border border-[#EDEDED] shadow-sm p-4 flex flex-col">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[13px] text-[#878787] font-medium">Total Volume</span>
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path opacity="0.2"
                                d="M3.5 5.5L5.5 10.5C5.5 11.6044 4.25 12 3.5 12C2.75 12 1.5 11.6044 1.5 10.5L3.5 5.5ZM12.5 3.5L10.5 8.5C10.5 9.60438 11.75 10 12.5 10C13.25 10 14.5 9.60438 14.5 8.5L12.5 3.5Z"
                                fill="#50CD89" />
                            <path
                                d="M14.9644 8.3125L12.9644 3.3125C12.9203 3.20237 12.8386 3.11147 12.7337 3.05606C12.6288 3.00065 12.5077 2.98432 12.3919 3.01L8.50001 3.875V2.5C8.50001 2.36739 8.44733 2.24021 8.35356 2.14645C8.2598 2.05268 8.13262 2 8.00001 2C7.8674 2 7.74022 2.05268 7.64646 2.14645C7.55269 2.24021 7.50001 2.36739 7.50001 2.5V4.09875L3.39126 5.01188C3.31204 5.02937 3.23827 5.06589 3.17631 5.11826C3.11436 5.17064 3.06607 5.2373 3.03563 5.3125V5.31625L1.03563 10.3125C1.01171 10.3721 0.999609 10.4358 1.00001 10.5C1.00001 11.9569 2.53376 12.5 3.50001 12.5C4.46626 12.5 6.00001 11.9569 6.00001 10.5C6.00041 10.4358 5.98831 10.3721 5.96438 10.3125L4.18251 5.86062L7.50001 5.125V13H6.50001C6.3674 13 6.24022 13.0527 6.14646 13.1464C6.05269 13.2402 6.00001 13.3674 6.00001 13.5C6.00001 13.6326 6.05269 13.7598 6.14646 13.8536C6.24022 13.9473 6.3674 14 6.50001 14H9.50001C9.63262 14 9.7598 13.9473 9.85356 13.8536C9.94733 13.7598 10 13.6326 10 13.5C10 13.3674 9.94733 13.2402 9.85356 13.1464C9.7598 13.0527 9.63262 13 9.50001 13H8.50001V4.90125L11.6875 4.19375L10.0356 8.3125C10.0117 8.3721 9.99961 8.43578 10 8.5C10 9.95687 11.5338 10.5 12.5 10.5C13.4663 10.5 15 9.95687 15 8.5C15.0004 8.43578 14.9883 8.3721 14.9644 8.3125ZM3.50001 11.5C3.02938 11.5 2.07751 11.2744 2.00438 10.585L3.50001 6.84625L4.99563 10.585C4.92251 11.2744 3.97063 11.5 3.50001 11.5ZM12.5 9.5C12.0294 9.5 11.0775 9.27438 11.0044 8.585L12.5 4.84625L13.9956 8.585C13.9225 9.27438 12.9706 9.5 12.5 9.5Z"
                                fill="#101010" />
                        </svg>

                    </div>
                    <p class="text-[20px] font-bold tracking-tight text-[#101010] leading-tight">{{ stats.total_volume }}</p>
                    <p class="mt-0.5 text-[12px] text-[#878787]">{{ stats.total_volume_sub }}</p>
                </div>

                <!-- Rating -->
                <div class="bg-white rounded-xl border border-[#EDEDED] shadow-sm p-4 flex flex-col">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[13px] text-[#878787] font-medium">Rating</span>
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path opacity="0.2"
                                d="M11.5079 9.23126C11.4394 9.29146 11.3884 9.36909 11.3605 9.45591C11.3325 9.54274 11.3286 9.63551 11.3492 9.72438L12.1942 13.3831C12.2165 13.4787 12.2102 13.5787 12.1761 13.6707C12.142 13.7627 12.0815 13.8427 12.0023 13.9006C11.9231 13.9585 11.8286 13.9918 11.7305 13.9964C11.6325 14.0009 11.5353 13.9766 11.451 13.9263L8.25728 11.9888C8.17968 11.9416 8.09061 11.9166 7.99978 11.9166C7.90896 11.9166 7.81989 11.9416 7.74228 11.9888L4.54853 13.9263C4.46427 13.9766 4.36706 14.0009 4.26903 13.9964C4.17101 13.9918 4.07649 13.9585 3.99726 13.9006C3.91803 13.8427 3.85759 13.7627 3.82348 13.6707C3.78936 13.5787 3.78308 13.4787 3.80541 13.3831L4.65041 9.72438C4.67095 9.63551 4.66704 9.54274 4.63908 9.45591C4.61113 9.36909 4.56019 9.29146 4.49166 9.23126L1.67228 6.77188C1.59691 6.70782 1.54223 6.62284 1.51517 6.52769C1.48811 6.43254 1.48988 6.3315 1.52025 6.23736C1.55063 6.14322 1.60825 6.0602 1.68582 5.99882C1.76339 5.93743 1.85743 5.90043 1.95603 5.89251L5.67228 5.57126C5.76295 5.56318 5.84969 5.53051 5.92316 5.47676C5.99662 5.42301 6.05402 5.35023 6.08916 5.26626L7.54103 1.80626C7.57981 1.7168 7.6439 1.64064 7.72541 1.58714C7.80691 1.53363 7.90229 1.50513 7.99978 1.50513C8.09728 1.50513 8.19266 1.53363 8.27416 1.58714C8.35567 1.64064 8.41976 1.7168 8.45853 1.80626L9.91041 5.26626C9.94555 5.35023 10.0029 5.42301 10.0764 5.47676C10.1499 5.53051 10.2366 5.56318 10.3273 5.57126L14.0435 5.89251C14.1421 5.90043 14.2362 5.93743 14.3137 5.99882C14.3913 6.0602 14.4489 6.14322 14.4793 6.23736C14.5097 6.3315 14.5115 6.43254 14.4844 6.52769C14.4573 6.62284 14.4027 6.70782 14.3273 6.77188L11.5079 9.23126Z"
                                fill="#50CD89" />
                            <path
                                d="M14.9502 6.08062C14.8897 5.89399 14.7755 5.72929 14.6221 5.60704C14.4686 5.4848 14.2825 5.4104 14.0871 5.39312L10.3752 5.07312L8.92021 1.61312C8.84446 1.43157 8.71668 1.27649 8.55297 1.16741C8.38926 1.05833 8.19693 1.00012 8.00021 1.00012C7.80349 1.00012 7.61116 1.05833 7.44745 1.16741C7.28374 1.27649 7.15596 1.43157 7.08021 1.61312L5.62958 5.07312L1.91333 5.395C1.71711 5.41149 1.53011 5.48554 1.37581 5.60788C1.2215 5.73022 1.10675 5.89539 1.04594 6.08269C0.985134 6.26999 0.980978 6.47107 1.03399 6.66071C1.08701 6.85036 1.19484 7.02014 1.34396 7.14875L4.16333 9.6125L3.31833 13.2712C3.27365 13.4627 3.2864 13.6631 3.35499 13.8474C3.42358 14.0316 3.54496 14.1916 3.70396 14.3072C3.86296 14.4229 4.05252 14.4891 4.24894 14.4976C4.44537 14.5061 4.63994 14.4565 4.80833 14.355L7.99583 12.4175L11.1902 14.355C11.3586 14.4565 11.5532 14.5061 11.7496 14.4976C11.946 14.4891 12.1356 14.4229 12.2946 14.3072C12.4536 14.1916 12.575 14.0316 12.6436 13.8474C12.7121 13.6631 12.7249 13.4627 12.6802 13.2712L11.8358 9.60875L14.6546 7.14875C14.8037 7.01969 14.9113 6.84944 14.9639 6.65938C15.0165 6.46933 15.0117 6.26797 14.9502 6.08062ZM13.999 6.39312L11.1802 8.85312C11.043 8.97246 10.941 9.12694 10.885 9.29994C10.8291 9.47295 10.8214 9.65793 10.8627 9.835L11.7096 13.5L8.51771 11.5625C8.36198 11.4677 8.18317 11.4175 8.00083 11.4175C7.8185 11.4175 7.63969 11.4677 7.48396 11.5625L4.29646 13.5L5.13771 9.8375C5.17905 9.66043 5.17134 9.47545 5.11539 9.30244C5.05945 9.12944 4.9574 8.97496 4.82021 8.85562L2.00021 6.39687C1.99998 6.395 1.99998 6.39312 2.00021 6.39125L5.71521 6.07C5.89659 6.05401 6.07015 5.98881 6.21719 5.88142C6.36423 5.77403 6.47916 5.62853 6.54958 5.46062L8.00021 2.005L9.45021 5.46062C9.52063 5.62853 9.63556 5.77403 9.7826 5.88142C9.92964 5.98881 10.1032 6.05401 10.2846 6.07L14.0002 6.39125C14.0002 6.39125 14.0002 6.395 14.0002 6.39562L13.999 6.39312Z"
                                fill="#101010" />
                        </svg>
                    </div>
                    <p class="text-[20px] font-bold tracking-tight text-[#101010] leading-tight">{{ stats.rating }}</p>
                    <p class="mt-0.5 text-[12px] text-[#878787]">{{ stats.rating_sub }}</p>
                </div>

            </div>

            <!-- ── Main 2-col layout ───────────────────────── -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

                <!-- LEFT col -->
                <div class="xl:col-span-2 flex flex-col gap-5">

                    <!-- Tren Volume Penjualan -->
                    <div class="bg-white rounded-xl border border-[#EDEDED] shadow-sm p-5">
                        <h2 class="text-[18px] font-semibold text-[#101010] mb-4">Tren Volume Penjualan</h2>
                        <div class="h-56">
                            <Bar :data="barData" :options="barOptions" />
                        </div>
                    </div>

                    <!-- Riwayat Transaksi Penjualan -->
                    <div class="bg-white rounded-xl border border-[#EDEDED] shadow-sm">
                        <div class="px-5 pt-4 pb-3 border-b border-gray-100">
                            <h2 class="text-[18px] font-semibold text-[#101010] mb-3">Riwayat Transaksi Penjualan</h2>

                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex items-center gap-2">
                                    <select v-model="txPerPage"
                                        class="h-[45px] w-16 rounded-lg border border-gray-300 bg-white px-2 text-sm text-gray-700 focus:border-[#007C95] focus:ring-1 focus:ring-[#007C95] focus:outline-none">
                                        <option :value="5">5</option>
                                        <option :value="10">10</option>
                                        <option :value="25">25</option>
                                        <option :value="50">50</option>
                                    </select>
                                    <span class="text-sm text-gray-500">Entri per halaman</span>
                                </div>

                                <div class="flex items-center gap-2">
                                    <TableFilter :filters="txFilterFields" :model-value="txFilterValues"
                                        @update:model-value="v => { txFilterValues = v }"
                                        @reset="txFilterValues = {}" />
                                    <div class="relative flex-1 sm:flex-none">
                                        <Search class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-gray-400" />
                                        <input v-model="txSearch" type="text" placeholder="Cari..."
                                            class="h-[45px] w-full rounded-lg border border-gray-300 bg-white py-2 pr-3 pl-9 text-sm placeholder-gray-400 focus:border-[#007C95] focus:ring-1 focus:ring-[#007C95] focus:outline-none sm:w-56" />
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
                                            Gudang</th>
                                        <th
                                            class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 whitespace-nowrap">
                                            Volume (kg)</th>
                                        <th
                                            class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 whitespace-nowrap">
                                            Harga/kg</th>
                                        <th
                                            class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 whitespace-nowrap">
                                            Total</th>
                                        <th
                                            class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 whitespace-nowrap">
                                            Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr v-for="tx in pagedTx" :key="tx.id" class="hover:bg-gray-50/50 transition">
                                        <td class="px-4 py-3 whitespace-nowrap font-medium text-[#101010] text-[13px]">
                                            {{ tx.no_dokumen }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-500 text-[13px]">{{ tx.tanggal
                                        }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-600 text-[13px]">{{ tx.gudang
                                        }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-600 text-[13px]">{{
                                            tx.volume.toLocaleString('id-ID') }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-600 text-[13px]">{{
                                            formatRp(tx.harga) }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-gray-600 text-[13px]">{{
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
                                        <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-400">
                                            Belum ada transaksi
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
                                <span class="shrink-0 text-[12px] text-gray-400 whitespace-nowrap">
                                    {{ log.waktu }}
                                </span>
                            </div>
                            <div v-if="activityLogs.length === 0" class="py-6 text-center text-sm text-gray-400">
                                Belum ada aktivitas
                            </div>
                        </div>
                    </div>

                </div>

                <!-- RIGHT col -->
                <div class="flex flex-col gap-5">

                    <!-- Profil Buyer -->
                    <div class="bg-white rounded-xl border border-[#EDEDED] shadow-sm p-5">
                        <h2 class="text-[18px] font-semibold text-[#101010] mb-4">Profil Buyer</h2>

                        <!-- Avatar + nama -->
                        <div class="flex items-center gap-3 mb-4">
                            <img v-if="buyer.foto_url" :src="buyer.foto_url"
                                class="h-10 w-10 rounded-full object-cover border border-gray-200" />
                            <div v-else
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#007C95]/10 text-sm font-bold text-[#007C95]">
                                {{ buyer.inisials }}
                            </div>
                            <div>
                                <p class="text-[14px] font-semibold text-[#101010]">{{ buyer.nama }}</p>
                                <p class="text-[12px] text-gray-400">
                                    {{ buyer.kode }} · {{ buyer.city_name ?? '—' }}
                                </p>
                            </div>
                        </div>

                        <!-- Detail rows -->
                        <div class="flex flex-col gap-2.5 text-[13px]">
                            <div class="flex items-start justify-between gap-2">
                                <span class="text-gray-400 shrink-0">Telepon:</span>
                                <span class="text-right text-[#101010]">{{ buyer.telepon ?? '—' }}</span>
                            </div>
                            <div class="flex items-start justify-between gap-2">
                                <span class="text-gray-400 shrink-0">Email:</span>
                                <span class="text-right text-[#101010] break-all">{{ buyer.email ?? '—' }}</span>
                            </div>
                            <div class="flex items-start justify-between gap-2">
                                <span class="text-gray-400 shrink-0">Alamat:</span>
                                <span class="text-right text-[#101010]">{{ buyer.alamat ?? '—' }}</span>
                            </div>
                            <div class="flex items-start justify-between gap-2">
                                <span class="text-gray-400 shrink-0">PIC:</span>
                                <span class="text-right text-[#101010]">{{ buyer.pic ?? '—' }}</span>
                            </div>
                            <div class="flex items-start justify-between gap-2">
                                <span class="text-gray-400 shrink-0">Website:</span>
                                <span class="text-right text-[#101010] break-all">{{ buyer.website ?? '—' }}</span>
                            </div>
                            <div class="flex items-start justify-between gap-2">
                                <span class="text-gray-400 shrink-0">NPWP:</span>
                                <span class="text-right text-[#101010]">{{ buyer.npwp ?? '—' }}</span>
                            </div>
                            <div class="flex items-start justify-between gap-2">
                                <span class="text-gray-400 shrink-0">Harga Default:</span>
                                <span class="text-right text-[#101010]">
                                    {{ buyer.harga_jual_default
                                        ? 'Rp ' + buyer.harga_jual_default.toLocaleString('id-ID') + '/kg'
                                        : '—' }}
                                </span>
                            </div>
                            <div class="flex items-start justify-between gap-2">
                                <span class="text-gray-400 shrink-0">Limit Kredit:</span>
                                <span class="text-right text-[#101010]">
                                    {{ buyer.limit_kredit
                                        ? 'Rp ' + buyer.limit_kredit.toLocaleString('id-ID')
                                        : '—' }}
                                </span>
                            </div>
                            <div class="flex items-start justify-between gap-2">
                                <span class="text-gray-400 shrink-0">Termin Bayar:</span>
                                <span class="text-right text-[#101010]">
                                    {{ buyer.termin_hari ? buyer.termin_hari + ' hari' : '—' }}
                                </span>
                            </div>
                            <div v-if="buyer.catatan" class="flex items-start justify-between gap-2">
                                <span class="text-gray-400 shrink-0">Catatan:</span>
                                <span class="text-right text-[#101010]">{{ buyer.catatan }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Ringkasan Piutang -->
                    <div class="bg-white rounded-xl border border-[#EDEDED] shadow-sm p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-[18px] font-semibold text-[#101010]">Ringkasan Piutang</h2>
                            <button
                                class="rounded-lg border border-gray-200 px-3 py-1 text-xs font-medium text-gray-600 hover:bg-gray-50 transition">
                                Terima
                            </button>
                        </div>

                        <div class="flex flex-col gap-2 text-[13px]">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-400">Total Penjualan:</span>
                                <span class="font-semibold text-[#101010]">{{ ringkasanPiutang.total_penjualan }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-400">Sudah Diterima:</span>
                                <span class="font-semibold text-[#101010]">{{ ringkasanPiutang.sudah_diterima }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-400">Piutang Aktif:</span>
                                <span class="font-semibold" :class="ringkasanPiutang.piutang_aktif === 'Rp 0'
                                    ? 'text-emerald-500'
                                    : 'text-red-500'">
                                    {{ ringkasanPiutang.piutang_aktif }}
                                </span>
                            </div>
                        </div>

                        <!-- Progress bar -->
                        <div class="mt-3">
                            <div class="h-1.5 w-full rounded-full bg-gray-100">
                                <div class="h-1.5 rounded-full bg-[#007C95] transition-all"
                                    :style="{ width: ringkasanPiutang.persen_diterima + '%' }" />
                            </div>
                            <p class="mt-1.5 text-[11px] text-gray-400">
                                {{ ringkasanPiutang.persen_diterima }}% dari total penjualan belum diterima
                            </p>
                        </div>
                    </div>

                    <!-- Produk yang Dibeli -->
                    <div class="bg-white rounded-xl border border-[#EDEDED] shadow-sm p-5">
                        <h2 class="text-[18px] font-semibold text-[#101010] mb-4">Produk yang Dibeli</h2>
                        <div class="flex flex-col gap-2.5">
                            <div v-for="p in produkDibeli" :key="p.nama">
                                <div class="relative h-7 w-full overflow-hidden rounded-lg bg-[#F9F9F9]">
                                    <div class="absolute inset-y-0 left-0 rounded-lg bg-[#AAEADB] transition-all"
                                        :style="{ width: p.persen + '%' }" />
                                    <span
                                        class="absolute inset-0 flex items-center justify-center text-[13px] font-medium text-[#101010]">
                                        {{ p.nama }} ({{ p.persen }}%)
                                    </span>
                                </div>
                            </div>
                            <div v-if="produkDibeli.length === 0" class="text-sm text-gray-400 text-center py-2">
                                Belum ada data
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>

        <BuyerFormModal v-model:open="isEditOpen" :editing-buyer="(buyer as any)" :cities="allCities ?? []"
            :post-url="`/master-data/buyer`" @success="router.reload()" />

        <BuyerStatusModal v-model:open="isStatusOpen" :buyer="(buyer as any)" toggle-url="/master-data/buyer"
            @success="router.reload()" />

    </AppLayout>
</template>