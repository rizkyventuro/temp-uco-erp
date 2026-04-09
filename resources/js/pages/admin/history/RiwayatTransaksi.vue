<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import {
    History,
    ArrowUpRight,
    ArrowDownLeft,
    Droplets,
    SlidersHorizontal,
    X,
    Loader2,
} from 'lucide-vue-next';
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { Vue3Lottie } from 'vue3-lottie';
import emptyAnimation from '@/../../public/assets/animations/Pencarian Tidak Ditemukan.json';

import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface HistoryItem {
    id: number;
    transaction_code: string;
    volume: number;
    type: number;
    type_label: string;
    counterpart_name: string | null;
    created_at: string;
    created_at_label: string;
}

interface GroupedHistory {
    label: string;
    items: HistoryItem[];
}

interface Summary {
    total_collection: number;
    total_transfer_out: number;
    total_transfer_in: number;
}

interface Filters {
    type: string | null;
    month: string | null;
}

const props = defineProps<{
    grouped: GroupedHistory[];
    summary: Summary;
    filters: Filters;
    nextPageUrl: string | null;
    hasMore: boolean;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Riwayat Transaksi', href: '/history' },
];

// ── Infinite scroll state ─────────────────────────────────────────
const localGrouped = ref<GroupedHistory[]>([...props.grouped]);
const nextPageUrl = ref<string | null>(props.nextPageUrl);
const isLoading = ref(false);
const sentinelEl = ref<HTMLElement | null>(null);
let observer: IntersectionObserver | null = null;

// Merge grup baru ke localGrouped (gabung item dengan label yang sama)
const mergeGroups = (incoming: GroupedHistory[]) => {
    incoming.forEach((newGroup) => {
        const existing = localGrouped.value.find(
            (g) => g.label === newGroup.label,
        );
        if (existing) {
            // Hindari duplikat
            const existingIds = new Set(existing.items.map((i) => i.id));
            newGroup.items.forEach((item) => {
                if (!existingIds.has(item.id)) existing.items.push(item);
            });
        } else {
            localGrouped.value.push(newGroup);
        }
    });
};

const loadMore = async () => {
    if (!nextPageUrl.value || isLoading.value) return;
    isLoading.value = true;
    try {
        const res = await axios.get(nextPageUrl.value, {
            headers: {
                'X-Inertia': true,
                'X-Inertia-Partial-Data': 'grouped,nextPageUrl,hasMore',
                'X-Inertia-Partial-Component': 'history/ListHistory',
            },
        });
        const data = res.data?.props ?? res.data;
        if (data?.grouped) mergeGroups(data.grouped);
        nextPageUrl.value = data?.nextPageUrl ?? null;
    } catch (e) {
        console.error(e);
    } finally {
        isLoading.value = false;
    }
};

onMounted(() => {
    observer = new IntersectionObserver(
        (entries) => {
            if (entries[0].isIntersecting) loadMore();
        },
        { threshold: 0.1 },
    );
    if (sentinelEl.value) observer.observe(sentinelEl.value);
});

onBeforeUnmount(() => observer?.disconnect());

// Reset list saat filter berubah dari server (navigasi baru)
const watchKey = computed(() => JSON.stringify(props.filters));
let prevKey = watchKey.value;
const checkReset = () => {
    if (watchKey.value !== prevKey) {
        prevKey = watchKey.value;
        localGrouped.value = [...props.grouped];
        nextPageUrl.value = props.nextPageUrl;
    }
};

// ── Filter ────────────────────────────────────────────────────────
const showFilter = ref(false);
const selectedType = ref<string>(props.filters.type ?? '');
const selectedMonth = ref<string>(props.filters.month ?? '');

const TYPE_OPTIONS = [
    { value: '', label: 'Semua' },
    { value: '1', label: 'Pengambilan' },
    { value: '2', label: 'Transfer Keluar' },
    { value: '3', label: 'Transfer Masuk' },
];

function applyFilter() {
    router.get(
        '/history',
        {
            type: selectedType.value || undefined,
            month: selectedMonth.value || undefined,
        },
        {
            preserveState: false,
            replace: true,
            onSuccess: () => {
                localGrouped.value = [...props.grouped];
                nextPageUrl.value = props.nextPageUrl;
            },
        },
    );
    showFilter.value = false;
}

function resetFilter() {
    selectedType.value = '';
    selectedMonth.value = '';
    router.get(
        '/history',
        {},
        {
            preserveState: false,
            replace: true,
            onSuccess: () => {
                localGrouped.value = [...props.grouped];
                nextPageUrl.value = props.nextPageUrl;
            },
        },
    );
    showFilter.value = false;
}

const hasActiveFilter = computed(
    () => !!props.filters.type || !!props.filters.month,
);

// ── Helpers ───────────────────────────────────────────────────────
function typeIcon(type: number) {
    if (type === 1) return Droplets;
    if (type === 2) return ArrowUpRight;
    return ArrowDownLeft;
}
function typeBg(type: number) {
    if (type === 1) return 'bg-blue-50 text-blue-500';
    if (type === 2) return 'bg-red-50 text-red-500';
    return 'bg-green-50 text-green-500';
}
function typeColor(type: number) {
    if (type === 1) return 'text-blue-600';
    if (type === 2) return 'text-red-500';
    return 'text-green-600';
}
function typeSign(type: number) {
    return type === 2 ? '−' : '+';
}
function fmt(n: number) {
    return n.toLocaleString('id-ID', {
        minimumFractionDigits: 1,
        maximumFractionDigits: 2,
    });
}
</script>

<template>
    <Head title="Riwayat Transaksi" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col items-center">
            <div class="flex h-full w-full max-w-4xl flex-col">
                <!-- Header text -->
                <div class="shrink-0 px-6 pt-6 pb-3">
                    <h1 class="text-[18px] font-bold text-gray-900">
                        Riwayat Transaksi
                    </h1>
                    <p class="mt-0.5 text-[14px] text-gray-500">
                        Riwayat transaksi pengambilan, transfer, dan penjualan
                        UCO
                    </p>
                </div>

                <!-- Summary card (sticky, tidak ikut scroll) -->
                <div
                    class="mx-6 mb-0 shrink-0 rounded-2xl bg-[#007C95] px-5 pt-5 pb-5"
                >
                    <div class="mb-4 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <History class="h-5 w-5 text-white/80" />
                            <span class="text-base font-semibold text-white"
                                >Riwayat Transaksi</span
                            >
                        </div>
                        <button
                            @click="showFilter = true"
                            class="relative flex items-center gap-1.5 rounded-full bg-white/20 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-white/30"
                        >
                            <SlidersHorizontal class="h-3.5 w-3.5" />
                            Filter
                            <span
                                v-if="hasActiveFilter"
                                class="absolute -top-1 -right-1 h-2 w-2 rounded-full bg-yellow-400"
                            />
                        </button>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <div
                            class="flex flex-col items-center rounded-2xl bg-white/15 px-2 py-3 backdrop-blur-sm"
                        >
                            <Droplets class="mb-1 h-4 w-4 text-blue-200" />
                            <span
                                class="text-sm leading-tight font-bold text-white"
                                >{{ fmt(summary.total_collection) }}</span
                            >
                            <span
                                class="mt-0.5 text-center text-[10px] text-white/60"
                                >Pengambilan (L)</span
                            >
                        </div>
                        <div
                            class="flex flex-col items-center rounded-2xl bg-white/15 px-2 py-3 backdrop-blur-sm"
                        >
                            <ArrowUpRight class="mb-1 h-4 w-4 text-red-200" />
                            <span
                                class="text-sm leading-tight font-bold text-white"
                                >{{ fmt(summary.total_transfer_out) }}</span
                            >
                            <span
                                class="mt-0.5 text-center text-[10px] text-white/60"
                                >Keluar (L)</span
                            >
                        </div>
                        <div
                            class="flex flex-col items-center rounded-2xl bg-white/15 px-2 py-3 backdrop-blur-sm"
                        >
                            <ArrowDownLeft
                                class="mb-1 h-4 w-4 text-green-200"
                            />
                            <span
                                class="text-sm leading-tight font-bold text-white"
                                >{{ fmt(summary.total_transfer_in) }}</span
                            >
                            <span
                                class="mt-0.5 text-center text-[10px] text-white/60"
                                >Masuk (L)</span
                            >
                        </div>
                    </div>
                </div>

                <!-- ── Scrollable list area ────────────────────────────── -->
                <div class="min-h-0 flex-1 overflow-y-auto px-6 pt-4 pb-8">
                    <!-- Active filter chips -->
                    <div
                        v-if="hasActiveFilter"
                        class="mb-4 flex flex-wrap gap-2"
                    >
                        <span
                            v-if="filters.type"
                            class="inline-flex items-center gap-1 rounded-full bg-[#007C95]/10 px-3 py-1 text-xs font-medium text-[#007C95]"
                        >
                            {{
                                TYPE_OPTIONS.find(
                                    (t) => t.value === filters.type,
                                )?.label
                            }}
                            <button @click="resetFilter">
                                <X class="h-3 w-3" />
                            </button>
                        </span>
                        <span
                            v-if="filters.month"
                            class="inline-flex items-center gap-1 rounded-full bg-[#007C95]/10 px-3 py-1 text-xs font-medium text-[#007C95]"
                        >
                            {{ filters.month }}
                            <button @click="resetFilter">
                                <X class="h-3 w-3" />
                            </button>
                        </span>
                    </div>

                    <!-- Empty state -->
                    <div
                        v-if="localGrouped.length === 0"
                        class="flex flex-col items-center justify-center py-20 text-center"
                    >
                        <Vue3Lottie
                            :animationData="emptyAnimation"
                            :height="160"
                            :width="160"
                            :loop="true"
                        />
                        <p class="text-sm font-semibold text-gray-500">
                            Belum ada transaksi
                        </p>
                        <p class="mt-1 text-xs text-gray-400">
                            Riwayat pengambilan dan transfer akan muncul di sini
                        </p>
                    </div>

                    <!-- Grouped list -->
                    <div
                        v-for="group in localGrouped"
                        :key="group.label"
                        class="mb-5"
                    >
                        <div class="mb-2 px-1">
                            <span
                                class="text-[11px] font-semibold tracking-wider text-gray-400 uppercase"
                            >
                                {{ group.label }}
                            </span>
                        </div>
                        <div
                            class="divide-y divide-gray-50 overflow-hidden rounded-2xl bg-white shadow-sm"
                        >
                            <div
                                v-for="item in group.items"
                                :key="item.id"
                                class="flex items-center gap-3 px-4 py-3.5 transition hover:bg-gray-50"
                            >
                                <div
                                    :class="[
                                        'flex h-10 w-10 shrink-0 items-center justify-center rounded-full',
                                        typeBg(item.type),
                                    ]"
                                >
                                    <component
                                        :is="typeIcon(item.type)"
                                        class="h-4.5 w-4.5"
                                    />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p
                                        class="truncate text-sm font-semibold text-gray-800"
                                    >
                                        {{ item.type_label }}
                                    </p>
                                    <p
                                        class="mt-0.5 truncate text-xs text-gray-400"
                                    >
                                        <template v-if="item.counterpart_name">
                                            {{
                                                item.type === 2 ? 'Ke' : 'Dari'
                                            }}
                                            {{ item.counterpart_name }} ·
                                        </template>
                                        {{ item.transaction_code }}
                                    </p>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p
                                        :class="[
                                            'text-sm font-bold',
                                            typeColor(item.type),
                                        ]"
                                    >
                                        {{ typeSign(item.type)
                                        }}{{ fmt(item.volume) }} L
                                    </p>
                                    <p class="mt-0.5 text-[10px] text-gray-400">
                                        {{
                                            new Date(
                                                item.created_at,
                                            ).toLocaleTimeString('id-ID', {
                                                hour: '2-digit',
                                                minute: '2-digit',
                                            })
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sentinel + loader -->
                    <div
                        ref="sentinelEl"
                        class="flex justify-center py-4"
                    >
                        <div
                            v-if="isLoading"
                            class="flex items-center gap-2 text-xs text-gray-400"
                        >
                            <Loader2 class="h-4 w-4 animate-spin" />
                            Memuat lebih banyak...
                        </div>
                        <p
                            v-else-if="!nextPageUrl && localGrouped.length > 0"
                            class="text-xs text-gray-400"
                        >
                            Semua transaksi sudah ditampilkan
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Filter Bottom Sheet ─────────────────────────────────── -->
        <Teleport to="body">
            <Transition name="sheet">
                <div
                    v-if="showFilter"
                    class="fixed inset-0 z-50 flex flex-col justify-end"
                >
                    <div
                        class="absolute inset-0 bg-black/40 backdrop-blur-sm"
                        @click="showFilter = false"
                    />
                    <div
                        class="relative rounded-t-3xl bg-white px-5 pt-5 pb-10 shadow-2xl"
                    >
                        <div class="mb-5 flex items-center justify-between">
                            <h3 class="text-base font-bold text-gray-800">
                                Filter Transaksi
                            </h3>
                            <button
                                @click="showFilter = false"
                                class="flex h-7 w-7 items-center justify-center rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>
                        <div class="mb-5">
                            <p
                                class="mb-2 text-xs font-semibold tracking-wide text-gray-500 uppercase"
                            >
                                Jenis Transaksi
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="opt in TYPE_OPTIONS"
                                    :key="opt.value"
                                    @click="selectedType = opt.value"
                                    :class="[
                                        'rounded-full border px-4 py-2 text-sm font-medium transition',
                                        selectedType === opt.value
                                            ? 'border-[#007C95] bg-[#007C95] text-white'
                                            : 'border-gray-200 bg-white text-gray-600 hover:border-[#007C95]',
                                    ]"
                                >
                                    {{ opt.label }}
                                </button>
                            </div>
                        </div>
                        <div class="mb-6">
                            <p
                                class="mb-2 text-xs font-semibold tracking-wide text-gray-500 uppercase"
                            >
                                Bulan
                            </p>
                            <input
                                type="month"
                                v-model="selectedMonth"
                                class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm text-gray-700 focus:border-[#007C95] focus:ring-2 focus:ring-[#007C95]/20 focus:outline-none"
                            />
                        </div>
                        <div class="flex gap-3">
                            <button
                                @click="resetFilter"
                                class="flex-1 rounded-xl border border-gray-200 py-3 text-sm font-semibold text-gray-600 transition hover:bg-gray-50"
                            >
                                Reset
                            </button>
                            <button
                                @click="applyFilter"
                                class="flex-1 rounded-xl bg-[#007C95] py-3 text-sm font-semibold text-white transition hover:bg-[#006880]"
                            >
                                Terapkan
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>

<style scoped>
.sheet-enter-active,
.sheet-leave-active {
    transition: opacity 0.25s ease;
}

.sheet-enter-from,
.sheet-leave-to {
    opacity: 0;
}

.sheet-enter-from .relative,
.sheet-leave-to .relative {
    transform: translateY(100%);
}

.sheet-enter-active .relative,
.sheet-leave-active .relative {
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
</style>
