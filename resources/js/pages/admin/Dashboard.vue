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


        </div>
    </AppLayout>
</template>