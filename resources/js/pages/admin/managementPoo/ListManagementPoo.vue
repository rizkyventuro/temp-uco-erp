<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { PermissionEnum } from '@/enums/PermissionEnum';
import {
    MapPin,
    Search,
    Pencil,
    Trash2,
    ChevronLeft,
    ChevronRight,
} from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import { Vue3Lottie } from 'vue3-lottie';
import emptyAnimation from '@/../../public/assets/animations/Pencarian Tidak Ditemukan.json';

import BadgeBussines from '@/components/BadgeBussines.vue';
import PooDeleteModal from '@/components/MasterPoo/MasterPooDeleteModal.vue';
import PooFormModal from '@/components/MasterPoo/MasterPooFormModal.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { usePermission } from '@/composables/usePermission';
import { type BreadcrumbItem } from '@/types';

const { can } = usePermission();

type BusinessType = 'Restoran' | 'UMKM' | 'Rumah Tangga';

interface POO {
    id: string;
    name: string;
    address: string;
    contact: string;
    type: BusinessType;
}

interface PaginatedPoos {
    data: POO[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: { url: string | null; label: string; active: boolean }[];
}

const props = defineProps<{
    poos: PaginatedPoos;
    filters: { search?: string; perPage?: number };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Manajemen POO', href: '/management-poo' },
];

const searchQuery = ref(props.filters.search ?? '');
const perPage = ref(props.filters.perPage ?? 10);
const isFormOpen = ref(false);
const isDeleteOpen = ref(false);
const editingPoo = ref<POO | null>(null);
const deletingPoo = ref<POO | null>(null);

// Debounce search agar tidak request tiap ketikan
let searchTimeout: ReturnType<typeof setTimeout>;
watch(searchQuery, (val) => {
    clearTimeout(searchTimeout);
    if (val.length === 0 || val.length >= 3) {
        searchTimeout = setTimeout(() => {
            router.get(
                '/management-poo',
                { search: val, perPage: perPage.value },
                {
                    preserveState: true,
                    replace: true,
                },
            );
        }, 400);
    }
});

watch(perPage, (val) => {
    router.get(
        '/management-poo',
        { search: searchQuery.value, perPage: val },
        {
            preserveState: true,
            replace: true,
        },
    );
});

const goToPage = (url: string | null) => {
    if (!url) return;
    router.get(
        url,
        { search: searchQuery.value, perPage: perPage.value },
        {
            preserveState: true,
        },
    );
};

const openCreate = () => {
    editingPoo.value = null;
    isFormOpen.value = true;
};
const openEdit = (poo: POO) => {
    editingPoo.value = poo;
    isFormOpen.value = true;
};
const openDelete = (poo: POO) => {
    deletingPoo.value = poo;
    isDeleteOpen.value = true;
};
</script>

<template>
    <Head title="Manajemen POO" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col items-center gap-6 p-6">
            <div class="flex w-full max-w-4xl flex-col gap-6">
                <!-- Header -->
                <div>
                    <div class="flex items-center gap-2">
                        <div
                            class="flex h-8 w-8 items-center justify-center text-[#007C95]"
                        >
                            <svg
                                width="24"
                                height="24"
                                viewBox="0 0 16 16"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <g clip-path="url(#clip0_25301_1724)">
                                    <path
                                        d="M10.6667 10.6667H14.6667"
                                        stroke="currentColor"
                                        stroke-width="1.33333"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                    <path
                                        d="M12.6667 8.66667V12.6667"
                                        stroke="currentColor"
                                        stroke-width="1.33333"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                    <path
                                        d="M14 6.66667V5.33333C13.9998 5.09952 13.938 4.86987 13.821 4.66744C13.704 4.46501 13.5358 4.29691 13.3333 4.18L8.66667 1.51333C8.46397 1.39631 8.23405 1.3347 8 1.3347C7.76595 1.3347 7.53603 1.39631 7.33333 1.51333L2.66667 4.18C2.46418 4.29691 2.29599 4.46501 2.17897 4.66744C2.06196 4.86987 2.00024 5.09952 2 5.33333V10.6667C2.00024 10.9005 2.06196 11.1301 2.17897 11.3326C2.29599 11.535 2.46418 11.7031 2.66667 11.82L7.33333 14.4867C7.53603 14.6037 7.76595 14.6653 8 14.6653C8.23405 14.6653 8.46397 14.6037 8.66667 14.4867L10 13.7267"
                                        stroke="currentColor"
                                        stroke-width="1.33333"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                    <path
                                        d="M5 2.84666L11 6.28"
                                        stroke="currentColor"
                                        stroke-width="1.33333"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                    <path
                                        d="M2.19333 4.66667L8 8.00001L13.8067 4.66667"
                                        stroke="currentColor"
                                        stroke-width="1.33333"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                    <path
                                        d="M8 14.6667V8"
                                        stroke="currentColor"
                                        stroke-width="1.33333"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </g>
                                <defs>
                                    <clipPath id="clip0_25301_1724">
                                        <rect
                                            width="16"
                                            height="16"
                                            fill="white"
                                        />
                                    </clipPath>
                                </defs>
                            </svg>
                        </div>
                        <h1 class="text-[18px] font-bold text-gray-900">
                            Manajemen POO
                        </h1>
                    </div>
                    <p class="mt-0.5 text-[14px] text-gray-500">
                        Kelola titik asal minyak jelantah
                    </p>
                </div>

                <!-- Search + Button -->
                <div class="flex items-center gap-3">
                    <div class="relative flex-1">
                        <Search
                            class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400"
                        />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari POO berdasarkan nama atau alamat..."
                            class="h-10 w-full rounded-lg border border-gray-200 bg-white py-2.5 pr-4 pl-10 text-sm placeholder-gray-400 focus:border-teal-400 focus:ring-2 focus:ring-teal-100 focus:outline-none"
                        />
                    </div>
                    <Button
                        v-if="can(PermissionEnum.CREATE_MASTER_POO)"
                        @click="openCreate"
                        class="shrink-0 rounded bg-primary px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-hover"
                    >
                        Tambah POO
                    </Button>
                </div>

                <!-- Table -->
                <div
                    class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
                >
                    <div class="border-b border-gray-100 px-5 py-4">
                        <h2 class="text-sm font-semibold text-gray-700">
                            Daftar POO
                            <span class="font-normal text-gray-400"
                                >({{ poos.total }})</span
                            >
                        </h2>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr
                                    class="border-b border-gray-100 bg-gray-50/60"
                                >
                                    <th
                                        class="px-5 py-2.5 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-gray-500"
                                    >
                                        Nama
                                    </th>
                                    <th
                                        class="px-5 py-2.5 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-gray-500"
                                    >
                                        Alamat
                                    </th>
                                    <th
                                        class="px-5 py-2.5 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-gray-500"
                                    >
                                        Kontak
                                    </th>
                                    <th
                                        class="px-5 py-2.5 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-gray-500"
                                    >
                                        Jenis Usaha
                                    </th>
                                    <th
                                        class="px-5 py-2.5 text-left text-xs font-semibold tracking-wide whitespace-nowrap text-gray-500"
                                    >
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr
                                    v-for="poo in poos.data"
                                    :key="poo.id"
                                    class="transition hover:bg-gray-50/50"
                                >
                                    <td
                                        class="px-5 py-3.5 font-medium whitespace-nowrap text-gray-900"
                                    >
                                        {{ poo.name }}
                                    </td>
                                    <td
                                        class="max-w-[200px] px-5 py-3.5 text-gray-500"
                                    >
                                        <div class="flex items-start gap-1.5">
                                            <MapPin
                                                class="mt-0.5 h-3.5 w-3.5 flex-shrink-0 text-gray-400"
                                            />
                                            <span class="line-clamp-2">{{
                                                poo.address
                                            }}</span>
                                        </div>
                                    </td>
                                    <td
                                        class="px-5 py-3.5 whitespace-nowrap text-gray-500"
                                    >
                                        {{ poo.contact || '-' }}
                                    </td>
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <BadgeBussines :type="poo.type" />
                                    </td>
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <div class="flex items-center gap-1">
                                            <button
                                                v-if="can(PermissionEnum.EDIT_MASTER_POO)"
                                                @click="openEdit(poo)"
                                                class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
                                            >
                                                <Pencil class="h-3.5 w-3.5" />
                                            </button>
                                            <button
                                                v-if="can(PermissionEnum.DELETE_MASTER_POO)"
                                                @click="openDelete(poo)"
                                                class="rounded-lg p-1.5 text-gray-400 transition hover:bg-red-50 hover:text-red-500"
                                            >
                                                <Trash2 class="h-3.5 w-3.5" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="poos.data.length === 0">
                                    <td
                                        colspan="5"
                                        class="px-5 py-10 text-center"
                                    >
                                        <div
                                            class="flex flex-col items-center gap-2"
                                        >
                                            <Vue3Lottie
                                                :animationData="emptyAnimation"
                                                :height="160"
                                                :width="160"
                                                :loop="true"
                                            />
                                            <p
                                                class="text-sm font-medium text-gray-600"
                                            >
                                                Tidak ada data POO
                                            </p>
                                            <p class="text-xs text-gray-400">
                                                Coba ubah kata kunci pencarian
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Footer -->
                    <div
                        class="flex items-center justify-between border-t border-gray-100 px-5 py-4"
                    >
                        <!-- Per page -->
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-500"
                                >Baris per halaman</span
                            >
                            <select
                                v-model="perPage"
                                class="h-8 rounded-lg border border-gray-200 bg-white px-2 text-xs text-gray-600 focus:ring-2 focus:ring-teal-100 focus:outline-none"
                            >
                                <option :value="10">10</option>
                                <option :value="25">25</option>
                                <option :value="50">50</option>
                                <option :value="100">100</option>
                            </select>
                        </div>

                        <!-- Info + Nav -->
                        <div class="flex items-center gap-3">
                            <span class="text-xs text-gray-500">
                                {{ poos.from }}–{{ poos.to }} dari
                                {{ poos.total }}
                            </span>
                            <div class="flex items-center gap-1">
                                <button
                                    @click="
                                        goToPage(poos.links[0]?.url ?? null)
                                    "
                                    :disabled="poos.current_page === 1"
                                    class="rounded-lg border border-gray-200 p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 disabled:cursor-not-allowed disabled:opacity-40"
                                >
                                    <ChevronLeft class="h-4 w-4" />
                                </button>
                                <button
                                    @click="
                                        goToPage(
                                            poos.links[poos.links.length - 1]
                                                ?.url ?? null,
                                        )
                                    "
                                    :disabled="
                                        poos.current_page === poos.last_page
                                    "
                                    class="rounded-lg border border-gray-200 p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 disabled:cursor-not-allowed disabled:opacity-40"
                                >
                                    <ChevronRight class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <PooFormModal
            v-model:open="isFormOpen"
            :editing-poo="editingPoo"
            :suggestions="poos.data"
            post-url="/management-poo"
            @success="
                toast.success('Berhasil!', {
                    description: editingPoo
                        ? 'Data POO berhasil diperbarui'
                        : 'Data POO berhasil ditambahkan',
                })
            "
            @error="
                toast.error('Gagal!', {
                    description: 'Terjadi kesalahan saat menyimpan data',
                })
            "
        />

        <PooDeleteModal
            v-model:open="isDeleteOpen"
            :poo="deletingPoo"
            delete-url="/management-poo"
            @success="
                toast.success('Berhasil!', {
                    description: 'Data POO berhasil dihapus',
                })
            "
            @error="
                toast.error('Gagal!', {
                    description: 'Terjadi kesalahan saat menghapus data',
                })
            "
        />
    </AppLayout>
</template>
