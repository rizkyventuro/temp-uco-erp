<script setup lang="ts">
import { Camera, X, ZoomIn } from 'lucide-vue-next';
import { ref, onUnmounted, watch } from 'vue';

const props = defineProps<{
    isOpen: boolean;
}>();

const emit = defineEmits<{
    (e: 'detected', code: string): void;
    (e: 'close'): void;
}>();

const videoRef = ref<HTMLVideoElement | null>(null);
const canvasRef = ref<HTMLCanvasElement | null>(null);
const overlayCanvasRef = ref<HTMLCanvasElement | null>(null);
const stream = ref<MediaStream | null>(null);
const scanning = ref(false);
const zoomLevel = ref(1);
const scanStatus = ref<'idle' | 'scanning' | 'found'>('idle');
const errorMsg = ref('');
const errorDetail = ref('');
const animationId = ref<number | null>(null);

// jsQR loaded via CDN fallback
let jsQR: any = null;

// Zoom state for canvas-based crop zoom
const targetZoom = ref(1);
const currentZoom = ref(1);
const zoomCenterX = ref(0.5);
const zoomCenterY = ref(0.5);

const loadJsQR = async () => {
    if (jsQR) return jsQR;
    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js';
        script.onload = () => {
            jsQR = (window as any).jsQR;
            resolve(jsQR);
        };
        script.onerror = reject;
        document.head.appendChild(script);
    });
};

const startCamera = async () => {
    errorMsg.value = '';
    errorDetail.value = '';
    try {
        await loadJsQR();

        let mediaStream: MediaStream | null = null;

        const constraintsList = [
            {
                video: {
                    facingMode: 'environment',
                    width: { ideal: 1920 },
                    height: { ideal: 1080 },
                },
            },
            { video: { facingMode: 'environment' } },
            { video: true },
        ];

        for (const constraints of constraintsList) {
            try {
                mediaStream =
                    await navigator.mediaDevices.getUserMedia(constraints);
                break;
            } catch (e: any) {
                console.warn('[QRScanner] Constraint failed:', e?.name);
            }
        }

        if (!mediaStream) throw new Error('All camera constraints failed');

        stream.value = mediaStream;

        if (videoRef.value) {
            videoRef.value.srcObject = stream.value;
            await videoRef.value.play();
            scanning.value = true;
            scanStatus.value = 'scanning';
            animationId.value = requestAnimationFrame(scanFrame);
        }
    } catch (err: any) {
        console.error('[QRScanner] Fatal error:', err);
        errorDetail.value = `${err?.name}: ${err?.message}`;
        if (err.name === 'NotAllowedError') {
            errorMsg.value =
                'Akses kamera ditolak. Mohon izinkan akses kamera di pengaturan browser.';
        } else if (err.name === 'NotFoundError') {
            errorMsg.value = 'Kamera tidak ditemukan pada perangkat ini.';
        } else if (err.name === 'NotReadableError') {
            errorMsg.value = 'Kamera sedang digunakan aplikasi lain.';
        } else {
            errorMsg.value = 'Gagal membuka kamera. Coba refresh halaman.';
        }
    }
};

const stopCamera = () => {
    if (animationId.value) {
        cancelAnimationFrame(animationId.value);
        animationId.value = null;
    }
    if (stream.value) {
        stream.value.getTracks().forEach((t) => t.stop());
        stream.value = null;
    }
    scanning.value = false;
    scanStatus.value = 'idle';
    targetZoom.value = 1;
    currentZoom.value = 1;
    zoomCenterX.value = 0.5;
    zoomCenterY.value = 0.5;
    zoomLevel.value = 1;
};

const drawOverlay = (
    location: any | null,
    scaleX: number,
    scaleY: number,
    offsetX: number,
    offsetY: number,
) => {
    const canvas = overlayCanvasRef.value;
    const video = videoRef.value;
    if (!canvas || !video) return;

    canvas.width = video.clientWidth;
    canvas.height = video.clientHeight;
    const ctx = canvas.getContext('2d');
    if (!ctx) return;
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    if (!location) return;

    const pts = [
        location.topLeftCorner,
        location.topRightCorner,
        location.bottomRightCorner,
        location.bottomLeftCorner,
    ];

    // Convert from cropped-canvas coords back to display coords
    const displayW = canvas.width;
    const displayH = canvas.height;
    const vw = video.videoWidth || displayW;
    const vh = video.videoHeight || displayH;

    const toDisplay = (p: any) => ({
        x: ((p.x / scaleX + offsetX) / vw) * displayW,
        y: ((p.y / scaleY + offsetY) / vh) * displayH,
    });

    const displayPts = pts.map(toDisplay);

    ctx.beginPath();
    ctx.moveTo(displayPts[0].x, displayPts[0].y);
    displayPts.slice(1).forEach((p) => ctx.lineTo(p.x, p.y));
    ctx.closePath();
    ctx.strokeStyle = '#00BFA5';
    ctx.lineWidth = 3;
    ctx.stroke();
    ctx.fillStyle = 'rgba(0, 191, 165, 0.15)';
    ctx.fill();
};

const scanFrame = () => {
    const video = videoRef.value;
    const canvas = canvasRef.value;
    if (!video || !canvas || !scanning.value) return;

    if (video.readyState >= video.HAVE_ENOUGH_DATA) {
        const vw = video.videoWidth;
        const vh = video.videoHeight;

        if (vw === 0 || vh === 0) {
            animationId.value = requestAnimationFrame(scanFrame);
            return;
        }

        // Smooth zoom interpolation
        currentZoom.value += (targetZoom.value - currentZoom.value) * 0.08;
        if (Math.abs(currentZoom.value - targetZoom.value) < 0.01) {
            currentZoom.value = targetZoom.value;
        }
        zoomLevel.value = currentZoom.value;

        const zoom = currentZoom.value;

        // Calculate crop region
        const cropW = vw / zoom;
        const cropH = vh / zoom;
        const cropX = Math.max(
            0,
            Math.min(vw - cropW, zoomCenterX.value * vw - cropW / 2),
        );
        const cropY = Math.max(
            0,
            Math.min(vh - cropH, zoomCenterY.value * vh - cropH / 2),
        );

        // Process at capped resolution for performance
        const processW = Math.min(640, vw);
        const processH = Math.round(processW * (cropH / cropW));
        canvas.width = processW;
        canvas.height = processH;

        const ctx = canvas.getContext('2d', { willReadFrequently: true });
        if (!ctx) {
            animationId.value = requestAnimationFrame(scanFrame);
            return;
        }

        // Draw cropped & scaled frame
        ctx.drawImage(
            video,
            cropX,
            cropY,
            cropW,
            cropH,
            0,
            0,
            processW,
            processH,
        );
        const imageData = ctx.getImageData(0, 0, processW, processH);

        const code = jsQR(imageData.data, imageData.width, imageData.height, {
            inversionAttempts: 'attemptBoth',
        });

        if (code) {
            scanStatus.value = 'found';

            const pts = [
                code.location.topLeftCorner,
                code.location.topRightCorner,
                code.location.bottomRightCorner,
                code.location.bottomLeftCorner,
            ];
            const xs = pts.map((p: any) => p.x);
            const ys = pts.map((p: any) => p.y);
            const qrW = Math.max(...xs) - Math.min(...xs);
            const qrH = Math.max(...ys) - Math.min(...ys);
            const qrCx = (Math.min(...xs) + Math.max(...xs)) / 2 / processW;
            const qrCy = (Math.min(...ys) + Math.max(...ys)) / 2 / processH;

            // Map QR center back to full video coordinates (normalized)
            zoomCenterX.value = cropX / vw + qrCx * (cropW / vw);
            zoomCenterY.value = cropY / vh + qrCy * (cropH / vh);

            // Target zoom so QR fills ~55% of frame
            const qrFraction = (qrW / processW + qrH / processH) / 2;
            const desiredZoom = Math.min(
                Math.max(zoom * (0.55 / qrFraction), 1),
                5,
            );
            targetZoom.value = desiredZoom;

            const scaleX = processW / cropW;
            const scaleY = processH / cropH;
            drawOverlay(code.location, scaleX, scaleY, cropX, cropY);

            setTimeout(() => {
                emit('detected', code.data);
                stopCamera();
            }, 700);
            return;
        } else {
            drawOverlay(null, 1, 1, 0, 0);

            // Slowly return to zoom 1 when no QR detected
            if (targetZoom.value > 1) {
                targetZoom.value = Math.max(1, targetZoom.value - 0.02);
            }
        }
    }

    animationId.value = requestAnimationFrame(scanFrame);
};

watch(
    () => props.isOpen,
    (val) => {
        if (val) startCamera();
        else stopCamera();
    },
);

onUnmounted(() => stopCamera());
</script>

<template>
    <div
        v-if="isOpen"
        class="flex flex-col gap-4"
    >
        <!-- Viewport -->
        <div
            class="relative w-full overflow-hidden rounded-2xl bg-black"
            style="aspect-ratio: 4/3"
        >
            <!-- Video: NO CSS transform zoom — zoom is handled by canvas crop -->
            <video
                ref="videoRef"
                class="absolute inset-0 h-full w-full object-cover"
                muted
                playsinline
                autoplay
            />

            <!-- Hidden canvas for jsQR processing -->
            <canvas
                ref="canvasRef"
                class="hidden"
            />

            <!-- Overlay canvas for QR highlight -->
            <canvas
                ref="overlayCanvasRef"
                class="pointer-events-none absolute inset-0 h-full w-full"
            />

            <!-- Scan frame guide -->
            <div
                class="pointer-events-none absolute inset-0 flex items-center justify-center"
            >
                <div class="relative h-52 w-52">
                    <span
                        class="absolute top-0 left-0 h-8 w-8 rounded-tl-lg border-t-4 border-l-4 transition-colors duration-300"
                        :class="
                            scanStatus === 'found'
                                ? 'border-green-400'
                                : 'border-white'
                        "
                    />
                    <span
                        class="absolute top-0 right-0 h-8 w-8 rounded-tr-lg border-t-4 border-r-4 transition-colors duration-300"
                        :class="
                            scanStatus === 'found'
                                ? 'border-green-400'
                                : 'border-white'
                        "
                    />
                    <span
                        class="absolute bottom-0 left-0 h-8 w-8 rounded-bl-lg border-b-4 border-l-4 transition-colors duration-300"
                        :class="
                            scanStatus === 'found'
                                ? 'border-green-400'
                                : 'border-white'
                        "
                    />
                    <span
                        class="absolute right-0 bottom-0 h-8 w-8 rounded-br-lg border-r-4 border-b-4 transition-colors duration-300"
                        :class="
                            scanStatus === 'found'
                                ? 'border-green-400'
                                : 'border-white'
                        "
                    />

                    <div
                        v-if="scanStatus === 'scanning'"
                        class="scan-line absolute right-2 left-2 h-0.5 rounded bg-teal-400 opacity-80"
                    />
                </div>
            </div>

            <!-- Zoom indicator -->
            <div
                v-if="zoomLevel > 1.1"
                class="absolute top-3 right-3 flex items-center gap-1 rounded-full bg-black/50 px-2.5 py-1 text-xs text-white backdrop-blur"
            >
                <ZoomIn class="h-3 w-3" />
                {{ zoomLevel.toFixed(1) }}x
            </div>

            <!-- Status label -->
            <div
                class="absolute bottom-3 left-1/2 -translate-x-1/2 whitespace-nowrap"
            >
                <span
                    v-if="scanStatus === 'scanning'"
                    class="rounded-full bg-black/50 px-3 py-1 text-xs text-white backdrop-blur"
                >
                    Arahkan ke QR Code batch UCO
                </span>
                <span
                    v-else-if="scanStatus === 'found'"
                    class="rounded-full bg-green-500/80 px-3 py-1 text-xs font-semibold text-white backdrop-blur"
                >
                    ✓ QR Terdeteksi!
                </span>
            </div>

            <!-- Error state -->
            <div
                v-if="errorMsg"
                class="absolute inset-0 flex flex-col items-center justify-center gap-3 bg-gray-900 px-6 text-center"
            >
                <Camera class="h-10 w-10 text-gray-500" />
                <p class="text-sm text-gray-300">{{ errorMsg }}</p>
                <p
                    v-if="errorDetail"
                    class="font-mono text-xs break-all text-red-400"
                >
                    {{ errorDetail }}
                </p>
            </div>
        </div>

        <!-- Close button -->
        <button
            @click="$emit('close')"
            class="flex items-center justify-center gap-2 rounded-xl border border-gray-200 py-2.5 text-sm font-medium text-gray-600 transition hover:bg-gray-50"
        >
            <X class="h-4 w-4" />
            Tutup Kamera
        </button>
    </div>
</template>

<style scoped>
@keyframes scan {
    0% {
        top: 8px;
        opacity: 1;
    }
    50% {
        opacity: 0.6;
    }
    100% {
        top: calc(100% - 8px);
        opacity: 1;
    }
}
.scan-line {
    animation: scan 2s ease-in-out infinite alternate;
}
</style>
