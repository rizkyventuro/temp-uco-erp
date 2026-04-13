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
    code: string;
    name: string;
    phone?: string | null;
    email?: string | null;
    city_id?: number | null;
    city_name?: string | null;
    monthly_capacity?: number | null;
    default_purchase_price?: number | null;
    bank?: string | null;
    account_number?: string | null;
    account_holder?: string | null;
    npwp?: string | null;
    pic?: string | null;
    address?: string | null;
    notes?: string | null;
    payment_term: 'cash' | 'tempo';
    payment_term_days?: number | null;
    payment_term_label?: string;
    is_active: boolean;
    inactive_reason?: string | null;
    photo_url?: string | null;
    initials: string;
}

// ── Props & Emits ──────────────────────────────────────────────

const props = defineProps<{
    open: boolean;
    editingSupplier?: Supplier | null;
    postUrl: string;
    putUrl?: string;
    cities: City[];
    nextCode?: string; // e.g. "SUP-006" passed from parent for new supplier
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'success'): void;
}>();

// ── Form ───────────────────────────────────────────────────────

const form = useForm({
    name: '',
    phone: '',
    email: '',
    city_id: '' as string | number,
    monthly_capacity: '' as string | number,
    default_purchase_price: '' as string | number,
    bank: '',
    account_number: '',
    account_holder: '',
    npwp: '',
    pic: '',
    address: '',
    notes: '',
    payment_term: 'cash' as 'cash' | 'tempo',
    payment_term_days: '' as string | number,
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
            displayKode.value = s.code ?? '';
            form.name = s.name;
            form.phone = s.phone ?? '';
            form.email = s.email ?? '';
            form.city_id = s.city_id ?? '';
            form.monthly_capacity = s.monthly_capacity ?? '';
            form.default_purchase_price = s.default_purchase_price ?? '';
            form.bank = s.bank ?? '';
            form.account_number = s.account_number ?? '';
            form.account_holder = s.account_holder ?? '';
            form.npwp = s.npwp ?? '';
            form.pic = s.pic ?? '';
            form.address = s.address ?? '';
            form.notes = s.notes ?? '';
            form.payment_term = s.payment_term ?? 'cash';
            form.payment_term_days = s.payment_term_days ?? '';
            form.foto = null;
            previewUrl.value = s.photo_url ?? null;
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
                            <Input v-model="form.name" placeholder="Nama lengkap atau perusahaan"
                                :class="inputClass(!!form.errors.name)" />
                            <span v-if="form.errors.name" class="text-xs text-red-500">{{ form.errors.name }}</span>
                        </div>

                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">Kode Supplier</Label>
                            <Input :model-value="displayKode" disabled
                                class="bg-gray-50 text-gray-500 cursor-not-allowed" />
                        </div>
                    </div>

                    <!-- Row 2: No. Telepon | Email -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">No. Telepon</Label>
                            <div class="flex items-center h-[45px] rounded-md border bg-white overflow-hidden"
                                :class="form.errors.phone ? 'border-red-400' : 'border-input'">
                                <span
                                    class="flex items-center px-3 text-sm text-gray-500 shrink-0 border-r border-gray-200 h-full bg-white">
                                    +62
                                </span>
                                <input v-model="form.phone" placeholder="Masukkan nomor telepon"
                                    class="flex-1 h-full px-3 text-sm text-gray-700 placeholder-gray-400 bg-transparent border-none outline-none focus:ring-0" />
                                <span class="flex items-center pr-3  shrink-0">
                                    <svg width="24" height="24" viewBox="0 0 24 24"
                                        class="rounded-full overflow-hidden">
                                        <rect width="24" height="12" fill="#FF0000" />
                                        <rect y="12" width="24" height="12" fill="#F9F9F9" />
                                    </svg>
                                </span>
                            </div>
                            <span v-if="form.errors.phone" class="text-xs text-red-500">{{ form.errors.phone
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
                            <Input v-model="form.monthly_capacity" type="number" min="0" placeholder="0"
                                :class="inputClass(!!form.errors.monthly_capacity)" />
                            <span v-if="form.errors.monthly_capacity" class="text-xs text-red-500">{{
                                form.errors.monthly_capacity }}</span>
                        </div>
                    </div>

                    <!-- Row 4: Harga Beli Default | Bank -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">Harga Beli Default (Rp/kg)</Label>
                            <Input v-model="form.default_purchase_price" type="number" min="0" placeholder="4500"
                                :class="inputClass(!!form.errors.default_purchase_price)" />
                            <span v-if="form.errors.default_purchase_price" class="text-xs text-red-500">{{
                                form.errors.default_purchase_price }}</span>
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
                            <Input v-model="form.account_number" placeholder="nomor rekening"
                                :class="inputClass(!!form.errors.account_number)" />
                            <span v-if="form.errors.account_number" class="text-xs text-red-500">{{ form.errors.account_number
                            }}</span>
                        </div>

                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">Atas Nama</Label>
                            <Input v-model="form.account_holder" placeholder="Atas nama pemilik rekening"
                                :class="inputClass(!!form.errors.account_holder)" />
                            <span v-if="form.errors.account_holder" class="text-xs text-red-500">{{ form.errors.account_holder
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
                            <select v-model="form.payment_term" :class="selectClass(!!form.errors.payment_term)">
                                <option value="cash">Cash</option>
                                <option value="tempo">Tempo</option>
                            </select>
                            <span v-if="form.errors.payment_term" class="text-xs text-red-500">{{ form.errors.payment_term }}</span>
                        </div>

                        <div v-if="form.payment_term === 'tempo'" class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">
                                Jangka Waktu (hari) <span class="text-red-500">*</span>
                            </Label>
                            <Input v-model="form.payment_term_days" type="number" min="1" placeholder="30"
                                :class="inputClass(!!form.errors.payment_term_days)" />
                            <span v-if="form.errors.payment_term_days" class="text-xs text-red-500">{{ form.errors.payment_term_days
                            }}</span>
                        </div>
                    </div>

                    <!-- Alamat Lengkap (full width) -->
                    <div class="grid gap-1.5">
                        <Label class="text-sm font-medium text-gray-700">Alamat Lengkap</Label>
                        <textarea v-model="form.address" rows="3" placeholder="alamat lengkap supplier..."
                            :class="textareaClass(!!form.errors.address)" />
                        <span v-if="form.errors.address" class="text-xs text-red-500">{{ form.errors.address }}</span>
                    </div>

                    <!-- Catatan Tambahan (full width) -->
                    <div class="grid gap-1.5">
                        <Label class="text-sm font-medium text-gray-700">Catatan Tambahan</Label>
                        <textarea v-model="form.notes" rows="3" placeholder="catatan tambahan..."
                            :class="textareaClass(!!form.errors.notes)" />
                        <span v-if="form.errors.notes" class="text-xs text-red-500">{{ form.errors.notes }}</span>
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