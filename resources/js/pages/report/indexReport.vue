<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { ChevronDown } from 'lucide-vue-next';
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

import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler, ChartDataLabels);

// ── Props ──────────────────────────────────────────────────────

const props = defineProps<{
    chartData?: { label: string; pendapatan: number; hpp: number; laba_bersih: number }[];
}>();

// ── Breadcrumbs ────────────────────────────────────────────────

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Laporan', href: '/laporan' },
];

// ── Report Cards ───────────────────────────────────────────────

interface ReportCard {
    title: string;
    description: string;
    icon: string;
    href: string;
}

const reportCards: ReportCard[] = [
    {
        title: 'Laporan Pembelian',
        description: 'Rekap barang masuk & hutang ke supplier',
        icon: 'pembelian',
        href: '/laporan/pembelian',
    },
    {
        title: 'Laporan Penjualan',
        description: 'Rekap barang keluar & piutang ke buyer',
        icon: 'penjualan',
        href: '/laporan/penjualan',
    },
    {
        title: 'Laporan Stok',
        description: 'Posisi stok per gudang & periode',
        icon: 'stok',
        href: '/laporan/stok',
    },
    {
        title: 'Laba/Rugi',
        description: 'Perhitungan laba rugi per periode',
        icon: 'labarugi',
        href: '/laporan/laba-rugi',
    },
    {
        title: 'Laporan Kas/Bank',
        description: 'Mutasi kas dan rekening bank',
        icon: 'kasbank',
        href: '/laporan/kas-bank',
    },
    {
        title: 'Laporan Supplier',
        description: 'Performa & riwayat per supplier',
        icon: 'supplier',
        href: '/laporan/supplier',
    },
];

// ── Dummy Chart Data ───────────────────────────────────────────

const dummyChart = [
    { label: 'Jan', pendapatan: 120, hpp: 95, laba_bersih: 100 },
    { label: 'Feb', pendapatan: 135, hpp: 100, laba_bersih: 110 },
    { label: 'Mar', pendapatan: 150, hpp: 110, laba_bersih: 95 },
    { label: 'Apr', pendapatan: 140, hpp: 105, laba_bersih: 130 },
    { label: 'May', pendapatan: 130, hpp: 90, laba_bersih: 105 },
    { label: 'Jun', pendapatan: 125, hpp: 95, laba_bersih: 90 },
    { label: 'Jul', pendapatan: 145, hpp: 100, laba_bersih: 115 },
    { label: 'Aug', pendapatan: 155, hpp: 110, laba_bersih: 100 },
    { label: 'Sep', pendapatan: 140, hpp: 105, laba_bersih: 120 },
    { label: 'Oct', pendapatan: 130, hpp: 95, laba_bersih: 110 },
    { label: 'Nov', pendapatan: 150, hpp: 115, laba_bersih: 125 },
    { label: 'Dec', pendapatan: 160, hpp: 120, laba_bersih: 135 },
];

const chartSource = computed(() => props.chartData?.length ? props.chartData : dummyChart);

const lineChartData = computed(() => ({
    labels: chartSource.value.map(d => d.label),
    datasets: [
        {
            label: 'Pendapatan',
            data: chartSource.value.map(d => d.pendapatan),
            borderColor: '#50CD89',
            backgroundColor: '#50CD89',
            tension: 0.4,
            pointRadius: 2,
            pointBackgroundColor: '#50CD89',
            borderWidth: 2,
        },
        {
            label: 'HPP',
            data: chartSource.value.map(d => d.hpp),
            borderColor: '#F14141',
            backgroundColor: '#F14141',
            tension: 0.4,
            pointRadius: 2,
            pointBackgroundColor: '#F14141',
            borderWidth: 2,
        },
        {
            label: 'Laba Bersih',
            data: chartSource.value.map(d => d.laba_bersih),
            borderColor: '#7239EA',
            backgroundColor: '#7239EA',
            tension: 0.4,
            pointRadius: 2,
            pointBackgroundColor: '#7239EA',
            borderWidth: 2,
        },
    ],
}));

const lineChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: true,
            position: 'bottom' as const,
            labels: {
                usePointStyle: true,
                pointStyle: 'rectRounded',
                padding: 24,
                font: { size: 12 },
            },
        },
        tooltip: {
            mode: 'index' as const,
            intersect: false,
        },
        datalabels: { display: false },
    },
    scales: {
        x: {
            grid: { display: false },
            ticks: { font: { size: 11 }, color: '#878787' },
        },
        y: {
            grid: { color: '#f3f4f6' },
            ticks: { font: { size: 11 }, color: '#878787' },
            beginAtZero: true,
        },
    },
};

// ── Export ──────────────────────────────────────────────────────

function handleExport(format: string) {
    // TODO: implement export
    router.get(`/laporan/export`, { format });
}
</script>

<template>

    <Head title="Laporan" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 pb-10 md:p-6">

            <!-- ── Header ─────────────────────────────────────── -->
            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div class="flex-1 min-w-0">
                    <h1 class="text-[24px] font-bold text-gray-900">Laporan</h1>
                    <p class="mt-0.5 text-[16px] text-gray-500">Analisis bisnis dan laporan keuangan</p>
                </div>

                <div class="flex w-full justify-end md:w-auto md:flex-shrink-0">
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button
                                class="flex w-fit items-center justify-center gap-1.5 rounded-lg bg-[#007C95] px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-[#006b80]">
                                Export Laporan
                                <ChevronDown class="size-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-44">
                            <DropdownMenuItem class="gap-2 text-sm" @click="handleExport('pdf')">
                                <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                                    <path d="M9.5 1.5H4a1 1 0 0 0-1 1v11a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V5L9.5 1.5z"
                                        stroke="currentColor" stroke-width="1.3" stroke-linejoin="round" />
                                    <path d="M9.5 1.5V5H13" stroke="currentColor" stroke-width="1.3"
                                        stroke-linejoin="round" />
                                </svg>
                                Export PDF
                            </DropdownMenuItem>
                            <DropdownMenuItem class="gap-2 text-sm" @click="handleExport('excel')">
                                <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                                    <path d="M9.5 1.5H4a1 1 0 0 0-1 1v11a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V5L9.5 1.5z"
                                        stroke="currentColor" stroke-width="1.3" stroke-linejoin="round" />
                                    <path d="M9.5 1.5V5H13" stroke="currentColor" stroke-width="1.3"
                                        stroke-linejoin="round" />
                                </svg>
                                Export Excel
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>

            <!-- ── Report Cards Grid ──────────────────────────── -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <div v-for="card in reportCards" :key="card.title"
                    class="bg-white rounded-xl border border-[#EDEDED] shadow-sm p-5 flex flex-col justify-between">

                    <!-- Icon -->
                    <div class="mb-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg">
                            <!-- Pembelian -->
                            <template v-if="card.icon === 'pembelian'">
                                <svg width="40" height="40" viewBox="0 0 40 40" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.2"
                                        d="M20 20.1695V36.2492C19.7901 36.2484 19.5837 36.1946 19.4 36.093L5.65 28.568C5.45366 28.4605 5.28977 28.3024 5.17543 28.11C5.06109 27.9176 5.00051 27.698 5 27.4742V12.5305C5.00006 12.3544 5.03733 12.1802 5.10938 12.0195L20 20.1695Z"
                                        fill="#007C95" />
                                    <path
                                        d="M34.95 10.3368L21.2 2.81338C20.8326 2.61039 20.4197 2.50391 20 2.50391C19.5803 2.50391 19.1674 2.61039 18.8 2.81338L5.05 10.3399C4.65733 10.5548 4.32954 10.8711 4.10086 11.2559C3.87219 11.6407 3.75102 12.0798 3.75 12.5274V27.4712C3.75102 27.9188 3.87219 28.3579 4.10086 28.7427C4.32954 29.1275 4.65733 29.4438 5.05 29.6587L18.8 37.1853C19.1674 37.3883 19.5803 37.4947 20 37.4947C20.4197 37.4947 20.8326 37.3883 21.2 37.1853L34.95 29.6587C35.3427 29.4438 35.6705 29.1275 35.8991 28.7427C36.1278 28.3579 36.249 27.9188 36.25 27.4712V12.529C36.2498 12.0806 36.129 11.6405 35.9003 11.2548C35.6716 10.8691 35.3434 10.5521 34.95 10.3368ZM20 5.00088L32.5531 11.8759L20 18.7509L7.44687 11.8759L20 5.00088ZM6.25 14.0634L18.75 20.904V34.3087L6.25 27.4728V14.0634ZM21.25 34.3087V20.9103L33.75 14.0634V27.4665L21.25 34.3087Z"
                                        fill="#101010" />
                                </svg>
                            </template>
                            <!-- Penjualan -->
                            <template v-else-if="card.icon === 'penjualan'">
                                <svg width="40" height="40" viewBox="0 0 40 40" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.2"
                                        d="M28.75 12.5V32.5C28.75 32.8315 28.6183 33.1495 28.3839 33.3839C28.1495 33.6183 27.8315 33.75 27.5 33.75H7.5C7.16848 33.75 6.85054 33.6183 6.61612 33.3839C6.3817 33.1495 6.25 32.8315 6.25 32.5V12.5C6.25 12.1685 6.3817 11.8505 6.61612 11.6161C6.85054 11.3817 7.16848 11.25 7.5 11.25H27.5C27.8315 11.25 28.1495 11.3817 28.3839 11.6161C28.6183 11.8505 28.75 12.1685 28.75 12.5Z"
                                        fill="#007C95" />
                                    <path
                                        d="M35 16.25C35 16.5815 34.8683 16.8995 34.6339 17.1339C34.3995 17.3683 34.0815 17.5 33.75 17.5C33.4185 17.5 33.1005 17.3683 32.8661 17.1339C32.6317 16.8995 32.5 16.5815 32.5 16.25V9.26875L22.1359 19.6344C21.9014 19.8689 21.5833 20.0007 21.2516 20.0007C20.9199 20.0007 20.6017 19.8689 20.3672 19.6344C20.1326 19.3998 20.0009 19.0817 20.0009 18.75C20.0009 18.4183 20.1326 18.1002 20.3672 17.8656L30.7312 7.5H23.75C23.4185 7.5 23.1005 7.3683 22.8661 7.13388C22.6317 6.89946 22.5 6.58152 22.5 6.25C22.5 5.91848 22.6317 5.60054 22.8661 5.36612C23.1005 5.1317 23.4185 5 23.75 5H33.75C34.0815 5 34.3995 5.1317 34.6339 5.36612C34.8683 5.60054 35 5.91848 35 6.25V16.25ZM28.75 20C28.4185 20 28.1005 20.1317 27.8661 20.3661C27.6317 20.6005 27.5 20.9185 27.5 21.25V32.5H7.5V12.5H18.75C19.0815 12.5 19.3995 12.3683 19.6339 12.1339C19.8683 11.8995 20 11.5815 20 11.25C20 10.9185 19.8683 10.6005 19.6339 10.3661C19.3995 10.1317 19.0815 10 18.75 10H7.5C6.83696 10 6.20107 10.2634 5.73223 10.7322C5.26339 11.2011 5 11.837 5 12.5V32.5C5 33.163 5.26339 33.7989 5.73223 34.2678C6.20107 34.7366 6.83696 35 7.5 35H27.5C28.163 35 28.7989 34.7366 29.2678 34.2678C29.7366 33.7989 30 33.163 30 32.5V21.25C30 20.9185 29.8683 20.6005 29.6339 20.3661C29.3995 20.1317 29.0815 20 28.75 20Z"
                                        fill="#101010" />
                                </svg>
                            </template>
                            <!-- Stok -->
                            <template v-else-if="card.icon === 'stok'">
                                <svg width="40" height="40" viewBox="0 0 40 40" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.2" d="M35 12.5L20 21.25L5 12.5L20 3.75L35 12.5Z" fill="#007C95" />
                                    <path
                                        d="M36.0797 26.8749C36.2448 27.1614 36.2896 27.5016 36.2044 27.8211C36.1192 28.1406 35.9109 28.4133 35.625 28.5796L20.625 37.3296C20.4339 37.441 20.2166 37.4998 19.9953 37.4998C19.7741 37.4998 19.5568 37.441 19.3656 37.3296L4.36563 28.5796C4.0839 28.4103 3.88018 28.1368 3.79859 27.8184C3.717 27.5 3.7641 27.1623 3.92969 26.8784C4.09528 26.5945 4.36603 26.3872 4.68333 26.3014C5.00062 26.2157 5.33893 26.2583 5.625 26.4202L20 34.803L34.375 26.4202C34.6615 26.2551 35.0018 26.2102 35.3212 26.2955C35.6407 26.3807 35.9135 26.589 36.0797 26.8749ZM34.375 18.9202L20 27.303L5.625 18.9202C5.34038 18.7785 5.01246 18.7506 4.70798 18.8421C4.40349 18.9336 4.14534 19.1378 3.98605 19.4129C3.82676 19.6881 3.77832 20.0136 3.85058 20.3232C3.92284 20.6329 4.11037 20.9033 4.375 21.0796L19.375 29.8296C19.5661 29.941 19.7834 29.9998 20.0047 29.9998C20.2259 29.9998 20.4432 29.941 20.6344 29.8296L35.6344 21.0796C35.7784 20.9981 35.9049 20.8887 36.0064 20.758C36.1079 20.6272 36.1824 20.4776 36.2255 20.3178C36.2687 20.158 36.2798 19.9912 36.258 19.8271C36.2362 19.663 36.182 19.5049 36.0986 19.3619C36.0152 19.2189 35.9043 19.0939 35.7722 18.9941C35.6401 18.8944 35.4895 18.8218 35.3291 18.7807C35.1688 18.7397 35.0019 18.7308 34.8381 18.7548C34.6743 18.7787 34.5169 18.8349 34.375 18.9202ZM3.75 12.4999C3.7505 12.2809 3.80849 12.066 3.91818 11.8765C4.02786 11.687 4.18539 11.5297 4.375 11.4202L19.375 2.67019C19.5661 2.55873 19.7834 2.5 20.0047 2.5C20.2259 2.5 20.4432 2.55873 20.6344 2.67019L35.6344 11.4202C35.8231 11.5303 35.9796 11.6879 36.0884 11.8773C36.1972 12.0668 36.2545 12.2814 36.2545 12.4999C36.2545 12.7183 36.1972 12.933 36.0884 13.1224C35.9796 13.3119 35.8231 13.4695 35.6344 13.5796L20.6344 22.3296C20.4432 22.441 20.2259 22.4998 20.0047 22.4998C19.7834 22.4998 19.5661 22.441 19.375 22.3296L4.375 13.5796C4.18539 13.4701 4.02786 13.3127 3.91818 13.1233C3.80849 12.9338 3.7505 12.7188 3.75 12.4999ZM7.48125 12.4999L20 19.803L32.5187 12.4999L20 5.19675L7.48125 12.4999Z"
                                        fill="#101010" />
                                </svg>
                            </template>
                            <!-- Laba/Rugi -->
                            <template v-else-if="card.icon === 'labarugi'">
                                <svg width="40" height="40" viewBox="0 0 40 40" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.2" d="M32.5 6.25V32.5H23.75V6.25H32.5Z" fill="#007C95" />
                                    <path
                                        d="M35 31.25H33.75V6.25C33.75 5.91848 33.6183 5.60054 33.3839 5.36612C33.1495 5.1317 32.8315 5 32.5 5H23.75C23.4185 5 23.1005 5.1317 22.8661 5.36612C22.6317 5.60054 22.5 5.91848 22.5 6.25V12.5H15C14.6685 12.5 14.3505 12.6317 14.1161 12.8661C13.8817 13.1005 13.75 13.4185 13.75 13.75V20H7.5C7.16848 20 6.85054 20.1317 6.61612 20.3661C6.3817 20.6005 6.25 20.9185 6.25 21.25V31.25H5C4.66848 31.25 4.35054 31.3817 4.11612 31.6161C3.8817 31.8505 3.75 32.1685 3.75 32.5C3.75 32.8315 3.8817 33.1495 4.11612 33.3839C4.35054 33.6183 4.66848 33.75 5 33.75H35C35.3315 33.75 35.6495 33.6183 35.8839 33.3839C36.1183 33.1495 36.25 32.8315 36.25 32.5C36.25 32.1685 36.1183 31.8505 35.8839 31.6161C35.6495 31.3817 35.3315 31.25 35 31.25ZM25 7.5H31.25V31.25H25V7.5ZM16.25 15H22.5V31.25H16.25V15ZM8.75 22.5H13.75V31.25H8.75V22.5Z"
                                        fill="#101010" />
                                </svg>
                            </template>
                            <!-- Kas/Bank -->
                            <template v-else-if="card.icon === 'kasbank'">
                                <svg width="40" height="40" viewBox="0 0 40 40" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.2" d="M36.25 15H3.75L20 5L36.25 15Z" fill="#007C95" />
                                    <path
                                        d="M3.75 16.2492H7.5V26.2492H5C4.66848 26.2492 4.35054 26.3809 4.11612 26.6153C3.8817 26.8498 3.75 27.1677 3.75 27.4992C3.75 27.8307 3.8817 28.1487 4.11612 28.3831C4.35054 28.6175 4.66848 28.7492 5 28.7492H35C35.3315 28.7492 35.6495 28.6175 35.8839 28.3831C36.1183 28.1487 36.25 27.8307 36.25 27.4992C36.25 27.1677 36.1183 26.8498 35.8839 26.6153C35.6495 26.3809 35.3315 26.2492 35 26.2492H32.5V16.2492H36.25C36.522 16.2489 36.7864 16.16 37.0033 15.9958C37.2201 15.8316 37.3775 15.6012 37.4515 15.3395C37.5256 15.0778 37.5122 14.7991 37.4135 14.5457C37.3149 14.2922 37.1362 14.0779 36.9047 13.9352L20.6547 3.93516C20.4578 3.81409 20.2312 3.75 20 3.75C19.7688 3.75 19.5422 3.81409 19.3453 3.93516L3.09531 13.9352C2.86379 14.0779 2.68514 14.2922 2.58645 14.5457C2.48777 14.7991 2.47443 15.0778 2.54847 15.3395C2.62251 15.6012 2.77989 15.8316 2.99672 15.9958C3.21356 16.16 3.47802 16.2489 3.75 16.2492ZM10 16.2492H15V26.2492H10V16.2492ZM22.5 16.2492V26.2492H17.5V16.2492H22.5ZM30 26.2492H25V16.2492H30V26.2492ZM20 6.46641L31.8344 13.7492H8.16562L20 6.46641ZM38.75 32.4992C38.75 32.8307 38.6183 33.1487 38.3839 33.3831C38.1495 33.6175 37.8315 33.7492 37.5 33.7492H2.5C2.16848 33.7492 1.85054 33.6175 1.61612 33.3831C1.3817 33.1487 1.25 32.8307 1.25 32.4992C1.25 32.1677 1.3817 31.8498 1.61612 31.6153C1.85054 31.3809 2.16848 31.2492 2.5 31.2492H37.5C37.8315 31.2492 38.1495 31.3809 38.3839 31.6153C38.6183 31.8498 38.75 32.1677 38.75 32.4992Z"
                                        fill="#101010" />
                                </svg>
                            </template>
                            <!-- Supplier -->
                            <template v-else-if="card.icon === 'supplier'">
                                <svg width="40" height="40" viewBox="0 0 40 40" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.2"
                                        d="M21.25 4.99964V33.7496H6.25V13.3356C6.24987 13.1297 6.30057 12.927 6.39761 12.7455C6.49465 12.564 6.63502 12.4092 6.80625 12.295L19.3062 3.96214C19.4943 3.83666 19.7129 3.76454 19.9387 3.75346C20.1645 3.74237 20.3891 3.79275 20.5886 3.89921C20.7881 4.00567 20.9549 4.16424 21.0714 4.35802C21.1879 4.55181 21.2496 4.77355 21.25 4.99964Z"
                                        fill="#007C95" />
                                    <path
                                        d="M37.5 32.4997H35V14.9997C35 14.3366 34.7366 13.7007 34.2678 13.2319C33.7989 12.7631 33.163 12.4997 32.5 12.4997H22.5V4.99967C22.5003 4.54696 22.3777 4.10266 22.1452 3.7142C21.9127 3.32574 21.5791 3.00771 21.18 2.79403C20.7808 2.58036 20.3312 2.47907 19.879 2.50098C19.4268 2.52288 18.9891 2.66717 18.6125 2.91842L6.1125 11.2497C5.76959 11.4785 5.48858 11.7885 5.29448 12.1521C5.10038 12.5158 4.99922 12.9218 5 13.334V32.4997H2.5C2.16848 32.4997 1.85054 32.6314 1.61612 32.8658C1.3817 33.1002 1.25 33.4182 1.25 33.7497C1.25 34.0812 1.3817 34.3991 1.61612 34.6336C1.85054 34.868 2.16848 34.9997 2.5 34.9997H37.5C37.8315 34.9997 38.1495 34.868 38.3839 34.6336C38.6183 34.3991 38.75 34.0812 38.75 33.7497C38.75 33.4182 38.6183 33.1002 38.3839 32.8658C38.1495 32.6314 37.8315 32.4997 37.5 32.4997ZM32.5 14.9997V32.4997H22.5V14.9997H32.5ZM7.5 13.334L20 4.99967V32.4997H7.5V13.334ZM17.5 17.4997V19.9997C17.5 20.3312 17.3683 20.6491 17.1339 20.8836C16.8995 21.118 16.5815 21.2497 16.25 21.2497C15.9185 21.2497 15.6005 21.118 15.3661 20.8836C15.1317 20.6491 15 20.3312 15 19.9997V17.4997C15 17.1682 15.1317 16.8502 15.3661 16.6158C15.6005 16.3814 15.9185 16.2497 16.25 16.2497C16.5815 16.2497 16.8995 16.3814 17.1339 16.6158C17.3683 16.8502 17.5 17.1682 17.5 17.4997ZM12.5 17.4997V19.9997C12.5 20.3312 12.3683 20.6491 12.1339 20.8836C11.8995 21.118 11.5815 21.2497 11.25 21.2497C10.9185 21.2497 10.6005 21.118 10.3661 20.8836C10.1317 20.6491 10 20.3312 10 19.9997V17.4997C10 17.1682 10.1317 16.8502 10.3661 16.6158C10.6005 16.3814 10.9185 16.2497 11.25 16.2497C11.5815 16.2497 11.8995 16.3814 12.1339 16.6158C12.3683 16.8502 12.5 17.1682 12.5 17.4997ZM12.5 26.2497V28.7497C12.5 29.0812 12.3683 29.3991 12.1339 29.6336C11.8995 29.868 11.5815 29.9997 11.25 29.9997C10.9185 29.9997 10.6005 29.868 10.3661 29.6336C10.1317 29.3991 10 29.0812 10 28.7497V26.2497C10 25.9182 10.1317 25.6002 10.3661 25.3658C10.6005 25.1314 10.9185 24.9997 11.25 24.9997C11.5815 24.9997 11.8995 25.1314 12.1339 25.3658C12.3683 25.6002 12.5 25.9182 12.5 26.2497ZM17.5 26.2497V28.7497C17.5 29.0812 17.3683 29.3991 17.1339 29.6336C16.8995 29.868 16.5815 29.9997 16.25 29.9997C15.9185 29.9997 15.6005 29.868 15.3661 29.6336C15.1317 29.3991 15 29.0812 15 28.7497V26.2497C15 25.9182 15.1317 25.6002 15.3661 25.3658C15.6005 25.1314 15.9185 24.9997 16.25 24.9997C16.5815 24.9997 16.8995 25.1314 17.1339 25.3658C17.3683 25.6002 17.5 25.9182 17.5 26.2497Z"
                                        fill="#101010" />
                                </svg>
                            </template>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="mb-4">
                        <h3 class="text-[18px] font-semibold text-[#101010]">{{ card.title }}</h3>
                        <p class="mt-0.5 text-[12px] text-gray-400">{{ card.description }}</p>
                    </div>

                    <!-- Button -->
                    <button
                        class="w-full rounded-lg bg-[#007C95] py-2.5 text-sm font-medium text-white transition hover:bg-[#006b80]"
                        @click="router.get(card.href)">
                        Lihat
                    </button>
                </div>
            </div>

            <!-- ── Chart: Ringkasan Laba/Rugi Bulanan ─────────── -->
            <div class="bg-white rounded-xl border border-[#EDEDED] shadow-sm p-5">
                <h2 class="text-[18px] font-semibold text-[#101010] mb-4">Ringkasan Laba/Rugi Bulanan</h2>
                <div class="h-72 sm:h-80">
                    <Line :data="lineChartData" :options="lineChartOptions" />
                </div>
            </div>

        </div>
    </AppLayout>
</template>