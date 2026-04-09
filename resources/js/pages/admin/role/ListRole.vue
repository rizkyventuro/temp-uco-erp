<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Shield, Plus, Pencil, Trash2 } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import RoleFormModal from '@/components/role/RoleFormModal.vue';
import { usePermission } from '@/composables/usePermission';
import { PermissionEnum } from '@/enums/PermissionEnum';
import { type BreadcrumbItem } from '@/types';

const { can } = usePermission();

interface PermissionItem { id: number; name: string }
interface PermissionGroup { group: string; items: PermissionItem[] }
interface RoleItem {
    id: number;
    name: string;
    permissions_count: number;
    permissions: string[];
}

const props = defineProps<{
    roles: RoleItem[];
    permissions: PermissionGroup[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Manajemen Role', href: '/roles' }];

const showModal  = ref(false);
const editTarget = ref<RoleItem | null>(null);

function openCreate() {
    editTarget.value = null;
    showModal.value  = true;
}

function openEdit(role: RoleItem) {
    editTarget.value = role;
    showModal.value  = true;
}

function closeModal() {
    showModal.value  = false;
    editTarget.value = null;
}

function destroy(role: RoleItem) {
    if (!confirm(`Hapus role "${role.name}"?`)) return;
    useForm({}).delete(`/roles/${role.id}`);
}
</script>

<template>
    <Head title="Manajemen Role" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col items-center">
            <div class="w-full max-w-4xl px-6 py-6">

                <!-- Page header -->
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h1 class="text-lg font-bold text-gray-900">Manajemen Role</h1>
                        <p class="mt-0.5 text-sm text-gray-500">Kelola role dan hak akses pengguna</p>
                    </div>
                    <!-- <button
                        v-if="can(PermissionEnum.CREATE_ROLE)"
                        @click="openCreate"
                        class="flex items-center gap-2 rounded-xl bg-[#007C95] px-4 py-2.5 text-sm font-medium text-white hover:bg-[#006880] transition"
                    >
                        <Plus class="h-4 w-4" /> Tambah Role
                    </button> -->
                </div>

                <!-- Role cards -->
                <div class="grid gap-3 sm:grid-cols-2">
                    <div
                        v-for="role in roles"
                        :key="role.id"
                        class="rounded-2xl bg-white p-5 shadow-sm border border-gray-100"
                    >
                        <div class="mb-3 flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-[#007C95]/10">
                                    <Shield class="h-5 w-5 text-[#007C95]" />
                                </div>
                                <div>
                                    <p class="font-semibold capitalize text-gray-800">{{ role.name }}</p>
                                    <p class="text-xs text-gray-400">{{ role.permissions_count }} permission</p>
                                </div>
                            </div>
                            <div class="flex gap-1">
                                <button
                                    v-if="can(PermissionEnum.EDIT_ROLE)"
                                    @click="openEdit(role)"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-[#007C95] transition"
                                >
                                    <Pencil class="h-4 w-4" />
                                </button>
                                <!-- <button
                                    v-if="can(PermissionEnum.DELETE_ROLE)"
                                    @click="destroy(role)"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-500 transition"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </button> -->
                            </div>
                        </div>

                        <!-- Permission chips preview -->
                        <div class="flex flex-wrap gap-1.5">
                            <span
                                v-for="perm in role.permissions.slice(0, 6)"
                                :key="perm"
                                class="rounded-full bg-[#007C95]/8 px-2.5 py-1 text-[11px] font-medium text-[#007C95]"
                            >
                                {{ perm }}
                            </span>
                            <span
                                v-if="role.permissions.length > 6"
                                class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] text-gray-400"
                            >
                                +{{ role.permissions.length - 6 }} lainnya
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <RoleFormModal
            :show="showModal"
            :permissions="permissions"
            :edit-target="editTarget"
            @close="closeModal"
        />
    </AppLayout>
</template>