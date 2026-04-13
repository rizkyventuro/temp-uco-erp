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
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#007C95]/10">
                            <!-- Pembelian -->
                            <template v-if="card.icon === 'pembelian'">
                                <svg width="20" height="20" viewBox="0 0 16 16" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.2" d="M14 5L8 8.5L2 5L8 1.5L14 5Z" fill="#007C95" />
                                    <path
                                        d="M14.4319 10.75C14.4979 10.8646 14.5159 11.0007 14.4818 11.1285C14.4477 11.2562 14.3643 11.3653 14.25 11.4318L8.25 14.9318C8.17355 14.9764 8.08663 14.9999 7.99813 14.9999C7.90962 14.9999 7.8227 14.9764 7.74625 14.9318L1.74625 11.4318C1.63356 11.3641 1.55207 11.2547 1.51944 11.1274C1.4868 11 1.50564 10.8649 1.57188 10.7514C1.63811 10.6378 1.74641 10.5549 1.87333 10.5206C2.00025 10.4863 2.13557 10.5033 2.25 10.5681L8 13.9212L13.75 10.5681C13.8646 10.502 14.0007 10.4841 14.1285 10.5182C14.2563 10.5523 14.3654 10.6356 14.4319 10.75ZM13.75 7.56808L8 10.9212L2.25 7.56808C2.13615 7.51139 2.00498 7.50022 1.88319 7.53684C1.7614 7.57345 1.65814 7.65511 1.59442 7.76517C1.53071 7.87524 1.51133 8.00545 1.54023 8.1293C1.56914 8.25315 1.64415 8.36133 1.75 8.43183L7.75 11.9318C7.82645 11.9764 7.91337 11.9999 8.00187 11.9999C8.09038 11.9999 8.1773 11.9764 8.25375 11.9318L14.2537 8.43183C14.3596 8.36133 14.4346 8.25315 14.4635 8.1293C14.4924 8.00545 14.473 7.87524 14.4093 7.76517C14.3456 7.65511 14.2423 7.57345 14.1206 7.53684C13.9988 7.50022 13.8676 7.51139 13.75 7.56808ZM1.5 4.99995C1.5002 4.91237 1.5234 4.82639 1.56727 4.7506C1.61115 4.6748 1.67416 4.61186 1.75 4.56808L7.75 1.06808C7.82645 1.02349 7.91337 1 8.00187 1C8.09038 1 8.1773 1.02349 8.25375 1.06808L14.2537 4.56808C14.3292 4.61211 14.3918 4.67516 14.4354 4.75093C14.4789 4.82671 14.5018 4.91257 14.5018 4.99995C14.5018 5.08733 14.4789 5.17319 14.4354 5.24897C14.3918 5.32474 14.3292 5.38779 14.2537 5.43183L8.25375 8.93183C8.1773 8.97641 8.09038 8.9999 8.00187 8.9999C7.91337 8.9999 7.82645 8.97641 7.75 8.93183L1.75 5.43183C1.67416 5.38804 1.61115 5.3251 1.56727 5.24931C1.5234 5.17351 1.5002 5.08753 1.5 4.99995ZM2.9925 4.99995L8 7.9212L13.0075 4.99995L8 2.0787L2.9925 4.99995Z"
                                        fill="#007C95" />
                                </svg>
                            </template>
                            <!-- Penjualan -->
                            <template v-else-if="card.icon === 'penjualan'">
                                <svg width="20" height="20" viewBox="0 0 16 16" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.2"
                                        d="M2 12.5V3.5C2 3.36739 2.05268 3.24021 2.14645 3.14645C2.24021 3.05268 2.36739 3 2.5 3H13.5C13.6326 3 13.7598 3.05268 13.8536 3.14645C13.9473 3.24021 14 3.36739 14 3.5V12.5C14 12.6326 13.9473 12.7598 13.8536 12.8536C13.7598 12.9473 13.6326 13 13.5 13H2.5C2.36739 13 2.24021 12.9473 2.14645 12.8536C2.05268 12.7598 2 12.6326 2 12.5Z"
                                        fill="#007C95" />
                                    <path
                                        d="M13.5 2.5H2.5C2.23478 2.5 1.98043 2.60536 1.79289 2.79289C1.60536 2.98043 1.5 3.23478 1.5 3.5V12.5C1.5 12.7652 1.60536 13.0196 1.79289 13.2071C1.98043 13.3946 2.23478 13.5 2.5 13.5H13.5C13.7652 13.5 14.0196 13.3946 14.2071 13.2071C14.3946 13.0196 14.5 12.7652 14.5 12.5V3.5C14.5 3.23478 14.3946 2.98043 14.2071 2.79289C14.0196 2.60536 13.7652 2.5 13.5 2.5ZM13.5 12.5H2.5V3.5H13.5V12.5ZM5 6.5L8 9.5L11 6.5"
                                        stroke="#007C95" stroke-width="1.3" stroke-linecap="round"
                                        stroke-linejoin="round" fill="none" />
                                </svg>
                            </template>
                            <!-- Stok -->
                            <template v-else-if="card.icon === 'stok'">
                                <svg width="20" height="20" viewBox="0 0 16 16" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.2" d="M15 6.5L8 10.5L1 6.5L8 2.5L15 6.5Z" fill="#007C95" />
                                    <path
                                        d="M0.750094 6.93759L7.75009 10.9376C7.82566 10.9808 7.91118 11.0035 7.99822 11.0035C8.08525 11.0035 8.17078 10.9808 8.24634 10.9376L15.2463 6.93759C15.323 6.89389 15.3867 6.83069 15.431 6.7544C15.4754 6.67811 15.4987 6.59145 15.4987 6.50321C15.4987 6.41498 15.4754 6.32832 15.431 6.25203C15.3867 6.17574 15.323 6.11254 15.2463 6.06884L8.24634 2.06884C8.17078 2.02565 8.08525 2.00293 7.99822 2.00293C7.91118 2.00293 7.82566 2.02565 7.75009 2.06884L0.750094 6.06884C0.673442 6.11254 0.609718 6.17574 0.565392 6.25203C0.521067 6.32832 0.497719 6.41498 0.497719 6.50321C0.497719 6.59145 0.521067 6.67811 0.565392 6.7544C0.609718 6.83069 0.673442 6.89389 0.750094 6.93759ZM8.00009 3.07571L13.992 6.50009L8.00009 9.92446L2.00822 6.50009L8.00009 3.07571ZM15.4376 8.75009C15.4709 8.80741 15.4926 8.8708 15.5012 8.93655C15.5098 9.00231 15.5053 9.06912 15.4878 9.1331C15.4704 9.19708 15.4404 9.25695 15.3995 9.30923C15.3587 9.3615 15.3079 9.40514 15.2501 9.43759L8.25009 13.4376C8.17453 13.4808 8.089 13.5035 8.00197 13.5035C7.91493 13.5035 7.82941 13.4808 7.75384 13.4376L0.750094 9.43759C0.636241 9.37067 0.555441 9.26103 0.523735 9.13371C0.49203 9.00638 0.512043 8.87198 0.579157 8.75828C0.646272 8.64458 0.756073 8.56401 0.883471 8.53257C1.01087 8.50112 1.14519 8.52141 1.25872 8.58871L8.00009 12.4245L14.7501 8.56571C14.8646 8.50169 14.9997 8.48453 15.1268 8.5185C15.2538 8.55247 15.3626 8.63458 15.4293 8.74809L15.4376 8.75009Z"
                                        fill="#007C95" />
                                </svg>
                            </template>
                            <!-- Laba/Rugi -->
                            <template v-else-if="card.icon === 'labarugi'">
                                <svg width="20" height="20" viewBox="0 0 16 16" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.2" d="M13 2.5V13H9.5V2.5H13Z" fill="#007C95" />
                                    <path
                                        d="M14 12.5H13.5V2.5C13.5 2.36739 13.4473 2.24021 13.3536 2.14645C13.2598 2.05268 13.1326 2 13 2H9.5C9.36739 2 9.24021 2.05268 9.14645 2.14645C9.05268 2.24021 9 2.36739 9 2.5V5H6C5.86739 5 5.74021 5.05268 5.64645 5.14645C5.55268 5.24021 5.5 5.36739 5.5 5.5V8H3C2.86739 8 2.74021 8.05268 2.64645 8.14645C2.55268 8.24021 2.5 8.36739 2.5 8.5V12.5H2C1.86739 12.5 1.74021 12.5527 1.64645 12.6464C1.55268 12.7402 1.5 12.8674 1.5 13C1.5 13.1326 1.55268 13.2598 1.64645 13.3536C1.74021 13.4473 1.86739 13.5 2 13.5H14C14.1326 13.5 14.2598 13.4473 14.3536 13.3536C14.4473 13.2598 14.5 13.1326 14.5 13C14.5 12.8674 14.4473 12.7402 14.3536 12.6464C14.2598 12.5527 14.1326 12.5 14 12.5ZM10 3H12.5V12.5H10V3ZM6.5 6H9V12.5H6.5V6ZM3.5 9H5.5V12.5H3.5V9Z"
                                        fill="#007C95" />
                                </svg>
                            </template>
                            <!-- Kas/Bank -->
                            <template v-else-if="card.icon === 'kasbank'">
                                <svg width="20" height="20" viewBox="0 0 16 16" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.2" d="M14.5 6H1.5L8 2L14.5 6Z" fill="#007C95" />
                                    <path
                                        d="M1.5 6.49969H3V10.4997H2C1.86739 10.4997 1.74021 10.5524 1.64645 10.6461C1.55268 10.7399 1.5 10.8671 1.5 10.9997C1.5 11.1323 1.55268 11.2595 1.64645 11.3532C1.74021 11.447 1.86739 11.4997 2 11.4997H14C14.1326 11.4997 14.2598 11.447 14.3536 11.3532C14.4473 11.2595 14.5 11.1323 14.5 10.9997C14.5 10.8671 14.4473 10.7399 14.3536 10.6461C14.2598 10.5524 14.1326 10.4997 14 10.4997H13V6.49969H14.5C14.6088 6.49958 14.7146 6.46399 14.8013 6.39832C14.888 6.33265 14.951 6.24049 14.9806 6.13581C15.0102 6.03112 15.0049 5.91964 14.9654 5.81826C14.9259 5.71689 14.8545 5.63115 14.7619 5.57406L8.26188 1.57406C8.18311 1.52564 8.09246 1.5 8 1.5C7.90754 1.5 7.81689 1.52564 7.73812 1.57406L1.23812 5.57406C1.14552 5.63115 1.07406 5.71689 1.03458 5.81826C0.995107 5.91964 0.989773 6.03112 1.01939 6.13581C1.04901 6.24049 1.11195 6.33265 1.19869 6.39832C1.28542 6.46399 1.39121 6.49958 1.5 6.49969ZM4 6.49969H6V10.4997H4V6.49969ZM9 6.49969V10.4997H7V6.49969H9ZM12 10.4997H10V6.49969H12V10.4997ZM8 2.58656L12.7338 5.49969H3.26625L8 2.58656ZM15.5 12.9997C15.5 13.1323 15.4473 13.2595 15.3536 13.3532C15.2598 13.447 15.1326 13.4997 15 13.4997H1C0.867392 13.4997 0.740215 13.447 0.646447 13.3532C0.552678 13.2595 0.5 13.1323 0.5 12.9997C0.5 12.8671 0.552678 12.7399 0.646447 12.6461C0.740215 12.5524 0.867392 12.4997 1 12.4997H15C15.1326 12.4997 15.2598 12.5524 15.3536 12.6461C15.4473 12.7399 15.5 12.8671 15.5 12.9997Z"
                                        fill="#007C95" />
                                </svg>
                            </template>
                            <!-- Supplier -->
                            <template v-else-if="card.icon === 'supplier'">
                                <svg width="20" height="20" viewBox="0 0 16 16" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.2"
                                        d="M15 7.5V11.5C15 11.6326 14.9473 11.7598 14.8536 11.8536C14.7598 11.9473 14.6326 12 14.5 12H13C13 11.76 12.9425 11.5235 12.8322 11.3104C12.7219 11.0973 12.562 10.9137 12.3661 10.7751C12.1702 10.6366 11.9438 10.547 11.7061 10.5141C11.4684 10.4811 11.2263 10.5056 11 10.5856C10.7075 10.689 10.4543 10.8806 10.2752 11.1339C10.0961 11.3872 9.99997 11.6898 10 12H6C6 11.6022 5.84196 11.2206 5.56066 10.9393C5.27936 10.658 4.89782 10.5 4.5 10.5C4.10218 10.5 3.72064 10.658 3.43934 10.9393C3.15804 11.2206 3 11.6022 3 12H1.5C1.36739 12 1.24021 11.9473 1.14645 11.8536C1.05268 11.7598 1 11.6326 1 11.5V9H11V7.5H15Z"
                                        fill="#007C95" />
                                    <path
                                        d="M15.4637 7.3125L14.5887 5.125C14.5145 4.93992 14.3864 4.78139 14.2211 4.66996C14.0557 4.55852 13.8607 4.49931 13.6613 4.5H11.5V4C11.5 3.86739 11.4473 3.74021 11.3536 3.64645C11.2598 3.55268 11.1326 3.5 11 3.5H1.5C1.23478 3.5 0.98043 3.60536 0.792893 3.79289C0.605357 3.98043 0.5 4.23478 0.5 4.5V11.5C0.5 11.7652 0.605357 12.0196 0.792893 12.2071C0.98043 12.3946 1.23478 12.5 1.5 12.5H2.5625C2.67265 12.9302 2.92285 13.3115 3.27366 13.5838C3.62446 13.8561 4.05591 14.0039 4.5 14.0039C4.94409 14.0039 5.37554 13.8561 5.72635 13.5838C6.07715 13.3115 6.32735 12.9302 6.4375 12.5H9.5625C9.67265 12.9302 9.92285 13.3115 10.2737 13.5838C10.6245 13.8561 11.0559 14.0039 11.5 14.0039C11.9441 14.0039 12.3755 13.8561 12.7263 13.5838C13.0771 13.3115 13.3273 12.9302 13.4375 12.5H14.5C14.7652 12.5 15.0196 12.3946 15.2071 12.2071C15.3946 12.0196 15.5 11.7652 15.5 11.5V7.5C15.5002 7.43574 15.4879 7.37206 15.4637 7.3125ZM11.5 5.5H13.6613L14.2612 7H11.5V5.5ZM1.5 4.5H10.5V8.5H1.5V4.5ZM4.5 13C4.30222 13 4.10888 12.9414 3.94443 12.8315C3.77998 12.7216 3.65181 12.5654 3.57612 12.3827C3.50043 12.2 3.48063 11.9989 3.51921 11.8049C3.5578 11.6109 3.65304 11.4327 3.79289 11.2929C3.93275 11.153 4.11093 11.0578 4.30491 11.0192C4.49889 10.9806 4.69996 11.0004 4.88268 11.0761C5.06541 11.1518 5.22159 11.28 5.33147 11.4444C5.44135 11.6089 5.5 11.8022 5.5 12C5.5 12.2652 5.39464 12.5196 5.20711 12.7071C5.01957 12.8946 4.76522 13 4.5 13ZM9.5625 11.5H6.4375C6.32735 11.0698 6.07715 10.6885 5.72635 10.4162C5.37554 10.1439 4.94409 9.99608 4.5 9.99608C4.05591 9.99608 3.62446 10.1439 3.27366 10.4162C2.92285 10.6885 2.67265 11.0698 2.5625 11.5H1.5V9.5H10.5V10.2694C10.2701 10.4023 10.0688 10.5795 9.9079 10.7907C9.74697 11.002 9.62957 11.243 9.5625 11.5ZM11.5 13C11.3022 13 11.1089 12.9414 10.9444 12.8315C10.78 12.7216 10.6518 12.5654 10.5761 12.3827C10.5004 12.2 10.4806 11.9989 10.5192 11.8049C10.5578 11.6109 10.653 11.4327 10.7929 11.2929C10.9327 11.153 11.1109 11.0578 11.3049 11.0192C11.4989 10.9806 11.7 11.0004 11.8827 11.0761C12.0654 11.1518 12.2216 11.28 12.3315 11.4444C12.4414 11.6089 12.5 11.8022 12.5 12C12.5 12.2652 12.3946 12.5196 12.2071 12.7071C12.0196 12.8946 11.7652 13 11.5 13ZM14.5 11.5H13.4375C13.326 11.0708 13.0754 10.6908 12.7247 10.4193C12.3741 10.1479 11.9434 10.0004 11.5 10V8H14.5V11.5Z"
                                        fill="#007C95" />
                                </svg>
                            </template>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="mb-4">
                        <h3 class="text-[16px] font-semibold text-[#101010]">{{ card.title }}</h3>
                        <p class="mt-0.5 text-[13px] text-gray-400">{{ card.description }}</p>
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