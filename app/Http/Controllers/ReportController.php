<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('report/indexReport', [
            'chartData' => [
                ['label' => 'Jan',  'pendapatan' => 120, 'hpp' => 95,  'laba_bersih' => 100],
                ['label' => 'Feb',  'pendapatan' => 135, 'hpp' => 100, 'laba_bersih' => 110],
                ['label' => 'Mar',  'pendapatan' => 150, 'hpp' => 110, 'laba_bersih' => 95],
                ['label' => 'Apr',  'pendapatan' => 140, 'hpp' => 105, 'laba_bersih' => 130],
                ['label' => 'May',  'pendapatan' => 130, 'hpp' => 90,  'laba_bersih' => 105],
                ['label' => 'Jun',  'pendapatan' => 125, 'hpp' => 95,  'laba_bersih' => 90],
                ['label' => 'Jul',  'pendapatan' => 145, 'hpp' => 100, 'laba_bersih' => 115],
                ['label' => 'Aug',  'pendapatan' => 155, 'hpp' => 110, 'laba_bersih' => 100],
                ['label' => 'Sep',  'pendapatan' => 140, 'hpp' => 105, 'laba_bersih' => 120],
                ['label' => 'Oct',  'pendapatan' => 130, 'hpp' => 95,  'laba_bersih' => 110],
                ['label' => 'Nov',  'pendapatan' => 150, 'hpp' => 115, 'laba_bersih' => 125],
                ['label' => 'Dec',  'pendapatan' => 160, 'hpp' => 120, 'laba_bersih' => 135],
            ],
        ]);
    }
}
