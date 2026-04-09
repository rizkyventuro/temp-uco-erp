<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import AppMobileLayout from '@/layouts/app/AppMobileLayout.vue';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const MOBILE_BREAKPOINT = 768;
const isMobile = ref(false);

function checkMobile() {
    isMobile.value = window.innerWidth < MOBILE_BREAKPOINT;
}

onMounted(() => {
    checkMobile();
    window.addEventListener('resize', checkMobile);
});

onUnmounted(() => {
    window.removeEventListener('resize', checkMobile);
});
</script>

<template>
    <AppMobileLayout
        v-if="isMobile"
        :breadcrumbs="breadcrumbs"
    >
        <slot />
    </AppMobileLayout>
    <AppSidebarLayout
        v-else
        :breadcrumbs="breadcrumbs"
    >
        <slot />
    </AppSidebarLayout>
</template>
