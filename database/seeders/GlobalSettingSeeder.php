<?php

namespace Database\Seeders;

use App\Models\GlobalSetting;
use Illuminate\Database\Seeder;

class GlobalSettingSeeder extends Seeder
{
    public function run(): void
    {
        GlobalSetting::set(
            'verification',
            [
                'mode'   => 'manual', // 'manual' | 'auto'
            ],
            'Pengaturan mode verifikasi user'
        );
    }
}
