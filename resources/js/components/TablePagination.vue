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

const props = defineProps<{
    paginator: Paginator;
}>();

const emit = defineEmits<{
    navigate: [url: string];
}>();

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
    <!-- Desktop (sm+): info text + numbered pagination -->
    <div class="hidden items-center justify-between px-4 py-3 sm:flex">
        <p class="text-sm text-gray-500">
            Menampilkan {{ paginator.from ?? 0 }} hingga {{ paginator.to ?? 0 }} dari {{ paginator.total }} entri
        </p>

        <div class="flex items-center gap-1">
            <!-- Prev -->
            <button
                :disabled="paginator.current_page === 1"
                class="bg-white border cursor-pointer flex size-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 disabled:cursor-not-allowed disabled:opacity-40"
                @click="goToUrl(prevUrl)"
            >
                <ChevronLeft class="size-4" />
            </button>

            <!-- Numbered pages -->
            <template v-for="(page, idx) in pages" :key="idx">
                <span v-if="page === '...'" class="px-1 text-sm text-gray-400">...</span>
                <button
                    v-else
                    class="cursor-pointer flex size-8 items-center justify-center rounded-lg text-sm font-medium transition"
                    :class="page === paginator.current_page
                        ? 'bg-[#007C95] text-white shadow-sm'
                        : 'bg-white border text-gray-600 hover:bg-gray-100'"
                    @click="goToPage(page)"
                >
                    {{ page }}
                </button>
            </template>

            <!-- Next -->
            <button
                :disabled="paginator.current_page === paginator.last_page"
                class="bg-white border cursor-pointer flex size-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 disabled:cursor-not-allowed disabled:opacity-40"
                @click="goToUrl(nextUrl)"
            >
                <ChevronRight class="size-4" />
            </button>
        </div>
    </div>

    <!-- Mobile (< sm): prev / current/total / next -->
    <div v-if="paginator.last_page > 1" class="flex items-center justify-between px-4 py-3 sm:hidden">
        <button
            :disabled="paginator.current_page === 1"
            class="flex items-center gap-1 rounded-lg px-3 py-1.5 text-sm font-medium text-gray-600 transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40"
            @click="goToUrl(prevUrl)"
        >
            <ChevronLeft class="size-4" />
            Sebelumnya
        </button>

        <span class="text-sm text-gray-500">
            {{ paginator.current_page }} / {{ paginator.last_page }}
        </span>

        <button
            :disabled="paginator.current_page === paginator.last_page"
            class="flex items-center gap-1 rounded-lg px-3 py-1.5 text-sm font-medium text-gray-600 transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40"
            @click="goToUrl(nextUrl)"
        >
            Selanjutnya
            <ChevronRight class="size-4" />
        </button>
    </div>
</template>
