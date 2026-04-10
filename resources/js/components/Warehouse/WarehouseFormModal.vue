<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch, ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export interface City {
    id: number;
    name: string;
}

export interface Warehouse {
    id: string;
    kode: string;
    nama: string;
    city_id?: number | null;
    city_name?: string | null;
    tipe?: 'Utama' | 'Cabang' | 'Transit' | 'Sementara' | null;
    alamat?: string | null;
    pic?: string | null;
    telepon_pic?: string | null;
    kapasitas_maks?: number | null;
    stok_minimum?: number | null;
    harga_estimasi?: number | null;
    biaya_operasional?: number | null;
    is_active: boolean;
    alasan_nonaktif?: string | null;
    catatan?: string | null;
    stok_saat_ini?: number;
    utilisasi?: number;
}

const props = defineProps<{
    open: boolean;
    editingWarehouse?: Warehouse | null;
    postUrl: string;
    putUrl?: string;
    cities: City[];
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'success'): void;
}>();

const form = useForm({
    nama: '',
    city_id: '' as string | number,
    tipe: '' as string,
    alamat: '',
    pic: '',
    telepon_pic: '',
    kapasitas_maks: '' as string | number,
    stok_minimum: '' as string | number,
    harga_estimasi: '' as string | number,
    biaya_operasional: '' as string | number,
    status: 'Aktif' as string,
    catatan: '',
});

const displayKode = ref<string>('');
const isEditing = computed(() => !!props.editingWarehouse);

watch(
    () => props.editingWarehouse,
    (g) => {
        if (g) {
            displayKode.value = g.kode ?? '';
            form.nama = g.nama;
            form.city_id = g.city_id ?? '';
            form.tipe = g.tipe ?? '';
            form.alamat = g.alamat ?? '';
            form.pic = g.pic ?? '';
            form.telepon_pic = g.telepon_pic ?? '';
            form.kapasitas_maks = g.kapasitas_maks ?? '';
            form.stok_minimum = g.stok_minimum ?? '';
            form.harga_estimasi = g.harga_estimasi ?? '';
            form.biaya_operasional = g.biaya_operasional ?? '';
            form.status = g.is_active ? 'Aktif' : 'Nonaktif';
            form.catatan = g.catatan ?? '';
        } else {
            displayKode.value = '';
            form.reset();
            form.status = 'Aktif';
        }
        form.clearErrors();
    },
    { immediate: true },
);

function handleSubmit() {
    const onSuccess = () => {
        emit('update:open', false);
        emit('success');
        form.reset();
    };

    if (isEditing.value && props.editingWarehouse) {
        form.patch(`${props.putUrl ?? props.postUrl}/${props.editingWarehouse.id}`, { onSuccess });
    } else {
        form.post(props.postUrl, { onSuccess });
    }
}

const inputClass = (hasError: boolean) => [hasError ? 'border-red-400' : ''];
const selectClass = (hasError: boolean) => [
    'h-10 w-full rounded-md border bg-white px-3 text-sm text-gray-700',
    'focus:border-[#007C95] focus:ring-1 focus:ring-[#007C95] focus:outline-none',
    hasError ? 'border-red-400' : 'border-input',
];
const textareaClass = (hasError: boolean) => [
    'w-full resize-none rounded-md border bg-white px-3 py-2 text-sm text-gray-700 placeholder-gray-400',
    'focus:border-[#007C95] focus:ring-1 focus:ring-[#007C95] focus:outline-none',
    hasError ? 'border-red-400' : 'border-input',
];
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="flex max-h-[90vh] flex-col overflow-hidden rounded-2xl p-0 sm:max-w-2xl">

            <!-- Header -->
            <div class="flex-shrink-0 px-6 pt-6 pb-4 border-b border-gray-100">
                <DialogHeader>
                    <DialogTitle class="text-base font-semibold text-gray-900">
                        {{ isEditing ? 'Ubah Data Warehouse' : 'Tambah Warehouse Baru' }}
                    </DialogTitle>
                </DialogHeader>
            </div>

            <!-- Body -->
            <div class="flex-1 overflow-y-auto px-6 py-5">
                <div class="grid gap-4">

                    <!-- Nama Warehouse | Kode -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">Nama Warehouse</Label>
                            <Input v-model="form.nama" placeholder="Nama warehouse"
                                :class="inputClass(!!form.errors.nama)" />
                            <span v-if="form.errors.nama" class="text-xs text-red-500">{{ form.errors.nama }}</span>
                        </div>
                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">Kapasitas (kg)</Label>
                            <Input v-model="form.kapasitas_maks" type="number" min="0" placeholder="0"
                                :class="inputClass(!!form.errors.kapasitas_maks)" />
                            <span v-if="form.errors.kapasitas_maks" class="text-xs text-red-500">{{
                                form.errors.kapasitas_maks }}</span>
                        </div>
                    </div>

                    <!-- Kota | Tipe -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">Kota</Label>
                            <select v-model="form.city_id" :class="selectClass(!!form.errors.city_id)">
                                <option value="">Pilih kota</option>
                                <option v-for="city in cities" :key="city.id" :value="city.id">{{ city.name }}</option>
                            </select>
                            <span v-if="form.errors.city_id" class="text-xs text-red-500">{{ form.errors.city_id
                                }}</span>
                        </div>
                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">Tipe</Label>
                            <select v-model="form.tipe" :class="selectClass(!!form.errors.tipe)">
                                <option value="">Pilih tipe</option>
                                <option value="Utama">Utama</option>
                                <option value="Cabang">Cabang</option>
                                <option value="Transit">Transit</option>
                                <option value="Sementara">Sementara</option>
                            </select>
                            <span v-if="form.errors.tipe" class="text-xs text-red-500">{{ form.errors.tipe }}</span>
                        </div>
                    </div>

                    <!-- Alamat Lengkap -->
                    <div class="grid gap-1.5">
                        <Label class="text-sm font-medium text-gray-700">Alamat Lengkap</Label>
                        <textarea v-model="form.alamat" rows="3" placeholder="alamat lengkap warehouse..."
                            :class="textareaClass(!!form.errors.alamat)" />
                        <span v-if="form.errors.alamat" class="text-xs text-red-500">{{ form.errors.alamat }}</span>
                    </div>

                    <!-- PIC | No. Telepon PIC -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">Penanggung Jawab (PIC)</Label>
                            <Input v-model="form.pic" placeholder="Nama PIC" :class="inputClass(!!form.errors.pic)" />
                            <span v-if="form.errors.pic" class="text-xs text-red-500">{{ form.errors.pic }}</span>
                        </div>
                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">No. Telepon PIC</Label>
                            <div class="flex items-center h-[45px] rounded-md border bg-white overflow-hidden"
                                :class="form.errors.telepon_pic ? 'border-red-400' : 'border-input'">
                                <span
                                    class="flex items-center px-3 text-sm text-gray-500 shrink-0 border-r border-gray-200 h-full bg-white">
                                    +62
                                </span>
                                <input v-model="form.telepon_pic" placeholder="Masukkan nomor telepon"
                                    class="flex-1 h-full px-3 text-sm text-gray-700 placeholder-gray-400 bg-transparent border-none outline-none focus:ring-0" />
                                <span class="flex items-center pr-3  shrink-0">
                                    <svg width="24" height="24" viewBox="0 0 24 24" class="rounded-full overflow-hidden">
                                        <rect width="24" height="12" fill="#FF0000" />
                                        <rect y="12" width="24" height="12" fill="#F9F9F9" />
                                    </svg>
                                </span>
                            </div>
                            <span v-if="form.errors.telepon_pic" class="text-xs text-red-500">{{ form.errors.telepon_pic
                                }}</span>
                        </div>
                    </div>

                    <!-- Biaya Operasional | Status -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">Biaya Operasional/Bulan (Rp)</Label>
                            <Input v-model="form.biaya_operasional" type="number" min="0" placeholder="0"
                                :class="inputClass(!!form.errors.biaya_operasional)" />
                            <span v-if="form.errors.biaya_operasional" class="text-xs text-red-500">{{
                                form.errors.biaya_operasional }}</span>
                        </div>
                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">Status</Label>
                            <select v-model="form.status" :class="selectClass(false)">
                                <option value="Aktif">Aktif</option>
                                <option value="Nonaktif">Nonaktif</option>
                            </select>
                        </div>
                    </div>

                    <!-- Stok Minimum | Harga Estimasi -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">Stok Minimum (kg)</Label>
                            <Input v-model="form.stok_minimum" type="number" min="0" placeholder="0"
                                :class="inputClass(!!form.errors.stok_minimum)" />
                            <span v-if="form.errors.stok_minimum" class="text-xs text-red-500">{{
                                form.errors.stok_minimum }}</span>
                        </div>
                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">Harga Estimasi (Rp/kg)</Label>
                            <Input v-model="form.harga_estimasi" type="number" min="0" placeholder="4.400"
                                :class="inputClass(!!form.errors.harga_estimasi)" />
                            <span v-if="form.errors.harga_estimasi" class="text-xs text-red-500">{{
                                form.errors.harga_estimasi }}</span>
                        </div>
                    </div>

                    <!-- Catatan -->
                    <div class="grid gap-1.5">
                        <Label class="text-sm font-medium text-gray-700">Catatan</Label>
                        <textarea v-model="form.catatan" rows="3" placeholder="catatan tambahan..."
                            :class="textareaClass(!!form.errors.catatan)" />
                        <span v-if="form.errors.catatan" class="text-xs text-red-500">{{ form.errors.catatan }}</span>
                    </div>

                </div>
            </div>

            <!-- Footer -->
            <div class="flex justify-end gap-3 border-t border-gray-100 p-5">
                <Button variant="outline" class="w-fit rounded-md" :disabled="form.processing"
                    @click="emit('update:open', false)">
                    Batal
                </Button>
                <Button @click="handleSubmit" :disabled="form.processing"
                    class="w-fit rounded-md bg-[#007C95] text-white hover:bg-[#006b80]">
                    {{ form.processing ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Simpan Warehouse') }}
                </Button>
            </div>

        </DialogContent>
    </Dialog>
</template>