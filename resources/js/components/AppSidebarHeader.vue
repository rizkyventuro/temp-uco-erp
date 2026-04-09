<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Bell, Mail, ChevronDown, ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useSidebar } from '@/components/ui/sidebar';
import { getInitials } from '@/composables/useInitials';
import UserMenuContent from '@/components/UserMenuContent.vue';
import type { BreadcrumbItem, User } from '@/types';

withDefaults(
    defineProps<{ breadcrumbs?: BreadcrumbItem[] }>(),
    { breadcrumbs: () => [] },
);

const page = usePage();
const auth = computed(() => page.props.auth as { user: User });

const { state, toggleSidebar } = useSidebar();

const currentPeriod = ref('April 2026');

const periods = [
    'Januari 2026', 'Februari 2026', 'Maret 2026',
    'April 2026', 'Mei 2026', 'Juni 2026',
    'Juli 2026', 'Agustus 2026', 'September 2026',
    'Oktober 2026', 'November 2026', 'Desember 2026',
];
</script>

<template>
    <header class="sticky top-0 z-10 flex h-[75px] shrink-0 items-center justify-end border-b border-gray-100 bg-white px-5 gap-3 shadow-sm">

        <!-- Periode selector — styled like a bordered input -->
        <div class="flex items-center">
            <Select v-model="currentPeriod">
                <SelectTrigger
                    class="!h-[45px] min-w-[11rem] border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 shadow-none rounded-md focus:ring-0 focus:border-gray-300 hover:border-gray-300 transition-colors"
                >
                    <span class="text-gray-500 mr-1">Periode :</span>
                    <SelectValue class="text-gray-800 font-semibold" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem v-for="p in periods" :key="p" :value="p">
                        {{ p }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>


        <!-- Bell -->
        <button
            type="button"
            class="relative border rounded-lg p-1.5 text-gray-600 hover:text-gray-900 transition-colors"
        >
            <Bell class="size-5" />
            <span class="absolute top-1 right-1 size-2 rounded-full bg-red-500 ring-2 ring-white" />
        </button>

        <!-- Mail -->
        <button
            type="button"
            class="border rounded-lg p-1.5 text-gray-600 hover:text-gray-900 transition-colors"
        >
            <Mail class="size-5" />
        </button>


        <!-- User dropdown -->
        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <button
                    type="button"
                    class="flex items-center gap-2.5 rounded-lg px-1.5 py-1 transition-colors hover:bg-gray-50"
                >
                    <!-- Avatar with online dot -->
                    <div class="relative shrink-0">
                        <Avatar class="size-8 overflow-hidden rounded-full">
                            <AvatarImage
                                v-if="auth.user.avatar"
                                :src="auth.user.avatar"
                                :alt="auth.user.name"
                            />
                            <AvatarFallback class="rounded-full bg-[#007C95]/10 text-xs font-semibold text-[#007C95]">
                                {{ getInitials(auth.user?.name) }}
                            </AvatarFallback>
                        </Avatar>
                        <!-- Online indicator — bottom-left green dot -->
                        <span
                            class="absolute bottom-0 left-0 size-2.5 rounded-full bg-emerald-400 ring-2 ring-white"
                        />
                    </div>

                    <!-- Name + company -->
                    <div class="hidden flex-col items-start sm:flex">
                        <span class="text-sm font-semibold leading-tight text-gray-900">
                            {{ auth.user.name }}
                        </span>
                        <span class="text-xs leading-tight text-gray-400">
                            {{ (auth.user as any).company_name ?? auth.user.email }}
                        </span>
                    </div>

                    <ChevronDown class="size-4 text-gray-400 ml-0.5" />
                </button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="w-56">
                <UserMenuContent :user="auth.user" />
            </DropdownMenuContent>
        </DropdownMenu>
    </header>

    <!-- Sidebar toggle button -->
    <Teleport to="body">
        <button
            type="button"
            :class="[
                'fixed top-[1.125rem] z-50 flex size-7 items-center justify-center rounded-full border border-gray-200 bg-white shadow-md text-gray-600 -translate-x-1/2 transition-[left] duration-200 ease-linear hover:bg-[#EBFFFA] hover:text-[#007C95] hover:border-[#007C95]',
                state === 'expanded' ? 'left-[16rem]' : 'left-[calc(3rem+1.125rem)]',
            ]"
            @click="toggleSidebar"
        >
            <ChevronLeft v-if="state === 'expanded'" class="size-3.5" />
            <ChevronRight v-else class="size-3.5" />
        </button>
    </Teleport>
</template>