<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { ArrowLeft, Download, Lock } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { PermissionEnum } from '@/enums/PermissionEnum';
import { usePermission } from '@/composables/usePermission';
import { type BreadcrumbItem } from '@/types';

const { can } = usePermission();

interface FinalExport {
    id: string;
    batch_code: string;
    poo_name: string;
    volume: number;
    exported_at: string;
    refinery_name: string;
    // ISCC document data
    iscc: {
        poo_name: string;
        poo_street: string;
        poo_city: string;
        poo_country: string;
        poo_phone: string;
        uco_amount: string;
        recipient: string;
        signatory: string;
        place_date: string;
    };
}

const props = defineProps<{
    export: FinalExport;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Penjualan / Export', href: '/exports' },
    { title: 'Export Berhasil', href: '#' },
];

const formatDate = (d: string) =>
    new Date(d).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });

const downloadPDF = () => {
    window.open(`/exports/${props.export.id}/download-stored`, '_blank');
};

const selesai = () => {
    router.visit('/exports');
};
</script>

<template>
    <Head title="Export Berhasil - FINAL LOCKED" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col items-center gap-6 p-6">
            <div class="flex w-full max-w-2xl flex-col gap-5">
                <!-- Back -->
                <button
                    @click="selesai"
                    class="flex w-fit items-center gap-1.5 text-sm text-gray-500 transition hover:text-gray-700"
                >
                    <ArrowLeft class="h-4 w-4" />
                    Kembali
                </button>

                <!-- Card -->
                <div
                    class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
                >
                    <!-- Header status -->
                    <div class="border-b border-gray-100 px-6 pt-6 pb-5">
                        <div class="mb-1 flex items-center gap-2">
                            <h1 class="text-[16px] font-bold text-gray-900">
                                Export Berhasil — Status FINAL LOCKED
                            </h1>
                        </div>
                        <p class="text-[13px] text-gray-500">
                            Batch
                            <span class="font-semibold text-teal-600">{{
                                props.export.batch_code
                            }}</span>
                            telah di-lock dan tidak dapat diubah. Dokumen ISCC
                            siap diunduh.
                        </p>
                    </div>

                    <!-- ISCC Document Preview -->
                    <div class="h-[1100px] w-full overflow-hidden">
                        <iframe
                            :src="`/exports/${props.export.id}/iscc-preview`"
                            class="h-full w-full border-0 bg-white"
                        />
                    </div>

                    <!-- Action buttons -->
                    <div class="grid grid-cols-2 gap-3 p-5">
                        <Button
                            variant="outline"
                            class="w-full rounded border-gray-200 font-medium text-gray-600"
                            @click="selesai"
                        >
                            Selesai
                        </Button>
                        <Button
                            v-if="can(PermissionEnum.DOWNLOAD_PENJUALAN)"
                            @click="downloadPDF"
                            class="w-full rounded bg-primary font-medium text-white hover:bg-primary-hover"
                        >
                            <Download class="mr-2 h-4 w-4" />
                            Download PDF
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
