<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { ChevronLeft, Eye, EyeOff } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import InputError from '@/components/InputError.vue';
import PermissionPicker from '@/components/ManagementUser/PermissionPicker.vue';
import type { BreadcrumbItem } from '@/types';

interface UserData {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    role: string | null;
    role_permissions: string[];
    direct_permissions: string[];
}

interface RoleItem {
    id: number;
    name: string;
    permissions: string[];
}

interface PermGroup {
    id: number;
    name: string;
    key: string;
    permissions: { name: string }[];
}

const props = defineProps<{
    user: UserData;
    roles: RoleItem[];
    permissionGroups: PermGroup[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Manajemen User', href: '/management-user' },
    { title: 'Edit User', href: `/management-user/${props.user.id}/edit` },
];

// Gabungkan role permission + direct permission sebagai nilai awal
const initialPermissions = [
    ...new Set([
        ...props.user.role_permissions,
        ...props.user.direct_permissions,
    ]),
];

const showPassword = ref(false);

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    password: '',
    role: props.user.role ?? '',
    permissions: initialPermissions,
    is_active: props.user.is_active,
});

// Permission bawaan role yang sedang dipilih
const rolePermissions = computed<string[]>(() => {
    if (!form.role) return [];
    return props.roles.find(r => r.name === form.role)?.permissions ?? [];
});

// Saat role berubah: centang ulang permission
watch(() => form.role, (newRole) => {
    const rolePerm = props.roles.find(r => r.name === newRole)?.permissions ?? [];
    // Pertahankan extra permission di luar bawaan role manapun
    const extraPerms = form.permissions.filter(p => !isFromAnyRole(p));
    form.permissions = [...new Set([...rolePerm, ...extraPerms])];
});

function isFromAnyRole(permName: string): boolean {
    return props.roles.some(r => r.permissions.includes(permName));
}

function submit() {
    form.put(`/management-user/${props.user.id}`);
}
</script>

<template>
    <Head title="Edit User" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">

            <!-- Header -->
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors"
                    @click="router.get('/management-user')"
                >
                    <ChevronLeft class="size-5" />
                </button>
                <div>
                    <h1 class="text-lg font-bold text-gray-900">Edit User</h1>
                    <p class="text-sm text-gray-500">{{ user.name }} — {{ user.email }}</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="flex flex-col gap-6">
                <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

                    <!-- Kolom kiri: Data user -->
                    <div class="xl:col-span-1 space-y-5">
                        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                            <div class="border-b border-gray-100 px-5 py-4">
                                <h2 class="text-sm font-semibold text-gray-700">Data User</h2>
                            </div>
                            <div class="space-y-4 p-5">

                                <!-- Nama -->
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-[#101010]">Nama Lengkap</label>
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        class="h-10 w-full rounded-lg border border-gray-200 px-3 text-sm placeholder-gray-400 focus:border-[#007C95] focus:ring-2 focus:ring-[#007C95]/20 focus:outline-none"
                                        :class="{ 'border-red-400': form.errors.name }"
                                    />
                                    <InputError :message="form.errors.name" class="mt-1" />
                                </div>

                                <!-- Email -->
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-[#101010]">Email</label>
                                    <input
                                        v-model="form.email"
                                        type="email"
                                        class="h-10 w-full rounded-lg border border-gray-200 px-3 text-sm placeholder-gray-400 focus:border-[#007C95] focus:ring-2 focus:ring-[#007C95]/20 focus:outline-none"
                                        :class="{ 'border-red-400': form.errors.email }"
                                    />
                                    <InputError :message="form.errors.email" class="mt-1" />
                                </div>

                                <!-- Password (opsional) -->
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-[#101010]">
                                        Password Baru
                                        <span class="ml-1 text-xs font-normal text-gray-400">(kosongkan jika tidak diubah)</span>
                                    </label>
                                    <div class="relative">
                                        <input
                                            v-model="form.password"
                                            :type="showPassword ? 'text' : 'password'"
                                            placeholder="Minimal 8 karakter"
                                            class="h-10 w-full rounded-lg border border-gray-200 px-3 pr-10 text-sm placeholder-gray-400 focus:border-[#007C95] focus:ring-2 focus:ring-[#007C95]/20 focus:outline-none"
                                            :class="{ 'border-red-400': form.errors.password }"
                                        />
                                        <button
                                            type="button"
                                            class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 hover:text-gray-600"
                                            @click="showPassword = !showPassword"
                                        >
                                            <EyeOff v-if="showPassword" class="size-4" />
                                            <Eye v-else class="size-4" />
                                        </button>
                                    </div>
                                    <InputError :message="form.errors.password" class="mt-1" />
                                </div>

                                <!-- Role -->
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-[#101010]">Role</label>
                                    <select
                                        v-model="form.role"
                                        class="h-10 w-full rounded-lg border border-gray-200 px-3 text-sm text-gray-700 focus:border-[#007C95] focus:ring-2 focus:ring-[#007C95]/20 focus:outline-none"
                                        :class="{ 'border-red-400': form.errors.role }"
                                    >
                                        <option value="">-- Tanpa Role --</option>
                                        <option v-for="role in roles" :key="role.id" :value="role.name">
                                            {{ role.name }}
                                        </option>
                                    </select>
                                    <InputError :message="form.errors.role" class="mt-1" />
                                    <p v-if="form.role" class="mt-1.5 text-xs text-gray-400">
                                        Permission bawaan role otomatis tercentang dan tidak bisa dihapus.
                                    </p>
                                </div>

                                <!-- Status aktif -->
                                <div class="flex items-center justify-between rounded-lg border border-gray-200 px-4 py-3">
                                    <div>
                                        <p class="text-sm font-medium text-[#101010]">Status Aktif</p>
                                        <p class="text-xs text-gray-400">User dapat login jika aktif</p>
                                    </div>
                                    <button
                                        type="button"
                                        class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full border-2 border-transparent transition-colors duration-200"
                                        :class="form.is_active ? 'bg-[#007C95]' : 'bg-gray-200'"
                                        @click="form.is_active = !form.is_active"
                                    >
                                        <span
                                            class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow transition duration-200"
                                            :class="form.is_active ? 'translate-x-4' : 'translate-x-0'"
                                        />
                                    </button>
                                </div>

                            </div>
                        </div>

                        <!-- Tombol aksi -->
                        <div class="flex gap-3">
                            <Button
                                type="button"
                                variant="outline"
                                class="flex-1"
                                @click="router.get('/management-user')"
                            >
                                Batal
                            </Button>
                            <Button
                                type="submit"
                                class="flex-1 bg-[#007C95] hover:bg-[#006b80] text-white"
                                :disabled="form.processing"
                            >
                                {{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                            </Button>
                        </div>
                    </div>

                    <!-- Kolom kanan: Permission -->
                    <div class="xl:col-span-2">
                        <PermissionPicker
                            v-model="form.permissions"
                            :disabled-permissions="rolePermissions"
                            :groups="permissionGroups"
                        />
                        <InputError :message="form.errors.permissions" class="mt-2" />
                    </div>

                </div>
            </form>
        </div>
    </AppLayout>
</template>
