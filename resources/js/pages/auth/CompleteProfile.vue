<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { watch, ref, computed, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthLayout from '@/layouts/auth/AuthSimpleLayout.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { AlertCircle, Clock, CheckCircle2, Upload, X, ImageIcon } from 'lucide-vue-next';
import KtpCropper from '@/components/KtpCropper.vue';
import axios from 'axios';

const props = defineProps<{
    user: {
        name: string;
        is_verified_by_admin: number;
        profile: {
            phone: string | null;
            birth_date: string | null;
            gender: string | null;
            occupation: string | null;
            address: string | null;
            province_referensi_id: string | null;
            city_referensi_id: string | null;
            district: string | null;
            village: string | null;
            postal_code: string | null;
            id_card_number: string | null;
            verification_note: string | null;
            id_card_photo_url: string | null;
        } | null;
    };
    provinces: { referensi_id: string; nama: string }[];
}>();

const form = useForm({
    name: props.user.name,
    phone: props.user.profile?.phone ?? '',
    birth_date: props.user.profile?.birth_date ?? '',
    gender: props.user.profile?.gender ?? '',
    occupation: props.user.profile?.occupation ?? '',
    address: props.user.profile?.address ?? '',
    province_referensi_id: props.user.profile?.province_referensi_id ?? '',
    city_referensi_id: props.user.profile?.city_referensi_id ?? '',
    district: props.user.profile?.district ?? '',
    village: props.user.profile?.village ?? '',
    postal_code: props.user.profile?.postal_code ?? '',
    id_card_number: props.user.profile?.id_card_number ?? '',
    id_card_photo: null as File | null,
});

// ── KOTA ──────────────────────────────────────────────
const cities = ref<{ referensi_id: string; nama: string }[]>([]);

const fetchCities = async (provinsiId: string) => {
    if (!provinsiId) { cities.value = []; return; }
    const res = await axios.get('/api/cities', { params: { province_referensi_id: provinsiId } });
    cities.value = res.data;
};

if (form.province_referensi_id) fetchCities(form.province_referensi_id);

watch(() => form.province_referensi_id, (val) => {
    form.city_referensi_id = '';
    fetchCities(val);
});

// ── FOTO KTP ───────────────────────────────────────────
const existingPhotoUrl = ref<string | null>(props.user.profile?.id_card_photo_url ?? null);
const previewUrl = ref<string | null>(null);
const isDragging = ref(false);

const displayPhoto = computed(() => previewUrl.value ?? existingPhotoUrl.value);
const hasPhoto = computed(() => !!displayPhoto.value);
const isNewFile = computed(() => !!previewUrl.value);

// ── KTP CROPPER REF ────────────────────────────────────
const ktpCropperRef = ref<InstanceType<typeof KtpCropper> | null>(null);

const handleIdCardPhoto = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (file) ktpCropperRef.value?.openModal(file);
};

const handleDrop = (e: DragEvent) => {
    isDragging.value = false;
    const file = e.dataTransfer?.files?.[0];
    if (file && file.type.startsWith('image/')) ktpCropperRef.value?.openModal(file);
};

const onCropCancel = () => {
    const input = document.getElementById('id_card_photo') as HTMLInputElement | null;
    if (input) input.value = '';
};

const removePhoto = () => {
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
        previewUrl.value = null;
    }
    form.id_card_photo = null;
    const input = document.getElementById('id_card_photo') as HTMLInputElement | null;
    if (input) input.value = '';
};

onUnmounted(() => {
    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
});

// ── OCR STATE ─────────────────────────────────────────
const isScanning = ref(false);
const scanError = ref<string | null>(null);

const applyFile = async (file: File) => {
    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
    previewUrl.value = URL.createObjectURL(file);
    form.id_card_photo = file;

    scanError.value = null;
    isScanning.value = true;

    try {
        // const formData = new FormData();
        // formData.append('photo', file);

        // const res = await axios.post('/complete-profile/ocr-ktp', formData, {
        //     headers: { 'Content-Type': 'multipart/form-data' },
        // });

        // if (res.data.success) {
        //     const d = res.data.data;
        //     if (d.name && !form.name) form.name = d.name;
        //     if (d.id_card_number && !form.id_card_number) form.id_card_number = d.id_card_number;
        //     if (d.birth_date && !form.birth_date) form.birth_date = d.birth_date;
        //     if (d.gender && !form.gender) form.gender = d.gender;
        //     if (d.occupation && !form.occupation) form.occupation = d.occupation;
        //     if (d.district && !form.district) form.district = d.district;
        //     if (d.village && !form.village) form.village = d.village;
        //     if (d.postal_code && !form.postal_code) form.postal_code = d.postal_code;

        //     if (d.province_referensi_id && !form.province_referensi_id) {
        //         form.province_referensi_id = d.province_referensi_id;
        //         await fetchCities(d.province_referensi_id);
        //         if (d.city_referensi_id && !form.city_referensi_id) {
        //             form.city_referensi_id = d.city_referensi_id;
        //         }
        //     }
        // }
    } catch (error: any) {
        const data = error?.response?.data;
        scanError.value = data?.message ?? 'Gagal membaca data KTP. Silakan isi manual.';
    } finally {
        isScanning.value = false;
    }
};

// ── SUBMIT ─────────────────────────────────────────────
const isVerifying = ref(false);

const submit = () => {
    form.post('/complete-profile', {
        onSuccess: () => {
            isVerifying.value = true;
            setTimeout(() => window.location.reload(), 3000);
        },
    });
};

const status = props.user.is_verified_by_admin;
const isPending = status === 1;
const isRejected = status === 3;
const showForm = status === 0 || status === 3;

let refreshTimer: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
    if (isPending) {
        refreshTimer = setInterval(() => {
            router.reload();
        }, 30000);
    }
});

onUnmounted(() => {
    if (refreshTimer) clearInterval(refreshTimer);
});
</script>

<template>
    <!-- ── KTP CROPPER COMPONENT ── -->
    <KtpCropper ref="ktpCropperRef" @cropped="applyFile" @cancel="onCropCancel" />

    <!-- ── OVERLAY VERIFIKASI ── -->
    <Teleport to="body">
        <div v-if="isVerifying"
            class="fixed inset-0 z-50 flex flex-col items-center justify-center gap-6 bg-white/90 backdrop-blur-sm">
            <div class="relative flex h-24 w-24 items-center justify-center">
                <div class="absolute h-24 w-24 animate-ping rounded-full bg-[#007C95]/20"></div>
                <div class="absolute h-16 w-16 animate-pulse rounded-full bg-[#007C95]/30"></div>
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-[#007C95]">
                    <svg class="h-6 w-6 animate-spin text-white" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
                    </svg>
                </div>
            </div>
            <div class="text-center">
                <p class="text-lg font-semibold text-gray-800">Sedang Memverifikasi</p>
                <p class="mt-1 text-sm text-gray-500">Mohon tunggu, data KTP Anda sedang diproses...</p>
            </div>
            <div class="w-64 overflow-hidden rounded-full bg-gray-100">
                <div class="h-1.5 animate-progress rounded-full bg-[#007C95]"></div>
            </div>
        </div>
    </Teleport>

    <AuthLayout :title="isPending ? 'Menunggu Verifikasi' : 'Lengkapi Profil'" :description="isPending
        ? 'Profil Anda sedang ditinjau oleh admin'
        : 'Silakan lengkapi data diri Anda untuk melanjutkan'">

        <Head title="Lengkapi Profil" />

        <!-- ── STATUS: MENUNGGU VERIFIKASI ── -->
        <div v-if="isPending" class="flex flex-col items-center gap-6 py-8 text-center">
            <div class="flex h-20 w-20 items-center justify-center rounded-full bg-amber-50">
                <Clock class="h-10 w-10 text-amber-400" />
            </div>
            <div class="space-y-2">
                <h2 class="text-lg font-semibold text-gray-800">Profil Sedang Diverifikasi</h2>
                <p class="text-sm text-gray-500 max-w-sm">
                    Data diri Anda telah berhasil dikirim. Tim admin akan segera meninjau dan memverifikasi informasi
                    Anda.
                </p>
            </div>
            <div class="w-full rounded-lg border border-amber-200 bg-amber-50 p-4">
                <div class="flex items-start gap-3 text-left">
                    <CheckCircle2 class="mt-0.5 h-5 w-5 shrink-0 text-amber-500" />
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-amber-700">Apa yang terjadi selanjutnya?</p>
                        <ul class="text-xs text-amber-600 space-y-1 list-disc list-inside">
                            <li>Admin akan memeriksa data dan foto KTP Anda</li>
                            <li>Proses verifikasi biasanya memakan waktu 1×24 jam</li>
                            <li>Anda akan diarahkan ke dashboard setelah disetujui</li>
                        </ul>
                    </div>
                </div>
            </div>
            <p class="text-xs text-gray-400">Butuh bantuan? Hubungi tim support kami.</p>
        </div>

        <!-- ── FORM ── -->
        <template v-if="showForm">
            <div v-if="isRejected && user.profile?.verification_note"
                class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4">
                <div class="flex items-start gap-3">
                    <AlertCircle class="mt-0.5 h-5 w-5 shrink-0 text-red-500" />
                    <div>
                        <p class="text-sm font-semibold text-red-700">Data diri Anda ditolak</p>
                        <p class="mt-1 text-sm text-red-600">{{ user.profile.verification_note }}</p>
                        <p class="mt-2 text-xs text-red-400">Silakan perbaiki data sesuai catatan di atas dan kirim
                            ulang.</p>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit" class="flex flex-col gap-6">
                <div class="grid gap-6">

                    <!-- ── FOTO KTP ── -->
                    <div class="grid gap-2">
                        <Label for="id_card_photo">
                            Foto KTP
                            <span v-if="!existingPhotoUrl" class="text-red-500">*</span>
                            <span v-else class="ml-1 text-xs font-normal text-gray-400">
                                (biarkan jika tidak ingin mengubah)
                            </span>
                        </Label>

                        <div v-if="hasPhoto"
                            class="relative overflow-hidden rounded-xl border border-gray-200 bg-gray-50">
                            <img :src="displayPhoto!" alt="Preview foto KTP" class="h-48 w-full object-contain p-2" />

                            <span class="absolute left-2 top-2 rounded-md px-2 py-0.5 text-xs font-medium"
                                :class="isNewFile ? 'bg-[#007C95]/10 text-[#007C95]' : 'bg-gray-100 text-gray-500'">
                                {{ isNewFile ? 'Foto baru (sudah dicrop)' : 'Foto tersimpan' }}
                            </span>

                            <div class="absolute right-2 top-2 flex gap-1.5">
                                <label for="id_card_photo"
                                    class="flex cursor-pointer items-center gap-1 rounded-md bg-white/90 px-2 py-1 text-xs font-medium text-gray-600 shadow-sm ring-1 ring-gray-200 hover:bg-gray-50 transition">
                                    <Upload class="h-3 w-3" />
                                    Ganti
                                </label>
                                <button v-if="isNewFile" type="button" @click="removePhoto"
                                    class="flex items-center gap-1 rounded-md bg-white/90 px-2 py-1 text-xs font-medium text-red-500 shadow-sm ring-1 ring-red-200 hover:bg-red-50 transition">
                                    <X class="h-3 w-3" />
                                    Batal
                                </button>
                            </div>
                        </div>

                        <label v-else for="id_card_photo" :class="[
                            'flex cursor-pointer flex-col items-center justify-center gap-3 rounded-xl border-2 border-dashed p-8 transition',
                            isDragging
                                ? 'border-[#007C95] bg-[#007C95]/5'
                                : 'border-gray-200 bg-gray-50 hover:border-[#007C95]/50 hover:bg-[#007C95]/5',
                        ]" @dragover.prevent="isDragging = true" @dragleave="isDragging = false"
                            @drop.prevent="handleDrop">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-[#007C95]/10">
                                <ImageIcon class="h-6 w-6 text-[#007C95]" />
                            </div>
                            <div class="text-center">
                                <p class="text-sm font-medium text-gray-700">Klik atau seret foto KTP ke sini</p>
                                <p class="mt-1 text-xs text-gray-400">JPG, PNG, WebP • Maks. 2 MB</p>
                            </div>
                        </label>

                        <input id="id_card_photo" type="file" accept="image/jpeg,image/png,image/jpg,image/webp"
                            class="sr-only" @change="handleIdCardPhoto" />

                        <div v-if="isScanning"
                            class="flex items-center gap-2 rounded-lg border border-blue-100 bg-blue-50 px-3 py-2 text-sm text-blue-600">
                            <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
                            </svg>
                            Sedang membaca data KTP...
                        </div>

                        <div v-if="scanError && !isScanning"
                            class="flex items-center gap-2 rounded-lg border border-amber-100 bg-amber-50 px-3 py-2 text-sm text-amber-600">
                            <AlertCircle class="h-4 w-4 shrink-0" />
                            {{ scanError }}
                        </div>

                        <InputError :message="form.errors.id_card_photo" />
                    </div>

                    <!-- NIK -->
                    <div class="grid gap-2">
                        <Label for="id_card_number">NIK KTP <span class="text-red-500">*</span></Label>
                        <Input id="id_card_number" type="text" v-model="form.id_card_number" placeholder="16 digit NIK"
                            maxlength="16" class="focus-visible:ring-[#007C95]" />
                        <InputError :message="form.errors.id_card_number" />
                    </div>

                    <!-- Nama -->
                    <div class="grid gap-2">
                        <Label for="name">Nama Lengkap <span class="text-red-500">*</span></Label>
                        <Input id="name" type="text" v-model="form.name" required placeholder="Nama Lengkap"
                            class="focus-visible:ring-[#007C95]" />
                        <InputError :message="form.errors.name" />
                    </div>

                    <!-- Telepon -->
                    <div class="grid gap-2">
                        <Label for="phone">No. Telepon <span class="text-red-500">*</span></Label>
                        <Input id="phone" type="tel" v-model="form.phone" placeholder="08123456789"
                            class="focus-visible:ring-[#007C95]" />
                        <InputError :message="form.errors.phone" />
                    </div>

                    <!-- Tanggal lahir & gender -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="birth_date">Tanggal Lahir <span class="text-red-500">*</span></Label>
                            <Input id="birth_date" type="date" v-model="form.birth_date"
                                class="focus-visible:ring-[#007C95]" />
                            <InputError :message="form.errors.birth_date" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="gender">Jenis Kelamin <span class="text-red-500">*</span></Label>
                            <select id="gender" v-model="form.gender"
                                class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-sm focus:border-[#007C95] focus:ring-1 focus:ring-[#007C95] focus:outline-none">
                                <option value="" disabled>Pilih jenis kelamin</option>
                                <option value="male">Laki-laki</option>
                                <option value="female">Perempuan</option>
                            </select>
                            <InputError :message="form.errors.gender" />
                        </div>
                    </div>

                    <!-- Pekerjaan -->
                    <div class="grid gap-2">
                        <Label for="occupation">Pekerjaan</Label>
                        <Input id="occupation" type="text" v-model="form.occupation" placeholder="Pekerjaan"
                            class="focus-visible:ring-[#007C95]" />
                        <InputError :message="form.errors.occupation" />
                    </div>

                    <!-- Provinsi & Kota -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="province">Provinsi <span class="text-red-500">*</span></Label>
                            <select id="province" v-model="form.province_referensi_id"
                                class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-sm focus:border-[#007C95] focus:ring-1 focus:ring-[#007C95] focus:outline-none">
                                <option value="" disabled>Pilih provinsi</option>
                                <option v-for="p in provinces" :key="p.referensi_id" :value="p.referensi_id">
                                    {{ p.nama }}
                                </option>
                            </select>
                            <InputError :message="form.errors.province_referensi_id" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="city">Kota/Kabupaten <span class="text-red-500">*</span></Label>
                            <select id="city" v-model="form.city_referensi_id"
                                :disabled="!form.province_referensi_id || cities.length === 0"
                                class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-sm focus:border-[#007C95] focus:ring-1 focus:ring-[#007C95] focus:outline-none disabled:opacity-50">
                                <option value="" disabled>Pilih kota/kabupaten</option>
                                <option v-for="c in cities" :key="c.referensi_id" :value="c.referensi_id">
                                    {{ c.nama }}
                                </option>
                            </select>
                            <InputError :message="form.errors.city_referensi_id" />
                        </div>
                    </div>

                    <!-- Kecamatan, Kelurahan, Kode Pos -->
                    <div class="grid grid-cols-3 gap-4">
                        <div class="grid gap-2">
                            <Label for="district">Kecamatan</Label>
                            <Input id="district" type="text" v-model="form.district" placeholder="Kecamatan"
                                class="focus-visible:ring-[#007C95]" />
                            <InputError :message="form.errors.district" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="village">Kelurahan</Label>
                            <Input id="village" type="text" v-model="form.village" placeholder="Kelurahan"
                                class="focus-visible:ring-[#007C95]" />
                            <InputError :message="form.errors.village" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="postal_code">Kode Pos</Label>
                            <Input id="postal_code" type="text" v-model="form.postal_code" placeholder="65141"
                                class="focus-visible:ring-[#007C95]" />
                            <InputError :message="form.errors.postal_code" />
                        </div>
                    </div>

                    <!-- Alamat -->
                    <div class="grid gap-2">
                        <Label for="address">Alamat Lengkap <span class="text-red-500">*</span></Label>
                        <Textarea id="address" v-model="form.address" placeholder="Jl. Contoh No. 1"
                            class="focus-visible:ring-[#007C95]" />
                        <InputError :message="form.errors.address" />
                    </div>

                    <Button type="submit" class="mt-2 w-full bg-[#007C95] hover:bg-[#00667a]"
                        :disabled="form.processing">
                        {{ form.processing ? 'Menyimpan...' : (isRejected ? 'Kirim Ulang' : 'Simpan Profil') }}
                    </Button>
                </div>
            </form>
        </template>

        <button type="button" @click="router.post('/logout')"
            class="mt-1 flex w-full items-center justify-center rounded-lg border border-gray-200 py-2 text-sm text-gray-500 hover:bg-gray-50 transition">
            Kembali ke Login
        </button>
    </AuthLayout>
</template>

<style scoped>
@keyframes progress {
    from {
        width: 0%;
    }

    to {
        width: 100%;
    }
}

.animate-progress {
    animation: progress 3s ease-in-out forwards;
}
</style>