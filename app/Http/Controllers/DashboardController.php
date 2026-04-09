<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('admin/Dashboard', $this->dataDashboard());
    }

    private function dataDashboard(): array
    {
        $totalUcoMasuk = [
            'value'  => '32,500 kg',
            'trend'  => '+8.6% from last month',
            'up'     => true,
        ];

        $totalPembelian = [
            'value'  => 'Rp 242,900,000',
            'trend'  => '+12.3% from last month',
            'up'     => true,
        ];

        $totalUtangAP = [
            'value'  => 'Rp 93,750,000',
            'trend'  => 'Outstanding payments',
            'up'     => null,
        ];

        $supplierAktif = [
            'value'  => '24',
            'trend'  => '+3 new this month',
            'up'     => true,
        ];

        $volumeChart = [
            ['label' => 'Jan',  'pembelian' => 30000, 'penjualan' => 21000],
            ['label' => 'Feb',  'pembelian' => 11000, 'penjualan' => 8000],
            ['label' => 'Mar',  'pembelian' => 5000,  'penjualan' => 3000],
            ['label' => 'Apr',  'pembelian' => 14000, 'penjualan' => 11000],
            ['label' => 'Mei',  'pembelian' => 24000, 'penjualan' => 16000],
            ['label' => 'Jun',  'pembelian' => 27000, 'penjualan' => 32000],
            ['label' => 'Jul',  'pembelian' => 25000, 'penjualan' => 27000],
            ['label' => 'Agu',  'pembelian' => 26000, 'penjualan' => 10000],
            ['label' => 'Sep',  'pembelian' => 12000, 'penjualan' => 14000],
            ['label' => 'Okt',  'pembelian' => 14000, 'penjualan' => 12000],
            ['label' => 'Nov',  'pembelian' => 23000, 'penjualan' => 8000],
            ['label' => 'Des',  'pembelian' => 18000, 'penjualan' => 33000],
        ];

        $distribusiStok = [
            ['label' => 'Gudang A', 'value' => 25, 'color' => '#22d3a5'],
            ['label' => 'Gudang B', 'value' => 25, 'color' => '#ef4444'],
            ['label' => 'Gudang C', 'value' => 25, 'color' => '#a855f7'],
            ['label' => 'Gudang D', 'value' => 25, 'color' => '#f97316'],
            ['label' => 'Gudang E', 'value' => 10, 'color' => '#3b82f6'],
        ];

        $recentTransactions = collect(range(1, 5))->map(fn($i) => [
            'id'        => '#GR024-000' . $i,
            'tanggal'   => '03 Apr 2024',
            'supplier'  => 'PT Sumber Rejeki',
            'volume'    => '8.000 kg',
            'gudang'    => 'Gudang Surabaya',
            'status'    => 'Lunas',
        ]);

        return compact(
            'totalUcoMasuk',
            'totalPembelian',
            'totalUtangAP',
            'supplierAktif',
            'volumeChart',
            'distribusiStok',
            'recentTransactions',
        );
    }
}