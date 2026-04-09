<script setup lang="ts">
/**
 * TableFilter.vue — Reusable filter dropdown component
 * No external UI library dependency (no Popover needed)
 *
 * Usage:
 * <TableFilter
 *   :filters="[
 *     { key: 'status', label: 'Status', type: 'select', options: [{ label: 'Aktif', value: 'active' }, { label: 'Non Aktif', value: 'inactive' }] },
 *     { key: 'role', label: 'Role', type: 'select', options: [{ label: 'Admin', value: 'admin' }, { label: 'User', value: 'user' }] },
 *     { key: 'date_from', label: 'Dari Tanggal', type: 'date' },
 *     { key: 'date_to', label: 'Sampai Tanggal', type: 'date' },
 *     { key: 'name', label: 'Nama', type: 'text', placeholder: 'Cari nama...' },
 *     { key: 'verified', label: 'Terverifikasi', type: 'checkbox' },
 *   ]"
 *   :model-value="{ status: 'active', role: undefined }"
 *   @update:model-value="handleFilter"
 *   @reset="handleReset"
 * />
 */
import { ref, watch, computed, onMounted, onBeforeUnmount } from 'vue';
import { Filter, X, RotateCcw } from 'lucide-vue-next';

// ── Types ──────────────────────────────────────────────
export interface FilterOption {
    label: string;
    value: string | number | boolean;
}

export interface FilterField {
    /** Unique key — maps to query param */
    key: string;
    /** Display label */
    label: string;
    /** Input type */
    type: 'select' | 'text' | 'date' | 'checkbox';
    /** Options for select type */
    options?: FilterOption[];
    /** Placeholder for text / date */
    placeholder?: string;
}

export type FilterValues = Record<string, string | number | boolean | undefined>;

// ── Props & Emits ──────────────────────────────────────
const props = withDefaults(defineProps<{
    filters: FilterField[];
    modelValue?: FilterValues;
}>(), {
    modelValue: () => ({}),
});

const emit = defineEmits<{
    'update:modelValue': [values: FilterValues];
    'reset': [];
}>();

// ── State ──────────────────────────────────────────────
const isOpen = ref(false);
const openToLeft = ref(true);
const wrapperRef = ref<HTMLElement | null>(null);
const localValues = ref<FilterValues>({ ...props.modelValue });

// Sync from parent when modelValue changes externally
watch(
    () => props.modelValue,
    (val) => { localValues.value = { ...val }; },
    { deep: true },
);

// Count active filters
const activeCount = computed(() =>
    Object.values(localValues.value).filter(
        (v) => v !== undefined && v !== '' && v !== false,
    ).length,
);

// ── Click outside to close ─────────────────────────────
const onClickOutside = (e: MouseEvent) => {
    if (wrapperRef.value && !wrapperRef.value.contains(e.target as Node)) {
        isOpen.value = false;
    }
};

onMounted(() => document.addEventListener('mousedown', onClickOutside));
onBeforeUnmount(() => document.removeEventListener('mousedown', onClickOutside));

// ── Methods ────────────────────────────────────────────
const PANEL_WIDTH = 320; // w-80 = 320px

const toggle = () => {
    if (!isOpen.value && wrapperRef.value) {
        const rect = wrapperRef.value.getBoundingClientRect();
        // Cek apakah ruang di sebelah kiri cukup untuk panel
        openToLeft.value = rect.left >= PANEL_WIDTH;
    }
    isOpen.value = !isOpen.value;
};

const updateField = (key: string, value: string | number | boolean | undefined) => {
    localValues.value[key] = value;
};

const apply = () => {
    const cleaned: FilterValues = {};
    for (const [k, v] of Object.entries(localValues.value)) {
        if (v !== undefined && v !== '' && v !== false) {
            cleaned[k] = v;
        }
    }
    emit('update:modelValue', cleaned);
    isOpen.value = false;
};

const reset = () => {
    localValues.value = {};
    emit('update:modelValue', {});
    emit('reset');
    isOpen.value = false;
};

const clearField = (key: string) => {
    localValues.value[key] = undefined;
};
</script>

<template>
    <div ref="wrapperRef" class="relative">
        <!-- Trigger Button -->
        <button @click="toggle"
            class="relative flex h-[45px] w-[45px] items-center justify-center rounded-lg border bg-white text-sm transition"
            :class="activeCount > 0
                ? 'border-[#007C95] text-[#007C95]'
                : 'border-gray-300 text-gray-600 hover:bg-gray-50'
                ">
            <Filter class="size-3.5" />
            <span v-if="activeCount > 0"
                class="flex size-5 items-center justify-center rounded-full bg-[#007C95] text-[10px] font-bold text-white">
                {{ activeCount }}
            </span>
        </button>

        <!-- Dropdown Panel -->
        <Transition enter-active-class="transition duration-150 ease-out" enter-from-class="scale-95 opacity-0"
            enter-to-class="scale-100 opacity-100" leave-active-class="transition duration-100 ease-in"
            leave-from-class="scale-100 opacity-100" leave-to-class="scale-95 opacity-0">
            <div v-if="isOpen"
                class="absolute z-50 mt-2 w-80 rounded-xl border border-gray-200 bg-white shadow-lg"
                :class="openToLeft
                    ? 'right-0 origin-top-right'
                    : 'left-0 origin-top-left'">
                <!-- Header -->
                <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                    <h3 class="text-sm font-semibold text-gray-800">Filter</h3>
                    <button v-if="activeCount > 0" @click="reset"
                        class="flex items-center gap-1 text-xs text-gray-400 transition hover:text-red-500">
                        <RotateCcw class="size-3" />
                        Reset
                    </button>
                </div>

                <!-- Filter Fields -->
                <div class="flex flex-col gap-4 px-4 py-4">
                    <div v-for="field in filters" :key="field.key" class="flex flex-col gap-1.5">
                        <label class="text-xs font-medium text-gray-500">{{ field.label }}</label>

                        <!-- Select -->
                        <div v-if="field.type === 'select'" class="relative">
                            <select :value="localValues[field.key] ?? ''"
                                @change="updateField(field.key, ($event.target as HTMLSelectElement).value || undefined)"
                                class="h-9 w-full appearance-none rounded-lg border border-gray-200 bg-gray-50 px-3 pr-8 text-sm text-gray-700 transition focus:border-[#007C95] focus:bg-white focus:ring-1 focus:ring-[#007C95] focus:outline-none">
                                <option value="">Semua</option>
                                <option v-for="opt in field.options" :key="String(opt.value)" :value="opt.value">
                                    {{ opt.label }}
                                </option>
                            </select>
                            <button v-if="localValues[field.key]" @click.stop="clearField(field.key)"
                                class="absolute top-1/2 right-2 -translate-y-1/2 rounded-full p-0.5 text-gray-400 hover:text-gray-600">
                                <X class="size-3" />
                            </button>
                        </div>

                        <!-- Text -->
                        <div v-else-if="field.type === 'text'" class="relative">
                            <input type="text" :value="(localValues[field.key] as string) ?? ''"
                                @input="updateField(field.key, ($event.target as HTMLInputElement).value || undefined)"
                                :placeholder="field.placeholder ?? `Masukkan ${field.label.toLowerCase()}...`"
                                class="h-9 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-700 placeholder-gray-400 transition focus:border-[#007C95] focus:bg-white focus:ring-1 focus:ring-[#007C95] focus:outline-none" />
                            <button v-if="localValues[field.key]" @click="clearField(field.key)"
                                class="absolute top-1/2 right-2 -translate-y-1/2 rounded-full p-0.5 text-gray-400 hover:text-gray-600">
                                <X class="size-3" />
                            </button>
                        </div>

                        <!-- Date -->
                        <div v-else-if="field.type === 'date'" class="relative">
                            <input type="date" :value="(localValues[field.key] as string) ?? ''"
                                @input="updateField(field.key, ($event.target as HTMLInputElement).value || undefined)"
                                class="h-9 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 text-sm text-gray-700 transition focus:border-[#007C95] focus:bg-white focus:ring-1 focus:ring-[#007C95] focus:outline-none" />
                            <button v-if="localValues[field.key]" @click="clearField(field.key)"
                                class="absolute top-1/2 right-8 -translate-y-1/2 rounded-full p-0.5 text-gray-400 hover:text-gray-600">
                                <X class="size-3" />
                            </button>
                        </div>

                        <!-- Checkbox -->
                        <div v-else-if="field.type === 'checkbox'" class="flex items-center gap-2">
                            <button @click="updateField(field.key, !localValues[field.key])"
                                class="flex size-5 items-center justify-center rounded border transition" :class="localValues[field.key]
                                    ? 'border-[#007C95] bg-[#007C95]'
                                    : 'border-gray-300 bg-white hover:border-gray-400'
                                    ">
                                <svg v-if="localValues[field.key]" class="size-3 text-white" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </button>
                            <span class="text-sm text-gray-700">{{ field.label }}</span>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex items-center justify-end gap-2 border-t border-gray-100 px-4 py-3">
                    <button @click="isOpen = false"
                        class="h-8 rounded-lg border border-gray-200 bg-white px-3 text-xs font-medium text-gray-600 transition hover:bg-gray-50">
                        Batal
                    </button>
                    <button @click="apply"
                        class="h-8 rounded-lg bg-[#007C95] px-4 text-xs font-medium text-white transition hover:bg-[#006b80]">
                        Terapkan
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>