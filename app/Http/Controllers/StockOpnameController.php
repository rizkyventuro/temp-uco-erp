<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class StockOpnameController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('stockOpname/ListStockOpname', [
            'opnames'    => [],
            'gudangs'    => [
                ['id' => 1, 'name' => 'Gudang Surabaya'],
                ['id' => 2, 'name' => 'Gudang Jakarta'],
                ['id' => 3, 'name' => 'Gudang Semarang'],
            ],
            'allGudangs' => [
                ['id' => 1, 'name' => 'Gudang Surabaya'],
                ['id' => 2, 'name' => 'Gudang Jakarta'],
                ['id' => 3, 'name' => 'Gudang Semarang'],
            ],
            'filters'    => [
                'search'    => $request->input('search'),
                'perPage'   => $request->input('perPage', 10),
                'status'    => $request->input('status'),
                'gudang_id' => $request->input('gudang_id'),
                'sort'      => $request->input('sort', 'date'),
                'direction' => $request->input('direction', 'desc'),
            ],
        ]);
    }
}
