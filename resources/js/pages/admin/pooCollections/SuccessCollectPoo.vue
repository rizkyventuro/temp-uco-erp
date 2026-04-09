<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { PermissionEnum } from '@/enums/PermissionEnum';
import { CheckCircle2, ArrowLeft } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { usePermission } from '@/composables/usePermission';
import { type BreadcrumbItem } from '@/types';

const { can } = usePermission();

interface Collection {
    code: string;
    poo_id: string;
    poo_name: string;
    volume: number;
    collected_at: string;
    photo_url: string | null;
    signature_url: string;
    notes: string | null;
    created_by: string | null;
}

const props = defineProps<{ collection: Collection }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pengambilan dari POO', href: '/collections' },
    { title: 'Berhasil', href: '#' },
];
</script>

<template>
    <Head title="Pengambilan Berhasil" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col items-center gap-6 p-6">
            <div class="flex w-full max-w-xl flex-col gap-5">
                <!-- Card -->
                <div
                    class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
                >
                    <!-- Success Banner -->
                    <div
                        class="flex flex-col items-center px-6 pt-8 pb-6 text-center"
                    >
                        <div
                            class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-green-100"
                        >
                            <CheckCircle2 class="h-8 w-8 text-green-500" />
                        </div>
                        <h1 class="text-[17px] font-bold text-gray-900">
                            Pengambilan Berhasil Dicatat!
                        </h1>
                        <p class="mt-1 max-w-xs text-sm text-gray-500">
                            UCO dari
                            <span class="font-semibold text-gray-700">{{
                                collection.poo_name
                            }}</span>
                            sebesar
                            <span class="font-semibold text-gray-700"
                                >{{ collection.volume }} Liter</span
                            >
                            telah berhasil dicatat dan masuk ke stok Anda.
                        </p>
                    </div>

                    <!-- Detail -->
                    <div class="px-6 pb-5">
                        <div
                            class="grid grid-cols-2 gap-y-3 rounded-xl bg-gray-50 p-4 text-sm"
                        >
                            <span class="text-gray-500">KODE</span>
                            <span
                                class="text-right font-semibold text-gray-900"
                                >{{ collection.code }}</span
                            >

                            <span class="text-gray-500">POO</span>
                            <span
                                class="text-right font-semibold text-gray-900"
                                >{{ collection.poo_name }}</span
                            >

                            <span class="text-gray-500">Volume</span>
                            <span class="text-right font-semibold text-gray-900"
                                >{{ collection.volume }} Liter</span
                            >

                            <span class="text-gray-500">Tanggal</span>
                            <span
                                class="text-right font-semibold text-gray-900"
                                >{{ collection.collected_at }}</span
                            >

                            <span class="text-gray-500">Petugas</span>
                            <span
                                class="text-right font-semibold text-gray-900"
                                >{{ collection.created_by ?? '-' }}</span
                            >

                            <template v-if="collection.notes">
                                <span class="text-gray-500">Catatan</span>
                                <span
                                    class="text-right font-semibold text-gray-900"
                                    >{{ collection.notes }}</span
                                >
                            </template>
                        </div>
                    </div>

                    <!-- Foto -->
                    <div
                        v-if="collection.photo_url"
                        class="px-6 pb-5"
                    >
                        <p
                            class="mb-2 text-xs font-semibold tracking-widest text-gray-400 uppercase"
                        >
                            Foto Pengambilan
                        </p>
                        <img
                            :src="collection.photo_url"
                            class="max-h-52 w-full rounded-xl border border-gray-200 object-cover"
                        />
                    </div>

                    <!-- Tanda Tangan -->
                    <div class="px-6 pb-6">
                        <p
                            class="mb-2 text-xs font-semibold tracking-widest text-gray-400 uppercase"
                        >
                            Tanda Tangan
                        </p>
                        <div
                            class="rounded-xl border border-gray-200 bg-gray-50 p-3"
                        >
                            <img
                                :src="collection.signature_url"
                                class="h-28 w-full object-contain"
                            />
                        </div>
                    </div>

                    <!-- Actions -->
                    <div
                        class="grid grid-cols-2 gap-3 border-t border-gray-100 p-5"
                    >
                        <Button
                            variant="outline"
                            class="w-full rounded border-gray-200"
                            @click="router.visit('/collections')"
                        >
                            <ArrowLeft class="mr-1.5 h-4 w-4" />
                            Kembali
                        </Button>
                        <Button
                            v-if="can(PermissionEnum.CREATE_PENGAMBILAN_POO)"
                            class="w-full rounded bg-primary font-medium text-white hover:bg-primary-hover"
                            @click="
                                router.visit(
                                    '/collections/' +
                                        collection.poo_id +
                                        '/create',
                                )
                            "
                        >
                            Pengambilan Baru
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
