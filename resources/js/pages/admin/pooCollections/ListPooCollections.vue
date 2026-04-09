<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { PermissionEnum } from '@/enums/PermissionEnum';
import { MapPin, Search, ChevronRight } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { toast } from 'vue-sonner';
import { Vue3Lottie } from 'vue3-lottie';
import emptyAnimation from '@/../../public/assets/animations/Pencarian Tidak Ditemukan.json';
import BadgeBussines from '@/components/BadgeBussines.vue';
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
    total_collected: number;
}

const props = defineProps<{
    poos: POO[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pengambilan dari POO', href: '/collections' },
];

const searchQuery = ref('');
const isFormOpen = ref(false);

const filteredPoos = computed(() => {
    if (searchQuery.value.length < 3) return props.poos;
    const q = searchQuery.value.toLowerCase();
    return props.poos.filter(
        (p) =>
            p.name.toLowerCase().includes(q) ||
            p.address.toLowerCase().includes(q),
    );
});

const openCreate = () => {
    isFormOpen.value = true;
};
const openBatches = (poo: POO) => router.visit(`/collections/${poo.id}/create`);
</script>

<template>
    <Head title="Pengambilan dari POO" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col items-center gap-6 p-6">
            <div class="flex w-full max-w-3xl flex-col gap-6">
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
                            Pengambilan dari POO
                        </h1>
                    </div>
                    <p class="text-[14px] text-gray-500">
                        Pilih titik asal minyak (POO) untuk mencatat pengambilan
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

                <!-- Cards Grid -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div
                        v-for="poo in filteredPoos"
                        :key="poo.id"
                        class="group relative flex cursor-pointer flex-col gap-3 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-teal-300 hover:shadow-md"
                        @click="openBatches(poo)"
                    >
                        <!-- Badge + Chevron -->
                        <div class="flex items-center justify-between">
                            <BadgeBussines :type="poo.type" />
                            <ChevronRight
                                class="h-4 w-4 text-gray-400 transition group-hover:translate-x-0.5 group-hover:text-teal-500"
                            />
                        </div>

                        <!-- Name -->
                        <h3 class="text-base font-semibold text-gray-900">
                            {{ poo.name }}
                        </h3>

                        <!-- Address -->
                        <div
                            class="flex items-start gap-1.5 text-sm text-gray-500"
                        >
                            <MapPin
                                class="mt-0.5 h-3.5 w-3.5 flex-shrink-0 text-gray-400"
                            />
                            <span>{{ poo.address }}</span>
                        </div>

                        <!-- Total -->
                        <div
                            class="mt-1 flex items-center justify-between border-t border-gray-100 pt-2"
                        >
                            <span class="text-xs text-gray-500"
                                >Total terkumpul</span
                            >
                            <span class="text-base font-bold text-teal-600"
                                >{{ poo.total_collected }} L</span
                            >
                        </div>
                    </div>

                    <!-- Empty state -->
                    <div
                        v-if="filteredPoos.length === 0"
                        class="col-span-2 flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-200 bg-white py-16 text-center"
                    >
                        <Vue3Lottie
                            :animationData="emptyAnimation"
                            :height="160"
                            :width="160"
                            :loop="true"
                        />
                        <p class="text-sm font-medium text-gray-600">
                            Tidak ada data POO
                        </p>
                        <p class="mt-1 text-xs text-gray-400">
                            Coba ubah kata kunci pencarian
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal hanya form tambah, tanpa edit/delete -->
        <PooFormModal
            v-model:open="isFormOpen"
            :editing-poo="null"
            post-url="/management-poo"
            :suggestions="poos"
            @success="
                toast.success('Berhasil!', {
                    description: 'Data POO berhasil ditambahkan',
                })
            "
            @error="
                toast.error('Gagal!', {
                    description: 'Terjadi kesalahan saat menyimpan data',
                })
            "
        />
    </AppLayout>
</template>
