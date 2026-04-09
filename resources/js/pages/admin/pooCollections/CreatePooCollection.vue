<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Camera,
    X,
    PenLine,
    RotateCcw,
    Check,
} from 'lucide-vue-next';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import BadgeBussines from '@/components/BadgeBussines.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface POO {
    id: string;
    name: string;
    address: string;
    type_label: string;
}

const props = defineProps<{
    poo: POO;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pengambilan dari POO', href: '/collections' },
    { title: 'Catat Pengambilan', href: '#' },
];

const form = useForm({
    m_poo_id: props.poo.id,
    volume: '',
    collected_at: new Date().toISOString().split('T')[0],
    photo: null as File | null,
    signature: '',
    notes: '',
});

// =================== FOTO ===================
const photoPreview = ref<string | null>(null);
const ALLOWED_TYPES = [
    'image/jpeg',
    'image/png',
    'image/webp',
    'image/gif',
    'image/bmp',
];

const compressToWebp = (file: File): Promise<File> => {
    return new Promise((resolve) => {
        const img = new Image();
        const url = URL.createObjectURL(file);
        img.onload = () => {
            // Max width 1280px, maintain aspect ratio
            const MAX = 1280;
            let { width, height } = img;
            if (width > MAX) {
                height = Math.round((height * MAX) / width);
                width = MAX;
            }

            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;
            canvas.getContext('2d')!.drawImage(img, 0, 0, width, height);

            URL.revokeObjectURL(url);
            canvas.toBlob(
                (blob) => {
                    const webpFile = new File(
                        [blob!],
                        file.name.replace(/\.[^.]+$/, '.webp'),
                        {
                            type: 'image/webp',
                        },
                    );
                    resolve(webpFile);
                },
                'image/webp',
                0.82, // kualitas 82%
            );
        };
        img.src = url;
    });
};

const onPhotoChange = async (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (!file) return;

    // Validasi tipe file
    if (!ALLOWED_TYPES.includes(file.type)) {
        toast.error('Format tidak didukung', {
            description:
                'Hanya file gambar yang diperbolehkan (JPG, PNG, WebP, GIF)',
        });
        // Reset input agar file tidak terpilih
        (e.target as HTMLInputElement).value = '';
        return;
    }

    const compressed = await compressToWebp(file);
    form.photo = compressed;
    photoPreview.value = URL.createObjectURL(compressed);
};

const removePhoto = () => {
    form.photo = null;
    photoPreview.value = null;
};

// =================== SIGNATURE FULLSCREEN ===================
const showSignaturePad = ref(false);
const canvasRef = ref<HTMLCanvasElement | null>(null);
const isDrawing = ref(false);
const hasSignature = ref(false);
const signaturePreview = ref<string | null>(null);

const openSignaturePad = () => {
    showSignaturePad.value = true;
    // Tunggu DOM render lalu clear canvas
    setTimeout(() => {
        if (canvasRef.value) {
            resizeCanvas();
            const ctx = canvasRef.value.getContext('2d')!;
            ctx.clearRect(0, 0, canvasRef.value.width, canvasRef.value.height);
            hasSignature.value = false;
        }
    }, 50);
};

const resizeCanvas = () => {
    if (!canvasRef.value) return;
    const canvas = canvasRef.value;
    // Simpan konten sebelum resize
    const data = canvas.toDataURL();
    canvas.width = canvas.offsetWidth;
    canvas.height = canvas.offsetHeight;
    // Restore konten
    const img = new Image();
    img.src = data;
    img.onload = () => canvas.getContext('2d')!.drawImage(img, 0, 0);
};

const startDraw = (e: MouseEvent | TouchEvent) => {
    if (!canvasRef.value) return;
    isDrawing.value = true;
    const ctx = canvasRef.value.getContext('2d')!;
    const pos = getPos(e);
    ctx.beginPath();
    ctx.moveTo(pos.x, pos.y);
};

const draw = (e: MouseEvent | TouchEvent) => {
    if (!isDrawing.value || !canvasRef.value) return;
    e.preventDefault();
    const ctx = canvasRef.value.getContext('2d')!;
    ctx.lineWidth = 2.5;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    ctx.strokeStyle = '#1a1a1a';
    const pos = getPos(e);
    ctx.lineTo(pos.x, pos.y);
    ctx.stroke();
    hasSignature.value = true;
};

const stopDraw = () => {
    isDrawing.value = false;
};

const getPos = (e: MouseEvent | TouchEvent) => {
    const canvas = canvasRef.value!;
    const rect = canvas.getBoundingClientRect();
    if (e instanceof TouchEvent) {
        return {
            x: e.touches[0].clientX - rect.left,
            y: e.touches[0].clientY - rect.top,
        };
    }
    return { x: e.clientX - rect.left, y: e.clientY - rect.top };
};

const clearCanvas = () => {
    if (!canvasRef.value) return;
    const ctx = canvasRef.value.getContext('2d')!;
    ctx.clearRect(0, 0, canvasRef.value.width, canvasRef.value.height);
    hasSignature.value = false;
};

const confirmSignature = () => {
    if (!hasSignature.value || !canvasRef.value) {
        toast.error('Tanda tangan wajib diisi');
        return;
    }
    // Ubah dari 'image/png' ke 'image/webp'
    signaturePreview.value = canvasRef.value.toDataURL('image/webp', 0.9);
    form.signature = signaturePreview.value;
    showSignaturePad.value = false;
};

const removeSignature = () => {
    signaturePreview.value = null;
    form.signature = '';
};

// =================== SUBMIT ===================
const handleSubmit = () => {
    if (!form.signature) {
        toast.error('Tanda tangan wajib diisi', {
            description: 'Silakan tambahkan tanda tangan terlebih dahulu',
        });
        return;
    }

    form.post('/collections', {
        onSuccess: () => {
            toast.success('Berhasil!', {
                description: 'Pengambilan UCO berhasil dicatat',
            });
        },
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
                    @click="router.visit('/collections')"
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
                        <h1 class="text-[16px] font-bold text-gray-900">
                            Catat Pengambilan UCO
                        </h1>
                        <p class="mt-0.5 text-[13px] text-gray-500">
                            Isi detail pengambilan minyak jelantah
                        </p>
                    </div>

                    <div class="grid gap-5 px-5 pb-5">
                        <!-- POO Terpilih -->
                        <div
                            class="rounded-xl border border-teal-100 bg-teal-50 px-4 py-3.5"
                        >
                            <p
                                class="mb-2 text-[11px] font-bold tracking-widest text-teal-500 uppercase"
                            >
                                POO Terpilih
                            </p>
                            <p class="text-sm font-semibold text-gray-900">
                                {{ poo.name }}
                            </p>
                            <div
                                class="mt-1.5 flex items-center justify-between"
                            >
                                <p class="text-xs text-gray-500">
                                    {{ poo.address }}
                                </p>
                                <BadgeBussines :type="poo.type_label as any" />
                            </div>
                        </div>

                        <!-- Volume -->
                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium">
                                Volume Minyak (Liter)
                                <span class="text-red-500">*</span>
                            </Label>
                            <Input
                                v-model="form.volume"
                                type="number"
                                min="0.1"
                                step="0.1"
                                placeholder="Contoh: 25"
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

                        <!-- Tanggal -->
                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium">
                                Tanggal Pengambilan
                                <span class="text-red-500">*</span>
                            </Label>
                            <Input
                                v-model="form.collected_at"
                                type="date"
                                :class="{
                                    'border-red-400': form.errors.collected_at,
                                }"
                            />
                            <span
                                v-if="form.errors.collected_at"
                                class="text-xs text-red-500"
                                >{{ form.errors.collected_at }}</span
                            >
                        </div>

                        <!-- Foto -->
                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium"
                                >Foto Pengambilan</Label
                            >
                            <div
                                v-if="!photoPreview"
                                class="relative flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-gray-200 bg-gray-50 py-8 transition hover:border-teal-300 hover:bg-teal-50/30"
                                @click="
                                    (
                                        $refs.photoInput as HTMLInputElement
                                    ).click()
                                "
                            >
                                <Camera class="h-6 w-6 text-gray-400" />
                                <p class="text-sm text-gray-500">Upload</p>
                                <p class="text-xs text-gray-400">
                                    Foto saat pengambilan (opsional)
                                </p>
                                <input
                                    ref="photoInput"
                                    type="file"
                                    accept="image/*"
                                    class="hidden"
                                    @change="onPhotoChange"
                                />
                            </div>
                            <div
                                v-else
                                class="relative overflow-hidden rounded-xl border border-gray-200"
                            >
                                <img
                                    :src="photoPreview"
                                    class="h-48 w-full object-cover"
                                />
                                <button
                                    @click="removePhoto"
                                    class="absolute top-2 right-2 rounded-full bg-black/50 p-1 text-white transition hover:bg-black/70"
                                >
                                    <X class="h-3.5 w-3.5" />
                                </button>
                            </div>
                        </div>

                        <!-- Tanda Tangan -->
                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium">
                                Tanda Tangan <span class="text-red-500">*</span>
                            </Label>

                            <!-- Belum ada TTD -->
                            <div
                                v-if="!signaturePreview"
                                class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 py-8 transition"
                                :class="
                                    form.errors.signature
                                        ? 'border-red-300 bg-red-50'
                                        : 'border-dashed border-gray-200 bg-gray-50 hover:border-teal-300 hover:bg-teal-50/30'
                                "
                                @click="openSignaturePad"
                            >
                                <PenLine class="h-6 w-6 text-gray-400" />
                                <p class="text-sm text-gray-500">
                                    Klik untuk tanda tangan
                                </p>
                            </div>

                            <!-- Preview TTD -->
                            <div
                                v-else
                                class="relative overflow-hidden rounded-xl border border-gray-200 bg-white"
                            >
                                <img
                                    :src="signaturePreview"
                                    class="h-28 w-full object-contain p-2"
                                />
                                <div
                                    class="flex items-center justify-between border-t border-gray-100 px-3 py-2"
                                >
                                    <span
                                        class="flex items-center gap-1 text-xs font-medium text-green-600"
                                    >
                                        <Check class="h-3.5 w-3.5" />
                                        Tanda tangan tersimpan
                                    </span>
                                    <button
                                        @click="openSignaturePad"
                                        class="text-xs text-teal-600 transition hover:text-teal-700"
                                    >
                                        Ubah TTD
                                    </button>
                                </div>
                            </div>

                            <span
                                v-if="form.errors.signature"
                                class="text-xs text-red-500"
                                >{{ form.errors.signature }}</span
                            >
                        </div>

                        <!-- Catatan -->
                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium">Catatan</Label>
                            <textarea
                                v-model="form.notes"
                                rows="3"
                                placeholder="Catatan tambahan (opsional)..."
                                class="w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm placeholder-gray-400 focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
                            />
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="border-t border-gray-100 p-5">
                        <Button
                            @click="handleSubmit"
                            :disabled="form.processing"
                            class="w-full rounded bg-primary font-medium text-white hover:bg-primary-hover"
                        >
                            Simpan
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fullscreen Signature Pad -->
        <Teleport to="body">
            <div
                v-if="showSignaturePad"
                class="fixed inset-0 z-50 flex flex-col bg-white"
            >
                <!-- Header -->
                <div
                    class="flex items-center justify-between border-b border-gray-100 px-5 py-4"
                >
                    <div>
                        <h2 class="text-[15px] font-bold text-gray-900">
                            Tanda Tangan
                        </h2>
                        <p class="mt-0.5 text-xs text-gray-500">
                            Tanda tangani di area bawah
                        </p>
                    </div>
                    <button
                        @click="showSignaturePad = false"
                        class="rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <!-- Canvas Area -->
                <div class="relative flex-1 bg-gray-50">
                    <!-- Placeholder text -->
                    <p
                        v-if="!hasSignature"
                        class="pointer-events-none absolute inset-0 flex items-center justify-center text-sm text-gray-300 select-none"
                    >
                        Tanda tangan di sini...
                    </p>
                    <canvas
                        ref="canvasRef"
                        class="h-full w-full cursor-crosshair touch-none"
                        @mousedown="startDraw"
                        @mousemove="draw"
                        @mouseup="stopDraw"
                        @mouseleave="stopDraw"
                        @touchstart.prevent="startDraw"
                        @touchmove.prevent="draw"
                        @touchend="stopDraw"
                    />
                </div>

                <!-- Footer -->
                <div
                    class="flex items-center gap-3 border-t border-gray-100 px-5 py-4"
                >
                    <Button
                        variant="outline"
                        class="flex items-center gap-2 rounded border-gray-200"
                        @click="clearCanvas"
                    >
                        <RotateCcw class="h-4 w-4" />
                        Ulangi
                    </Button>
                    <Button
                        class="flex-1 rounded bg-primary font-medium text-white hover:bg-primary-hover"
                        @click="confirmSignature"
                    >
                        <Check class="mr-1.5 h-4 w-4" />
                        Konfirmasi TTD
                    </Button>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
