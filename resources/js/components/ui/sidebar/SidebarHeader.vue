<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <div
    data-slot="sidebar-header"
    data-sidebar="header"
    :class="cn('flex flex-col gap-2 mb-4  border-b border-sidebar-border/50 h-[calc(75px-8px)]', props.class)"
  >
    <slot />
  </div>
</template>
