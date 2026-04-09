<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    Search,
    ChevronLeft,
    ChevronRight,
    CheckCircle,
} from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { ProfileController } from '@/actions/Settings/ProfileController';

interface User {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    is_verified_by_admin: boolean;
    profile_completed_at: string | null;
    profile_photo_url?: string | null;
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
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Daftar User', href: '/admin/user' },
];

const searchQuery = ref(props.filters.search ?? '');
const perPage = ref(props.filters.perPage ?? 10);
const currentStatus = ref(props.filters.status ?? 'all');

let searchTimeout: ReturnType<typeof setTimeout>;
const updateFilters = () => {
    router.get(
        route('management-user.index'),
        { 
            search: searchQuery.value, 
            perPage: perPage.value,
            status: currentStatus.value === 'pending' ? 'pending' : undefined
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

watch(searchQuery, (val) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(updateFilters, 400);
});

watch([perPage, currentStatus], updateFilters);

const goToPage = (url: string | null) => {
    if (!url) return;
    router.get(
        url,
        { 
            search: searchQuery.value, 
            perPage: perPage.value,
            status: currentStatus.value === 'pending' ? 'pending' : undefined
        },
        {
            preserveState: true,
        },
    );
};

const verifyUser = (user: User) => {
    router.patch(
        route('management-user.verify', { user: user.id }),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Berhasil!', {
                    description: `User ${user.name} telah diverifikasi.`,
                });
            },
            onError: () => {
                toast.error('Gagal!', {
                    description: 'Gagal memverifikasi user.',
                });
            },
        },
    );
};

const getInitials = (name: string) => {
    return name
        .split(' ')
        .slice(0, 2)
        .map((w) => w[0]?.toUpperCase() ?? '')
        .join('');
};
</script>

<template>
    <Head title="Manajemen User" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col items-center gap-6 p-6 font-sans">
            <div class="flex w-full max-w-5xl flex-col gap-6">
                <!-- Header -->
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Manajemen User</h1>
                    <p class="text-sm text-gray-500">Kelola dan verifikasi pengguna sistem</p>
                </div>

                <!-- Tabs -->
                <div class="flex border-b border-gray-200">
                    <button
                        @click="currentStatus = 'all'"
                        :class="[
                            'px-4 py-2 text-sm font-medium transition-colors',
                            currentStatus === 'all' 
                                ? 'border-b-2 border-[#007C95] text-[#007C95]' 
                                : 'text-gray-500 hover:text-gray-700'
                        ]"
                    >
                        Semua
                    </button>
                    <button
                        @click="currentStatus = 'pending'"
                        :class="[
                            'px-4 py-2 text-sm font-medium transition-colors',
                            currentStatus === 'pending' 
                                ? 'border-b-2 border-[#007C95] text-[#007C95]' 
                                : 'text-gray-500 hover:text-gray-700'
                        ]"
                    >
                        Perlu Verifikasi
                    </button>
                </div>

                <!-- Search -->
                <div class="relative">
                    <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari nama atau email..."
                        class="h-10 w-full rounded-lg border border-gray-200 bg-white pl-10 pr-4 text-sm focus:border-[#007C95] focus:ring-1 focus:ring-[#007C95] focus:outline-none"
                    />
                </div>

                <!-- Table -->
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-gray-700 uppercase">
                            <tr>
                                <th class="px-6 py-4 font-semibold">Nama</th>
                                <th class="px-6 py-4 font-semibold">Email</th>
                                <th class="px-6 py-4 font-semibold text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div v-if="user.profile_photo_url" class="h-8 w-8 rounded-full overflow-hidden">
                                            <img :src="user.profile_photo_url" alt="" class="h-full w-full object-cover" />
                                        </div>
                                        <div v-else class="h-8 w-8 rounded-full bg-[#007C95]/10 text-[#007C95] flex items-center justify-center font-bold text-xs uppercase">
                                            {{ getInitials(user.name) }}
                                        </div>
                                        <span class="font-medium text-gray-900">{{ user.name }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-gray-600">{{ user.email }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex justify-center">
                                        <Button
                                            v-if="!user.is_verified_by_admin && user.profile_completed_at"
                                            @click="verifyUser(user)"
                                            size="sm"
                                            class="bg-[#007C95] hover:bg-[#00667a] text-white flex items-center gap-2"
                                        >
                                            <CheckCircle class="h-4 w-4" />
                                            Verifikasi
                                        </Button>
                                        <span v-else-if="user.is_verified_by_admin" class="text-green-600 flex items-center gap-1 text-xs font-semibold">
                                            <CheckCircle class="h-3 w-3" />
                                            Terverifikasi
                                        </span>
                                        <span v-else class="text-gray-400 text-xs italic">
                                            Belum Lengkap
                                        </span>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="users.data.length === 0">
                                <td colspan="3" class="px-6 py-10 text-center text-gray-500">
                                    Tidak ada user ditemukan.
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                        <div class="text-xs text-gray-500">
                            Menampilkan {{ users.from || 0 }} ke {{ users.to || 0 }} dari {{ users.total }} user
                        </div>
                        <div class="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="users.current_page === 1"
                                @click="goToPage(users.links[0]?.url)"
                            >
                                <ChevronLeft class="h-4 w-4" />
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="users.current_page === users.last_page"
                                @click="goToPage(users.links[users.links.length - 1]?.url)"
                            >
                                <ChevronRight class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
