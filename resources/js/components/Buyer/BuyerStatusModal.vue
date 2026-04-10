<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch, computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import type { Buyer } from '@/components/Buyer/BuyerFormModal.vue';

const props = defineProps<{
    open: boolean;
    buyer: Buyer | null;
    toggleUrl: string;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'success'): void;
}>();

const ALASAN_OPTIONS = [
    'Tidak aktif beroperasi',
    'Pindah lokasi',
    'Kualitas tidak memenuhi standar',
    'Harga tidak kompetitif',
    'Lainnya',
];

const form = useForm({
    alasan_nonaktif: '',
    catatan: '',
});

const isDeactivating = computed(() => !!props.buyer?.is_active);
const alasanOpen = ref(false);

import { ref } from 'vue';

watch(() => props.open, (open) => {
    if (!open) {
        form.reset();
        form.clearErrors();
        alasanOpen.value = false;
    }
});

function selectAlasan(val: string) {
    form.alasan_nonaktif = val;
    alasanOpen.value = false;
}

function handleSubmit() {
    if (!props.buyer) return;
    form.patch(`${props.toggleUrl}/${props.buyer.id}/toggle-status`, {
        onSuccess: () => {
            emit('update:open', false);
            emit('success');
            form.reset();
        },
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="rounded-2xl p-0 sm:max-w-md">

            <div class="px-6 pt-6 pb-0">
                <DialogHeader>
                    <DialogTitle class="text-lg font-semibold text-gray-900">
                        {{ isDeactivating ? 'Konfirmasi Nonaktifkan' : 'Aktifkan Buyer' }}
                    </DialogTitle>
                </DialogHeader>
            </div>

            <div class="px-6 pt-4 pb-5 grid gap-5">

                <!-- Banner -->
                <div v-if="isDeactivating"
                    class="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">
                    <span
                        class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-amber-400 text-white text-xs font-bold">!</span>
                    <div class="flex-1 text-sm text-amber-800 leading-snug">
                        Buyer yang dinonaktifkan tidak dapat menerima penjualan baru. Data historis tetap tersimpan.
                    </div>
                    <button type="button" class="text-amber-400 hover:text-amber-600 shrink-0"
                        @click="emit('update:open', false)">
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                            <path d="M13 1L1 13M1 1l12 12" stroke="currentColor" stroke-width="1.5"
                                stroke-linecap="round" />
                        </svg>
                    </button>
                </div>
                <div v-else class="flex items-start gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3">
                    <span
                        class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white text-xs font-bold">✓</span>
                    <div class="flex-1 text-sm text-emerald-800 leading-snug">
                        Buyer akan diaktifkan kembali dan dapat digunakan dalam penjualan baru.
                    </div>
                    <button type="button" class="text-emerald-400 hover:text-emerald-600 shrink-0"
                        @click="emit('update:open', false)">
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                            <path d="M13 1L1 13M1 1l12 12" stroke="currentColor" stroke-width="1.5"
                                stroke-linecap="round" />
                        </svg>
                    </button>
                </div>

                <!-- Confirmation text -->
                <p class="text-sm text-gray-800">
                    <template v-if="isDeactivating">
                        Anda yakin ingin menonaktifkan buyer <strong>"{{ buyer?.nama }}"</strong>?
                    </template>
                    <template v-else>
                        Aktifkan kembali buyer <strong>"{{ buyer?.nama }}"</strong>?
                    </template>
                </p>

                <!-- Alasan -->
                <div v-if="isDeactivating" class="grid gap-1.5">
                    <Label class="text-sm font-semibold text-gray-900">Alasan Nonaktifkan</Label>
                    <div class="relative">
                        <button type="button"
                            class="h-11 w-full rounded-lg border border-gray-200 bg-white px-3 text-left text-sm flex items-center justify-between focus:border-gray-400 focus:outline-none"
                            :class="form.alasan_nonaktif ? 'text-gray-800' : 'text-gray-400'"
                            @click="alasanOpen = !alasanOpen">
                            <span>{{ form.alasan_nonaktif || 'Tidak aktif beroperasi' }}</span>
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" class="shrink-0 text-gray-400">
                                <path d="M4 6l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                        </button>
                        <div v-if="alasanOpen"
                            class="absolute z-10 mt-1 w-full rounded-lg border border-gray-200 bg-white shadow-md py-1">
                            <button v-for="opt in ALASAN_OPTIONS" :key="opt" type="button"
                                class="w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50"
                                @click="selectAlasan(opt)">
                                {{ opt }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Catatan -->
                <div v-if="isDeactivating" class="grid gap-1.5">
                    <Label class="text-sm font-semibold text-gray-900">Catatan (opsional)</Label>
                    <textarea v-model="form.catatan" rows="4" placeholder="Masukkan catatan"
                        class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-700 placeholder-gray-400 resize-none focus:border-gray-400 focus:outline-none" />
                </div>

            </div>

            <div class="grid grid-cols-2 gap-3 border-t border-gray-100 px-6 py-4">
                <Button variant="outline" class="w-full rounded-xl h-11 text-gray-600 border-gray-200"
                    :disabled="form.processing" @click="emit('update:open', false)">
                    Batal
                </Button>
                <Button :disabled="form.processing" :class="[
                    'w-full rounded-xl h-11 text-white font-medium',
                    isDeactivating ? 'bg-red-500 hover:bg-red-600' : 'bg-emerald-600 hover:bg-emerald-700',
                ]" @click="handleSubmit">
                    {{ form.processing ? 'Menyimpan...' : (isDeactivating ? 'Nonaktifkan' : 'Aktifkan') }}
                </Button>
            </div>

        </DialogContent>
    </Dialog>
</template>