<script setup lang="ts">
import { Clock, CircleCheck, CircleX } from 'lucide-vue-next';
import { computed } from 'vue';

type TransferStatus = 'Pending' | 'Completed' | 'Cancelled';

const props = defineProps<{
    status: TransferStatus;
}>();

const colorClass = computed(() => {
    const map: Record<string, string> = {
        'Pending': 'bg-yellow-100 text-yellow-600',
        'Completed': 'bg-green-100 text-green-600',
        'Cancelled': 'bg-red-100 text-red-600',
    };

    return map[props.status] ?? 'bg-gray-100 text-gray-600';
});

const icon = computed(() => {
    const map: Record<string, any> = {
        'Pending': Clock,
        'Completed': CircleCheck,
        'Cancelled': CircleX,
    };

    return map[props.status] ?? Clock;
});
</script>

<template>
    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium" :class="colorClass">
        <component :is="icon" class="h-3 w-3" />
        {{ status }}
    </span>
</template>
