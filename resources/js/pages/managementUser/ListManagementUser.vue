<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { PermissionEnum } from '@/enums/PermissionEnum';
import {
    Search,
    Pencil,
    Trash2,
    ChevronUp,
    ChevronDown,
    Plus,
    EllipsisVertical,
} from 'lucide-vue-next';
import { ref, watch, computed } from 'vue';
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
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import TableFilter from '@/components/TableFilter.vue';
import type { FilterValues } from '@/components/TableFilter.vue';
import TablePagination from '@/components/TablePagination.vue';
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
    roles: string[];
    filters: { search?: string; perPage?: number; status?: string; role?: string; sort?: string; direction?: 'asc' | 'desc' };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Manajemen User', href: '/management-user' },
];

const searchQuery = ref(props.filters.search ?? '');
const perPage = ref(props.filters.perPage ?? 10);
const sortColumn = ref(props.filters.sort ?? 'created_at');
const sortDirection = ref<'asc' | 'desc'>(props.filters.direction ?? 'desc');

const filterFields = computed(() => [
    {
        key: 'status',
        label: 'Status',
        type: 'select' as const,
        options: [
            { label: 'Aktif', value: 'active' },
            { label: 'Non Aktif', value: 'inactive' },
        ],
    },
    {
        key: 'role',
        label: 'Role',
        type: 'select' as const,
        options: props.roles.map((r) => ({ label: r, value: r })),
    },
]);

const filterValues = ref<FilterValues>({
    status: props.filters.status ?? undefined,
    role: props.filters.role ?? undefined,
});

const buildParams = (extra: FilterValues = {}) => ({
    search: searchQuery.value || undefined,
    perPage: perPage.value,
    sort: sortColumn.value,
    direction: sortDirection.value,
    ...extra,
});

const handleSort = (column: string) => {
    if (sortColumn.value === column) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortColumn.value = column;
        sortDirection.value = 'asc';
    }
    router.get('/management-user', buildParams(filterValues.value), {
        preserveState: true,
        replace: true,
    });
};

const handleFilter = (values: FilterValues) => {
    filterValues.value = values;
    router.get('/management-user', buildParams(values), {
        preserveState: true,
        replace: true,
    });
};

const handleFilterReset = () => {
    filterValues.value = {};
    router.get('/management-user', buildParams({}), {
        preserveState: true,
        replace: true,
    });
};

// Delete state
const isDeleteOpen = ref(false);
const deletingUser = ref<User | null>(null);
const isDeleting = ref(false);

let searchTimeout: ReturnType<typeof setTimeout>;
watch(searchQuery, (val) => {
    clearTimeout(searchTimeout);
    if (val.length === 0 || val.length >= 3) {
        searchTimeout = setTimeout(() => {
            router.get('/management-user', buildParams(filterValues.value), {
                preserveState: true,
                replace: true,
            });
        }, 400);
    }
});

watch(perPage, () => {
    router.get('/management-user', buildParams(filterValues.value), {
        preserveState: true,
        replace: true,
    });
});

const goToPage = (url: string | null) => {
    if (!url) return;
    router.get(url, buildParams(filterValues.value), { preserveState: true });
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

const roleBadgeClass = (roleName: string) => {
    const map: Record<string, string> = {
        Admin: 'bg-violet-50 text-violet-700',
        Owner: 'bg-amber-50 text-amber-700',
        Staff: 'bg-sky-50 text-sky-700',
    };
    return map[roleName] ?? 'bg-gray-100 text-gray-600';
};

</script>

<template>

    <Head title="Manajemen User" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 pb-10 md:p-6">

            <!-- Header -->
            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <!-- Title -->
                <div class="flex-1 min-w-0">
                    <h1 class="text-[24px] font-bold text-gray-900">
                        Manajemen User
                    </h1>
                    <p class="mt-0.5 text-[16px] text-gray-500">
                        Kelola pengguna sistem
                    </p>
                </div>

                <!-- Actions -->
                <div class="flex w-full justify-end md:w-auto md:flex-shrink-0">
                    <div class="flex flex-col gap-2 md:flex-row">
                        <Button v-if="can(PermissionEnum.CREATE_USER)"
                            class="flex w-fit items-center justify-center gap-1.5 rounded-lg bg-[#007C95] px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-[#006b80]"
                            @click="router.get('/management-user/create')">
                            <Plus class="size-4" />
                            Tambah User
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Toolbar: Entries per page + Filter + Search -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-2">
                    <select v-model="perPage"
                        class="h-[45px] w-16 rounded-lg border border-gray-300 bg-white px-2 text-sm text-gray-700 focus:border-[#007C95] focus:ring-1 focus:ring-[#007C95] focus:outline-none">
                        <option :value="1">1</option>
                        <option :value="10">10</option>
                        <option :value="25">25</option>
                        <option :value="50">50</option>
                        <option :value="100">100</option>
                    </select>
                    <span class="text-sm text-gray-500">Entri per halaman</span>
                </div>

                <div class="flex items-center gap-2">
                    <TableFilter :filters="filterFields" :model-value="filterValues" @update:model-value="handleFilter"
                        @reset="handleFilterReset" />
                    <div class="relative flex-1 sm:flex-none">
                        <Search class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-gray-400" />
                        <input v-model="searchQuery" type="text" placeholder="Cari user..."
                            class="h-[45px] w-full rounded-lg border border-gray-300 bg-white py-2 pr-3 pl-9 text-sm placeholder-gray-400 focus:border-[#007C95] focus:ring-1 focus:ring-[#007C95] focus:outline-none sm:w-56" />
                    </div>
                </div>
            </div>

            <div>
                <div class=" overflow-hidden rounded-xl  border-gray-200 bg-white ">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 bg-[#F9F9F9]">
                                    <th class="px-4 py-3 text-left">
                                        <button
                                            class="flex items-center gap-1 text-xs font-semibold uppercase tracking-wider transition"
                                            :class="sortColumn === 'name' ? 'text-[#007C95]' : 'text-gray-500 hover:text-gray-700'"
                                            @click="handleSort('name')">
                                            Nama
                                            <span class="flex flex-col">
                                                <ChevronUp class="size-3 -mb-0.5"
                                                    :class="sortColumn === 'name' && sortDirection === 'asc' ? 'text-[#007C95]' : 'text-gray-300'" />
                                                <ChevronDown class="size-3 -mt-0.5"
                                                    :class="sortColumn === 'name' && sortDirection === 'desc' ? 'text-[#007C95]' : 'text-gray-300'" />
                                            </span>
                                        </button>
                                    </th>
                                    <th class="px-4 py-3 text-left">
                                        <button
                                            class="flex items-center gap-1 text-xs font-semibold uppercase tracking-wider transition"
                                            :class="sortColumn === 'email' ? 'text-[#007C95]' : 'text-gray-500 hover:text-gray-700'"
                                            @click="handleSort('email')">
                                            Email
                                            <span class="flex flex-col">
                                                <ChevronUp class="size-3 -mb-0.5"
                                                    :class="sortColumn === 'email' && sortDirection === 'asc' ? 'text-[#007C95]' : 'text-gray-300'" />
                                                <ChevronDown class="size-3 -mt-0.5"
                                                    :class="sortColumn === 'email' && sortDirection === 'desc' ? 'text-[#007C95]' : 'text-gray-300'" />
                                            </span>
                                        </button>
                                    </th>
                                    <th class="px-4 py-3 text-left">
                                        <span
                                            class="text-xs font-semibold uppercase tracking-wider text-gray-500">Role</span>
                                    </th>
                                    <th class="px-4 py-3 text-left">
                                        <span
                                            class="text-xs font-semibold uppercase tracking-wider text-gray-500">Status</span>
                                    </th>
                                    <th class="px-4 py-3 text-left w-[50px]">
                                        <span
                                            class="text-xs font-semibold uppercase tracking-wider text-gray-500">Aksi</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="user in users.data" :key="user.id" class="transition hover:bg-gray-50/60">
                                    <!-- Nama -->
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <img v-if="user.profile_photo_url" :src="user.profile_photo_url"
                                                :alt="user.name"
                                                class="size-8 shrink-0 rounded-full border border-gray-200 object-cover" />
                                            <div v-else
                                                class="flex size-8 shrink-0 items-center justify-center rounded-full bg-[#007C95]/10">
                                                <span class="text-xs font-semibold text-[#007C95]">{{
                                                    getInitials(user.name)
                                                }}</span>
                                            </div>
                                            <p class="font-medium text-gray-900">{{ user.name }}</p>
                                        </div>
                                    </td>

                                    <!-- Email -->
                                    <td class="px-4 py-3 whitespace-nowrap text-gray-500">
                                        {{ user.email }}
                                    </td>

                                    <!-- Role -->
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span v-if="user.roles.length"
                                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                            :class="roleBadgeClass(user.roles[0].name)">
                                            {{ user.roles[0].name }}
                                        </span>
                                        <span v-else class="text-xs text-gray-400">—</span>
                                    </td>

                                    <!-- Status -->
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <button v-if="can(PermissionEnum.EDIT_USER)"
                                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium transition"
                                            :class="user.is_active
                                                ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
                                                : 'bg-rose-50 text-rose-600 hover:bg-rose-100'"
                                            @click="toggleStatus(user)">
                                            {{ user.is_active ? 'Aktif' : 'Non Aktif' }}
                                        </button>
                                        <span v-else
                                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                            :class="user.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-600'">
                                            {{ user.is_active ? 'Aktif' : 'Non Aktif' }}
                                        </span>
                                    </td>

                                    <!-- Aksi -->
                                    <td class="px-4 py-3 whitespace-nowrap w-[50px]">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <button
                                                    class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                                                    <EllipsisVertical class="size-4" />
                                                </button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end" class="w-40">
                                                <DropdownMenuItem v-if="can(PermissionEnum.EDIT_USER)"
                                                    class="gap-2 text-sm"
                                                    @click="router.get(`/management-user/${user.id}/edit`)">
                                                    <Pencil class="size-3.5" />
                                                    Edit
                                                </DropdownMenuItem>
                                                <DropdownMenuItem v-if="can(PermissionEnum.DELETE_USER)"
                                                    class="gap-2 text-sm text-red-600 focus:text-red-600"
                                                    @click="openDelete(user)">
                                                    <Trash2 class="size-3.5" />
                                                    Hapus
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </td>
                                </tr>

                                <!-- Empty State -->
                                <tr v-if="users.data.length === 0">
                                    <td colspan="5" class="px-5 py-10 text-center">
                                        <div class="flex flex-col items-center gap-2">
                                            <Vue3Lottie :animationData="emptyAnimation" :height="160" :width="160"
                                                :loop="true" />
                                            <p class="text-sm font-medium text-gray-600">Tidak ada data User</p>
                                            <p class="text-xs text-gray-400">Coba ubah kata kunci pencarian</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <TablePagination :paginator="users" @navigate="goToPage" />
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
                    <AlertDialogAction class="bg-red-600 hover:bg-red-700" :disabled="isDeleting"
                        @click="confirmDelete">
                        {{ isDeleting ? 'Menghapus...' : 'Ya, Hapus' }}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

    </AppLayout>
</template>
