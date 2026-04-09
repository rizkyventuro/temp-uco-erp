<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Bar, Doughnut } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    ArcElement,
    Title,
    Tooltip,
    Legend,
} from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, BarElement, ArcElement, Title, Tooltip, Legend);

// ── Props ──────────────────────────────────────────────────
const props = defineProps<{
    totalUcoMasuk: { value: string; trend: string; up: boolean | null };
    totalPembelian: { value: string; trend: string; up: boolean | null };
    totalUtangAP: { value: string; trend: string; up: boolean | null };
    supplierAktif: { value: string; trend: string; up: boolean | null };
    volumeChart: { label: string; pembelian: number; penjualan: number }[];
    distribusiStok: { label: string; value: number; color: string }[];
    recentTransactions: { id: string; tanggal: string; supplier: string; volume: string; gudang: string; status: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];

// ── Stat cards ─────────────────────────────────────────────
const statCards = computed(() => [
    {
        label: 'Total UCO Masuk',
        value: props.totalUcoMasuk.value,
        trend: props.totalUcoMasuk.trend,
        up: props.totalUcoMasuk.up,
        iconPath: 'M3 6h18M3 12h18M3 18h18',           // list / box icon approximation
        iconType: 'box',
    },
    {
        label: 'Total Pembelian',
        value: props.totalPembelian.value,
        trend: props.totalPembelian.trend,
        up: props.totalPembelian.up,
        iconType: 'dollar',
    },
    {
        label: 'Total Utang (AP)',
        value: props.totalUtangAP.value,
        trend: props.totalUtangAP.trend,
        up: props.totalUtangAP.up,
        iconType: 'credit',
        danger: true,
    },
    {
        label: 'Supplier Aktif',
        value: props.supplierAktif.value,
        trend: props.supplierAktif.trend,
        up: props.supplierAktif.up,
        iconType: 'truck',
    },
]);

// ── Bar chart ──────────────────────────────────────────────
const barData = computed(() => ({
    labels: props.volumeChart.map(d => d.label),
    datasets: [
        {
            label: 'Pembelian (kg)',
            data: props.volumeChart.map(d => d.pembelian),
            backgroundColor: '#22d3a5',
            borderRadius: 3,
            barPercentage: 0.6,
        },
        {
            label: 'Penjualan (kg)',
            data: props.volumeChart.map(d => d.penjualan),
            backgroundColor: '#a78bfa',
            borderRadius: 3,
            barPercentage: 0.6,
        },
    ],
}));

const barOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'top' as const,
            align: 'end' as const,
            labels: { font: { size: 11 }, boxWidth: 10, padding: 12 },
        },
        tooltip: { mode: 'index' as const, intersect: false },
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

// ── Doughnut ───────────────────────────────────────────────
const doughnutData = computed(() => ({
    labels: props.distribusiStok.map(d => d.label),
    datasets: [
        {
            data: props.distribusiStok.map(d => d.value),
            backgroundColor: props.distribusiStok.map(d => d.color),
            borderWidth: 2,
            borderColor: '#ffffff',
        },
    ],
}));

const doughnutOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            callbacks: { label: (ctx: any) => ` ${ctx.label}: ${ctx.raw}%` },
        },
    },
};
</script>

<template>

    <Head title="Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6 bg-gray-50 min-h-screen">

            <!-- Page Title -->
            <div>
                <h1 class="text-xl font-bold text-gray-900">Dashboard</h1>
                <p class="text-sm text-gray-400">Gambaran sistem manajemen UCO</p>
            </div>

            <!-- ── Stat Cards ──────────────────────────────── -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                <div v-for="card in statCards" :key="card.label"
                    class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 flex flex-col gap-3">
                    <!-- header row -->
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500 font-medium">{{ card.label }}</span>

                        <!-- icon -->
                        <span class="text-gray-300">
                            <!-- box / UCO -->
                            <template v-if="card.iconType === 'box'">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.6">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M20 7l-8-4-8 4m16 0v10l-8 4m0-14L4 17m8-4v4" />
                                </svg>
                            </template>
                            <!-- dollar -->
                            <template v-else-if="card.iconType === 'dollar'">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.6">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6" />
                                </svg>
                            </template>
                            <!-- credit card -->
                            <template v-else-if="card.iconType === 'credit'">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.6">
                                    <rect x="2" y="5" width="20" height="14" rx="2" stroke="currentColor" />
                                    <path stroke-linecap="round" d="M2 10h20" />
                                </svg>
                            </template>
                            <!-- truck -->
                            <template v-else>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.6">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M1 3h15v13H1zM16 8h4l3 3v5h-7V8zM5.5 19a1.5 1.5 0 100-3 1.5 1.5 0 000 3zM18.5 19a1.5 1.5 0 100-3 1.5 1.5 0 000 3z" />
                                </svg>
                            </template>
                        </span>
                    </div>

                    <!-- value -->
                    <p class="text-2xl font-bold tracking-tight"
                        :class="card.danger ? 'text-red-500' : 'text-gray-900'">
                        {{ card.value }}
                    </p>

                    <!-- trend -->
                    <div class="flex items-center gap-1 text-xs font-medium" :class="{
                        'text-emerald-500': card.up === true,
                        'text-red-400': card.up === false,
                        'text-gray-400': card.up === null,
                    }">
                        <svg v-if="card.up === true" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5"
                            viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z"
                                clip-rule="evenodd" />
                        </svg>
                        <svg v-else-if="card.up === false" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5"
                            viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z"
                                clip-rule="evenodd" />
                        </svg>
                        {{ card.trend }}
                    </div>
                </div>
            </div>

            <!-- ── Charts Row ──────────────────────────────── -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">

                <!-- Bar Chart: Volume -->
                <div class="xl:col-span-2 bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                    <h2 class="text-sm font-semibold text-gray-800 mb-4">Volume Pembelian &amp; Penjualan</h2>
                    <div class="h-64">
                        <Bar :data="barData" :options="barOptions" />
                    </div>
                </div>

                <!-- Doughnut: Distribusi Stok -->
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 flex flex-col">
                    <h2 class="text-sm font-semibold text-gray-800 mb-4">Distribusi Stok</h2>
                    <div class="flex-1 flex items-center justify-center">
                        <div class="h-52 w-52">
                            <Doughnut :data="doughnutData" :options="doughnutOptions" />
                        </div>
                    </div>
                    <!-- legend -->
                    <div class="mt-4 grid grid-cols-2 gap-x-4 gap-y-2">
                        <div v-for="item in distribusiStok" :key="item.label"
                            class="flex items-center gap-2 text-xs text-gray-600">
                            <span class="h-2.5 w-2.5 rounded-full shrink-0" :style="{ background: item.color }" />
                            {{ item.label }}
                            <span class="ml-auto text-gray-400 font-medium">{{ item.value }}%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Recent Transactions ─────────────────────── -->
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold text-gray-800">Transaksi Terakhir</h2>
                    <button
                        class="text-xs font-semibold bg-gray-900 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors"
                        @click="router.get('/transactions')">
                        Lihat Semua
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th v-for="col in ['Transaksi', 'Tanggal', 'Supplier', 'Volume', 'Gudang', 'Status']"
                                    :key="col"
                                    class="text-left text-xs font-semibold text-gray-400 pb-3 pr-4 whitespace-nowrap">
                                    <div class="flex items-center gap-1">
                                        {{ col }}
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-gray-300"
                                            viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M5 12l5-5 5 5H5z" />
                                        </svg>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="tx in recentTransactions" :key="tx.id"
                                class="border-b border-gray-50 hover:bg-gray-50/60 transition-colors">
                                <td class="py-3 pr-4 font-medium text-gray-700 whitespace-nowrap">{{ tx.id }}</td>
                                <td class="py-3 pr-4 text-gray-500 whitespace-nowrap">{{ tx.tanggal }}</td>
                                <td class="py-3 pr-4 text-gray-700 whitespace-nowrap">{{ tx.supplier }}</td>
                                <td class="py-3 pr-4 text-gray-500 whitespace-nowrap">{{ tx.volume }}</td>
                                <td class="py-3 pr-4 text-gray-500 whitespace-nowrap">{{ tx.gudang }}</td>
                                <td class="py-3 pr-4 whitespace-nowrap">
                                    <span class="text-xs font-semibold" :class="{
                                        'text-emerald-500': tx.status === 'Lunas',
                                        'text-amber-500': tx.status === 'Pending',
                                        'text-red-400': tx.status === 'Belum Lunas',
                                    }">{{ tx.status }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </AppLayout>
</template>