<script setup lang="ts">
import {
    CircleCheck,
    Truck,
    ArrowRightLeft,
    Lock,
    Clock,
} from 'lucide-vue-next';
import { computed } from 'vue';

type BatchStatus =
    | 'Aktif'
    | 'In Transfer'
    | 'Transferred'
    | 'Final Export'
    | 'Expired';

const props = defineProps<{
    status: BatchStatus;
}>();

const colorClass = computed(() => {
    const map: Record<string, string> = {
        Aktif: 'bg-green-100 text-green-600',
        'In Transfer': 'bg-yellow-100 text-yellow-600',
        Transferred: 'bg-blue-100 text-blue-600',
        'Final Export': 'bg-purple-100 text-purple-600',
        Expired: 'bg-red-100 text-red-600',
    };

    return map[props.status] ?? 'bg-gray-100 text-gray-600';
});

const icon = computed(() => {
    const map: Record<string, any> = {
        'Aktif': CircleCheck,
        'In Transfer': Truck,
        'Transferred': ArrowRightLeft,
        'Final Export': Lock,
        'Expired': Clock,
    };

    return map[props.status] ?? CircleCheck;
});
</script>

<template>
    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium" :class="colorClass">
        <component :is="icon" class="h-3 w-3" />
        {{ status }}
    </span>
</template>
