<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BankAccountSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        DB::table('bank_accounts')->insert([
            [
                'id'         => Str::uuid(),
                'code'       => 'KAS-001',
                'name'       => 'Kas Tunai',
                'type'       => 'cash',
                'is_active'  => true,
                'notes'      => 'Kas tunai operasional kantor',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id'         => Str::uuid(),
                'code'       => 'BCA-001',
                'name'       => 'Bank BCA',
                'type'       => 'bank',
                'is_active'  => true,
                'notes'      => 'Rekening operasional utama BCA',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id'         => Str::uuid(),
                'code'       => 'MDR-001',
                'name'       => 'Bank Mandiri',
                'type'       => 'bank',
                'is_active'  => true,
                'notes'      => 'Rekening operasional Mandiri',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
