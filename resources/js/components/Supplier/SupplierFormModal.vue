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

// ── Types ──────────────────────────────────────────────────────

export interface City {
    id: number;
    name: string;
}

export interface Supplier {
    id: string;
    kode: string;
    nama: string;
    telepon?: string | null;
    email?: string | null;
    city_id?: number | null;
    city_name?: string | null;
    kapasitas_per_bulan?: number | null;
    harga_beli_default?: number | null;
    bank?: string | null;
    no_rekening?: string | null;
    atas_nama?: string | null;
    npwp?: string | null;
    pic?: string | null;
    alamat?: string | null;
    catatan?: string | null;
    termin: 'cash' | 'tempo';
    termin_hari?: number | null;
    termin_label?: string;
    is_active: boolean;
    alasan_nonaktif?: string | null;
    foto_url?: string | null;
    inisials: string;
}

// ── Props & Emits ──────────────────────────────────────────────

const props = defineProps<{
    open: boolean;
    editingSupplier?: Supplier | null;
    postUrl: string;
    putUrl?: string;
    cities: City[];
    nextKode?: string; // e.g. "SUP-006" passed from parent for new supplier
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'success'): void;
}>();

// ── Form ───────────────────────────────────────────────────────

const form = useForm({
    nama: '',
    telepon: '',
    email: '',
    city_id: '' as string | number,
    kapasitas_per_bulan: '' as string | number,
    harga_beli_default: '' as string | number,
    bank: '',
    no_rekening: '',
    atas_nama: '',
    npwp: '',
    pic: '',
    alamat: '',
    catatan: '',
    termin: 'cash' as 'cash' | 'tempo',
    termin_hari: '' as string | number,
    foto: null as File | null,
});

const fileInputRef = ref<HTMLInputElement | null>(null);
const previewUrl = ref<string | null>(null);
const selectedFileName = ref<string>('');
const displayKode = ref<string>('');

const isEditing = computed(() => !!props.editingSupplier);

watch(
    () => props.editingSupplier,
    (s) => {
        if (s) {
            displayKode.value = s.kode ?? '';
            form.nama = s.nama;
            form.telepon = s.telepon ?? '';
            form.email = s.email ?? '';
            form.city_id = s.city_id ?? '';
            form.kapasitas_per_bulan = s.kapasitas_per_bulan ?? '';
            form.harga_beli_default = s.harga_beli_default ?? '';
            form.bank = s.bank ?? '';
            form.no_rekening = s.no_rekening ?? '';
            form.atas_nama = s.atas_nama ?? '';
            form.npwp = s.npwp ?? '';
            form.pic = s.pic ?? '';
            form.alamat = s.alamat ?? '';
            form.catatan = s.catatan ?? '';
            form.termin = s.termin ?? 'cash';
            form.termin_hari = s.termin_hari ?? '';
            form.foto = null;
            previewUrl.value = s.foto_url ?? null;
            selectedFileName.value = '';
        } else {
            displayKode.value = '';
            form.reset();
            previewUrl.value = null;
            selectedFileName.value = '';
        }
        form.clearErrors();
    },
    { immediate: true },
);

// ── Foto handling ──────────────────────────────────────────────

function triggerFileInput() {
    fileInputRef.value?.click();
}

function handleFileChange(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) return;
    form.foto = file;
    selectedFileName.value = file.name;
    const reader = new FileReader();
    reader.onload = (e) => { previewUrl.value = e.target?.result as string; };
    reader.readAsDataURL(file);
}

function removeFoto() {
    previewUrl.value = null;
    selectedFileName.value = '';
    form.foto = null;
    if (fileInputRef.value) fileInputRef.value.value = '';
}

// ── Submit ─────────────────────────────────────────────────────

function handleSubmit() {
    const onSuccess = () => {
        emit('update:open', false);
        emit('success');
        form.reset();
        previewUrl.value = null;
        selectedFileName.value = '';
    };

    if (isEditing.value && props.editingSupplier) {
        form.patch(`${props.putUrl ?? props.postUrl}/${props.editingSupplier.id}`, { onSuccess });
    } else {
        form.post(props.postUrl, { onSuccess });
    }
}

// ── Input class helpers ────────────────────────────────────────

const inputClass = (hasError: boolean) => [
    hasError ? 'border-red-400' : '',
];

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
                        {{ isEditing ? 'Ubah Data Supplier' : 'Tambah Supplier Baru' }}
                    </DialogTitle>
                </DialogHeader>
            </div>

            <!-- Body (scrollable) -->
            <div class="flex-1 overflow-y-auto px-6 py-5">
                <div class="grid gap-4">

                    <!-- Row 1: Nama Supplier | Kode Supplier -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">Nama Supplier</Label>
                            <Input v-model="form.nama" placeholder="Nama lengkap atau perusahaan"
                                :class="inputClass(!!form.errors.nama)" />
                            <span v-if="form.errors.nama" class="text-xs text-red-500">{{ form.errors.nama }}</span>
                        </div>

                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">Kode Supplier</Label>
                            <Input :model-value="displayKode" disabled class="bg-gray-50 text-gray-500 cursor-not-allowed" />
                        </div>
                    </div>

                    <!-- Row 2: No. Telepon | Email -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">No. Telepon</Label>
                            <div class="flex gap-2">
                                <span
                                    class="flex h-10 items-center rounded-md border border-input bg-gray-50 px-3 text-sm text-gray-500 shrink-0">
                                    +62
                                </span>
                                <Input v-model="form.telepon" placeholder="Input your phone"
                                    :class="['flex-1', { 'border-red-400': form.errors.telepon }]" />
                            </div>
                            <span v-if="form.errors.telepon" class="text-xs text-red-500">{{ form.errors.telepon
                                }}</span>
                        </div>

                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">Email</Label>
                            <Input v-model="form.email" type="email" placeholder="email@domain.com"
                                :class="inputClass(!!form.errors.email)" />
                            <span v-if="form.errors.email" class="text-xs text-red-500">{{ form.errors.email }}</span>
                        </div>
                    </div>

                    <!-- Row 3: Kota | Kapasitas/Bulan -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">Kota</Label>
                            <select v-model="form.city_id" :class="selectClass(!!form.errors.city_id)">
                                <option value="">Pilih Kota</option>
                                <option v-for="city in cities" :key="city.id" :value="city.id">
                                    {{ city.name }}
                                </option>
                            </select>
                            <span v-if="form.errors.city_id" class="text-xs text-red-500">{{ form.errors.city_id
                                }}</span>
                        </div>

                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">Kapasitas/Bulan (kg)</Label>
                            <Input v-model="form.kapasitas_per_bulan" type="number" min="0" placeholder="0"
                                :class="inputClass(!!form.errors.kapasitas_per_bulan)" />
                            <span v-if="form.errors.kapasitas_per_bulan" class="text-xs text-red-500">{{
                                form.errors.kapasitas_per_bulan }}</span>
                        </div>
                    </div>

                    <!-- Row 4: Harga Beli Default | Bank -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">Harga Beli Default (Rp/kg)</Label>
                            <Input v-model="form.harga_beli_default" type="number" min="0" placeholder="4500"
                                :class="inputClass(!!form.errors.harga_beli_default)" />
                            <span v-if="form.errors.harga_beli_default" class="text-xs text-red-500">{{
                                form.errors.harga_beli_default }}</span>
                        </div>

                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">Bank</Label>
                            <Input v-model="form.bank" placeholder="Nama bank"
                                :class="inputClass(!!form.errors.bank)" />
                            <span v-if="form.errors.bank" class="text-xs text-red-500">{{ form.errors.bank }}</span>
                        </div>
                    </div>

                    <!-- Row 5: No. Rekening | Atas Nama -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">No. Rekening</Label>
                            <Input v-model="form.no_rekening" placeholder="nomor rekening"
                                :class="inputClass(!!form.errors.no_rekening)" />
                            <span v-if="form.errors.no_rekening" class="text-xs text-red-500">{{ form.errors.no_rekening
                                }}</span>
                        </div>

                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">Atas Nama</Label>
                            <Input v-model="form.atas_nama" placeholder="Atas nama pemilik rekening"
                                :class="inputClass(!!form.errors.atas_nama)" />
                            <span v-if="form.errors.atas_nama" class="text-xs text-red-500">{{ form.errors.atas_nama
                                }}</span>
                        </div>
                    </div>

                    <!-- Row 6: NPWP | PIC / Contact Person -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">NPWP</Label>
                            <Input v-model="form.npwp" placeholder="xx.xxx.xxx.x-xxx.xxx"
                                :class="inputClass(!!form.errors.npwp)" />
                            <span v-if="form.errors.npwp" class="text-xs text-red-500">{{ form.errors.npwp }}</span>
                        </div>

                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">PIC / Contact Person</Label>
                            <Input v-model="form.pic" placeholder="Nama penanggung jawab"
                                :class="inputClass(!!form.errors.pic)" />
                            <span v-if="form.errors.pic" class="text-xs text-red-500">{{ form.errors.pic }}</span>
                        </div>
                    </div>

                    <!-- Row 7: Termin Pembayaran | Termin Hari (conditional) -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">
                                Termin Pembayaran <span class="text-red-500">*</span>
                            </Label>
                            <select v-model="form.termin" :class="selectClass(!!form.errors.termin)">
                                <option value="cash">Cash</option>
                                <option value="tempo">Tempo</option>
                            </select>
                            <span v-if="form.errors.termin" class="text-xs text-red-500">{{ form.errors.termin }}</span>
                        </div>

                        <div v-if="form.termin === 'tempo'" class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">
                                Jangka Waktu (hari) <span class="text-red-500">*</span>
                            </Label>
                            <Input v-model="form.termin_hari" type="number" min="1" placeholder="30"
                                :class="inputClass(!!form.errors.termin_hari)" />
                            <span v-if="form.errors.termin_hari" class="text-xs text-red-500">{{ form.errors.termin_hari
                                }}</span>
                        </div>
                    </div>

                    <!-- Alamat Lengkap (full width) -->
                    <div class="grid gap-1.5">
                        <Label class="text-sm font-medium text-gray-700">Alamat Lengkap</Label>
                        <textarea v-model="form.alamat" rows="3" placeholder="alamat lengkap supplier..."
                            :class="textareaClass(!!form.errors.alamat)" />
                        <span v-if="form.errors.alamat" class="text-xs text-red-500">{{ form.errors.alamat }}</span>
                    </div>

                    <!-- Catatan Tambahan (full width) -->
                    <div class="grid gap-1.5">
                        <Label class="text-sm font-medium text-gray-700">Catatan Tambahan</Label>
                        <textarea v-model="form.catatan" rows="3" placeholder="catatan tambahan..."
                            :class="textareaClass(!!form.errors.catatan)" />
                        <span v-if="form.errors.catatan" class="text-xs text-red-500">{{ form.errors.catatan }}</span>
                    </div>

                    <!-- Foto Supplier -->
                    <div class="grid gap-1.5">
                        <Label class="text-sm font-medium text-gray-700">Foto Supplier</Label>
                        <div class="relative flex cursor-pointer flex-col items-center justify-center gap-3 rounded-lg border border-dashed border-input p-5 transition-colors hover:bg-muted/40"
                            @click="triggerFileInput">
                            <div v-if="previewUrl" class="relative">
                                <img :src="previewUrl" alt="Foto preview"
                                    class="h-20 w-20 rounded-full border border-border object-cover" />
                                <button type="button"
                                    class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full border border-border bg-background text-xs text-muted-foreground hover:text-foreground"
                                    @click.stop="removeFoto">✕</button>
                            </div>
                            <div v-else
                                class="flex h-12 w-12 items-center justify-center rounded-full border border-border bg-muted">
                                <svg class="h-5 w-5 text-muted-foreground" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="1.5">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                            </div>
                            <div class="text-center">
                                <p class="text-sm text-foreground">
                                    {{ previewUrl ? 'Klik untuk ganti foto' : 'Klik untuk upload foto' }}
                                </p>
                                <p v-if="selectedFileName"
                                    class="mt-0.5 max-w-[200px] truncate text-xs text-muted-foreground">
                                    {{ selectedFileName }}
                                </p>
                                <p v-else class="text-xs text-muted-foreground">PNG, JPG, WebP maks. 2MB</p>
                            </div>
                            <input ref="fileInputRef" type="file" accept="image/*" class="hidden"
                                @change="handleFileChange" />
                        </div>
                        <span v-if="form.errors.foto" class="mt-1 block text-xs text-red-500">{{ form.errors.foto
                            }}</span>
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
                    {{ form.processing
                        ? 'Menyimpan...'
                        : (isEditing ? 'Simpan Perubahan' : 'Simpan Supplier') }}
                </Button>
            </div>

        </DialogContent>
    </Dialog>
</template>