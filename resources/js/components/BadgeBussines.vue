<script setup lang="ts">
import { UtensilsCrossed, Store, Home } from 'lucide-vue-next';
import { computed } from 'vue';

type PooType = 'Restoran' | 'UMKM' | 'Rumah Tangga';

type Props = {
    type: PooType;
};

const props = defineProps<Props>();

const colorClass = computed(() => {
    const map = {
        Restoran: 'bg-orange-100 text-orange-600',
        UMKM: 'bg-blue-100 text-blue-600',
        'Rumah Tangga': 'bg-green-100 text-green-600',
    };

    return map[props.type] ?? 'bg-gray-100 text-gray-600';
});

const icon = computed(() => {
    const map = {
        Restoran: UtensilsCrossed,
        UMKM: Store,
        'Rumah Tangga': Home,
    };

    return map[props.type] ?? Store;
});
</script>

<template>
    <span
        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
        :class="colorClass"
    >
        <component
            :is="icon"
            class="h-3 w-3"
        />
        {{ type }}
    </span>
</template>
