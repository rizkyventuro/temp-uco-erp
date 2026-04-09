<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { PermissionEnum } from '@/enums/PermissionEnum';
import {
    Search,
    Pencil,
    Trash2,
    ChevronLeft,
    ChevronRight,
    Plus,
} from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import { Vue3Lottie } from 'vue3-lottie';
import emptyAnimation from '@/../../public/assets/animations/Pencarian Tidak Ditemukan.json';

import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { usePermission } from '@/composables/usePermission';
import type { BreadcrumbItem } from '@/types';

const { can } = usePermission();

interface Role {
    id: number;
    name: string;
}

interface User {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    profile_photo_url?: string | null;
    roles: Role[];
}

interface PaginatedUsers {
    data: User[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: { url: string | null; label: string; active: boolean }[];
}

const props = defineProps<{
    users: PaginatedUsers;
    filters: { search?: string; perPage?: number; status?: string };
    pendingCount: number;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Manajemen User', href: '/management-user' },
];

const searchQuery = ref(props.filters.search ?? '');
const perPage = ref(props.filters.perPage ?? 10);
const currentStatus = ref(props.filters.status ?? 'all');

// Delete state
const isDeleteOpen = ref(false);
const deletingUser = ref<User | null>(null);
const isDeleting = ref(false);

const buildParams = () => ({
    search: searchQuery.value,
    perPage: perPage.value,
    status: currentStatus.value !== 'all' ? currentStatus.value : undefined,
});

let searchTimeout: ReturnType<typeof setTimeout>;
watch(searchQuery, (val) => {
    clearTimeout(searchTimeout);
    if (val.length === 0 || val.length >= 3) {
        searchTimeout = setTimeout(() => {
            router.get('/management-user', buildParams(), {
                preserveState: true,
                replace: true,
            });
        }, 400);
    }
});

watch([perPage, currentStatus], () => {
    router.get('/management-user', buildParams(), {
        preserveState: true,
        replace: true,
    });
});

const goToPage = (url: string | null) => {
    if (!url) return;
    router.get(url, buildParams(), { preserveState: true });
};

const openDelete = (user: User) => {
    deletingUser.value = user;
    isDeleteOpen.value = true;
};

const confirmDelete = () => {
    if (!deletingUser.value) return;
    isDeleting.value = true;
    router.delete(`/management-user/${deletingUser.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Berhasil!', { description: 'User berhasil dihapus' });
            isDeleteOpen.value = false;
        },
        onError: () => toast.error('Gagal!', { description: 'Gagal menghapus user' }),
        onFinish: () => { isDeleting.value = false; },
    });
};

const toggleStatus = (user: User) => {
    router.put(
        `/management-user/${user.id}`,
        { name: user.name, email: user.email, is_active: !user.is_active },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => toast.success('Berhasil!', { description: 'Status user berhasil diubah' }),
            onError: () => toast.error('Gagal!', { description: 'Gagal mengubah status user' }),
        },
    );
};

const getInitials = (name: string) =>
    name.split(' ').slice(0, 2).map((w) => w[0]?.toUpperCase() ?? '').join('');
</script>

<template>
    <Head title="Manajemen User" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col items-center gap-6 p-6">
            <div class="flex w-full max-w-5xl flex-col gap-6">

                <!-- Header -->
                <div>
                    <div class="flex items-center gap-2">
                        <div class="flex h-8 w-8 items-center justify-center text-[#007C95]">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                                <circle cx="9" cy="7" r="4" />
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                            </svg>
                        </div>
                        <h1 class="text-[18px] font-bold text-gray-900">Manajemen User</h1>
                    </div>
                    <p class="mt-0.5 text-[14px] text-gray-500">Kelola pengguna sistem</p>
                </div>

                <!-- Tabs -->
                <div class="flex border-b border-gray-200">
                    <button @click="currentStatus = 'all'"
                        :class="['px-4 py-2 text-sm font-medium transition-colors', currentStatus === 'all' ? 'border-b-2 border-[#007C95] text-[#007C95]' : 'text-gray-500 hover:text-gray-700']">
                        Semua
                    </button>
                    <button @click="currentStatus = 'pending'"
                        :class="['relative px-4 py-2 text-sm font-medium transition-colors', currentStatus === 'pending' ? 'border-b-2 border-[#007C95] text-[#007C95]' : 'text-gray-500 hover:text-gray-700']">
                        Perlu Verifikasi
                        <span v-if="pendingCount > 0"
                            class="ml-1.5 inline-flex items-center justify-center rounded-full bg-red-500 px-1.5 py-0.5 text-[10px] font-bold text-white">
                            {{ pendingCount }}
                        </span>
                    </button>
                </div>

                <!-- Search + Button -->
                <div class="flex items-center gap-3">
                    <div class="relative flex-1">
                        <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400" />
                        <input v-model="searchQuery" type="text" placeholder="Cari user berdasarkan nama atau email..."
                            class="h-10 w-full rounded-lg border border-gray-200 bg-white py-2.5 pr-4 pl-10 text-sm placeholder-gray-400 focus:border-teal-400 focus:ring-2 focus:ring-teal-100 focus:outline-none" />
                    </div>
                    <Button
                        v-if="can(PermissionEnum.CREATE_USER)"
                        class="shrink-0 gap-1.5 bg-[#007C95] hover:bg-[#006b80] text-white"
                        @click="router.get('/management-user/create')"
                    >
                        <Plus class="size-4" />
                        Tambah User
                    </Button>
                </div>

                <!-- Table -->
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-100 px-5 py-4">
                        <h2 class="text-sm font-semibold text-gray-700">
                            Daftar User
                            <span class="font-normal text-gray-400">({{ users.total }})</span>
                        </h2>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 bg-gray-50/60">
                                    <th class="px-5 py-2.5 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-gray-500">Nama</th>
                                    <th class="px-5 py-2.5 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-gray-500">Email</th>
                                    <th class="px-5 py-2.5 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-gray-500">Role</th>
                                    <th class="px-5 py-2.5 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-gray-500">Status</th>
                                    <th class="px-5 py-2.5 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-gray-500">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="user in users.data" :key="user.id" class="transition hover:bg-gray-50/50">

                                    <!-- Nama -->
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <img v-if="user.profile_photo_url" :src="user.profile_photo_url"
                                                :alt="user.name"
                                                class="h-8 w-8 shrink-0 rounded-full border border-gray-200 object-cover" />
                                            <div v-else
                                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-teal-100 bg-teal-50">
                                                <span class="text-xs font-semibold text-teal-600">{{ getInitials(user.name) }}</span>
                                            </div>
                                            <span class="font-medium text-gray-900">{{ user.name }}</span>
                                        </div>
                                    </td>

                                    <!-- Email -->
                                    <td class="px-5 py-3.5 whitespace-nowrap text-gray-500">{{ user.email }}</td>

                                    <!-- Role -->
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <span v-if="user.roles.length"
                                            class="inline-flex items-center rounded-full bg-teal-50 px-2.5 py-0.5 text-xs font-medium text-teal-700 border border-teal-100">
                                            {{ user.roles[0].name }}
                                        </span>
                                        <span v-else class="text-xs italic text-gray-400">—</span>
                                    </td>

                                    <!-- Status Active toggle -->
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <button v-if="can(PermissionEnum.EDIT_USER)" @click="toggleStatus(user)"
                                            class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer items-center justify-center rounded-full border-2 border-transparent transition-colors duration-200 focus:ring-2 focus:ring-[#007C95] focus:ring-offset-2 focus:outline-none"
                                            :class="user.is_active ? 'bg-[#007C95]' : 'bg-gray-200'"
                                            role="switch" :aria-checked="user.is_active">
                                            <span class="sr-only">Toggle Active status</span>
                                            <span
                                                class="pointer-events-none absolute left-0 inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                                :class="user.is_active ? 'translate-x-4' : 'translate-x-0'" />
                                        </button>
                                    </td>

                                    <!-- Aksi -->
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <div class="flex items-center gap-1">
                                            <button
                                                v-if="can(PermissionEnum.EDIT_USER)"
                                                class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
                                                @click="router.get(`/management-user/${user.id}/edit`)"
                                            >
                                                <Pencil class="h-3.5 w-3.5" />
                                            </button>
                                            <button
                                                v-if="can(PermissionEnum.DELETE_USER)"
                                                class="rounded-lg p-1.5 text-gray-400 transition hover:bg-red-50 hover:text-red-500"
                                                @click="openDelete(user)"
                                            >
                                                <Trash2 class="h-3.5 w-3.5" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="users.data.length === 0">
                                    <td colspan="5" class="px-5 py-10 text-center">
                                        <div class="flex flex-col items-center gap-2">
                                            <Vue3Lottie :animationData="emptyAnimation" :height="160" :width="160" :loop="true" />
                                            <p class="text-sm font-medium text-gray-600">Tidak ada data User</p>
                                            <p class="text-xs text-gray-400">Coba ubah kata kunci pencarian</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Footer -->
                    <div class="flex items-center justify-between border-t border-gray-100 px-5 py-4">
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-500">Baris per halaman</span>
                            <select v-model="perPage"
                                class="h-8 rounded-lg border border-gray-200 bg-white px-2 text-xs text-gray-600 focus:ring-2 focus:ring-teal-100 focus:outline-none">
                                <option :value="10">10</option>
                                <option :value="25">25</option>
                                <option :value="50">50</option>
                                <option :value="100">100</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-xs text-gray-500">
                                {{ users.from }}–{{ users.to }} dari {{ users.total }}
                            </span>
                            <div class="flex items-center gap-1">
                                <button @click="goToPage(users.links[0]?.url ?? null)"
                                    :disabled="users.current_page === 1"
                                    class="rounded-lg border border-gray-200 p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 disabled:cursor-not-allowed disabled:opacity-40">
                                    <ChevronLeft class="h-4 w-4" />
                                </button>
                                <button @click="goToPage(users.links[users.links.length - 1]?.url ?? null)"
                                    :disabled="users.current_page === users.last_page"
                                    class="rounded-lg border border-gray-200 p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 disabled:cursor-not-allowed disabled:opacity-40">
                                    <ChevronRight class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <AlertDialog v-model:open="isDeleteOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Hapus User</AlertDialogTitle>
                    <AlertDialogDescription>
                        Apakah Anda yakin ingin menghapus user <strong>{{ deletingUser?.name }}</strong>?
                        Tindakan ini tidak dapat dibatalkan.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Batal</AlertDialogCancel>
                    <AlertDialogAction
                        class="bg-red-600 hover:bg-red-700"
                        :disabled="isDeleting"
                        @click="confirmDelete"
                    >
                        {{ isDeleting ? 'Menghapus...' : 'Ya, Hapus' }}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

    </AppLayout>
</template>
