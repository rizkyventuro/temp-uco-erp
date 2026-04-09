<script setup lang="ts">
import { MapPin } from 'lucide-vue-next';
import { computed, type Component } from 'vue';
import IconPengepul from '@/components/icons/timeline/IconPengepul.vue';
import IconPoo from '@/components/icons/timeline/IconPoo.vue';

interface Ownership {
    id: number;
    role: string;
    name: string;
    company: string;
    location: string;
    volume: number;
    owned_at: string;
    is_current: boolean;
}

const props = defineProps<{
    ownerships: Ownership[];
    formatDate: (d: string) => string;
}>();

const roleLabel: Record<string, string> = {
    poo: 'POO',
    pengepul: 'PENGEPUL',
};

const roleColor: Record<string, string> = {
    poo: 'bg-green-100 text-green-600',
    pengepul: 'bg-blue-100 text-blue-600',
};

const roleBorderColor: Record<string, string> = {
    poo: 'border-green-500 text-green-600',
    pengepul: 'border-blue-500 text-blue-600',
};

const roleIcon: Record<string, Component> = {
    poo: IconPoo,
    pengepul: IconPengepul,
};
</script>

<template>
    <div class="space-y-6">
        <div
            v-for="(ownership, index) in ownerships"
            :key="ownership.id"
            class="relative flex gap-4"
        >
            <!-- Timeline -->
            <div class="relative flex flex-col items-center">
                <!-- line -->
                <div
                    v-if="index < ownerships.length - 1"
                    class="absolute top-10 h-full w-px bg-gray-300"
                ></div>

                <!-- icon -->
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-full border-2 bg-white"
                    :class="
                        roleBorderColor[ownership.role] ??
                        'border-gray-400 text-gray-500'
                    "
                >
                    <component
                        :is="roleIcon[ownership.role]"
                        class="h-4 w-4"
                    />
                </div>
            </div>

            <!-- Content -->
            <div class="flex-1">
                <!-- role badge -->
                <span
                    class="mb-1 inline-block rounded-md px-2 py-0.5 text-[11px] font-semibold"
                    :class="
                        roleColor[ownership.role] ?? 'bg-gray-100 text-gray-600'
                    "
                >
                    {{ roleLabel[ownership.role] ?? ownership.role }}
                </span>

                <p class="text-sm font-semibold text-gray-900">
                    {{ ownership.name }}
                </p>

                <div
                    class="mt-0.5 flex items-center gap-1 text-xs text-gray-500"
                >
                    <MapPin class="h-3 w-3" />
                    {{ ownership.location }}
                </div>

                <p
                    v-if="ownership.is_current"
                    class="mt-2 flex items-center gap-1 text-xs font-semibold text-green-600"
                >
                    &#10003; Pemilik saat ini
                </p>
            </div>

            <!-- Right info -->
            <div class="text-right">
                <p class="text-xs text-gray-400">
                    {{ formatDate(ownership.owned_at) }}
                </p>

                <p class="text-sm font-semibold text-teal-600">
                    {{ ownership.volume }} L
                </p>
            </div>
        </div>
    </div>
</template>
