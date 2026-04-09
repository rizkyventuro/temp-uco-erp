<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import type {
    ColumnDef,
    ColumnFiltersState,
    PaginationState,
} from '@tanstack/vue-table';
import {
    FlexRender,
    getCoreRowModel,
    getFilteredRowModel,
    getPaginationRowModel,
    getSortedRowModel,
    useVueTable,
} from '@tanstack/vue-table';
import {
    Plus,
    Pencil,
    Trash2,
    ChevronLeft,
    ChevronRight,
    ArrowUpDown,
} from 'lucide-vue-next';
import { h, ref } from 'vue';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { valueUpdater } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';

interface Station {
    id: number;
    code: string;
    name: string;
    city: string;
    province: string;
}

const props = defineProps<{
    stations: Station[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Stasiun',
        href: '/admin/station',
    },
];

// Modal states
const isCreateOpen = ref(false);
const isEditOpen = ref(false);
const isDeleteOpen = ref(false);
const isConfirmOpen = ref(false);
const confirmAction = ref<'create' | 'edit' | null>(null);

// Form
const form = useForm({
    id: null as number | null,
    code: '',
    name: '',
    city: '',
    province: '',
});

const openCreate = () => {
    form.reset();
    isCreateOpen.value = true;
};

const openEdit = (station: Station) => {
    form.id = station.id;
    form.code = station.code;
    form.name = station.name;
    form.city = station.city;
    form.province = station.province;
    isEditOpen.value = true;
};

const openDelete = (station: Station) => {
    form.id = station.id;
    form.name = station.name;
    isDeleteOpen.value = true;
};

const handleSubmit = (action: 'create' | 'edit') => {
    // Validasi frontend sederhana
    if (!form.code || !form.name || !form.city || !form.province) {
        // Set manual errors untuk field yang kosong
        if (!form.code) form.errors.code = 'Kode Stasiun wajib diisi';
        if (!form.name) form.errors.name = 'Nama Stasiun wajib diisi';
        if (!form.city) form.errors.city = 'Kota wajib diisi';
        if (!form.province) form.errors.province = 'Provinsi wajib diisi';
        return;
    }

    // Clear errors dan tampilkan modal konfirmasi
    form.clearErrors();
    confirmAction.value = action;
    isConfirmOpen.value = true;
};

const confirmSubmit = () => {
    if (confirmAction.value === 'create') {
        form.post('/admin/station', {
            onSuccess: () => {
                isCreateOpen.value = false;
                isConfirmOpen.value = false;
                form.reset();
                toast.success('Berhasil!', {
                    description: 'Data stasiun berhasil ditambahkan',
                });
            },
            onError: () => {
                isConfirmOpen.value = false;
                toast.error('Gagal!', {
                    description: 'Terjadi kesalahan saat menyimpan data',
                });
            },
        });
    } else if (confirmAction.value === 'edit') {
        form.put(`/admin/station/${form.id}`, {
            onSuccess: () => {
                isEditOpen.value = false;
                isConfirmOpen.value = false;
                form.reset();
                toast.success('Berhasil!', {
                    description: 'Data stasiun berhasil diperbarui',
                });
            },
            onError: () => {
                isConfirmOpen.value = false;
                toast.error('Gagal!', {
                    description: 'Terjadi kesalahan saat memperbarui data',
                });
            },
        });
    }
};

const confirmDelete = () => {
    form.delete(`/admin/station/${form.id}`, {
        onSuccess: () => {
            isDeleteOpen.value = false;
            form.reset();
            toast.success('Berhasil!', {
                description: 'Data stasiun berhasil dihapus',
            });
        },
        onError: () => {
            toast.error('Gagal!', {
                description: 'Terjadi kesalahan saat menghapus data',
            });
        },
    });
};

// Table setup
const columns: ColumnDef<Station>[] = [
    {
        accessorKey: 'id',
        header: '#',
        cell: ({ row }) => h('div', { class: 'text-center' }, row.index + 1),
    },
    {
        accessorKey: 'name',
        header: ({ column }) => {
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () =>
                        column.toggleSorting(column.getIsSorted() === 'asc'),
                },
                () => [
                    'Nama Stasiun',
                    h(ArrowUpDown, { class: 'ml-2 h-4 w-4' }),
                ],
            );
        },
    },
    {
        accessorKey: 'code',
        header: ({ column }) => {
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () =>
                        column.toggleSorting(column.getIsSorted() === 'asc'),
                },
                () => [
                    'Kode Stasiun',
                    h(ArrowUpDown, { class: 'ml-2 h-4 w-4' }),
                ],
            );
        },
    },
    {
        accessorKey: 'city',
        header: ({ column }) => {
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () =>
                        column.toggleSorting(column.getIsSorted() === 'asc'),
                },
                () => ['Kota', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })],
            );
        },
    },
    {
        accessorKey: 'province',
        header: ({ column }) => {
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () =>
                        column.toggleSorting(column.getIsSorted() === 'asc'),
                },
                () => ['Provinsi', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })],
            );
        },
    },
    {
        id: 'actions',
        header: () => h('div', { class: 'text-center' }, 'Aksi'),
        cell: ({ row }) => {
            const station = row.original;
            return h('div', { class: 'flex justify-center gap-2' }, [
                h(
                    Button,
                    {
                        variant: 'outline',
                        size: 'sm',
                        onClick: () => openEdit(station),
                    },
                    () => h(Pencil, { class: 'h-4 w-4' }),
                ),
                h(
                    Button,
                    {
                        variant: 'destructive',
                        size: 'sm',
                        onClick: () => openDelete(station),
                    },
                    () => h(Trash2, { class: 'h-4 w-4' }),
                ),
            ]);
        },
    },
];

const columnFilters = ref<ColumnFiltersState>([]);
const pagination = ref<PaginationState>({
    pageIndex: 0,
    pageSize: 10,
});

const table = useVueTable({
    get data() {
        return props.stations;
    },
    columns,
    getCoreRowModel: getCoreRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    onColumnFiltersChange: (updaterOrValue) =>
        valueUpdater(updaterOrValue, columnFilters),
    onPaginationChange: (updaterOrValue) =>
        valueUpdater(updaterOrValue, pagination),
    state: {
        get columnFilters() {
            return columnFilters.value;
        },
        get pagination() {
            return pagination.value;
        },
    },
});
</script>

<template>
    <Head title="Stasiun" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Data Stasiun</h1>
                <Button @click="openCreate">
                    <Plus class="mr-2 h-4 w-4" />
                    Tambah Stasiun Baru
                </Button>
            </div>

            <div class="flex items-center gap-2">
                <Input
                    :model-value="
                        table.getColumn('name')?.getFilterValue() as string
                    "
                    @update:model-value="
                        table.getColumn('name')?.setFilterValue($event)
                    "
                    placeholder="Cari stasiun..."
                    class="max-w-sm"
                />
            </div>

            <div class="rounded-lg border bg-white">
                <Table>
                    <TableHeader>
                        <TableRow
                            v-for="headerGroup in table.getHeaderGroups()"
                            :key="headerGroup.id"
                        >
                            <TableHead
                                v-for="header in headerGroup.headers"
                                :key="header.id"
                            >
                                <FlexRender
                                    v-if="!header.isPlaceholder"
                                    :render="header.column.columnDef.header"
                                    :props="header.getContext()"
                                />
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <template v-if="table.getRowModel().rows?.length">
                            <TableRow
                                v-for="row in table.getRowModel().rows"
                                :key="row.id"
                            >
                                <TableCell
                                    v-for="cell in row.getVisibleCells()"
                                    :key="cell.id"
                                >
                                    <FlexRender
                                        :render="cell.column.columnDef.cell"
                                        :props="cell.getContext()"
                                    />
                                </TableCell>
                            </TableRow>
                        </template>
                        <TableRow v-else>
                            <TableCell
                                :colspan="columns.length"
                                class="h-24 text-center"
                            >
                                Tidak ada data stasiun
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                <div class="flex items-center justify-between px-4 py-4">
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-muted-foreground"
                            >Baris per halaman</span
                        >
                        <select
                            :value="table.getState().pagination.pageSize"
                            @change="
                                (e) =>
                                    table.setPageSize(
                                        Number(
                                            (e.target as HTMLSelectElement)
                                                .value,
                                        ),
                                    )
                            "
                            class="h-9 w-[70px] rounded-md border border-input bg-background px-3 py-1 text-sm"
                        >
                            <option :value="10">10</option>
                            <option :value="20">20</option>
                            <option :value="50">50</option>
                            <option :value="100">100</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-muted-foreground">
                            Halaman
                            {{ table.getState().pagination.pageIndex + 1 }} dari
                            {{ table.getPageCount() }}
                        </span>
                        <div class="flex gap-1">
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="!table.getCanPreviousPage()"
                                @click="table.previousPage()"
                            >
                                <ChevronLeft class="h-4 w-4" />
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="!table.getCanNextPage()"
                                @click="table.nextPage()"
                            >
                                <ChevronRight class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <!-- Create Modal -->
            <Dialog v-model:open="isCreateOpen">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Tambah Stasiun Baru</DialogTitle>
                        <DialogDescription>
                            Isi form di bawah untuk menambahkan stasiun baru
                        </DialogDescription>
                    </DialogHeader>
                    <div class="grid gap-4 py-4">
                        <div class="grid gap-2">
                            <Label for="code"
                                >Kode Stasiun
                                <span class="text-red-500">*</span></Label
                            >
                            <Input
                                id="code"
                                v-model="form.code"
                                placeholder="Contoh: BD"
                                :class="{ 'border-red-500': form.errors.code }"
                            />
                            <span
                                v-if="form.errors.code"
                                class="text-sm text-red-500"
                                >{{ form.errors.code }}</span
                            >
                        </div>
                        <div class="grid gap-2">
                            <Label for="name"
                                >Nama Stasiun
                                <span class="text-red-500">*</span></Label
                            >
                            <Input
                                id="name"
                                v-model="form.name"
                                placeholder="Contoh: Bandung"
                                :class="{ 'border-red-500': form.errors.name }"
                            />
                            <span
                                v-if="form.errors.name"
                                class="text-sm text-red-500"
                                >{{ form.errors.name }}</span
                            >
                        </div>
                        <div class="grid gap-2">
                            <Label for="city"
                                >Kota <span class="text-red-500">*</span></Label
                            >
                            <Input
                                id="city"
                                v-model="form.city"
                                placeholder="Contoh: Bandung"
                                :class="{ 'border-red-500': form.errors.city }"
                            />
                            <span
                                v-if="form.errors.city"
                                class="text-sm text-red-500"
                                >{{ form.errors.city }}</span
                            >
                        </div>
                        <div class="grid gap-2">
                            <Label for="province"
                                >Provinsi
                                <span class="text-red-500">*</span></Label
                            >
                            <Input
                                id="province"
                                v-model="form.province"
                                placeholder="Contoh: Jawa Barat"
                                :class="{
                                    'border-red-500': form.errors.province,
                                }"
                            />
                            <span
                                v-if="form.errors.province"
                                class="text-sm text-red-500"
                                >{{ form.errors.province }}</span
                            >
                        </div>
                    </div>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            @click="isCreateOpen = false"
                            >Batal</Button
                        >
                        <Button
                            @click="handleSubmit('create')"
                            :disabled="form.processing"
                            >Simpan</Button
                        >
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- Edit Modal -->
            <Dialog v-model:open="isEditOpen">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Edit Stasiun</DialogTitle>
                        <DialogDescription>
                            Ubah data stasiun di bawah ini
                        </DialogDescription>
                    </DialogHeader>
                    <div class="grid gap-4 py-4">
                        <div class="grid gap-2">
                            <Label for="edit-code"
                                >Kode Stasiun
                                <span class="text-red-500">*</span></Label
                            >
                            <Input
                                id="edit-code"
                                v-model="form.code"
                                :class="{ 'border-red-500': form.errors.code }"
                            />
                            <span
                                v-if="form.errors.code"
                                class="text-sm text-red-500"
                                >{{ form.errors.code }}</span
                            >
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-name"
                                >Nama Stasiun
                                <span class="text-red-500">*</span></Label
                            >
                            <Input
                                id="edit-name"
                                v-model="form.name"
                                :class="{ 'border-red-500': form.errors.name }"
                            />
                            <span
                                v-if="form.errors.name"
                                class="text-sm text-red-500"
                                >{{ form.errors.name }}</span
                            >
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-city"
                                >Kota <span class="text-red-500">*</span></Label
                            >
                            <Input
                                id="edit-city"
                                v-model="form.city"
                                :class="{ 'border-red-500': form.errors.city }"
                            />
                            <span
                                v-if="form.errors.city"
                                class="text-sm text-red-500"
                                >{{ form.errors.city }}</span
                            >
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-province"
                                >Provinsi
                                <span class="text-red-500">*</span></Label
                            >
                            <Input
                                id="edit-province"
                                v-model="form.province"
                                :class="{
                                    'border-red-500': form.errors.province,
                                }"
                            />
                            <span
                                v-if="form.errors.province"
                                class="text-sm text-red-500"
                                >{{ form.errors.province }}</span
                            >
                        </div>
                    </div>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            @click="isEditOpen = false"
                            >Batal</Button
                        >
                        <Button
                            @click="handleSubmit('edit')"
                            :disabled="form.processing"
                            >Simpan</Button
                        >
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- Delete Confirmation -->
            <Dialog v-model:open="isDeleteOpen">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Hapus Stasiun?</DialogTitle>
                        <DialogDescription>
                            Apakah Anda yakin ingin menghapus stasiun
                            <strong>{{ form.name }}</strong
                            >? Tindakan ini tidak dapat dibatalkan.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            @click="isDeleteOpen = false"
                            >Batal</Button
                        >
                        <Button
                            variant="destructive"
                            @click="confirmDelete"
                            :disabled="form.processing"
                        >
                            Hapus
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- Save Confirmation -->
            <Dialog v-model:open="isConfirmOpen">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Konfirmasi Penyimpanan</DialogTitle>
                        <DialogDescription>
                            Apakah Anda yakin ingin
                            {{
                                confirmAction === 'create'
                                    ? 'menambahkan'
                                    : 'mengubah'
                            }}
                            data stasiun ini? Pastikan semua data sudah benar.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            @click="isConfirmOpen = false"
                            >Batal</Button
                        >
                        <Button
                            @click="confirmSubmit"
                            :disabled="form.processing"
                        >
                            Ya, Simpan
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
