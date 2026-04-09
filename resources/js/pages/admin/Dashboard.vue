<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Users, UserCheck, Clock, XCircle, TrendingUp } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Line, Bar, Doughnut } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    ArcElement,
    Title,
    Tooltip,
    Legend,
    Filler,
} from 'chart.js';

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    ArcElement,
    Title,
    Tooltip,
    Legend,
    Filler,
);

const props = defineProps<{
    userStats: { total: number; active: number; pending: number; rejected: number };
    userGrowth: { label: string; total: number }[];
    verificationStatus: { label: string; value: number; color: string }[];
    transactionChart: { label: string; pengambilan: number; transfer: number }[];
    recentUsers: { id: number; name: string; email: string; role: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];

const statCards = computed(() => [
    { label: 'Total User', value: props.userStats.total, icon: Users, color: 'text-[#007C95]', bg: 'bg-[#007C95]/10' },
    { label: 'User Aktif', value: props.userStats.active, icon: UserCheck, color: 'text-green-600', bg: 'bg-green-50' },
    { label: 'Menunggu Verifikasi', value: props.userStats.pending, icon: Clock, color: 'text-amber-500', bg: 'bg-amber-50' },
    { label: 'Ditolak', value: props.userStats.rejected, icon: XCircle, color: 'text-red-500', bg: 'bg-red-50' },
]);

// ── User Growth Line Chart ─────────────────────────────────
const lineChartData = computed(() => ({
    labels: props.userGrowth.map(d => d.label),
    datasets: [
        {
            label: 'User Baru',
            data: props.userGrowth.map(d => d.total),
            borderColor: '#007C95',
            backgroundColor: 'rgba(0, 124, 149, 0.08)',
            borderWidth: 2,
            pointRadius: 3,
            pointBackgroundColor: '#007C95',
            fill: true,
            tension: 0.4,
        },
    ],
}));

const lineChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: { mode: 'index' as const, intersect: false },
    },
    scales: {
        x: { grid: { color: '#f0f0f0' }, ticks: { font: { size: 10 } } },
        y: { grid: { color: '#f0f0f0' }, ticks: { font: { size: 10 }, stepSize: 1 } },
    },
};

// ── Verification Status Doughnut ───────────────────────────
const doughnutData = computed(() => ({
    labels: props.verificationStatus.map(d => d.label),
    datasets: [
        {
            data: props.verificationStatus.map(d => d.value),
            backgroundColor: props.verificationStatus.map(d => d.color),
            borderWidth: 2,
            borderColor: '#ffffff',
        },
    ],
}));

const doughnutOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '65%',
    plugins: {
        legend: { display: false },
        tooltip: {
            callbacks: {
                label: (ctx: any) => ` ${ctx.label}: ${ctx.raw}`,
            },
        },
    },
};

// ── Transaction Bar Chart ──────────────────────────────────
const barChartData = computed(() => ({
    labels: props.transactionChart.map(d => d.label),
    datasets: [
        {
            label: 'Pengambilan POO',
            data: props.transactionChart.map(d => d.pengambilan),
            backgroundColor: '#007C95',
            borderRadius: 4,
        },
        {
            label: 'Transfer UCO',
            data: props.transactionChart.map(d => d.transfer),
            backgroundColor: '#34d399',
            borderRadius: 4,
        },
    ],
}));

const barChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom' as const,
            labels: { font: { size: 11 }, boxWidth: 12, padding: 16 },
        },
        tooltip: { mode: 'index' as const, intersect: false },
    },
    scales: {
        x: { grid: { display: false }, ticks: { font: { size: 10 } } },
        y: { grid: { color: '#f0f0f0' }, ticks: { font: { size: 10 } } },
    },
};

const goToPending = () => router.get('/management-user', { status: 'pending' });
</script>

<template>

    <Head title="Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">

            <!-- Page Title -->
            <div>
                <h1 class="text-lg font-bold text-gray-900">Dashboard Admin</h1>
                <p class="text-sm text-gray-500">Ringkasan data sistem hari ini</p>
            </div>

            <!-- Stat Cards -->
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <div v-for="card in statCards" :key="card.label"
                    class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-gray-400">{{ card.label }}</p>
                            <p class="mt-1 text-2xl font-bold text-gray-800">{{ card.value }}</p>
                        </div>
                        <div :class="['flex h-10 w-10 items-center justify-center rounded-xl', card.bg]">
                            <component :is="card.icon" :class="['h-5 w-5', card.color]" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row 2: User Growth + Verification Status -->
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">

                <!-- User Growth Line Chart -->
                <div class="lg:col-span-2 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                    <div class="mb-4 flex items-center gap-2">
                        <TrendingUp class="h-4 w-4 text-[#007C95]" />
                        <h2 class="text-sm font-semibold text-gray-700">Pertumbuhan User</h2>
                        <span class="text-xs text-gray-400">(12 bulan terakhir)</span>
                    </div>
                    <div class="h-[220px]">
                        <Line :data="lineChartData" :options="lineChartOptions" />
                    </div>
                </div>

                <!-- Verification Status Doughnut -->
                <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                    <h2 class="mb-4 text-sm font-semibold text-gray-700">Status Verifikasi</h2>
                    <div class="h-[180px]">
                        <Doughnut :data="doughnutData" :options="doughnutOptions" />
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-1.5">
                        <div v-for="item in verificationStatus" :key="item.label" class="flex items-center gap-1.5">
                            <span class="h-2.5 w-2.5 shrink-0 rounded-full" :style="{ backgroundColor: item.color }" />
                            <span class="text-[11px] text-gray-500">{{ item.label }}</span>
                            <span class="ml-auto text-[11px] font-semibold text-gray-700">{{ item.value }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row 3: Transaksi Chart + Pending Users -->
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">

                <!-- Transaksi Bar Chart -->
                <div class="lg:col-span-2 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                    <h2 class="mb-4 text-sm font-semibold text-gray-700">
                        Volume Transaksi UCO
                        <span class="font-normal text-gray-400">(liter)</span>
                    </h2>
                    <div class="h-[220px]">
                        <Bar :data="barChartData" :options="barChartOptions" />
                    </div>
                </div>

                <!-- Pending Users -->
                <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm flex flex-col">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-gray-700">Menunggu Verifikasi</h2>
                        <button @click="goToPending" class="text-xs text-[#007C95] hover:underline">Lihat semua</button>
                    </div>

                    <div v-if="recentUsers.length" class="flex flex-col gap-2 flex-1">
                        <div v-for="user in recentUsers" :key="user.id"
                            class="flex items-center gap-3 rounded-xl bg-gray-50 px-3 py-2.5">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center
                                        rounded-full bg-[#007C95]/10 text-xs font-semibold text-[#007C95]">
                                {{ user.name.slice(0, 2).toUpperCase() }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-xs font-medium text-gray-800">{{ user.name }}</p>
                                <p class="truncate text-[10px] text-gray-400">{{ user.email }}</p>
                            </div>
                            <span class="shrink-0 rounded-full bg-amber-100 px-2 py-0.5
                                         text-[10px] font-medium text-amber-600">
                                {{ user.role }}
                            </span>
                        </div>
                    </div>

                    <div v-else class="flex flex-1 items-center justify-center">
                        <p class="text-xs text-gray-400">Tidak ada user pending</p>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>