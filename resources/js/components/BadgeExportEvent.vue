<script setup lang="ts">
import { Package, ArrowRightLeft, ShoppingCart } from 'lucide-vue-next';
import { computed } from 'vue';

type EventType = 'Collection' | 'Transfer' | 'Export';

const props = defineProps<{
    type: EventType;
}>();

const colorClass = computed(() => {
    const map: Record<string, string> = {
        'Collection': 'bg-green-100 text-green-600',
        'Transfer': 'bg-blue-100 text-blue-600',
        'Export': 'bg-purple-100 text-purple-600',
    };

    return map[props.type] ?? 'bg-gray-100 text-gray-600';
});

const icon = computed(() => {
    const map: Record<string, any> = {
        'Collection': Package,
        'Transfer': ArrowRightLeft,
        'Export': ShoppingCart,
    };

    return map[props.type] ?? Package;
});
</script>

<template>
    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium" :class="colorClass">
        <component :is="icon" class="h-3 w-3" />
        {{ type }}
    </span>
</template>
