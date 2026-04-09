<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <img
        src="/assets/images/logo-mark.png"
        alt="Logo"
        :class="className"
        v-bind="$attrs"
    />
</template>
