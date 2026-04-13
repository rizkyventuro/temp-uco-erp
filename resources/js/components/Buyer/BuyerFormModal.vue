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

export interface Buyer {
    id: string;
    code: string;
    name: string;
    type?: 'PT' | 'CV' | 'UD' | 'Perorangan' | null;
    phone?: string | null;
    email?: string | null;
    city_id?: number | null;
    city_name?: string | null;
    default_selling_price?: number | null;
    credit_limit?: number | null;
    payment_term_days?: number | null;
    pic?: string | null;
    npwp?: string | null;
    website?: string | null;
    address?: string | null;
    notes?: string | null;
    is_active: boolean;
    inactive_reason?: string | null;
    photo_url?: string | null;
    initials: string;
}

const props = defineProps<{
    open: boolean;
    editingBuyer?: Buyer | null;
    postUrl: string;
    putUrl?: string;
    cities: City[];
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'success'): void;
}>();

const form = useForm({
    name: '',
    type: '' as string,
    phone: '',
    email: '',
    city_id: '' as string | number,
    default_selling_price: '' as string | number,
    credit_limit: '' as string | number,
    payment_term_days: '' as string | number,
    pic: '',
    npwp: '',
    website: '',
    address: '',
    notes: '',
    foto: null as File | null,
});

const fileInputRef = ref<HTMLInputElement | null>(null);
const previewUrl = ref<string | null>(null);
const selectedFileName = ref<string>('');
const displayKode = ref<string>('');
const isEditing = computed(() => !!props.editingBuyer);

watch(
    () => props.editingBuyer,
    (b) => {
        if (b) {
            displayKode.value = b.code ?? '';
            form.name = b.name;
            form.type = b.type ?? '';
            form.phone = b.phone ?? '';
            form.email = b.email ?? '';
            form.city_id = b.city_id ?? '';
            form.default_selling_price = b.default_selling_price ?? '';
            form.credit_limit = b.credit_limit ?? '';
            form.payment_term_days = b.payment_term_days ?? '';
            form.pic = b.pic ?? '';
            form.npwp = b.npwp ?? '';
            form.website = b.website ?? '';
            form.address = b.address ?? '';
            form.notes = b.notes ?? '';
            form.foto = null;
            previewUrl.value = b.photo_url ?? null;
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

function triggerFileInput() { fileInputRef.value?.click(); }

function handleFileChange(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0];
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

function handleSubmit() {
    const onSuccess = () => {
        emit('update:open', false);
        emit('success');
        form.reset();
        previewUrl.value = null;
        selectedFileName.value = '';
    };

    if (isEditing.value && props.editingBuyer) {
        form.patch(`${props.putUrl ?? props.postUrl}/${props.editingBuyer.id}`, { onSuccess });
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

            <div class="flex-shrink-0 px-6 pt-6 pb-4 border-b border-gray-100">
                <DialogHeader>
                    <DialogTitle class="text-base font-semibold text-gray-900">
                        {{ isEditing ? 'Ubah Data Buyer' : 'Tambah Buyer Baru' }}
                    </DialogTitle>
                </DialogHeader>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-5">
                <div class="grid gap-4">

                    <!-- Nama Buyer | Kode Buyer -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">Nama Buyer</Label>
                            <Input v-model="form.name" placeholder="Nama lengkap atau perusahaan"
                                :class="inputClass(!!form.errors.name)" />
                            <span v-if="form.errors.name" class="text-xs text-red-500">{{ form.errors.name }}</span>
                        </div>
                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">Kode Buyer</Label>
                            <Input :model-value="displayKode" disabled
                                class="bg-gray-50 text-gray-500 cursor-not-allowed" />
                        </div>
                    </div>

                    <!-- Tipe | No. Telepon -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">Tipe</Label>
                            <select v-model="form.type" :class="selectClass(!!form.errors.type)">
                                <option value="">Pilih tipe</option>
                                <option value="PT">PT</option>
                                <option value="CV">CV</option>
                                <option value="UD">UD</option>
                                <option value="Perorangan">Perorangan</option>
                            </select>
                            <span v-if="form.errors.type" class="text-xs text-red-500">{{ form.errors.type }}</span>
                        </div>
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
                    </div>

                    <!-- Email | Kota -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">Email</Label>
                            <Input v-model="form.email" type="email" placeholder="email@domain.com"
                                :class="inputClass(!!form.errors.email)" />
                            <span v-if="form.errors.email" class="text-xs text-red-500">{{ form.errors.email }}</span>
                        </div>
                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">Kota</Label>
                            <select v-model="form.city_id" :class="selectClass(!!form.errors.city_id)">
                                <option value="">Pilih kota</option>
                                <option v-for="city in cities" :key="city.id" :value="city.id">
                                    {{ city.name }}
                                </option>
                            </select>
                            <span v-if="form.errors.city_id" class="text-xs text-red-500">{{ form.errors.city_id
                            }}</span>
                        </div>
                    </div>

                    <!-- Harga Jual Default | Limit Kredit -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">Harga Jual Default (Rp/kg)</Label>
                            <Input v-model="form.default_selling_price" type="number" min="0" placeholder="4.400"
                                :class="inputClass(!!form.errors.default_selling_price)" />
                            <span v-if="form.errors.default_selling_price" class="text-xs text-red-500">{{
                                form.errors.default_selling_price }}</span>
                        </div>
                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">Limit Kredit (Rp)</Label>
                            <Input v-model="form.credit_limit" type="number" min="0" placeholder="50.000.000"
                                :class="inputClass(!!form.errors.credit_limit)" />
                            <span v-if="form.errors.credit_limit" class="text-xs text-red-500">{{
                                form.errors.credit_limit }}</span>
                        </div>
                    </div>

                    <!-- Termin Pembayaran | PIC -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">Termin Pembayaran (hari)</Label>
                            <Input v-model="form.payment_term_days" type="number" min="1" placeholder="30"
                                :class="inputClass(!!form.errors.payment_term_days)" />
                            <span v-if="form.errors.payment_term_days" class="text-xs text-red-500">{{
                                form.errors.payment_term_days }}</span>
                        </div>
                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">PIC / Contact Person</Label>
                            <Input v-model="form.pic" placeholder="Nama penanggung jawab"
                                :class="inputClass(!!form.errors.pic)" />
                            <span v-if="form.errors.pic" class="text-xs text-red-500">{{ form.errors.pic }}</span>
                        </div>
                    </div>

                    <!-- NPWP | Website -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">NPWP</Label>
                            <Input v-model="form.npwp" placeholder="xx.xxx.xxx.x-xxx.xxx"
                                :class="inputClass(!!form.errors.npwp)" />
                            <span v-if="form.errors.npwp" class="text-xs text-red-500">{{ form.errors.npwp }}</span>
                        </div>
                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">Website</Label>
                            <Input v-model="form.website" placeholder="www.perusahaan.com"
                                :class="inputClass(!!form.errors.website)" />
                            <span v-if="form.errors.website" class="text-xs text-red-500">{{ form.errors.website
                            }}</span>
                        </div>
                    </div>

                    <!-- Alamat Lengkap -->
                    <div class="grid gap-1.5">
                        <Label class="text-sm font-medium text-gray-700">Alamat Lengkap</Label>
                        <textarea v-model="form.address" rows="3" placeholder="alamat lengkap buyer..."
                            :class="textareaClass(!!form.errors.address)" />
                        <span v-if="form.errors.address" class="text-xs text-red-500">{{ form.errors.address }}</span>
                    </div>

                    <!-- Catatan Tambahan -->
                    <div class="grid gap-1.5">
                        <Label class="text-sm font-medium text-gray-700">Catatan Tambahan</Label>
                        <textarea v-model="form.notes" rows="3" placeholder="catatan tambahan..."
                            :class="textareaClass(!!form.errors.notes)" />
                        <span v-if="form.errors.notes" class="text-xs text-red-500">{{ form.errors.notes }}</span>
                    </div>

                    <!-- Foto Buyer -->
                    <div class="grid gap-1.5">
                        <Label class="text-sm font-medium text-gray-700">Foto Buyer</Label>
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
                        <span v-if="form.errors.foto" class="text-xs text-red-500">{{ form.errors.foto }}</span>
                    </div>

                </div>
            </div>

            <div class="flex justify-end gap-3 border-t border-gray-100 p-5">
                <Button variant="outline" class="w-fit rounded-md" :disabled="form.processing"
                    @click="emit('update:open', false)">
                    Batal
                </Button>
                <Button @click="handleSubmit" :disabled="form.processing"
                    class="w-fit rounded-md bg-[#007C95] text-white hover:bg-[#006b80]">
                    {{ form.processing ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Simpan Buyer') }}
                </Button>
            </div>

        </DialogContent>
    </Dialog>
</template>