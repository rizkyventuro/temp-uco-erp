<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Bell } from 'lucide-vue-next';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);
</script>

<template>
    <header
        class="sticky top-0 z-10 flex h-15.75 shrink-0 items-center justify-between gap-2 border-b border-sidebar-border/70 bg-white px-6 transition-[width,height] ease-linear md:px-4"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <div class="flex items-center gap-4">
            <!-- Notification Bell -->
            <button
                type="button"
                class="relative rounded-full p-2 hover:bg-gray-100"
            >
                <Bell class="size-5 text-gray-600" />
                <span
                    class="absolute top-1.5 right-1.5 size-2.5 rounded-full bg-red-500 ring-2 ring-white"
                />
            </button>
        </div>
    </header>
</template>
