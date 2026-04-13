<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Warehouse } from './WarehouseFormModal.vue';

interface WarehouseOption {
    id: string | number;
    label: string;
    name?: string;
    code?: string;
    current_stock?: number;
    capacity_max?: number;
    occupancy?: number;
}

const props = defineProps<{
    open: boolean;
    sourceWarehouse?: Warehouse | null;
    allWarehouses: WarehouseOption[];
    transferUrl: string;
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'success'): void;
}>();

const form = useForm({
    from_warehouse_id: '' as string | number,
    to_warehouse_id: '' as string | number,
    volume: '' as string | number,
    transferred_at: '',
    estimated_arrival: '',
    officer: '',
    notes: '',
});

watch(() => props.open, (val) => {
    if (val && props.sourceWarehouse) {
        form.from_warehouse_id = props.sourceWarehouse.id;
    }
    if (!val) form.reset();
});

// ── Computed helpers ───────────────────────────────────────────

const sourceData = computed(() => {
    if (!props.sourceWarehouse) return null;
    const currentStock = Number(props.sourceWarehouse.current_stock ?? 0);
    const capacityMax = Number(props.sourceWarehouse.capacity_max ?? 0);
    const occupancy = capacityMax > 0 ? Math.round((currentStock / capacityMax) * 100) : 0;
    return {
        name: props.sourceWarehouse.name,
        currentStock,
        capacityMax,
        occupancy,
    };
});

const destinationData = computed(() => {
    if (!form.to_warehouse_id) return null;
    const wh = props.allWarehouses.find(w => w.id == form.to_warehouse_id);
    if (!wh) return null;
    const currentStock = Number(wh.current_stock ?? 0);
    const capacityMax = Number(wh.capacity_max ?? 0);
    const occupancy = capacityMax > 0 ? Math.round((currentStock / capacityMax) * 100) : 0;
    return {
        name: wh.name ?? wh.label,
        currentStock,
        capacityMax,
        occupancy,
    };
});

const volumeNum = computed(() => {
    const v = Number(form.volume);
    return isNaN(v) || v <= 0 ? 0 : v;
});

const sourceAfter = computed(() => {
    if (!sourceData.value) return 0;
    return sourceData.value.currentStock - volumeNum.value;
});

const destAfter = computed(() => {
    if (!destinationData.value) return 0;
    return destinationData.value.currentStock + volumeNum.value;
});

// ── Format helpers ─────────────────────────────────────────────

function formatNumber(val: number): string {
    return val.toLocaleString('id-ID');
}

function occupancyColor(pct: number): string {
    if (pct >= 90) return 'text-red-500';
    if (pct >= 70) return 'text-amber-500';
    return 'text-emerald-600';
}

// ── Submit ─────────────────────────────────────────────────────

function handleSubmit() {
    form.post(props.transferUrl, {
        onSuccess: () => {
            emit('update:open', false);
            emit('success');
            form.reset();
        },
    });
}

const inputClass = 'h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-sm text-gray-700 placeholder-gray-400 focus:border-[#007C95] focus:ring-1 focus:ring-[#007C95] focus:outline-none';
const selectClass = (hasError: boolean) => [
    'h-10 w-full rounded-lg border bg-white px-3 text-sm text-gray-700 appearance-none',
    'focus:border-[#007C95] focus:ring-1 focus:ring-[#007C95] focus:outline-none',
    hasError ? 'border-red-400' : 'border-gray-200',
];
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="rounded-2xl sm:max-w-xl p-0 gap-0 overflow-hidden">
            <!-- Header -->
            <DialogHeader class="px-6 pt-5 pb-4 border-b border-gray-100">
                <DialogTitle class="text-base font-semibold text-gray-900">Buat Transfer Stok</DialogTitle>
            </DialogHeader>

            <div class="px-6 py-5 flex flex-col gap-4 max-h-[75vh] overflow-y-auto">

                <!-- Row 1: Dari & Ke Warehouse -->
                <div class="grid grid-cols-2 gap-4">
                    <!-- Dari Gudang (Asal) -->
                    <div class="flex flex-col gap-1.5">
                        <Label class="text-sm font-medium text-gray-700">Dari Gudang (Asal)</Label>
                        <div
                            class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 flex items-center text-sm text-gray-700">
                            <template v-if="sourceData">
                                <span class="truncate">
                                    {{ sourceData.name }}
                                    <span class="text-gray-400">({{ formatNumber(sourceData.currentStock) }} kg ·</span>
                                    <span :class="occupancyColor(sourceData.occupancy)" class="font-medium">{{
                                        sourceData.occupancy }}%</span>
                                    <span class="text-gray-400">)</span>
                                </span>
                            </template>
                            <template v-else>
                                <span class="text-gray-400">Pilih warehouse</span>
                            </template>
                        </div>
                        <span v-if="form.errors.from_warehouse_id" class="text-xs text-red-500">
                            {{ form.errors.from_warehouse_id }}
                        </span>
                    </div>

                    <!-- Ke Gudang (Tujuan) -->
                    <div class="flex flex-col gap-1.5">
                        <Label class="text-sm font-medium text-gray-700">Ke Gudang (Tujuan)</Label>
                        <div class="relative">
                            <select v-model="form.to_warehouse_id" :class="selectClass(!!form.errors.to_warehouse_id)">
                                <option value="">Select destination warehouse</option>
                                <option v-for="g in allWarehouses" :key="g.id" :value="g.id">{{ g.label }}</option>
                            </select>
                            <svg class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-gray-400"
                                width="14" height="14" viewBox="0 0 16 16" fill="none">
                                <path d="M4 6l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                        </div>
                        <!-- Destination warehouse info -->
                        <div v-if="destinationData" class="text-xs text-gray-400">
                            {{ formatNumber(destinationData.currentStock) }} kg ·
                            <span :class="occupancyColor(destinationData.occupancy)" class="font-medium">
                                {{ destinationData.occupancy }}%
                            </span>
                        </div>
                        <span v-if="form.errors.to_warehouse_id" class="text-xs text-red-500">
                            {{ form.errors.to_warehouse_id }}
                        </span>
                    </div>
                </div>

                <!-- Row 2: Volume & Tanggal Transfer -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1.5">
                        <Label class="text-sm font-medium text-gray-700">Volume Transfer (kg)</Label>
                        <Input v-model="form.volume" type="number" min="0.01" step="0.01" placeholder="0"
                            :class="[inputClass, form.errors.volume ? 'border-red-400' : '']" />
                        <span v-if="form.errors.volume" class="text-xs text-red-500">{{ form.errors.volume }}</span>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <Label class="text-sm font-medium text-gray-700">Tanggal Transfer</Label>
                        <div class="relative">
                            <input v-model="form.transferred_at" type="date" :class="inputClass" />
                        </div>
                    </div>
                </div>

                <!-- Row 3: Estimasi Tiba & Petugas -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1.5">
                        <Label class="text-sm font-medium text-gray-700">Estimasi Tiba</Label>
                        <div class="relative">
                            <input v-model="form.estimated_arrival" type="date" :class="inputClass" />
                        </div>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <Label class="text-sm font-medium text-gray-700">Petugas</Label>
                        <input v-model="form.officer" type="text" placeholder="Nama petugas transfer"
                            :class="inputClass" />
                    </div>
                </div>

                <!-- Keterangan -->
                <div class="flex flex-col gap-1.5">
                    <Label class="text-sm font-medium text-gray-700">Keterangan</Label>
                    <textarea v-model="form.notes" rows="3" placeholder="Alasan / catatan transfer..."
                        class="w-full resize-none rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-700 placeholder-gray-400 focus:border-[#007C95] focus:ring-1 focus:ring-[#007C95] focus:outline-none" />
                </div>

                <!-- ── Summary Cards ────────────────────────── -->
                <div v-if="sourceData && destinationData && volumeNum > 0" class="grid grid-cols-2 gap-3">

                    <!-- Source summary -->
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3.5">
                        <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wide mb-2">
                            GUDANG ASAL — {{ sourceData.name }}
                        </p>
                        <div class="flex flex-col gap-1 text-[13px]">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">Stok saat ini</span>
                                <span class="text-gray-800 font-medium">{{ formatNumber(sourceData.current_stock) }} kg</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">Volume transfer</span>
                                <span class="text-red-500 font-medium">- {{ formatNumber(volumeNum) }} kg</span>
                            </div>
                            <div class="border-t border-gray-200 my-1" />
                            <div class="flex items-center justify-between">
                                <span class="text-gray-800 font-semibold">Stok setelah</span>
                                <span class="text-gray-800 font-semibold">{{ formatNumber(sourceAfter) }} kg</span>
                            </div>
                        </div>
                    </div>

                    <!-- Destination summary -->
                    <div class="rounded-lg border border-[#007C95]/20 bg-[#007C95]/[0.03] p-3.5">
                        <p class="text-[11px] font-bold text-[#007C95] uppercase tracking-wide mb-2">
                            GUDANG TUJUAN — {{ destinationData.name }}
                        </p>
                        <div class="flex flex-col gap-1 text-[13px]">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">Stok saat ini</span>
                                <span class="text-gray-800 font-medium">{{ formatNumber(destinationData.currentStock) }}
                                    kg</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">Volume transfer</span>
                                <span class="text-emerald-600 font-medium">+ {{ formatNumber(volumeNum) }} kg</span>
                            </div>
                            <div class="border-t border-[#007C95]/10 my-1" />
                            <div class="flex items-center justify-between">
                                <span class="text-gray-800 font-semibold">Stok setelah</span>
                                <span class="text-gray-800 font-semibold">{{ formatNumber(destAfter) }} kg</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Warning if source goes below zero -->
                <div v-if="sourceData && volumeNum > 0 && sourceAfter < 0"
                    class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-600 flex items-center gap-2">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="none" class="shrink-0">
                        <path d="M8 5v3m0 2.5h.005M14.5 8a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z" stroke="currentColor"
                            stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    Volume transfer melebihi stok yang tersedia di gudang asal.
                </div>
            </div>

            <!-- Footer -->
            <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-100 bg-white">
                <Button variant="outline" class="rounded-lg border-gray-200 px-5" :disabled="form.processing"
                    @click="emit('update:open', false)">
                    Batal
                </Button>
                <Button :disabled="form.processing || !form.to_warehouse_id || volumeNum <= 0"
                    class="rounded-lg bg-[#007C95] text-white hover:bg-[#006b80] px-5" @click="handleSubmit">
                    {{ form.processing ? 'Memproses...' : 'Proses Transfer' }}
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>