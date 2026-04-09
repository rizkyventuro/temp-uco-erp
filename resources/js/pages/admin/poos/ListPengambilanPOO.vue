<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { MapPin, Search, ChevronRight, Pencil, Trash2 } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { toast } from 'vue-sonner';
import BadgeBussines from '@/components/BadgeBussines.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface POO {
    id: string;
    name: string;
    address: string;
    contact: string;
    type: 'Restoran' | 'UMKM' | 'Rumah Tangga';
    total_collected: number;
}

const props = defineProps<{
    poos: POO[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Pengambilan dari POO',
        href: '/poos',
    },
];

const searchQuery = ref('');
const isFormOpen = ref(false);
const isDeleteOpen = ref(false);
const editingPoo = ref<POO | null>(null);
const deletingPoo = ref<POO | null>(null);
const selectedType = ref<'Restoran' | 'UMKM' | 'Rumah Tangga'>('Restoran');

const form = useForm({
    name: '',
    address: '',
    contact: '',
    type: 'Restoran' as 'Restoran' | 'UMKM' | 'Rumah Tangga',
});

const filteredPoos = computed(() => {
    if (!searchQuery.value) return props.poos;
    const q = searchQuery.value.toLowerCase();
    return props.poos.filter(
        (p) =>
            p.name.toLowerCase().includes(q) ||
            p.address.toLowerCase().includes(q),
    );
});

const isEditing = computed(() => editingPoo.value !== null);

const openCreate = () => {
    editingPoo.value = null;
    form.reset();
    form.clearErrors();
    selectedType.value = 'Restoran';
    isFormOpen.value = true;
};

const openEdit = (poo: POO) => {
    editingPoo.value = poo;
    form.name = poo.name;
    form.address = poo.address;
    form.contact = poo.contact ?? '';
    form.type = poo.type;
    selectedType.value = poo.type;
    form.clearErrors();
    isFormOpen.value = true;
};

const openDelete = (poo: POO) => {
    deletingPoo.value = poo;
    isDeleteOpen.value = true;
};

const openBatches = (poo: POO) => {
    router.visit(`/poos/batches/${poo.id}/create`);
};

const setType = (type: 'Restoran' | 'UMKM' | 'Rumah Tangga') => {
    selectedType.value = type;
    form.type = type;
};

const handleSubmit = () => {
    form.type = selectedType.value;

    if (isEditing.value && editingPoo.value) {
        form.put(`/poos/${editingPoo.value.id}`, {
            onSuccess: () => {
                isFormOpen.value = false;
                editingPoo.value = null;
                form.reset();
                toast.success('Berhasil!', {
                    description: 'Data POO berhasil diperbarui',
                });
            },
            onError: () => {
                toast.error('Gagal!', {
                    description: 'Terjadi kesalahan saat memperbarui data',
                });
            },
        });
    } else {
        form.post('/poos', {
            onSuccess: () => {
                isFormOpen.value = false;
                form.reset();
                toast.success('Berhasil!', {
                    description: 'Data POO berhasil ditambahkan',
                });
            },
            onError: () => {
                toast.error('Gagal!', {
                    description: 'Terjadi kesalahan saat menyimpan data',
                });
            },
        });
    }
};

const handleDelete = () => {
    if (!deletingPoo.value) return;

    router.delete(`/poos/${deletingPoo.value.id}`, {
        onSuccess: () => {
            isDeleteOpen.value = false;
            deletingPoo.value = null;
            toast.success('Berhasil!', {
                description: 'Data POO berhasil dihapus',
            });
        },
        onError: () => {
            toast.error('Gagal!', {
                description: 'Terjadi kesalahan saat menghapus data',
            });
        },
    });
};
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
                    <div>
                        <p class="text-[14px] text-gray-500">
                            Pilih titik asal minyak (POO) untuk mencatat
                            pengambilan
                        </p>
                    </div>
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
                            class="h-10.25 w-full rounded-core border border-gray-200 bg-white py-2.5 pr-4 pl-10 text-sm placeholder-gray-400 focus:border-teal-400 focus:ring-2 focus:ring-teal-100 focus:outline-none"
                        />
                    </div>
                    <Button
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
                    >
                        <!-- Type Badge + Actions -->
                        <div class="flex items-center justify-between">
                            <BadgeBussines :type="poo.type" />
                            <div class="flex items-center gap-1">
                                <button
                                    @click.stop="openEdit(poo)"
                                    class="rounded-lg p-1.5 text-gray-400 opacity-0 transition group-hover:opacity-100 hover:bg-gray-100 hover:text-gray-600"
                                >
                                    <Pencil class="h-3.5 w-3.5" />
                                </button>
                                <button
                                    @click.stop="openDelete(poo)"
                                    class="rounded-lg p-1.5 text-gray-400 opacity-0 transition group-hover:opacity-100 hover:bg-red-50 hover:text-red-500"
                                >
                                    <Trash2 class="h-3.5 w-3.5" />
                                </button>
                                <ChevronRight
                                    @click.stop="openBatches(poo)"
                                    class="h-4 w-4 text-gray-400 transition group-hover:translate-x-0.5 group-hover:text-teal-500"
                                />
                            </div>
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
                        <div
                            class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100"
                        >
                            <Search class="h-5 w-5 text-gray-400" />
                        </div>
                        <p class="text-sm font-medium text-gray-600">
                            Tidak ada data POO
                        </p>
                        <p class="mt-1 text-xs text-gray-400">
                            Coba ubah kata kunci pencarian
                        </p>
                    </div>
                </div>
            </div>

            <!-- Create/Edit POO Modal -->
            <Dialog v-model:open="isFormOpen">
                <DialogContent
                    class="overflow-hidden rounded-2xl p-0 sm:max-w-md"
                >
                    <div class="p-6 pb-0">
                        <DialogHeader>
                            <DialogTitle
                                class="text-lg font-bold text-gray-900"
                            >
                                {{ isEditing ? 'Edit POO' : 'Tambah POO Baru' }}
                            </DialogTitle>
                        </DialogHeader>
                    </div>

                    <div class="grid gap-5 p-5">
                        <!-- Nama -->
                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium">
                                Nama <span class="text-red-500">*</span>
                            </Label>
                            <Input
                                v-model="form.name"
                                placeholder="Rumah Padang"
                                class="border-gray-200"
                                :class="{ 'border-red-400': form.errors.name }"
                            />
                            <span
                                v-if="form.errors.name"
                                class="text-xs text-red-500"
                                >{{ form.errors.name }}</span
                            >
                        </div>

                        <!-- Alamat -->
                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium">
                                Alamat <span class="text-red-500">*</span>
                            </Label>
                            <textarea
                                v-model="form.address"
                                placeholder="Alamat lengkap"
                                rows="3"
                                class="w-full rounded-core border border-gray-200 px-3 py-2.5 text-sm placeholder-gray-400 focus:border-primary focus:ring-2 focus:ring-primary-surface focus:outline-none"
                                :class="{
                                    'border-red-400': form.errors.address,
                                }"
                            />
                            <span
                                v-if="form.errors.address"
                                class="text-xs text-red-500"
                                >{{ form.errors.address }}</span
                            >
                        </div>

                        <!-- Kontak -->
                        <div class="grid gap-1.5">
                            <Label class="text-sm font-medium">Kontak</Label>
                            <Input
                                v-model="form.contact"
                                placeholder="0857373647"
                            />
                        </div>

                        <!-- Jenis Usaha -->
                        <div class="grid gap-2">
                            <Label class="text-sm font-medium"
                                >Jenis Usaha</Label
                            >

                            <div class="grid grid-cols-3 gap-2">
                                <button
                                    v-for="type in [
                                        'Restoran',
                                        'UMKM',
                                        'Rumah Tangga',
                                    ]"
                                    :key="type"
                                    @click="setType(type as any)"
                                    class="w-full rounded-core border py-2 text-sm font-medium transition"
                                    :class="
                                        selectedType === type
                                            ? 'border-primary bg-primary-surface text-primary'
                                            : 'border-gray-200 bg-white text-gray-600 hover:border-primary'
                                    "
                                >
                                    {{ type }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div
                        class="grid grid-cols-2 gap-3 border-t border-gray-100 p-5"
                    >
                        <Button
                            variant="outline"
                            class="w-full rounded text-gray-600"
                            @click="isFormOpen = false"
                        >
                            Batal
                        </Button>

                        <Button
                            @click="handleSubmit"
                            :disabled="form.processing"
                            class="w-full rounded bg-primary text-white hover:bg-primary-hover"
                        >
                            {{
                                isEditing
                                    ? 'Simpan Perubahan'
                                    : 'Simpan & Pilih'
                            }}
                        </Button>
                    </div>
                </DialogContent>
            </Dialog>

            <!-- Delete Confirmation Modal -->
            <Dialog v-model:open="isDeleteOpen">
                <DialogContent
                    class="overflow-hidden rounded-2xl p-0 sm:max-w-sm"
                >
                    <div class="p-6">
                        <DialogHeader>
                            <DialogTitle class="text-lg font-bold text-gray-900"
                                >Hapus POO</DialogTitle
                            >
                        </DialogHeader>
                        <p class="mt-3 text-sm text-gray-500">
                            Apakah Anda yakin ingin menghapus
                            <span class="font-semibold text-gray-700">{{
                                deletingPoo?.name
                            }}</span
                            >? Tindakan ini tidak dapat dibatalkan.
                        </p>
                    </div>

                    <div
                        class="grid grid-cols-2 gap-3 border-t border-gray-100 p-5"
                    >
                        <Button
                            variant="outline"
                            class="w-full rounded text-gray-600"
                            @click="isDeleteOpen = false"
                        >
                            Batal
                        </Button>
                        <Button
                            @click="handleDelete"
                            class="w-full rounded bg-red-500 text-white hover:bg-red-600"
                        >
                            Hapus
                        </Button>
                    </div>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
