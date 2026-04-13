<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\DefaultImporter;

class ReferensiLocationSeeder extends Seeder
{
    public function run(): void
    {
        // Provinsi
        $this->doExport('provinsi.xlsx', 'provinces', [
            'reference_id' => 1,
            'name'         => 0,
        ]);

        $provinces = DB::table('provinces')->get()->keyBy('reference_id');

        // Kota / Kabupaten
        $this->doExport('kabupaten.xlsx', 'cities', function ($item) use ($provinces) {
            $kode_parent     = substr($item[1], 0, 2);
            $selected_parent = $provinces[$kode_parent] ?? null;

            if (! $selected_parent) return null;

            return [
                'reference_id' => $item[1],
                'name'         => $item[0],
                'province_id'  => $selected_parent->id,
            ];
        });
    }

    public function doExport(string $filename, string $table, array|callable $column, int|false $limit = false): void
    {
        $data = Excel::toArray(new DefaultImporter, public_path('data-master/' . $filename))[0];

        // Hapus header row
        unset($data[0]);
        $data = collect(array_values($data));

        if (is_array($column)) {
            $map = $data->map(function ($item) use ($column) {
                $row = [];
                foreach ($column as $key => $index) {
                    $row[$key] = $item[$index];
                }
                return $row;
            });
        } else {
            $map = $data->map($column);
        }

        if ($limit !== false) {
            $map = $map->take($limit);
        }

        // Filter null (parent tidak ditemukan)
        $map = $map->filter()->values();

        foreach ($map->chunk(1000) as $chunk) {
            DB::table($table)->insert($chunk->toArray());
        }
    }
}
