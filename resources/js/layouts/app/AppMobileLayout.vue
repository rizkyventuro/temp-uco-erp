<script setup lang="ts">
import 'vue-sonner/style.css';
import { usePage } from '@inertiajs/vue3';
import { Bell, ChevronDown } from 'lucide-vue-next';
import { computed } from 'vue';
import AppBottomBar from '@/components/AppBottomBar.vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Toaster } from '@/components/ui/sonner';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { useInitials } from '@/composables/useInitials';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

// const page = usePage();
// const user = page.props.auth.user;
// const { getInitials } = useInitials();

// const showAvatar = computed(
//     () => user.avatar && user.avatar !== '',
// );
</script>

<template>
    <div class="flex min-h-screen flex-col bg-[#F5F7FA]">
        <!-- Mobile Header -->
        <header
            class="sticky top-0 z-40 flex h-14 items-center justify-between border-b border-gray-200 bg-white px-4"
        >
            <div class="flex items-center gap-2">
                <div
                    class="flex size-8 items-center justify-center rounded-[10px] bg-[#007C95]"
                >
                    <AppLogoIcon class="size-5 fill-current text-white" />
                </div>
                <span class="text-sm font-semibold">UCO Tracker</span>
            </div>

            <div class="flex items-center gap-2">
                <!-- Notification Bell -->
                <!-- <button type="button" class="relative rounded-full p-2 hover:bg-gray-100">
                    <Bell class="size-5 text-gray-600" />
                    <span class="absolute top-1.5 right-1.5 size-2.5 rounded-full bg-red-500 ring-2 ring-white" />
                </button> -->

                <!-- User Avatar Dropdown -->
                <!-- <DropdownMenu>
                    <DropdownMenuTrigger class="flex items-center rounded-full focus:outline-none">
                        <Avatar class="h-8 w-8 overflow-hidden rounded-full">
                            <AvatarImage v-if="showAvatar" :src="user.avatar!" :alt="user.name" />
                            <AvatarFallback class="rounded-full bg-teal-600 text-white text-xs">
                                {{ getInitials(user.name) }}
                            </AvatarFallback>
                        </Avatar>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent class="w-56 rounded-lg" align="end" :side-offset="8">
                        <UserMenuContent :user="user" />
                    </DropdownMenuContent>
                </DropdownMenu> -->
            </div>
        </header>

        <!-- Page Content -->
        <main class="flex-1 overflow-x-hidden pb-20">
            <slot />
        </main>

        <!-- Bottom Navigation Bar -->
        <AppBottomBar />
    </div>

    <Teleport to="body">
        <Toaster position="top-right" />
    </Teleport>
</template>
