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
                <div v-for="card in statCards" :key="card.label"
                    class="bg-white rounded-xl border border-[#EDEDED] shadow-sm p-4 flex flex-col">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[13px] text-[#878787] font-medium">{{ card.label }}</span>
                        <!-- Star icon khusus untuk rating -->
                        <span v-if="card.iconType === 'star'" class="text-amber-400">★</span>
                    </div>
                    <p class="text-[20px] font-bold tracking-tight text-[#101010] leading-tight">
                        {{ card.value }}
                        <span v-if="card.iconType === 'star'" class="text-amber-400 text-base">★</span>
                    </p>
                    <p class="mt-0.5 text-[12px] text-[#878787]">{{ card.sub }}</p>
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