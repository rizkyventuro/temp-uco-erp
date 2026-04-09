<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { PermissionEnum } from '@/enums/PermissionEnum';
import { ArrowRightLeft, Send, QrCode } from 'lucide-vue-next';
import { Vue3Lottie } from 'vue3-lottie';
import emptyAnimation from '@/../../public/assets/animations/Pencarian Tidak Ditemukan.json';
import BadgeBussines from '@/components/BadgeBussines.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { usePermission } from '@/composables/usePermission';
import { type BreadcrumbItem } from '@/types';

const { can } = usePermission();

type BusinessType = 'Restoran' | 'UMKM' | 'Rumah Tangga';

interface Collection {
    id: string;
    transaction_code: string;
    total_volume: number;
}

const props = defineProps<{
    collections: Collection[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Transfer UCO', href: '/transfers' },
];
</script>

<template>
    <Head title="Transfer UCO" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col items-center gap-6 p-6">
            <div class="flex w-full max-w-3xl flex-col gap-6">
                <!-- Header -->
                <div>
                    <div class="flex items-center gap-2">
                        <div
                            class="flex h-8 w-8 items-center justify-center text-[#007C95]"
                        >
                            <ArrowRightLeft class="h-5 w-5" />
                        </div>
                        <h1 class="text-[18px] font-bold text-gray-900">
                            Transfer UCO
                        </h1>
                    </div>
                    <p class="mt-0.5 text-[14px] text-gray-500">
                        Pindahkan kepemilikan minyak jelantah antar pengepul
                    </p>
                </div>

                <!-- Action Cards -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <button
                        v-if="can('create transfer')"
                        @click="router.visit('/transfers/create')"
                        class="group relative flex cursor-pointer flex-col gap-3 rounded-2xl border-2 border-transparent bg-white p-6 text-left shadow-sm transition hover:border-primary hover:bg-primary-surface hover:shadow-md"
                    >
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-surface text-primary transition group-hover:bg-teal-100"
                        >
                            <Send class="h-5 w-5" />
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">
                                Kirim UCO
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Transfer collection ke pengguna lain. QR code
                                akan digenerate untuk penerima.
                            </p>
                        </div>
                    </button>

                    <button
                        v-if="can(PermissionEnum.CLAIM_TRANSFER)"
                        @click="router.visit('/transfers/claim')"
                        class="group relative flex cursor-pointer flex-col gap-3 rounded-2xl border border-2 border-transparent bg-white p-6 text-left shadow-sm transition hover:border-primary hover:shadow-md"
                    >
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-surface text-primary transition group-hover:bg-teal-100"
                        >
                            <QrCode class="h-5 w-5" />
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">
                                Terima UCO
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Scan QR code dari pengirim untuk menerima
                                kepemilikan UCO.
                            </p>
                        </div>
                    </button>
                </div>

                <!-- Collection Saya -->
                <div
                    class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
                >
                    <div class="border-b border-gray-100 px-5 py-4">
                        <h2 class="text-sm font-semibold text-gray-700">
                            Collection Saya
                            <span class="font-normal text-gray-400"
                                >({{ collections.length }})</span
                            >
                        </h2>
                    </div>

                    <div
                        class="grid grid-cols-1 gap-3 px-5 py-4 lg:grid-cols-2"
                    >
                        <template v-if="collections.length > 0">
                            <div
                                v-for="col in collections"
                                :key="col.id"
                                class="group flex items-center justify-between rounded-xl border border-gray-100 bg-[#F5F7FA] px-4 py-3.5 transition"
                            >
                                <div class="flex flex-col gap-1">
                                    <span
                                        class="text-sm font-semibold text-primary"
                                        >{{ col.transaction_code }}</span
                                    >
                                </div>
                                <span class="text-sm font-bold text-gray-600"
                                    >{{ col.total_volume }} L</span
                                >
                            </div>
                        </template>

                        <!-- Empty state -->
                        <div
                            v-else
                            class="col-span-full flex flex-col items-center justify-center py-12 text-center"
                        >
                            <Vue3Lottie
                                :animationData="emptyAnimation"
                                :height="160"
                                :width="160"
                                :loop="true"
                            />
                            <p class="text-sm font-medium text-gray-600">
                                Tidak ada collection
                            </p>
                            <p class="mt-1 text-xs text-gray-400">
                                Collection akan muncul setelah pengambilan
                                dicatat
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
