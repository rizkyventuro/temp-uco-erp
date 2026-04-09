<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { ArrowLeft, Camera, MapPin, Store } from 'lucide-vue-next';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import BadgeBussines from '@/components/BadgeBussines.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface POO {
    id: number;
    name: string;
    address: string;
    type: 'Restoran' | 'UMKM' | 'Rumah Tangga';
}

const props = defineProps<{
    poo: POO;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pengambilan dari POO', href: '/collections/' },
    { title: 'Catat Pengambilan', href: '#' },
];

const photoPreview = ref<string | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);

const form = useForm({
    poo_id: props.poo.id,
    volume: '',
    collection_date: new Date().toISOString().split('T')[0],
    photo: null as File | null,
    notes: '',
});

const handlePhotoChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    const file = target.files?.[0];
    if (file) {
        form.photo = file;
        const reader = new FileReader();
        reader.onload = (ev) => {
            photoPreview.value = ev.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};

const triggerUpload = () => {
    fileInput.value?.click();
};

const handleSubmit = () => {
    form.post('/poos/batches', {
        onError: () => {
            toast.error('Gagal!', {
                description: 'Terjadi kesalahan saat menyimpan data',
            });
        },
    });
};
</script>

<template>
    <Head title="Catat Pengambilan UCO" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col items-center gap-6 p-6">
            <div class="flex w-full max-w-xl flex-col gap-5">
                <!-- Back -->
                <button
                    @click="router.visit('/poos/')"
                    class="flex w-fit items-center gap-1.5 text-sm text-gray-500 transition hover:text-gray-700"
                >
                    <ArrowLeft class="h-4 w-4" />
                    Kembali
                </button>

                <!-- Card -->
                <div
                    class="overflow-hidden rounded-2xl border border-[#EDEDED] bg-white shadow-sm"
                >
                    <!-- Header -->
                    <div class="border-b border-gray-100 px-6 pt-6 pb-4">
                        <h1 class="text-[16px] font-bold text-gray-900">
                            Catat Pengambilan UCO
                        </h1>
                        <p class="mt-0.5 text-[13px] text-gray-500">
                            Isi detail pengambilan minyak jelantah
                        </p>
                    </div>

                    <div class="grid gap-5 p-6">
                        <!-- POO Terpilih -->
                        <div
                            class="rounded-xl border border-primary bg-primary-surface p-4"
                        >
                            <p class="mb-2 text-xs font-semibold text-primary">
                                POO Terpilih
                            </p>
                            <p class="text-sm font-bold text-gray-900">
                                {{ props.poo.name }}
                            </p>
                            <div class="mt-1 flex items-start gap-1.5">
                                <MapPin
                                    class="mt-0.5 h-3.5 w-3.5 flex-shrink-0 text-gray-400"
                                />
                                <p class="text-xs text-gray-500">
                                    {{ props.poo.address }}
                                </p>
                            </div>
                            <div class="mt-2">
                                <BadgeBussines :type="poo.type" />
                            </div>
                        </div>

                        <!-- Volume -->
                        <div class="grid gap-1.5">
                            <Label class="text-sm font-bold">
                                Volume Minyak (Liter)
                                <span class="text-red-500">*</span>
                            </Label>
                            <Input
                                v-model="form.volume"
                                type="number"
                                placeholder="Contoh: 25"
                                class="focus:primary-surface border-[#EDEDED] focus:border-primary-hover focus:ring-2"
                                :class="{
                                    'border-red-400': form.errors.volume,
                                }"
                            />
                            <span
                                v-if="form.errors.volume"
                                class="text-xs text-red-500"
                                >{{ form.errors.volume }}</span
                            >
                        </div>

                        <!-- Tanggal Pengambilan -->
                        <div class="grid gap-1.5">
                            <Label class="text-sm font-bold">
                                Tanggal Pengambilan
                                <span class="text-red-500">*</span>
                            </Label>
                            <Input
                                v-model="form.collection_date"
                                type="date"
                                class="focus:primary-surface border-[#EDEDED] focus:border-primary-hover focus:ring-2"
                                :class="{
                                    'border-red-400':
                                        form.errors.collection_date,
                                }"
                            />
                            <span
                                v-if="form.errors.collection_date"
                                class="text-xs text-red-500"
                                >{{ form.errors.collection_date }}</span
                            >
                        </div>

                        <!-- Foto Pengambilan -->
                        <div class="grid gap-1.5">
                            <Label class="text-sm font-bold"
                                >Foto Pengambilan</Label
                            >
                            <input
                                ref="fileInput"
                                type="file"
                                accept="image/*"
                                class="hidden"
                                @change="handlePhotoChange"
                            />
                            <button
                                @click="triggerUpload"
                                type="button"
                                class="flex w-full cursor-pointer flex-col items-center justify-center gap-2 overflow-hidden rounded-xl border-2 border-dashed border-[#D6D6D6] bg-gray-50 py-8 transition hover:border-primary hover:bg-primary-surface"
                            >
                                <template v-if="photoPreview">
                                    <img
                                        :src="photoPreview"
                                        class="max-h-40 rounded-lg object-contain"
                                    />
                                    <span class="mt-1 text-xs text-gray-400"
                                        >Klik untuk ganti foto</span
                                    >
                                </template>
                                <template v-else>
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100"
                                    >
                                        <Camera class="h-5 w-5 text-gray-400" />
                                    </div>
                                    <span
                                        class="text-sm font-bold text-gray-500"
                                        >Upload</span
                                    >
                                    <span class="text-xs text-gray-400"
                                        >Foto saat pengambilan (opsional)</span
                                    >
                                </template>
                            </button>
                        </div>

                        <!-- Catatan -->
                        <div class="grid gap-1.5">
                            <Label class="text-sm font-bold">Catatan</Label>
                            <textarea
                                v-model="form.notes"
                                placeholder="Catatan tambahan (opsional)..."
                                rows="3"
                                class="w-full rounded-core border px-3 py-2.5 text-sm placeholder-gray-400 focus:border-primary-surface focus:ring-2 focus:outline-none"
                            />
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-6 pb-6">
                        <Button
                            @click="handleSubmit"
                            :disabled="form.processing"
                            class="w-full rounded bg-primary py-2.5 font-medium text-white hover:bg-primary-hover"
                        >
                            Simpan &amp; Generate QR
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
