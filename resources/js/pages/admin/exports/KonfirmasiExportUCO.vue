<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { ArrowLeft, AlertTriangle } from 'lucide-vue-next';
import { toast } from 'vue-sonner';
import TimelineOwnership from '@/components/TimelineOwnership.vue';
import { Button } from '@/components/ui/button';
import { PermissionEnum } from '@/enums/PermissionEnum';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { usePermission } from '@/composables/usePermission';
import { type BreadcrumbItem } from '@/types';

const { can } = usePermission();

interface Ownership {
    id: number;
    role: 'poo' | 'pengepul';
    name: string;
    company: string;
    location: string;
    volume: number;
    owned_at: string;
    is_current: boolean;
}

interface Batch {
    id: string;
    code: string;
    poo_name: string;
    volume: number;
    collection_date: string;
}

interface Refinery {
    id: number;
    name: string;
}

const props = defineProps<{
    batch: Batch;
    ownerships: Ownership[];
    refineries: Refinery[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Penjualan / Export', href: '/exports' },
    { title: 'Konfirmasi Export', href: '#' },
];

const form = useForm({
    refinery_name: '',
});

const formatDate = (d: string) =>
    new Date(d).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });

const handleGenerate = () => {
    if (!form.refinery_name) {
        toast.error('Pilih nama refinery / pembeli terlebih dahulu');
        return;
    }
    form.post(`/exports/${props.batch.id}/generate`, {
        onSuccess: () => {
            toast.success('Berhasil!', {
                description: 'Dokumen ISCC berhasil digenerate',
            });
        },
        onError: () => {
            toast.error('Gagal!', {
                description: 'Terjadi kesalahan saat generate dokumen',
            });
        },
    });
};
</script>

<template>
    <Head title="Konfirmasi Final Export" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col items-center gap-6 p-6">
            <div class="flex w-full max-w-xl flex-col gap-5">
                <!-- Back -->
                <button
                    @click="router.visit('/exports')"
                    class="flex w-fit items-center gap-1.5 text-sm text-gray-500 transition hover:text-gray-700"
                >
                    <ArrowLeft class="h-4 w-4" />
                    Kembali
                </button>

                <!-- Card -->
                <div
                    class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
                >
                    <div class="p-5">
                        <div class="pb-4">
                            <h1 class="text-[16px] font-bold text-gray-900">
                                Konfirmasi Final Export
                            </h1>
                            <p class="mt-0.5 text-[13px] text-gray-500">
                                Periksa detail sebelum melakukan final export
                            </p>
                        </div>

                        <div class="grid gap-5">
                            <!-- Detail Batch -->
                            <div class="rounded-2xl bg-[#F5F7FA] px-4 py-5">
                                <p
                                    class="mb-4 text-[11px] font-bold tracking-widest text-gray-400 uppercase"
                                >
                                    Detail Batch
                                </p>
                                <div class="space-y-3">
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span class="text-sm text-gray-500"
                                            >Kode Batch</span
                                        >
                                        <span
                                            class="text-sm font-semibold text-teal-600"
                                            >{{ props.batch.code }}</span
                                        >
                                    </div>
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span class="text-sm text-gray-500"
                                            >POO</span
                                        >
                                        <span
                                            class="text-sm font-semibold text-gray-900"
                                            >{{ props.batch.poo_name }}</span
                                        >
                                    </div>
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span class="text-sm text-gray-500"
                                            >Volume</span
                                        >
                                        <span
                                            class="text-sm font-semibold text-gray-900"
                                            >{{ props.batch.volume }} L</span
                                        >
                                    </div>
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span class="text-sm text-gray-500"
                                            >Tanggal</span
                                        >
                                        <span
                                            class="text-sm font-semibold text-gray-900"
                                            >{{
                                                formatDate(
                                                    props.batch.collection_date,
                                                )
                                            }}</span
                                        >
                                    </div>
                                </div>
                            </div>

                            <!-- Nama Refinery -->
                            <div class="grid gap-1.5">
                                <Label class="text-sm font-semibold">
                                    Nama Refinery/Pembeli
                                    <span class="text-red-500">*</span>
                                </Label>
                                <Select v-model="form.refinery_name">
                                    <SelectTrigger
                                        class="w-full rounded rounded-core border-gray-200 focus:border-primary focus:ring-2 focus:ring-primary-surface"
                                        :class="{
                                            'border-red-400':
                                                form.errors.refinery_name,
                                        }"
                                    >
                                        <SelectValue
                                            placeholder="Pilih refinery / pembeli"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="r in props.refineries"
                                            :key="r.id"
                                            :value="r.name"
                                        >
                                            {{ r.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <span
                                    v-if="form.errors.refinery_name"
                                    class="text-xs text-red-500"
                                >
                                    {{ form.errors.refinery_name }}
                                </span>
                            </div>

                            <!-- Rantai Kepemilikan -->
                            <div class="rounded-2xl bg-gray-100 p-4">
                                <p
                                    class="mb-5 text-sm font-semibold text-gray-800"
                                >
                                    Rantai Kepemilikan
                                </p>
                                <TimelineOwnership
                                    :ownerships="props.ownerships"
                                    :format-date="formatDate"
                                />
                            </div>

                            <!-- Warning -->
                            <div
                                class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3.5"
                            >
                                <AlertTriangle
                                    class="mt-0.5 h-4 w-4 flex-shrink-0 text-red-500"
                                />
                                <p class="text-xs leading-relaxed text-red-600">
                                    <span class="font-bold">PERHATIAN:</span>
                                    Setelah konfirmasi, batch ini akan berstatus
                                    <span class="font-bold">FINAL LOCKED</span>
                                    dan tidak dapat ditransfer atau diubah lagi.
                                    Tindakan ini tidak dapat dibatalkan.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div
                        class="grid grid-cols-2 gap-3 border-t border-gray-100 p-5"
                    >
                        <Button
                            variant="outline"
                            class="w-full rounded border-gray-200 text-gray-600"
                            @click="router.visit('/exports')"
                        >
                            Batal
                        </Button>
                        <Button
                            v-if="can(PermissionEnum.CREATE_PENJUALAN)"
                            @click="handleGenerate"
                            :disabled="form.processing"
                            class="w-full rounded bg-primary font-medium text-white hover:bg-primary-hover"
                        >
                            Generate ISCC Dokumen
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
