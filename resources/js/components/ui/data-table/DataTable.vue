<script setup lang="ts" generic="TData, TValue">
import { ref } from 'vue';
import {
    FlexRender,
    getCoreRowModel,
    getFilteredRowModel,
    getPaginationRowModel,
    getSortedRowModel,
    useVueTable,
    type ColumnDef,
} from '@tanstack/vue-table';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';

interface DataTableProps {
    columns: ColumnDef<TData, TValue>[];
    data: TData[];
    searchPlaceholder?: string;
}

const props = withDefaults(defineProps<DataTableProps>(), {
    searchPlaceholder: 'Cari...',
});

const globalFilter = ref('');
const pagination = ref({
    pageIndex: 0,
    pageSize: 10,
});

const table = useVueTable({
    get data() {
        return props.data;
    },
    get columns() {
        return props.columns;
    },
    getCoreRowModel: getCoreRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    state: {
        get globalFilter() {
            return globalFilter.value;
        },
        get pagination() {
            return pagination.value;
        },
    },
    onGlobalFilterChange: (value) => {
        globalFilter.value = value;
    },
    onPaginationChange: (updater) => {
        if (typeof updater === 'function') {
            pagination.value = updater(pagination.value);
        } else {
            pagination.value = updater;
        }
    },
});
</script>

<template>
    <div class="space-y-4">
        <Input
            v-model="globalFilter"
            :placeholder="searchPlaceholder"
            class="max-w-sm"
        />

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
                            <slot name="empty">Tidak ada data</slot>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>

            <div class="flex items-center justify-between px-4 py-4">
                <div class="flex items-center gap-2">
                    <span class="text-sm text-muted-foreground">Baris per halaman</span>
                    <select
                        :value="table.getState().pagination.pageSize"
                        @change="(e) => table.setPageSize(Number((e.target as HTMLSelectElement).value))"
                        class="h-9 w-[70px] rounded-md border border-input bg-background px-3 py-1 text-sm"
                    >
                        <option value="10">10</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-muted-foreground">
                        Halaman {{ table.getState().pagination.pageIndex + 1 }} dari {{ table.getPageCount() }}
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
</template>
