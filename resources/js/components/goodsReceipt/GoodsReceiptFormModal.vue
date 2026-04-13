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

export interface SupplierOption {
    id: string;
    name: string;
    payment_term: 'cash' | 'tempo';
    payment_term_days: number | null;
    default_purchase_price: number | null;
}

export interface WarehouseOption {
    id: number;
    name: string;
}

export interface GoodsReceipt {
    id: string;
    transaction_number: string;
    date: string;
    supplier_id: string;
    supplier_name: string;
    warehouse_id: number;
    warehouse_name: string;
    volume: number;
    purchase_price: number;
    total_price: number;
    status: 'lunas' | 'belum_lunas';
    due_date: string | null;
    notes: string | null;
}

// ── Props & Emits ──────────────────────────────────────────────

const props = defineProps<{
    open: boolean;
    editingGoodsReceipt?: GoodsReceipt | null;
    postUrl: string;
    suppliers: SupplierOption[];
    warehouses: WarehouseOption[];
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'success'): void;
}>();

// ── Form ───────────────────────────────────────────────────────

const form = useForm({
    date:         '',
    supplier_id:  '' as string | number,
    warehouse_id: '' as string | number,
    volume:       '' as string | number,
    purchase_price: '' as string | number,
    status:       'lunas' as 'lunas' | 'belum_lunas',
    due_date:     '',
    notes:        '',
});

const isEditing = computed(() => !!props.editingGoodsReceipt);

// Auto-total
const totalPrice = computed(() => {
    const v = parseFloat(String(form.volume))        || 0;
    const h = parseFloat(String(form.purchase_price)) || 0;
    return v * h;
});

// Auto-fill purchase_price dari default supplier
watch(
    () => form.supplier_id,
    (id) => {
        if (!id || isEditing.value) return;
        const supplier = props.suppliers.find((s) => String(s.id) === String(id));
        if (supplier?.default_purchase_price) {
            form.purchase_price = supplier.default_purchase_price;
        }
        // Auto set due_date dari payment_term supplier jika tempo
        if (supplier?.payment_term === 'tempo' && supplier.payment_term_days) {
            form.status = 'belum_lunas';
            const d = new Date();
            d.setDate(d.getDate() + supplier.payment_term_days);
            form.due_date = d.toISOString().split('T')[0];
        } else {
            form.status = 'lunas';
            form.due_date = '';
        }
    },
);

// Clear due_date jika status kembali ke lunas
watch(
    () => form.status,
    (val) => {
        if (val === 'lunas') form.due_date = '';
    },
);

watch(
    () => props.editingGoodsReceipt,
    (gr) => {
        if (gr) {
            form.date          = gr.date ?? '';
            form.supplier_id   = gr.supplier_id ?? '';
            form.warehouse_id  = gr.warehouse_id ?? '';
            form.volume        = gr.volume ?? '';
            form.purchase_price = gr.purchase_price ?? '';
            form.status        = gr.status ?? 'lunas';
            form.due_date      = gr.due_date ?? '';
            form.notes         = gr.notes ?? '';
        } else {
            form.reset();
            // Default date = hari ini
            form.date = new Date().toISOString().split('T')[0];
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

    if (isEditing.value && props.editingGoodsReceipt) {
        form.patch(`${props.postUrl}/${props.editingGoodsReceipt.id}`, { onSuccess });
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
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="flex max-h-[90vh] flex-col overflow-hidden rounded-2xl p-0 sm:max-w-2xl">

            <!-- Header -->
            <div class="flex-shrink-0 px-6 pt-6 pb-4 border-b border-gray-100">
                <DialogHeader>
                    <DialogTitle class="text-base font-semibold text-gray-900">
                        {{ isEditing ? 'Ubah Data Barang Masuk' : 'Tambah Barang Masuk' }}
                    </DialogTitle>
                </DialogHeader>
            </div>

            <!-- Body (scrollable) -->
            <div class="flex-1 overflow-y-auto px-6 py-5">
                <div class="grid gap-4">

                    <!-- Row 1: No Transaksi (readonly jika edit) | Tanggal -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">No. Transaksi</Label>
                            <Input
                                :model-value="editingGoodsReceipt?.transaction_number ?? 'Auto Generate'"
                                disabled
                                class="bg-gray-50 text-gray-400 cursor-not-allowed font-mono" />
                        </div>

                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">
                                Tanggal <span class="text-red-500">*</span>
                            </Label>
                            <Input
                                v-model="form.date"
                                type="date"
                                :class="inputClass(!!form.errors.date)" />
                            <span v-if="form.errors.date" class="text-xs text-red-500">{{ form.errors.date }}</span>
                        </div>
                    </div>

                    <!-- Row 2: Supplier | Gudang -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">
                                Supplier <span class="text-red-500">*</span>
                            </Label>
                            <select v-model="form.supplier_id" :class="selectClass(!!form.errors.supplier_id)">
                                <option value="">Pilih Supplier</option>
                                <option v-for="s in suppliers" :key="s.id" :value="s.id">
                                    {{ s.name }}
                                </option>
                            </select>
                            <span v-if="form.errors.supplier_id" class="text-xs text-red-500">{{ form.errors.supplier_id }}</span>
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
                            <span v-if="form.errors.warehouse_id" class="text-xs text-red-500">{{ form.errors.warehouse_id }}</span>
                        </div>
                    </div>

                    <!-- Row 3: Volume | Harga Beli -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">
                                Volume (kg) <span class="text-red-500">*</span>
                            </Label>
                            <Input
                                v-model="form.volume"
                                type="number"
                                min="0.01"
                                step="0.01"
                                placeholder="0"
                                :class="inputClass(!!form.errors.volume)" />
                            <span v-if="form.errors.volume" class="text-xs text-red-500">{{ form.errors.volume }}</span>
                        </div>

                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">
                                Harga Beli (Rp/kg) <span class="text-red-500">*</span>
                            </Label>
                            <Input
                                v-model="form.purchase_price"
                                type="number"
                                min="0"
                                placeholder="0"
                                :class="inputClass(!!form.errors.purchase_price)" />
                            <span v-if="form.errors.purchase_price" class="text-xs text-red-500">{{ form.errors.purchase_price }}</span>
                        </div>
                    </div>

                    <!-- Total Harga (read-only computed) -->
                    <div class="grid gap-1.5">
                        <Label class="text-sm font-medium text-gray-700">Total Harga</Label>
                        <div class="flex h-10 w-full items-center rounded-md border border-input bg-gray-50 px-3 text-sm font-semibold text-gray-700">
                            {{ formatRupiah(totalPrice) }}
                        </div>
                    </div>

                    <!-- Row 4: Status Pembayaran | Jatuh Tempo (conditional) -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">
                                Status Pembayaran <span class="text-red-500">*</span>
                            </Label>
                            <select v-model="form.status" :class="selectClass(!!form.errors.status)">
                                <option value="lunas">Lunas</option>
                                <option value="belum_lunas">Belum Lunas</option>
                            </select>
                            <span v-if="form.errors.status" class="text-xs text-red-500">{{ form.errors.status }}</span>
                        </div>

                        <div v-if="form.status === 'belum_lunas'" class="grid gap-1.5">
                            <Label class="text-sm font-medium text-gray-700">
                                Jatuh Tempo <span class="text-red-500">*</span>
                            </Label>
                            <Input
                                v-model="form.due_date"
                                type="date"
                                :min="form.date"
                                :class="inputClass(!!form.errors.due_date)" />
                            <span v-if="form.errors.due_date" class="text-xs text-red-500">{{ form.errors.due_date }}</span>
                        </div>
                    </div>

                    <!-- Keterangan (full width) -->
                    <div class="grid gap-1.5">
                        <Label class="text-sm font-medium text-gray-700">Keterangan</Label>
                        <textarea
                            v-model="form.notes"
                            rows="3"
                            placeholder="Catatan tambahan untuk transaksi ini..."
                            :class="textareaClass(!!form.errors.notes)" />
                        <span v-if="form.errors.notes" class="text-xs text-red-500">{{ form.errors.notes }}</span>
                    </div>

                </div>
            </div>

            <!-- Footer -->
            <div class="flex justify-end gap-3 border-t border-gray-100 p-5">
                <Button
                    variant="outline"
                    class="w-fit rounded-md"
                    :disabled="form.processing"
                    @click="emit('update:open', false)">
                    Batal
                </Button>
                <Button
                    :disabled="form.processing"
                    class="w-fit rounded-md bg-[#007C95] text-white hover:bg-[#006b80]"
                    @click="handleSubmit">
                    {{ form.processing
                        ? 'Menyimpan...'
                        : (isEditing ? 'Simpan Perubahan' : 'Simpan Barang Masuk') }}
                </Button>
            </div>

        </DialogContent>
    </Dialog>
</template>
