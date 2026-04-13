<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch, computed } from 'vue';
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

export interface BuyerOption {
    id: string;
    name: string;
    default_selling_price: number | null;
}

export interface WarehouseOption {
    id: number;
    name: string;
}

export interface GoodsIssue {
    id: string;
    transaction_number: string;
    date: string;
    buyer_id: string;
    buyer_name: string;
    warehouse_id: number;
    warehouse_name: string;
    volume: number;
    selling_price: number;
    total_price: number;
    status: 'pending' | 'shipped' | 'delivered' | 'cancelled';
    status_label: string;
    status_color: string;
    notes: string | null;
}

// ── Props & Emits ──────────────────────────────────────────────

const props = defineProps<{
    open: boolean;
    editingGoodsIssue?: GoodsIssue | null;
    postUrl: string;
    buyers: BuyerOption[];
    warehouses: WarehouseOption[];
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'success'): void;
}>();

// ── Form ───────────────────────────────────────────────────────

const form = useForm({
    date: '',
    buyer_id: '' as string | number,
    warehouse_id: '' as string | number,
    volume: '' as string | number,
    selling_price: '' as string | number,
    status: 'pending' as GoodsIssue['status'],
    notes: '',
});

const isEditing = computed(() => !!props.editingGoodsIssue);

// Auto-total
const totalPrice = computed(() => {
    const v = parseFloat(String(form.volume)) || 0;
    const h = parseFloat(String(form.selling_price)) || 0;
    return v * h;
});

// Auto-fill selling_price dari buyer default
watch(
    () => form.buyer_id,
    (id) => {
        if (!id || isEditing.value) return;
        const buyer = props.buyers.find((b) => String(b.id) === String(id));
        if (buyer?.default_selling_price) {
            form.selling_price = buyer.default_selling_price;
        }
    },
);

watch(
    () => props.editingGoodsIssue,
    (gi) => {
        if (gi) {
            form.date = gi.date ?? '';
            form.buyer_id = gi.buyer_id ?? '';
            form.warehouse_id = gi.warehouse_id ?? '';
            form.volume = gi.volume ?? '';
            form.selling_price = gi.selling_price ?? '';
            form.status = gi.status ?? 'pending';
            form.notes = gi.notes ?? '';
        } else {
            form.reset();
            form.date = new Date().toISOString().split('T')[0];
            form.status = 'pending';
        }
        form.clearErrors();
    },
    { immediate: true },
);

// ── Submit ─────────────────────────────────────────────────────

function handleSubmit() {
    const onSuccess = () => {
        emit('update:open', false);
        emit('success');
        form.reset();
    };

    if (isEditing.value && props.editingGoodsIssue) {
        form.patch(`${props.postUrl}/${props.editingGoodsIssue.id}`, { onSuccess });
    } else {
        form.post(props.postUrl, { onSuccess });
    }
}

// ── Helpers ────────────────────────────────────────────────────

const formatRupiah = (val: number) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val);

const inputClass = (hasError: boolean) =>
    hasError ? 'border-red-400' : '';

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

const STATUS_OPTIONS: { label: string; value: GoodsIssue['status'] }[] = [
    { label: 'Pending', value: 'pending' },
    { label: 'Shipped', value: 'shipped' },
    { label: 'Delivered', value: 'delivered' },
    { label: 'Cancelled', value: 'cancelled' },
];
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="flex max-h-[90vh] flex-col overflow-hidden rounded-2xl p-0 sm:max-w-2xl">

            <!-- Header -->
            <div class="flex-shrink-0 px-6 pt-6 pb-4 border-b border-gray-100">
                <DialogHeader>
                    <DialogTitle class="text-base font-semibold text-gray-900">
                        {{ isEditing ? 'Ubah Data Barang Keluar' : 'Tambah Barang Keluar' }}
                    </DialogTitle>
                </DialogHeader>
            </div>

            <!-- Body (scrollable) -->
            <div class="flex-1 overflow-y-auto px-6 py-5">
                <div class="grid gap-4">

                    <!-- Row 1: No Transaksi | Tanggal -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">No. Transaksi</Label>
                            <Input :model-value="editingGoodsIssue?.transaction_number ?? 'Auto Generate'" disabled
                                class="bg-gray-50 text-gray-400 cursor-not-allowed font-mono" />
                        </div>

                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">
                                Tanggal <span class="text-red-500">*</span>
                            </Label>
                            <Input v-model="form.date" type="date" :class="inputClass(!!form.errors.date)" />
                            <span v-if="form.errors.date" class="text-xs text-red-500">{{ form.errors.date
                                }}</span>
                        </div>
                    </div>

                    <!-- Row 2: Buyer | Gudang -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">
                                Buyer <span class="text-red-500">*</span>
                            </Label>
                            <select v-model="form.buyer_id" :class="selectClass(!!form.errors.buyer_id)">
                                <option value="">Pilih Buyer</option>
                                <option v-for="b in buyers" :key="b.id" :value="b.id">
                                    {{ b.name }}
                                </option>
                            </select>
                            <span v-if="form.errors.buyer_id" class="text-xs text-red-500">{{ form.errors.buyer_id
                                }}</span>
                        </div>

                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">
                                Gudang <span class="text-red-500">*</span>
                            </Label>
                            <select v-model="form.warehouse_id" :class="selectClass(!!form.errors.warehouse_id)">
                                <option value="">Pilih Gudang</option>
                                <option v-for="w in warehouses" :key="w.id" :value="w.id">
                                    {{ w.name }}
                                </option>
                            </select>
                            <span v-if="form.errors.warehouse_id" class="text-xs text-red-500">{{
                                form.errors.warehouse_id }}</span>
                        </div>
                    </div>

                    <!-- Row 3: Volume | Harga Jual -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">
                                Volume (kg) <span class="text-red-500">*</span>
                            </Label>
                            <Input v-model="form.volume" type="number" min="0.01" step="0.01" placeholder="0"
                                :class="inputClass(!!form.errors.volume)" />
                            <span v-if="form.errors.volume" class="text-xs text-red-500">{{ form.errors.volume }}</span>
                        </div>

                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">
                                Harga Jual (Rp/kg) <span class="text-red-500">*</span>
                            </Label>
                            <Input v-model="form.selling_price" type="number" min="0" placeholder="0"
                                :class="inputClass(!!form.errors.selling_price)" />
                            <span v-if="form.errors.selling_price" class="text-xs text-red-500">{{ form.errors.selling_price
                                }}</span>
                        </div>
                    </div>

                    <!-- Total Harga (read-only computed) -->
                    <div class="grid gap-1.5">
                        <Label class="text-sm font-medium text-gray-700">Total Harga</Label>
                        <div
                            class="flex h-10 w-full items-center rounded-md border border-input bg-gray-50 px-3 text-sm font-semibold text-gray-700">
                            {{ formatRupiah(totalPrice) }}
                        </div>
                    </div>

                    <!-- Row 4: Status -->
                    <div class="grid gap-1.5">
                        <Label class="text-sm font-medium text-gray-700">
                            Status <span class="text-red-500">*</span>
                        </Label>
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                            <button v-for="opt in STATUS_OPTIONS" :key="opt.value" type="button"
                                class="flex items-center justify-center rounded-lg border px-3 py-2 text-sm font-medium transition"
                                :class="form.status === opt.value
                                    ? {
                                        pending: 'border-amber-400 bg-amber-50 text-amber-700',
                                        shipped: 'border-blue-400 bg-blue-50 text-blue-700',
                                        delivered: 'border-emerald-400 bg-emerald-50 text-emerald-700',
                                        cancelled: 'border-red-400 bg-red-50 text-red-700',
                                    }[opt.value]
                                    : 'border-gray-200 bg-white text-gray-500 hover:border-gray-300 hover:bg-gray-50'"
                                @click="form.status = opt.value">
                                {{ opt.label }}
                            </button>
                        </div>
                        <span v-if="form.errors.status" class="text-xs text-red-500">{{ form.errors.status }}</span>
                    </div>

                    <!-- Keterangan (full width) -->
                    <div class="grid gap-1.5">
                        <Label class="text-sm font-medium text-gray-700">Keterangan</Label>
                        <textarea v-model="form.notes" rows="3"
                            placeholder="Catatan tambahan untuk transaksi ini..."
                            :class="textareaClass(!!form.errors.notes)" />
                        <span v-if="form.errors.notes" class="text-xs text-red-500">{{ form.errors.notes
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
                <Button :disabled="form.processing" class="w-fit rounded-md bg-[#007C95] text-white hover:bg-[#006b80]"
                    @click="handleSubmit">
                    {{ form.processing
                        ? 'Menyimpan...'
                        : (isEditing ? 'Simpan Perubahan' : 'Simpan Barang Keluar') }}
                </Button>
            </div>

        </DialogContent>
    </Dialog>
</template>