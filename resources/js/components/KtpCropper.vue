<script setup lang="ts">
import { ref, nextTick, onUnmounted } from 'vue';
import { ScanLine, RotateCcw, RotateCw, X, CheckCircle2, ZoomIn, ZoomOut } from 'lucide-vue-next';

const emit = defineEmits<{
    (e: 'cropped', file: File): void;
    (e: 'cancel'): void;
}>();

// ── State ──
const showModal = ref(false);
const canvasRef = ref<HTMLCanvasElement | null>(null);
const wrapperRef = ref<HTMLDivElement | null>(null);

const rotation = ref<0 | 90 | 180 | 270>(0);
const imgOffset = ref({ x: 0, y: 0 });
const imgScale = ref(1);

// KTP aspect ratio: 86mm × 54mm
const KTP_RATIO = 86 / 54;

let cachedImg: HTMLImageElement | null = null;
let naturalW = 0, naturalH = 0;

// Canvas display area
let canvasW = 0, canvasH = 0;

// KTP box in canvas coords
let ktpBox = { x: 0, y: 0, w: 0, h: 0 };

// Drag & pinch state
let isDragging = false;
let lastPointer = { x: 0, y: 0 };
let lastPinchDist = 0;

// ── Public API ──
const openModal = async (file: File) => {
    rotation.value = 0;
    imgOffset.value = { x: 0, y: 0 };
    imgScale.value = 1;

    const reader = new FileReader();
    reader.onload = async (e) => {
        const src = e.target?.result as string;
        showModal.value = true;
        await nextTick();
        loadImage(src);
    };
    reader.readAsDataURL(file);
};
defineExpose({ openModal });

// ── Image Loading ──
const loadImage = (src: string) => {
    const img = new Image();
    img.onload = () => {
        cachedImg = img;
        naturalW = img.naturalWidth;
        naturalH = img.naturalHeight;
        setupCanvas();
    };
    img.src = src;
};

const setupCanvas = () => {
    const canvas = canvasRef.value;
    const wrapper = wrapperRef.value;
    if (!canvas || !wrapper || !cachedImg) return;

    canvasW = wrapper.clientWidth;
    canvasH = wrapper.clientHeight;
    canvas.width = canvasW;
    canvas.height = canvasH;

    // KTP box: 78% width, centered
    const ktpW = Math.round(canvasW * 0.78);
    const ktpH = Math.round(ktpW / KTP_RATIO);
    ktpBox = {
        x: (canvasW - ktpW) / 2,
        y: (canvasH - ktpH) / 2,
        w: ktpW,
        h: ktpH,
    };

    fitImageToKtp();
    draw();
};

// ── FIT: hitung scale agar gambar COVER penuh ktpBox ──
// Ini adalah fungsi kunci yang diperbaiki.
// Kita gunakan dimensi gambar SETELAH rotasi diterapkan,
// lalu hitung scale agar gambar cover (memenuhi) ktpBox.
const fitImageToKtp = () => {
    if (!cachedImg) return;

    // Dimensi gambar dalam koordinat layar setelah rotasi
    // Rotasi 90 atau 270 → lebar & tinggi tertukar
    const isPortrait = rotation.value === 90 || rotation.value === 270;
    const rotatedW = isPortrait ? naturalH : naturalW;
    const rotatedH = isPortrait ? naturalW : naturalH;

    // Scale agar gambar COVER (min scale agar tidak ada celah di ktpBox)
    const scaleX = ktpBox.w / rotatedW;
    const scaleY = ktpBox.h / rotatedH;
    imgScale.value = Math.max(scaleX, scaleY);

    // Pusatkan gambar tepat di tengah ktpBox
    imgOffset.value = {
        x: ktpBox.x + ktpBox.w / 2,
        y: ktpBox.y + ktpBox.h / 2,
    };
};

// ── Drawing ──
const draw = () => {
    const canvas = canvasRef.value;
    if (!canvas || !cachedImg) return;
    const ctx = canvas.getContext('2d', { willReadFrequently: false })!;

    // Gunakan imageSmoothingQuality tinggi agar kualitas gambar terjaga
    ctx.imageSmoothingEnabled = true;
    ctx.imageSmoothingQuality = 'high';

    ctx.clearRect(0, 0, canvasW, canvasH);

    // Draw full image (dimmed) sebagai latar
    drawImage(ctx, 0.2);

    // Clip ke KTP box lalu gambar terang
    ctx.save();
    ctx.beginPath();
    ctx.rect(ktpBox.x, ktpBox.y, ktpBox.w, ktpBox.h);
    ctx.clip();
    drawImage(ctx, 1);
    ctx.restore();

    // Overlay gelap di luar KTP
    ctx.save();
    ctx.fillStyle = 'rgba(0,0,0,0.60)';
    ctx.beginPath();
    ctx.rect(0, 0, canvasW, canvasH);
    ctx.rect(ktpBox.x, ktpBox.y, ktpBox.w, ktpBox.h);
    ctx.fill('evenodd');
    ctx.restore();

    drawKtpFrame(ctx);
};

const drawImage = (ctx: CanvasRenderingContext2D, alpha: number) => {
    if (!cachedImg) return;

    // Dimensi gambar yang akan digambar di canvas (setelah scale)
    // Rotasi 90/270: lebar & tinggi naturalnya tertukar
    const isPortrait = rotation.value === 90 || rotation.value === 270;
    const drawW = (isPortrait ? naturalH : naturalW) * imgScale.value;
    const drawH = (isPortrait ? naturalW : naturalH) * imgScale.value;

    ctx.save();
    ctx.globalAlpha = alpha;
    ctx.imageSmoothingEnabled = true;
    ctx.imageSmoothingQuality = 'high';

    // Translate ke pusat gambar, rotate, lalu gambar
    ctx.translate(imgOffset.value.x, imgOffset.value.y);
    ctx.rotate((rotation.value * Math.PI) / 180);

    // Gambar dengan dimensi ASLI gambar (bukan drawW/drawH),
    // karena scale sudah ditangani oleh translate + rotate di atas.
    // Kita scale via ctx.scale agar lebih presisi.
    ctx.scale(imgScale.value, imgScale.value);
    ctx.drawImage(cachedImg, -naturalW / 2, -naturalH / 2, naturalW, naturalH);

    ctx.restore();
};

const drawKtpFrame = (ctx: CanvasRenderingContext2D) => {
    const { x, y, w, h } = ktpBox;
    const cornerLen = 22;
    const r = 8;

    // Rounded rect border biru
    ctx.save();
    ctx.strokeStyle = '#00C2E0';
    ctx.lineWidth = 2.5;
    ctx.shadowColor = '#00C2E0';
    ctx.shadowBlur = 10;
    roundRect(ctx, x, y, w, h, r);
    ctx.stroke();
    ctx.restore();

    // Corner accents putih
    ctx.save();
    ctx.strokeStyle = '#fff';
    ctx.lineWidth = 3;
    ctx.lineCap = 'round';
    const corners = [
        [[x + r, y], [x, y], [x, y + cornerLen]],
        [[x + w - r, y], [x + w, y], [x + w, y + cornerLen]],
        [[x, y + h - cornerLen], [x, y + h], [x + r, y + h]],
        [[x + w, y + h - cornerLen], [x + w, y + h], [x + w - r, y + h]],
    ] as [number, number][][];
    for (const pts of corners) {
        ctx.beginPath();
        ctx.moveTo(pts[0][0], pts[0][1]);
        ctx.lineTo(pts[1][0], pts[1][1]);
        ctx.lineTo(pts[2][0], pts[2][1]);
        ctx.stroke();
    }
    ctx.restore();

    // Guide foto profil KTP (kanan, ~68% dari kiri)
    const photoW = Math.round(w * 0.21);
    const photoH = Math.round(photoW * 1.38);
    const photoX = x + w - photoW - Math.round(w * 0.058); // jarak kanan 0.5cm
    const photoY = y + Math.round(h * 0.185);               // jarak atas 1cm

    ctx.save();
    ctx.fillStyle = 'rgba(0, 194, 224, 0.08)';
    roundRect(ctx, photoX, photoY, photoW, photoH, 3);
    ctx.fill();
    ctx.restore();

    ctx.save();
    ctx.strokeStyle = '#FFD166';
    ctx.lineWidth = 1.5;
    ctx.setLineDash([4, 3]);
    ctx.shadowColor = '#FFD166';
    ctx.shadowBlur = 4;
    roundRect(ctx, photoX, photoY, photoW, photoH, 3);
    ctx.stroke();
    ctx.restore();

    // Corner ticks foto
    ctx.save();
    ctx.strokeStyle = '#FFD166';
    ctx.lineWidth = 2;
    ctx.lineCap = 'round';
    const cl = 7;
    const photoCorners = [
        [[photoX + 2, photoY], [photoX, photoY], [photoX, photoY + cl]],
        [[photoX + photoW - 2, photoY], [photoX + photoW, photoY], [photoX + photoW, photoY + cl]],
        [[photoX, photoY + photoH - cl], [photoX, photoY + photoH], [photoX + 2, photoY + photoH]],
        [[photoX + photoW, photoY + photoH - cl], [photoX + photoW, photoY + photoH], [photoX + photoW - 2, photoY + photoH]],
    ] as [number, number][][];
    for (const pts of photoCorners) {
        ctx.beginPath();
        ctx.moveTo(pts[0][0], pts[0][1]);
        ctx.lineTo(pts[1][0], pts[1][1]);
        ctx.lineTo(pts[2][0], pts[2][1]);
        ctx.stroke();
    }
    ctx.restore();

    // Label "Foto"
    ctx.save();
    ctx.fillStyle = '#FFD166';
    ctx.font = `bold ${Math.round(photoW * 0.18)}px sans-serif`;
    ctx.textAlign = 'center';
    ctx.globalAlpha = 0.85;
    ctx.fillText('Foto', photoX + photoW / 2, photoY + photoH + 12);
    ctx.restore();

    // Garis panduan crosshair tipis
    ctx.save();
    ctx.strokeStyle = 'rgba(255,255,255,0.12)';
    ctx.lineWidth = 1;
    ctx.setLineDash([4, 6]);
    ctx.beginPath();
    ctx.moveTo(x + w / 2, y + 8); ctx.lineTo(x + w / 2, y + h - 8);
    ctx.moveTo(x + 8, y + h / 2); ctx.lineTo(x + w - 8, y + h / 2);
    ctx.stroke();
    ctx.restore();
};

const roundRect = (ctx: CanvasRenderingContext2D, x: number, y: number, w: number, h: number, r: number) => {
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.lineTo(x + w - r, y);
    ctx.quadraticCurveTo(x + w, y, x + w, y + r);
    ctx.lineTo(x + w, y + h - r);
    ctx.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
    ctx.lineTo(x + r, y + h);
    ctx.quadraticCurveTo(x, y + h, x, y + h - r);
    ctx.lineTo(x, y + r);
    ctx.quadraticCurveTo(x, y, x + r, y);
    ctx.closePath();
};

// ── Zoom ──
const zoomIn = () => { imgScale.value = Math.min(imgScale.value * 1.15, 10); draw(); };
const zoomOut = () => {
    // Jangan biarkan zoom terlalu kecil sehingga gambar tidak cover ktpBox
    const isPortrait = rotation.value === 90 || rotation.value === 270;
    const rotatedW = isPortrait ? naturalH : naturalW;
    const rotatedH = isPortrait ? naturalW : naturalH;
    const minScale = Math.max(ktpBox.w / rotatedW, ktpBox.h / rotatedH);
    imgScale.value = Math.max(imgScale.value * 0.87, minScale * 0.5);
    draw();
};

// ── Rotation ──
// Perbaikan utama: saat rotate, kita hitung ulang scale minimal
// berdasarkan orientasi BARU, lalu re-center gambar.
const rotateCW = () => {
    rotation.value = ((rotation.value + 90) % 360) as 0 | 90 | 180 | 270;
    fitImageToKtp(); // recalculate scale & center untuk orientasi baru
    draw();
};
const rotateCCW = () => {
    rotation.value = ((rotation.value + 270) % 360) as 0 | 90 | 180 | 270;
    fitImageToKtp();
    draw();
};

// ── Mouse / Touch Events ──
const getPos = (e: MouseEvent | Touch) => ({ x: e.clientX, y: e.clientY });

const onMouseDown = (e: MouseEvent) => {
    isDragging = true;
    lastPointer = getPos(e);
};
const onMouseMove = (e: MouseEvent) => {
    if (!isDragging) return;
    const pos = getPos(e);
    imgOffset.value.x += pos.x - lastPointer.x;
    imgOffset.value.y += pos.y - lastPointer.y;
    lastPointer = pos;
    draw();
};
const onMouseUp = () => { isDragging = false; };

const onWheel = (e: WheelEvent) => {
    e.preventDefault();
    const factor = e.deltaY < 0 ? 1.08 : 0.93;
    imgScale.value = Math.max(0.1, imgScale.value * factor);
    draw();
};

const onTouchStart = (e: TouchEvent) => {
    e.preventDefault();
    if (e.touches.length === 1) {
        isDragging = true;
        lastPointer = getPos(e.touches[0]);
    } else if (e.touches.length === 2) {
        isDragging = false;
        lastPinchDist = pinchDist(e.touches);
    }
};
const onTouchMove = (e: TouchEvent) => {
    e.preventDefault();
    if (e.touches.length === 1 && isDragging) {
        const pos = getPos(e.touches[0]);
        imgOffset.value.x += pos.x - lastPointer.x;
        imgOffset.value.y += pos.y - lastPointer.y;
        lastPointer = pos;
        draw();
    } else if (e.touches.length === 2) {
        const dist = pinchDist(e.touches);
        const factor = dist / (lastPinchDist || dist);
        imgScale.value = Math.max(0.1, imgScale.value * factor);
        lastPinchDist = dist;
        draw();
    }
};
const onTouchEnd = () => { isDragging = false; };

const pinchDist = (touches: TouchList) => {
    const dx = touches[0].clientX - touches[1].clientX;
    const dy = touches[0].clientY - touches[1].clientY;
    return Math.sqrt(dx * dx + dy * dy);
};

// ── Confirm — crop dengan kualitas tinggi ──
const confirm = () => {
    if (!cachedImg) return;

    const isPortrait = rotation.value === 90 || rotation.value === 270;

    // Dimensi gambar yang dirender di canvas (setelah rotate & scale)
    const renderedW = (isPortrait ? naturalH : naturalW) * imgScale.value;
    const renderedH = (isPortrait ? naturalW : naturalH) * imgScale.value;

    // Posisi KTP box relatif terhadap pusat gambar
    const relX = ktpBox.x - imgOffset.value.x;
    const relY = ktpBox.y - imgOffset.value.y;

    // Balik rotasi untuk mendapatkan koordinat dalam ruang gambar asli
    const angle = -(rotation.value * Math.PI) / 180;
    const cos = Math.cos(angle), sin = Math.sin(angle);
    const rotRelX = relX * cos - relY * sin;
    const rotRelY = relX * sin + relY * cos;

    // Konversi ke koordinat piksel gambar asli
    const scaleX = naturalW / renderedW;
    const scaleY = naturalH / renderedH;
    const srcX = Math.round((rotRelX + renderedW / 2) * scaleX);
    const srcY = Math.round((rotRelY + renderedH / 2) * scaleY);
    const srcW = Math.round(ktpBox.w * scaleX);
    const srcH = Math.round(ktpBox.h * scaleY);

    // Render gambar yang sudah dirotasi ke offscreen canvas resolusi penuh
    const rotW = isPortrait ? naturalH : naturalW;
    const rotH = isPortrait ? naturalW : naturalH;
    const offscreen = document.createElement('canvas');
    offscreen.width = rotW;
    offscreen.height = rotH;
    const octx = offscreen.getContext('2d')!;
    octx.imageSmoothingEnabled = true;
    octx.imageSmoothingQuality = 'high';
    octx.translate(rotW / 2, rotH / 2);
    octx.rotate((rotation.value * Math.PI) / 180);
    octx.drawImage(cachedImg, -naturalW / 2, -naturalH / 2, naturalW, naturalH);

    // ── Output canvas dengan resolusi minimal 1200px lebar ──
    // Ini memastikan kualitas gambar tetap tinggi untuk OCR
    const TARGET_W = 1200;
    const cropAspect = srcW / srcH;
    const outW = Math.max(srcW, TARGET_W);
    const outH = Math.round(outW / cropAspect);

    const result = document.createElement('canvas');
    result.width = outW;
    result.height = outH;
    const rctx = result.getContext('2d')!;
    rctx.imageSmoothingEnabled = true;
    rctx.imageSmoothingQuality = 'high';
    // Gambar dari offscreen (full resolution) ke result (upscale jika perlu)
    rctx.drawImage(offscreen, srcX, srcY, srcW, srcH, 0, 0, outW, outH);

    // Export dengan kualitas tinggi 97%
    result.toBlob((blob) => {
        if (!blob) return;
        showModal.value = false;
        cachedImg = null;
        emit('cropped', new File([blob], 'ktp-crop.jpg', { type: 'image/jpeg' }));
    }, 'image/jpeg', 0.97);
};
const cancel = () => {
    showModal.value = false;
    cachedImg = null;
    emit('cancel');
};

onUnmounted(() => { cachedImg = null; });
</script>

<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="showModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
                <div
                    class="w-full max-w-2xl rounded-2xl bg-[#0f1923] shadow-2xl overflow-hidden border border-white/10">

                    <!-- Header -->
                    <div class="flex items-center justify-between px-5 py-4 border-b border-white/10">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#00C2E0]/15 border border-[#00C2E0]/30">
                                <ScanLine class="h-4 w-4 text-[#00C2E0]" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-white">Posisikan KTP</p>
                                <p class="text-xs text-white/40">Drag & pinch untuk memposisikan KTP dalam kotak</p>
                            </div>
                        </div>
                        <button @click="cancel"
                            class="flex h-8 w-8 items-center justify-center rounded-lg hover:bg-white/10 transition text-white/40 hover:text-white">
                            <X class="h-4 w-4" />
                        </button>
                    </div>

                    <!-- Canvas Area -->
                    <div ref="wrapperRef" class="relative bg-[#070d14]" style="height: 380px;">
                        <canvas ref="canvasRef"
                            class="w-full h-full cursor-grab active:cursor-grabbing touch-none select-none"
                            @mousedown="onMouseDown" @mousemove="onMouseMove" @mouseup="onMouseUp"
                            @mouseleave="onMouseUp" @wheel.prevent="onWheel" @touchstart.prevent="onTouchStart"
                            @touchmove.prevent="onTouchMove" @touchend="onTouchEnd" />

                        <!-- Label overlay -->
                        <div class="absolute bottom-3 left-1/2 -translate-x-1/2 pointer-events-none">
                            <span
                                class="text-[10px] font-medium text-[#00C2E0]/70 bg-black/50 px-3 py-1 rounded-full tracking-widest uppercase">
                                Area KTP
                            </span>
                        </div>
                    </div>

                    <!-- Tip -->
                    <div class="px-5 py-2.5 bg-[#00C2E0]/8 border-t border-[#00C2E0]/15">
                        <p class="text-xs text-[#00C2E0]/80">
                            💡 Pastikan seluruh bagian KTP (NIK, nama, tanggal lahir) masuk dalam kotak biru.
                        </p>
                    </div>

                    <!-- Footer -->
                    <div class="flex items-center justify-between px-5 py-4 border-t border-white/10">
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs text-white/30 mr-1">Putar:</span>
                            <button type="button" @click="rotateCCW"
                                class="group flex h-8 w-8 items-center justify-center rounded-lg border border-white/10 bg-white/5 hover:border-[#00C2E0]/50 hover:bg-[#00C2E0]/10 transition">
                                <RotateCcw class="h-3.5 w-3.5 text-white/50 group-hover:text-[#00C2E0]" />
                            </button>
                            <span class="w-10 text-center text-xs font-mono font-medium text-white/40">
                                {{ rotation }}°
                            </span>
                            <button type="button" @click="rotateCW"
                                class="group flex h-8 w-8 items-center justify-center rounded-lg border border-white/10 bg-white/5 hover:border-[#00C2E0]/50 hover:bg-[#00C2E0]/10 transition">
                                <RotateCw class="h-3.5 w-3.5 text-white/50 group-hover:text-[#00C2E0]" />
                            </button>

                            <div class="w-px h-5 bg-white/10 mx-1" />

                            <span class="text-xs text-white/30 mr-1">Zoom:</span>
                            <button type="button" @click="zoomOut"
                                class="group flex h-8 w-8 items-center justify-center rounded-lg border border-white/10 bg-white/5 hover:border-[#00C2E0]/50 hover:bg-[#00C2E0]/10 transition">
                                <ZoomOut class="h-3.5 w-3.5 text-white/50 group-hover:text-[#00C2E0]" />
                            </button>
                            <button type="button" @click="zoomIn"
                                class="group flex h-8 w-8 items-center justify-center rounded-lg border border-white/10 bg-white/5 hover:border-[#00C2E0]/50 hover:bg-[#00C2E0]/10 transition">
                                <ZoomIn class="h-3.5 w-3.5 text-white/50 group-hover:text-[#00C2E0]" />
                            </button>
                        </div>

                        <div class="flex items-center gap-3">
                            <button @click="cancel"
                                class="px-4 py-2 text-sm font-medium text-white/40 rounded-lg hover:bg-white/10 hover:text-white transition">
                                Batal
                            </button>
                            <button @click="confirm"
                                class="flex items-center gap-2 px-5 py-2 text-sm font-semibold text-white rounded-xl bg-[#007C95] hover:bg-[#00a0b8] transition shadow-lg shadow-[#007C95]/30">
                                <CheckCircle2 class="h-3.5 w-3.5" />
                                Gunakan Foto Ini
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-active .w-full,
.modal-leave-active .w-full {
    transition: transform 0.2s ease;
}

.modal-enter-from .w-full {
    transform: scale(0.96) translateY(8px);
}
</style>