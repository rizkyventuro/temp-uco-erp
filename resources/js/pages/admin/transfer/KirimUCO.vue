<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    PackageCheck,
    Layers,
    Search,
    X,
    Loader2,
    AlertCircle,
} from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

type Mode = 'manual' | 'volume';

interface Collection {
    id: string;
    transaction_code: string;
    volume: number;
}

interface PreviewCollection {
    id: string;
    transaction_code: string;
    volume: number;
}

const props = defineProps<{
    collections: Collection[];
    volumeTolerance: number;
    toleranceAllow: boolean;
    totalAvailableVolume: number;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Transfer UCO', href: '/transfers' },
    { title: 'Kirim UCO', href: '#' },
];

const mode = ref<Mode>('manual');
const searchQuery = ref('');

const form = useForm({
    mode: 'manual' as Mode,
    poo_ids: [] as string[],
    volume: '',
});

// =================== MANUAL MODE ===================
const filteredCollections = computed(() => {
    const q = searchQuery.value.toLowerCase().trim();
    if (q.length < 3) return props.collections;
    return props.collections.filter((c) =>
        c.transaction_code.toLowerCase().includes(q),
    );
});

const toggleSelect = (id: string) => {
    const idx = form.poo_ids.indexOf(id);
    if (idx === -1) {
        form.poo_ids.push(id);
    } else {
        form.poo_ids.splice(idx, 1);
    }
};

const isSelected = (id: string) => form.poo_ids.includes(id);

const totalSelectedVolume = computed(() =>
    props.collections
        .filter((c) => form.poo_ids.includes(c.id))
        .reduce((sum, c) => sum + c.volume, 0),
);

const selectedCollections = computed(() =>
    props.collections.filter((c) => form.poo_ids.includes(c.id)),
);

const selectAll = () => {
    filteredCollections.value.forEach((c) => {
        if (!form.poo_ids.includes(c.id)) form.poo_ids.push(c.id);
    });
};

const clearAll = () => {
    form.poo_ids = [];
};

// =================== VOLUME MODE ===================
const volumeVal = computed(() => parseFloat(form.volume));

const minAllowed = computed(() => {
    const val = volumeVal.value;
    if (!props.toleranceAllow || !form.volume || isNaN(val)) return null;
    return val * (1 - props.volumeTolerance / 100);
});

const maxAllowed = computed(() => {
    const val = volumeVal.value;
    if (!props.toleranceAllow || !form.volume || isNaN(val)) return null;
    return val * (1 + props.volumeTolerance / 100);
});

const volumeError = computed(() => {
    const val = volumeVal.value;
    if (!form.volume || isNaN(val)) return null;
    if (val <= 0) return 'Volume harus lebih dari 0';
    if (props.collections.length === 0) return 'Tidak ada collection tersedia';

    if (props.toleranceAllow) {
        const min = minAllowed.value!;
        const max = maxAllowed.value!;

        if (props.totalAvailableVolume < min) {
            return `Total collection tersedia (${props.totalAvailableVolume.toFixed(2)} L) kurang dari minimum ${min.toFixed(2)} L`;
        }

        const minVol = Math.min(...props.collections.map((c) => c.volume));
        if (minVol > max) {
            return `Semua collection (terkecil ${minVol.toFixed(2)} L) melebihi batas maksimum ${max.toFixed(2)} L`;
        }
    }

    return null;
});

const toleranceInfo = computed(() => {
    const val = volumeVal.value;
    const tol = props.volumeTolerance / 100;

    if (!props.toleranceAllow) {
        return 'Toleransi tidak aktif — sistem memilih kombinasi yang paling mendekati target';
    }

    if (!form.volume || isNaN(val)) {
        return `Sistem mencari kombinasi dalam rentang ±${props.volumeTolerance}% dari target`;
    }

    const min = (val * (1 - tol)).toFixed(2);
    const max = (val * (1 + tol)).toFixed(2);
    return `Rentang valid: ${min} – ${max} L`;
});

// =================== VOLUME PREVIEW ===================
const isPreviewing = ref(false);
const previewCollections = ref<PreviewCollection[]>([]);
const previewTotalVolume = ref(0);
const previewError = ref<string | null>(null);
const hasPreview = ref(false);

// Reset preview ketika volume berubah
const onVolumeInput = () => {
    hasPreview.value = false;
    previewCollections.value = [];
    previewError.value = null;
};

const fetchPreview = async () => {
    if (!form.volume || volumeError.value) {
        if (volumeError.value) toast.error(volumeError.value);
        else toast.error('Masukkan volume terlebih dahulu');
        return;
    }

    isPreviewing.value = true;
    previewError.value = null;
    hasPreview.value = false;

    try {
        const csrfToken = (document.cookie.match(/XSRF-TOKEN=([^;]+)/) ||
            [])[1];
        const response = await fetch('/transfers/preview', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': decodeURIComponent(csrfToken || ''),
                Accept: 'application/json',
            },
            body: JSON.stringify({ volume: parseFloat(form.volume) }),
        });

        const data = await response.json();

        if (data.success) {
            previewCollections.value = data.collections;
            previewTotalVolume.value = data.total_volume;
            hasPreview.value = true;
        } else {
            previewError.value = data.message;
            hasPreview.value = true;
        }
    } catch {
        previewError.value = 'Gagal menghubungi server, coba lagi.';
    } finally {
        isPreviewing.value = false;
    }
};

// =================== SUBMIT ===================
const handleSubmit = () => {
    form.mode = mode.value;

    if (mode.value === 'manual' && form.poo_ids.length === 0) {
        toast.error('Pilih minimal 1 collection untuk ditransfer');
        return;
    }

    if (mode.value === 'volume') {
        if (!form.volume) {
            toast.error('Masukkan jumlah volume yang ingin ditransfer');
            return;
        }
        if (volumeError.value) {
            toast.error(volumeError.value);
            return;
        }
        if (!hasPreview.value || previewError.value) {
            toast.error(
                'Lakukan preview terlebih dahulu untuk melihat collection yang akan ditransfer',
            );
            return;
        }
        if (previewCollections.value.length === 0) {
            toast.error('Tidak ada collection yang cocok ditemukan');
            return;
        }
    }

    form.post('/transfers', {
        onError: () => {
            toast.error('Gagal menemukan kombinasi collection yang sesuai');
        },
    });
};
</script>

<template>
    <Head title="Kirim UCO" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col items-center gap-6 p-6">
            <div class="flex w-full max-w-xl flex-col gap-5">
                <!-- Back -->
                <button
                    @click="router.visit('/transfers')"
                    class="flex w-fit items-center gap-1.5 text-sm text-gray-500 transition hover:text-gray-700"
                >
                    <ArrowLeft class="h-4 w-4" />
                    Kembali
                </button>

                <!-- Card -->
                <div
                    class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
                >
                    <div class="border-b border-gray-100 px-6 pt-6 pb-4">
                        <h1 class="text-[16px] font-bold text-gray-900">
                            Kirim UCO
                        </h1>
                        <p class="mt-0.5 text-[13px] text-gray-500">
                            Pilih collection yang ingin ditransfer
                        </p>
                    </div>

                    <div class="grid gap-5 p-6">
                        <!-- Mode Toggle -->
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                @click="mode = 'manual'"
                                class="flex items-center justify-center gap-2 rounded-xl border py-2.5 text-sm font-medium transition"
                                :class="
                                    mode === 'manual'
                                        ? 'border-primary bg-primary/10 text-primary'
                                        : 'border-gray-200 bg-white text-gray-600 hover:border-primary'
                                "
                            >
                                <PackageCheck class="h-4 w-4" />
                                Pilih Manual
                            </button>
                            <button
                                type="button"
                                @click="mode = 'volume'"
                                class="flex items-center justify-center gap-2 rounded-xl border py-2.5 text-sm font-medium transition"
                                :class="
                                    mode === 'volume'
                                        ? 'border-primary bg-primary/10 text-primary'
                                        : 'border-gray-200 bg-white text-gray-600 hover:border-primary'
                                "
                            >
                                <Layers class="h-4 w-4" />
                                By Volume
                            </button>
                        </div>

                        <!-- ===== MANUAL MODE ===== -->
                        <div
                            v-if="mode === 'manual'"
                            class="grid gap-3"
                        >
                            <Label class="text-sm font-medium">
                                Pilih Collection
                                <span class="text-red-500">*</span>
                            </Label>

                            <!-- Search Bar -->
                            <div class="relative">
                                <Search
                                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400"
                                />
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Cari kode transaksi..."
                                    class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 pr-9 pl-9 text-sm text-gray-700 placeholder-gray-400 transition outline-none focus:border-primary focus:bg-white"
                                />
                                <button
                                    v-if="searchQuery"
                                    @click="searchQuery = ''"
                                    class="absolute top-1/2 right-3 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                >
                                    <X class="h-3.5 w-3.5" />
                                </button>
                            </div>

                            <!-- Select All / Clear -->
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-400">
                                    {{ filteredCollections.length }} collection
                                    ditemukan
                                </span>
                                <div class="flex gap-3">
                                    <button
                                        type="button"
                                        @click="selectAll"
                                        class="text-xs font-medium text-primary hover:underline"
                                    >
                                        Pilih Semua
                                    </button>
                                    <button
                                        v-if="form.poo_ids.length > 0"
                                        type="button"
                                        @click="clearAll"
                                        class="text-xs font-medium text-red-400 hover:underline"
                                    >
                                        Hapus Semua
                                    </button>
                                </div>
                            </div>

                            <!-- Empty state -->
                            <div
                                v-if="collections.length === 0"
                                class="rounded-xl border border-dashed border-gray-200 py-10 text-center"
                            >
                                <p class="text-sm text-gray-400">
                                    Tidak ada collection tersedia
                                </p>
                            </div>

                            <div
                                v-else-if="filteredCollections.length === 0"
                                class="rounded-xl border border-dashed border-gray-200 py-8 text-center"
                            >
                                <p class="text-sm text-gray-400">
                                    Tidak ada hasil untuk "{{ searchQuery }}"
                                </p>
                            </div>

                            <!-- List -->
                            <div
                                v-else
                                class="grid max-h-72 gap-2 overflow-y-auto pr-1"
                            >
                                <div
                                    v-for="col in filteredCollections"
                                    :key="col.id"
                                    class="flex cursor-pointer items-center justify-between rounded-xl border px-4 py-3 transition"
                                    :class="
                                        isSelected(col.id)
                                            ? 'border-primary bg-primary/5'
                                            : 'border-gray-200 bg-white hover:border-primary/50'
                                    "
                                    @click="toggleSelect(col.id)"
                                >
                                    <div class="flex flex-col gap-0.5">
                                        <span
                                            class="text-sm font-semibold text-gray-900"
                                            >{{ col.transaction_code }}</span
                                        >
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="text-sm font-bold text-teal-600"
                                            >{{ col.volume }} L</span
                                        >
                                        <div
                                            class="flex h-5 w-5 items-center justify-center rounded-full border-2 transition"
                                            :class="
                                                isSelected(col.id)
                                                    ? 'border-primary bg-primary'
                                                    : 'border-gray-300 bg-white'
                                            "
                                        >
                                            <svg
                                                v-if="isSelected(col.id)"
                                                class="h-3 w-3 text-white"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                                stroke-width="3"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M5 13l4 4L19 7"
                                                />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Total Selected -->
                            <div
                                v-if="form.poo_ids.length > 0"
                                class="flex items-center justify-between rounded-xl border border-teal-100 bg-teal-50 px-4 py-3"
                            >
                                <span class="text-sm text-teal-700">
                                    {{ form.poo_ids.length }} collection dipilih
                                </span>
                                <span class="text-sm font-bold text-teal-600">
                                    Total:
                                    {{ totalSelectedVolume.toFixed(2) }} L
                                </span>
                            </div>

                            <!-- Preview list untuk manual mode -->
                            <div
                                v-if="form.poo_ids.length > 0"
                                class="grid gap-2"
                            >
                                <p
                                    class="text-xs font-semibold tracking-widest text-gray-400 uppercase"
                                >
                                    Collection yang akan ditransfer ({{
                                        selectedCollections.length
                                    }})
                                </p>
                                <div class="grid gap-2">
                                    <div
                                        v-for="col in selectedCollections"
                                        :key="col.id"
                                        class="flex items-center justify-between rounded-xl border border-gray-100 bg-gray-50 px-4 py-2.5"
                                    >
                                        <span
                                            class="text-sm font-medium text-gray-900"
                                            >{{ col.transaction_code }}</span
                                        >
                                        <span
                                            class="text-sm font-semibold text-gray-900"
                                            >{{ col.volume }} L</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ===== VOLUME MODE ===== -->
                        <div
                            v-else
                            class="grid gap-3"
                        >
                            <Label class="text-sm font-medium">
                                Jumlah Volume (Liter)
                                <span class="text-red-500">*</span>
                            </Label>

                            <!-- Input + Tombol Preview -->
                            <div class="flex gap-2">
                                <Input
                                    v-model="form.volume"
                                    type="number"
                                    min="0.1"
                                    step="0.1"
                                    placeholder="Contoh: 13"
                                    class="flex-1"
                                    :class="{
                                        'border-red-400 focus:border-red-400':
                                            volumeError || form.errors.volume,
                                    }"
                                    @input="onVolumeInput"
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    class="shrink-0 rounded-xl border-primary px-4 text-primary hover:bg-primary/5"
                                    :disabled="
                                        isPreviewing ||
                                        !form.volume ||
                                        !!volumeError
                                    "
                                    @click="fetchPreview"
                                >
                                    <Loader2
                                        v-if="isPreviewing"
                                        class="mr-1 h-4 w-4 animate-spin"
                                    />
                                    <span>{{
                                        isPreviewing
                                            ? 'Mencari...'
                                            : 'Cek Collection'
                                    }}</span>
                                </Button>
                            </div>

                            <!-- Info box -->
                            <div
                                class="grid gap-1 rounded-lg border border-blue-100 bg-blue-50 px-3 py-2.5"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-blue-500"
                                        >Total tersedia</span
                                    >
                                    <span
                                        class="text-xs font-bold text-blue-700"
                                    >
                                        {{ totalAvailableVolume.toFixed(2) }} L
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-blue-500"
                                        >Toleransi</span
                                    >
                                    <span
                                        class="text-xs font-semibold text-blue-700"
                                    >
                                        {{
                                            toleranceAllow
                                                ? `±${volumeTolerance}%`
                                                : 'Tidak aktif'
                                        }}
                                    </span>
                                </div>
                                <!-- Min/Max hanya tampil jika toleransi aktif dan ada input -->
                                <template
                                    v-if="
                                        toleranceAllow &&
                                        form.volume &&
                                        !isNaN(volumeVal)
                                    "
                                >
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span class="text-xs text-blue-400"
                                            >Min</span
                                        >
                                        <span
                                            class="text-xs font-medium text-blue-600"
                                            >{{
                                                minAllowed?.toFixed(2)
                                            }}
                                            L</span
                                        >
                                    </div>
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span class="text-xs text-blue-400"
                                            >Max</span
                                        >
                                        <span
                                            class="text-xs font-medium text-blue-600"
                                            >{{
                                                maxAllowed?.toFixed(2)
                                            }}
                                            L</span
                                        >
                                    </div>
                                </template>
                                <div
                                    class="mt-0.5 border-t border-blue-100 pt-1"
                                >
                                    <p class="text-xs text-blue-500">
                                        {{ toleranceInfo }}
                                    </p>
                                </div>
                            </div>

                            <!-- Error FE -->
                            <p
                                v-if="volumeError"
                                class="flex items-center gap-1 text-xs text-red-500"
                            >
                                ⚠ {{ volumeError }}
                            </p>
                            <!-- Error BE -->
                            <p
                                v-else-if="form.errors.volume"
                                class="flex items-center gap-1 text-xs text-red-500"
                            >
                                ⚠ {{ form.errors.volume }}
                            </p>

                            <!-- ===== HASIL PREVIEW ===== -->
                            <template v-if="hasPreview">
                                <!-- Error Preview -->
                                <div
                                    v-if="previewError"
                                    class="flex items-start gap-2.5 rounded-xl border border-red-100 bg-red-50 px-4 py-3"
                                >
                                    <AlertCircle
                                        class="mt-0.5 h-4 w-4 shrink-0 text-red-500"
                                    />
                                    <p class="text-sm text-red-600">
                                        {{ previewError }}
                                    </p>
                                </div>

                                <!-- Preview Collections -->
                                <div
                                    v-else-if="previewCollections.length > 0"
                                    class="grid gap-2"
                                >
                                    <!-- Header -->
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <p
                                            class="text-xs font-semibold tracking-widest text-gray-400 uppercase"
                                        >
                                            Collection yang akan ditransfer ({{
                                                previewCollections.length
                                            }})
                                        </p>
                                        <span
                                            class="text-xs font-bold text-teal-600"
                                        >
                                            Total:
                                            {{
                                                previewTotalVolume.toFixed(2)
                                            }}
                                            L
                                        </span>
                                    </div>

                                    <!-- List — desain sama persis dengan BerhasilKirimUCO -->
                                    <div class="grid gap-2">
                                        <div
                                            v-for="col in previewCollections"
                                            :key="col.id"
                                            class="flex items-center justify-between rounded-xl border border-gray-100 bg-gray-50 px-4 py-2.5"
                                        >
                                            <span
                                                class="text-sm font-medium text-gray-900"
                                                >{{
                                                    col.transaction_code
                                                }}</span
                                            >
                                            <span
                                                class="text-sm font-semibold text-gray-900"
                                                >{{ col.volume }} L</span
                                            >
                                        </div>
                                    </div>

                                    <!-- Summary -->
                                    <div
                                        class="flex items-center justify-between rounded-xl border border-teal-100 bg-teal-50 px-4 py-3"
                                    >
                                        <span class="text-sm text-teal-700"
                                            >{{
                                                previewCollections.length
                                            }}
                                            collection dipilih sistem</span
                                        >
                                        <span
                                            class="text-sm font-bold text-teal-600"
                                            >{{
                                                previewTotalVolume.toFixed(2)
                                            }}
                                            L</span
                                        >
                                    </div>

                                    <!-- Catatan selisih dari target -->
                                    <p
                                        v-if="
                                            Math.abs(
                                                previewTotalVolume - volumeVal,
                                            ) > 0.001
                                        "
                                        class="text-xs text-gray-400"
                                    >
                                        ℹ Selisih dari target:
                                        {{
                                            Math.abs(
                                                previewTotalVolume - volumeVal,
                                            ).toFixed(2)
                                        }}
                                        L ({{
                                            previewTotalVolume > volumeVal
                                                ? '+'
                                                : '-'
                                        }}{{
                                            (
                                                (Math.abs(
                                                    previewTotalVolume -
                                                        volumeVal,
                                                ) /
                                                    volumeVal) *
                                                100
                                            ).toFixed(1)
                                        }}%)
                                    </p>
                                </div>
                            </template>

                            <!-- Hint sebelum preview -->
                            <p
                                v-else
                                class="text-xs text-gray-400"
                            >
                                Klik "Cek Collection" untuk melihat collection
                                mana yang akan dipilih sistem sebelum mengirim
                            </p>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div
                        class="grid grid-cols-2 gap-3 border-t border-gray-100 p-5"
                    >
                        <Button
                            variant="outline"
                            class="w-full rounded border-gray-200"
                            @click="router.visit('/transfers')"
                        >
                            Batal
                        </Button>
                        <Button
                            @click="handleSubmit"
                            :disabled="
                                form.processing ||
                                (mode === 'volume' &&
                                    (!hasPreview || !!previewError))
                            "
                            class="w-full rounded bg-primary font-medium text-white hover:bg-primary-hover disabled:opacity-40"
                        >
                            Generate QR
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
