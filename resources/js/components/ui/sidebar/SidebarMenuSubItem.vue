<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"
import iconNode from '@/components/icons/iconNode.vue';

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <li data-slot="sidebar-menu-sub-item" data-sidebar="menu-sub-item"
    :class="cn('group/menu-sub-item relative flex items-center', props.class)">
    <iconNode />
    <slot />
  </li>
</template>
