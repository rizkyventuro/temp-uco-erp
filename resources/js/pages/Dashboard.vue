<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Bar, Pie } from 'vue-chartjs';
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
import ChartDataLabels from 'chartjs-plugin-datalabels';

ChartJS.register(CategoryScale, LinearScale, BarElement, ArcElement, Title, Tooltip, Legend, ChartDataLabels);

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
            backgroundColor: '#007C95',
            borderRadius: 3,
            barPercentage: 0.6,
        },
        {
            label: 'Penjualan (kg)',
            data: props.volumeChart.map(d => d.penjualan),
            backgroundColor: '#7239EA',
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
        // Nonaktifkan datalabels di bar chart agar angka tidak muncul di sini
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

// ── Pie chart ──────────────────────────────────────────────
const pieData = computed(() => ({
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

const pieOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            callbacks: { label: (ctx: any) => ` ${ctx.label}: ${ctx.raw}%` },
        },
        // Tampilkan angka % di dalam setiap slice pie
        datalabels: {
            color: '#ffffff',
            font: { size: 13, weight: 'bold' as const },
            formatter: (value: number) => value + '%',
        },
    },
};
</script>

<template>

    <Head title="Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6 bg-gray-50 pb-10">

            <!-- Page Title -->
            <div>
                <h1 class="text-[24px] font-bold text-[#101010]">Dashboard</h1>
                <p class="text-[16px] text-[#878787]">Gambaran sistem manajemen UCO</p>
            </div>

            <!-- ── Stat Cards ──────────────────────────────── -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                <div v-for="card in statCards" :key="card.label"
                    class="bg-white rounded-xl border border-[#EDEDED] shadow-sm p-5 flex flex-col">
                    <!-- header row -->
                    <div class="flex items-center justify-between">
                        <span class="text-[14px] text-[#101010] font-medium mb-3">{{ card.label }}</span>

                        <!-- icon -->
                        <span class="text-gray-300">
                            <!-- box / UCO -->
                            <template v-if="card.iconType === 'box'">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.2"
                                        d="M8 8.06811V14.5C7.91602 14.4996 7.83348 14.4781 7.76 14.4375L2.26 11.4275C2.18147 11.3845 2.11591 11.3212 2.07017 11.2443C2.02444 11.1673 2.0002 11.0795 2 10.99V5.01248C2.00002 4.94203 2.01493 4.87239 2.04375 4.80811L8 8.06811Z"
                                        fill="#50CD89" />
                                    <path
                                        d="M13.98 4.13439L8.48 1.12501C8.33305 1.04381 8.16789 1.00122 8 1.00122C7.83211 1.00122 7.66695 1.04381 7.52 1.12501L2.02 4.13564C1.86293 4.22158 1.73181 4.34811 1.64034 4.50203C1.54888 4.65594 1.50041 4.83159 1.5 5.01064V10.9881C1.50041 11.1672 1.54888 11.3428 1.64034 11.4967C1.73181 11.6507 1.86293 11.7772 2.02 11.8631L7.52 14.8738C7.66695 14.955 7.83211 14.9976 8 14.9976C8.16789 14.9976 8.33305 14.955 8.48 14.8738L13.98 11.8631C14.1371 11.7772 14.2682 11.6507 14.3597 11.4967C14.4511 11.3428 14.4996 11.1672 14.5 10.9881V5.01126C14.4999 4.8319 14.4516 4.65586 14.3601 4.50158C14.2686 4.34731 14.1373 4.22048 13.98 4.13439ZM8 2.00001L13.0212 4.75001L8 7.50001L2.97875 4.75001L8 2.00001ZM2.5 5.62501L7.5 8.36126V13.7231L2.5 10.9888V5.62501ZM8.5 13.7231V8.36376L13.5 5.62501V10.9863L8.5 13.7231Z"
                                        fill="#101010" />
                                </svg>
                            </template>
                            <!-- dollar -->
                            <template v-else-if="card.iconType === 'dollar'">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.2"
                                        d="M12 10.5C12 10.8283 11.9353 11.1534 11.8097 11.4567C11.6841 11.76 11.4999 12.0356 11.2678 12.2678C11.0356 12.4999 10.76 12.6841 10.4567 12.8097C10.1534 12.9353 9.8283 13 9.5 13H8V8H9.5C9.8283 8 10.1534 8.06466 10.4567 8.1903C10.76 8.31594 11.0356 8.50009 11.2678 8.73223C11.4999 8.96438 11.6841 9.23998 11.8097 9.54329C11.9353 9.84661 12 10.1717 12 10.5ZM7 3C6.33696 3 5.70107 3.26339 5.23223 3.73223C4.76339 4.20107 4.5 4.83696 4.5 5.5C4.5 6.16304 4.76339 6.79893 5.23223 7.26777C5.70107 7.73661 6.33696 8 7 8H8V3H7Z"
                                        fill="#50CD89" />
                                    <path
                                        d="M12.5 10.5C12.4992 11.2954 12.1828 12.058 11.6204 12.6204C11.058 13.1828 10.2954 13.4992 9.5 13.5H8.5V14.5C8.5 14.6326 8.44732 14.7598 8.35355 14.8536C8.25979 14.9473 8.13261 15 8 15C7.86739 15 7.74021 14.9473 7.64645 14.8536C7.55268 14.7598 7.5 14.6326 7.5 14.5V13.5H6.5C5.7046 13.4992 4.94202 13.1828 4.37959 12.6204C3.81716 12.058 3.50083 11.2954 3.5 10.5C3.5 10.3674 3.55268 10.2402 3.64645 10.1464C3.74021 10.0527 3.86739 10 4 10C4.13261 10 4.25979 10.0527 4.35355 10.1464C4.44732 10.2402 4.5 10.3674 4.5 10.5C4.5 11.0304 4.71071 11.5391 5.08579 11.9142C5.46086 12.2893 5.96957 12.5 6.5 12.5H9.5C10.0304 12.5 10.5391 12.2893 10.9142 11.9142C11.2893 11.5391 11.5 11.0304 11.5 10.5C11.5 9.96957 11.2893 9.46086 10.9142 9.08579C10.5391 8.71071 10.0304 8.5 9.5 8.5H7C6.20435 8.5 5.44129 8.18393 4.87868 7.62132C4.31607 7.05871 4 6.29565 4 5.5C4 4.70435 4.31607 3.94129 4.87868 3.37868C5.44129 2.81607 6.20435 2.5 7 2.5H7.5V1.5C7.5 1.36739 7.55268 1.24021 7.64645 1.14645C7.74021 1.05268 7.86739 1 8 1C8.13261 1 8.25979 1.05268 8.35355 1.14645C8.44732 1.24021 8.5 1.36739 8.5 1.5V2.5H9C9.7954 2.50083 10.558 2.81716 11.1204 3.37959C11.6828 3.94202 11.9992 4.7046 12 5.5C12 5.63261 11.9473 5.75979 11.8536 5.85355C11.7598 5.94732 11.6326 6 11.5 6C11.3674 6 11.2402 5.94732 11.1464 5.85355C11.0527 5.75979 11 5.63261 11 5.5C11 4.96957 10.7893 4.46086 10.4142 4.08579C10.0391 3.71071 9.53043 3.5 9 3.5H7C6.46957 3.5 5.96086 3.71071 5.58579 4.08579C5.21071 4.46086 5 4.96957 5 5.5C5 6.03043 5.21071 6.53914 5.58579 6.91421C5.96086 7.28929 6.46957 7.5 7 7.5H9.5C10.2954 7.50083 11.058 7.81716 11.6204 8.37959C12.1828 8.94202 12.4992 9.7046 12.5 10.5Z"
                                        fill="#101010" />
                                </svg>
                            </template>
                            <!-- credit card -->
                            <template v-else-if="card.iconType === 'credit'">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.2"
                                        d="M14.5 6V12C14.5 12.1326 14.4473 12.2598 14.3536 12.3536C14.2598 12.4473 14.1326 12.5 14 12.5H2C1.86739 12.5 1.74021 12.4473 1.64645 12.3536C1.55268 12.2598 1.5 12.1326 1.5 12V6H14.5Z"
                                        fill="#F14141" />
                                    <path
                                        d="M14 3H2C1.73478 3 1.48043 3.10536 1.29289 3.29289C1.10536 3.48043 1 3.73478 1 4V12C1 12.2652 1.10536 12.5196 1.29289 12.7071C1.48043 12.8946 1.73478 13 2 13H14C14.2652 13 14.5196 12.8946 14.7071 12.7071C14.8946 12.5196 15 12.2652 15 12V4C15 3.73478 14.8946 3.48043 14.7071 3.29289C14.5196 3.10536 14.2652 3 14 3ZM14 4V5.5H2V4H14ZM14 12H2V6.5H14V12ZM13 10.5C13 10.6326 12.9473 10.7598 12.8536 10.8536C12.7598 10.9473 12.6326 11 12.5 11H10.5C10.3674 11 10.2402 10.9473 10.1464 10.8536C10.0527 10.7598 10 10.6326 10 10.5C10 10.3674 10.0527 10.2402 10.1464 10.1464C10.2402 10.0527 10.3674 10 10.5 10H12.5C12.6326 10 12.7598 10.0527 12.8536 10.1464C12.9473 10.2402 13 10.3674 13 10.5ZM9 10.5C9 10.6326 8.94732 10.7598 8.85355 10.8536C8.75979 10.9473 8.63261 11 8.5 11H7.5C7.36739 11 7.24021 10.9473 7.14645 10.8536C7.05268 10.7598 7 10.6326 7 10.5C7 10.3674 7.05268 10.2402 7.14645 10.1464C7.24021 10.0527 7.36739 10 7.5 10H8.5C8.63261 10 8.75979 10.0527 8.85355 10.1464C8.94732 10.2402 9 10.3674 9 10.5Z"
                                        fill="#101010" />
                                </svg>
                            </template>
                            <!-- truck -->
                            <template v-else>
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.2"
                                        d="M15 7.5V11.5C15 11.6326 14.9473 11.7598 14.8536 11.8536C14.7598 11.9473 14.6326 12 14.5 12H13C13 11.76 12.9425 11.5235 12.8322 11.3104C12.7219 11.0973 12.562 10.9137 12.3661 10.7751C12.1702 10.6366 11.9438 10.547 11.7061 10.5141C11.4684 10.4811 11.2263 10.5056 11 10.5856C10.7075 10.689 10.4543 10.8806 10.2752 11.1339C10.0961 11.3872 9.99997 11.6898 10 12H6C6 11.6022 5.84196 11.2206 5.56066 10.9393C5.27936 10.658 4.89782 10.5 4.5 10.5C4.10218 10.5 3.72064 10.658 3.43934 10.9393C3.15804 11.2206 3 11.6022 3 12H1.5C1.36739 12 1.24021 11.9473 1.14645 11.8536C1.05268 11.7598 1 11.6326 1 11.5V9H11V7.5H15Z"
                                        fill="#50CD89" />
                                    <path
                                        d="M15.4637 7.3125L14.5887 5.125C14.5145 4.93992 14.3864 4.78139 14.2211 4.66996C14.0557 4.55852 13.8607 4.49931 13.6613 4.5H11.5V4C11.5 3.86739 11.4473 3.74021 11.3536 3.64645C11.2598 3.55268 11.1326 3.5 11 3.5H1.5C1.23478 3.5 0.98043 3.60536 0.792893 3.79289C0.605357 3.98043 0.5 4.23478 0.5 4.5V11.5C0.5 11.7652 0.605357 12.0196 0.792893 12.2071C0.98043 12.3946 1.23478 12.5 1.5 12.5H2.5625C2.67265 12.9302 2.92285 13.3115 3.27366 13.5838C3.62446 13.8561 4.05591 14.0039 4.5 14.0039C4.94409 14.0039 5.37554 13.8561 5.72635 13.5838C6.07715 13.3115 6.32735 12.9302 6.4375 12.5H9.5625C9.67265 12.9302 9.92285 13.3115 10.2737 13.5838C10.6245 13.8561 11.0559 14.0039 11.5 14.0039C11.9441 14.0039 12.3755 13.8561 12.7263 13.5838C13.0771 13.3115 13.3273 12.9302 13.4375 12.5H14.5C14.7652 12.5 15.0196 12.3946 15.2071 12.2071C15.3946 12.0196 15.5 11.7652 15.5 11.5V7.5C15.5002 7.43574 15.4879 7.37206 15.4637 7.3125ZM11.5 5.5H13.6613L14.2612 7H11.5V5.5ZM1.5 4.5H10.5V8.5H1.5V4.5ZM4.5 13C4.30222 13 4.10888 12.9414 3.94443 12.8315C3.77998 12.7216 3.65181 12.5654 3.57612 12.3827C3.50043 12.2 3.48063 11.9989 3.51921 11.8049C3.5578 11.6109 3.65304 11.4327 3.79289 11.2929C3.93275 11.153 4.11093 11.0578 4.30491 11.0192C4.49889 10.9806 4.69996 11.0004 4.88268 11.0761C5.06541 11.1518 5.22159 11.28 5.33147 11.4444C5.44135 11.6089 5.5 11.8022 5.5 12C5.5 12.2652 5.39464 12.5196 5.20711 12.7071C5.01957 12.8946 4.76522 13 4.5 13ZM9.5625 11.5H6.4375C6.32735 11.0698 6.07715 10.6885 5.72635 10.4162C5.37554 10.1439 4.94409 9.99608 4.5 9.99608C4.05591 9.99608 3.62446 10.1439 3.27366 10.4162C2.92285 10.6885 2.67265 11.0698 2.5625 11.5H1.5V9.5H10.5V10.2694C10.2701 10.4023 10.0688 10.5795 9.9079 10.7907C9.74697 11.002 9.62957 11.243 9.5625 11.5ZM11.5 13C11.3022 13 11.1089 12.9414 10.9444 12.8315C10.78 12.7216 10.6518 12.5654 10.5761 12.3827C10.5004 12.2 10.4806 11.9989 10.5192 11.8049C10.5578 11.6109 10.653 11.4327 10.7929 11.2929C10.9327 11.153 11.1109 11.0578 11.3049 11.0192C11.4989 10.9806 11.7 11.0004 11.8827 11.0761C12.0654 11.1518 12.2216 11.28 12.3315 11.4444C12.4414 11.6089 12.5 11.8022 12.5 12C12.5 12.2652 12.3946 12.5196 12.2071 12.7071C12.0196 12.8946 11.7652 13 11.5 13ZM14.5 11.5H13.4375C13.326 11.0708 13.0754 10.6908 12.7247 10.4193C12.3741 10.1479 11.9434 10.0004 11.5 10V8H14.5V11.5Z"
                                        fill="#101010" />
                                </svg>
                            </template>
                        </span>
                    </div>

                    <!-- value -->
                    <p class="text-[24px] font-bold tracking-tight"
                        :class="card.danger ? 'text-[#F14141]' : 'text-[#101010]'">
                        {{ card.value }}
                    </p>

                    <!-- trend — FIX: fill="currentColor" (bukan fill="#currentColor"), ukuran seragam 16x16 -->
                    <div class="flex items-center gap-1 text-xs font-medium" :class="{
                        'text-emerald-500': card.up === true,
                        'text-red-400': card.up === false,
                        'text-gray-400': card.up === null,
                    }">
                        <svg v-if="card.up === true" width="16" height="16" viewBox="0 0 16 16" fill="none"
                            xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M15 3.5V7.5C15 7.63261 14.9473 7.75979 14.8536 7.85355C14.7598 7.94732 14.6326 8 14.5 8C14.3674 8 14.2402 7.94732 14.1465 7.85355C14.0527 7.75979 14 7.63261 14 7.5V4.70687L8.85375 9.85375C8.80732 9.90024 8.75217 9.93712 8.69147 9.96228C8.63077 9.98744 8.56571 10.0004 8.5 10.0004C8.4343 10.0004 8.36923 9.98744 8.30853 9.96228C8.24783 9.93712 8.19269 9.90024 8.14625 9.85375L6 7.70687L1.85375 11.8538C1.75993 11.9476 1.63269 12.0003 1.5 12.0003C1.36732 12.0003 1.24007 11.9476 1.14625 11.8538C1.05243 11.7599 0.999725 11.6327 0.999725 11.5C0.999725 11.3673 1.05243 11.2401 1.14625 11.1462L5.64625 6.64625C5.69269 6.59976 5.74783 6.56288 5.80853 6.53772C5.86923 6.51256 5.9343 6.49961 6 6.49961C6.06571 6.49961 6.13077 6.51256 6.19147 6.53772C6.25217 6.56288 6.30732 6.59976 6.35375 6.64625L8.5 8.79313L13.2931 4H10.5C10.3674 4 10.2402 3.94732 10.1465 3.85355C10.0527 3.75979 10 3.63261 10 3.5C10 3.36739 10.0527 3.24021 10.1465 3.14645C10.2402 3.05268 10.3674 3 10.5 3H14.5C14.6326 3 14.7598 3.05268 14.8536 3.14645C14.9473 3.24021 15 3.36739 15 3.5Z"
                                fill="currentColor" />
                        </svg>

                        <svg v-else-if="card.up === false" width="16" height="16" viewBox="0 0 16 16" fill="none"
                            xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M15 12.5V8.5C15 8.36739 14.9473 8.24021 14.8536 8.14645C14.7598 8.05268 14.6326 8 14.5 8C14.3674 8 14.2402 8.05268 14.1465 8.14645C14.0527 8.24021 14 8.36739 14 8.5V11.2931L8.85375 6.14625C8.80732 6.09976 8.75217 6.06288 8.69147 6.03772C8.63077 6.01256 8.56571 5.99961 8.5 5.99961C8.4343 5.99961 8.36923 6.01256 8.30853 6.03772C8.24783 6.06288 8.19269 6.09976 8.14625 6.14625L6 8.29313L1.85375 4.14625C1.75993 4.05243 1.63269 3.99973 1.5 3.99973C1.36732 3.99973 1.24007 4.05243 1.14625 4.14625C1.05243 4.24007 0.999725 4.36732 0.999725 4.5C0.999725 4.63268 1.05243 4.75993 1.14625 4.85375L5.64625 9.35375C5.69269 9.40024 5.74783 9.43712 5.80853 9.46228C5.86923 9.48744 5.9343 9.50039 6 9.50039C6.06571 9.50039 6.13077 9.48744 6.19147 9.46228C6.25217 9.43712 6.30732 9.40024 6.35375 9.35375L8.5 7.20687L13.2931 12H10.5C10.3674 12 10.2402 12.0527 10.1465 12.1464C10.0527 12.2402 10 12.3674 10 12.5C10 12.6326 10.0527 12.7598 10.1465 12.8536C10.2402 12.9473 10.3674 13 10.5 13H14.5C14.6326 13 14.7598 12.9473 14.8536 12.8536C14.9473 12.7598 15 12.6326 15 12.5Z"
                                fill="currentColor" />
                        </svg>

                        {{ card.trend }}
                    </div>
                </div>
            </div>

            <!-- ── Charts Row ──────────────────────────────── -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">

                <!-- Bar Chart: Volume -->
                <div class="xl:col-span-2 bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                    <h2 class="text-[18px] text-[#101010] mb-4">Volume Pembelian &amp; Penjualan</h2>
                    <div class="h-64">
                        <Bar :data="barData" :options="barOptions" />
                    </div>
                </div>

                <!-- Pie: Distribusi Stok -->
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 flex flex-col">
                    <h2 class="text-[18px] text-[#101010] mb-4">Distribusi Stok</h2>
                    <div class="flex-1 flex items-center justify-center">
                        <div class="h-52 w-52">
                            <Pie :data="pieData" :options="pieOptions" />
                        </div>
                    </div>
                    <!-- legend -->
                    <!-- <div class="mt-4 grid grid-cols-2 gap-x-4 gap-y-2">
                        <div v-for="item in distribusiStok" :key="item.label"
                            class="flex items-center gap-2 text-xs text-gray-600">
                            <span class="h-2.5 w-2.5 rounded-full shrink-0" :style="{ background: item.color }" />
                            {{ item.label }}
                            <span class="ml-auto text-gray-400 font-medium">{{ item.value }}%</span>
                        </div>
                    </div> -->
                </div>
            </div>

            <!-- ── Recent Transactions ─────────────────────── -->
            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">

                <!-- Header -->
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <h2 class="text-[18px] text-[#101010]">
                        Transaksi Terakhir
                    </h2>
                    <button
                        class="shrink-0 rounded bg-[#383838] py-2 px-3 text-[14px] font-medium text-white hover:bg-[#555555] transition"
                        @click="router.get('/barang-masuk')">
                        Lihat Semua
                    </button>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 bg-gray-50/60">
                                <th
                                    class="px-5 py-2.5 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-gray-500">
                                    No. Transaksi</th>
                                <th
                                    class="px-5 py-2.5 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-gray-500">
                                    Tanggal</th>
                                <th
                                    class="px-5 py-2.5 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-gray-500">
                                    Supplier</th>
                                <th
                                    class="px-5 py-2.5 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-gray-500">
                                    Volume</th>
                                <th
                                    class="px-5 py-2.5 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-gray-500">
                                    Gudang</th>
                                <th
                                    class="px-5 py-2.5 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-gray-500">
                                    Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="tx in recentTransactions" :key="tx.id" class="transition hover:bg-gray-50/50">
                                <td class="px-5 py-3.5 whitespace-nowrap font-medium text-gray-900">{{ tx.id }}</td>
                                <td class="px-5 py-3.5 whitespace-nowrap text-gray-500">{{ tx.tanggal }}</td>
                                <td class="px-5 py-3.5 whitespace-nowrap text-gray-700">{{ tx.supplier }}</td>
                                <td class="px-5 py-3.5 whitespace-nowrap text-gray-500">{{ tx.volume }}</td>
                                <td class="px-5 py-3.5 whitespace-nowrap text-gray-500">{{ tx.gudang }}</td>
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium border"
                                        :class="{
                                            'bg-emerald-50 text-emerald-700 border-emerald-100': tx.status === 'Lunas',
                                            'bg-amber-50 text-amber-700 border-amber-100': tx.status === 'Pending',
                                            'bg-red-50 text-red-600 border-red-100': tx.status === 'Belum Lunas',
                                        }">{{ tx.status }}</span>
                                </td>
                            </tr>

                            <tr v-if="recentTransactions.length === 0">
                                <td colspan="6" class="px-5 py-10 text-center">
                                    <p class="text-sm font-medium text-gray-500">Belum ada transaksi</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </AppLayout>
</template>