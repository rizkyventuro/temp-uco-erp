<script setup lang="ts">
import { computed } from 'vue';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';

interface PaginatorLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Paginator {
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    links: PaginatorLink[];
}

// ── Types ──────────────────────────────────────────────
export type PaginationType = 'simplePaginate' | 'centerPaginate';

// ── Props & Emits ──────────────────────────────────────
const props = withDefaults(defineProps<{
    paginator: Paginator;
    type?: PaginationType;
}>(), {
    type: 'simplePaginate',
});

const emit = defineEmits<{
    navigate: [url: string];
}>();

// ── Helpers ────────────────────────────────────────────
const goToUrl = (url: string | null | undefined) => {
    if (url) emit('navigate', url);
};

const prevUrl = computed(() => props.paginator.links[0]?.url ?? null);
const nextUrl = computed(() => props.paginator.links[props.paginator.links.length - 1]?.url ?? null);

const pages = computed(() => {
    const current = props.paginator.current_page;
    const last = props.paginator.last_page;
    const result: (number | '...')[] = [];

    if (last <= 7) {
        for (let i = 1; i <= last; i++) result.push(i);
    } else {
        result.push(1);
        if (current > 3) result.push('...');
        const start = Math.max(2, current - 1);
        const end = Math.min(last - 1, current + 1);
        for (let i = start; i <= end; i++) result.push(i);
        if (current < last - 2) result.push('...');
        result.push(last);
    }
    return result;
});

const goToPage = (page: number) => {
    const link = props.paginator.links.find((l) => l.label === String(page));
    if (link?.url) emit('navigate', link.url);
};
</script>

<template>
    <!-- ── simplePaginate (default) ── -->
    <template v-if="type === 'simplePaginate'">
        <div class=" items-center justify-between px-4 py-3 sm:flex">
            <p class="text-sm text-gray-500">
                Menampilkan {{ paginator.from ?? 0 }} hingga {{ paginator.to ?? 0 }} dari {{ paginator.total }} entri
            </p>

            <div class="flex items-center gap-1">
                <button :disabled="paginator.current_page === 1"
                    class="bg-white border cursor-pointer flex size-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 disabled:cursor-not-allowed disabled:opacity-40"
                    @click="goToUrl(prevUrl)">
                    <ChevronLeft class="size-4" />
                </button>

                <template v-for="(page, idx) in pages" :key="idx">
                    <span v-if="page === '...'" class="px-1 text-sm text-gray-400">...</span>
                    <button v-else
                        class="cursor-pointer flex size-8 items-center justify-center rounded-lg text-sm font-medium transition"
                        :class="page === paginator.current_page
                            ? 'bg-[#007C95] text-white shadow-sm'
                            : 'bg-white border text-gray-600 hover:bg-gray-100'" @click="goToPage(page)">
                        {{ page }}
                    </button>
                </template>

                <button :disabled="paginator.current_page === paginator.last_page"
                    class="bg-white border cursor-pointer flex size-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 disabled:cursor-not-allowed disabled:opacity-40"
                    @click="goToUrl(nextUrl)">
                    <ChevronRight class="size-4" />
                </button>
            </div>
        </div>
    </template>

    <!-- ── centerPaginate ── -->
    <template v-else-if="type === 'centerPaginate'">
        <div class=" items-center justify-center px-4 py-3 sm:flex">

            <div class="flex items-center gap-1.5">
                <button :disabled="paginator.current_page === 1"
                    class="flex size-9 cursor-pointer items-center justify-center rounded-xl  text-gray-400 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-600 disabled:cursor-not-allowed disabled:opacity-40"
                    @click="goToUrl(prevUrl)">
                    <ChevronLeft class="size-4" />
                </button>

                <template v-for="(page, idx) in pages" :key="idx">
                    <span v-if="page === '...'"
                        class="flex size-9 items-center justify-center text-sm text-gray-400">…</span>
                    <button v-else
                        class="flex size-9 cursor-pointer items-center justify-center rounded-xl border text-sm font-medium transition"
                        :class="page === paginator.current_page
                            ? 'border-[#007C95] bg-[#007C95] text-white shadow-sm'
                            : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300 hover:bg-gray-50'"
                        @click="goToPage(page)">
                        {{ page }}
                    </button>
                </template>

                <button :disabled="paginator.current_page === paginator.last_page"
                    class="flex size-9 cursor-pointer items-center justify-center rounded-xl  text-gray-400 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-600 disabled:cursor-not-allowed disabled:opacity-40"
                    @click="goToUrl(nextUrl)">
                    <ChevronRight class="size-4" />
                </button>
            </div>
        </div>

    </template>
</template>