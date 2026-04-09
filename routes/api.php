<?php

use App\Http\Controllers\UCOBatchController;
use App\Models\City;
use App\Models\Province;
use Illuminate\Support\Facades\Route;

// // QR Code endpoints (stateless, no auth required)
// Route::get('/qr/generate/{batchCode}', [UCOBatchController::class, 'apiGenerate']);
// Route::post('/qr/verify', [UCOBatchController::class, 'apiVerify']);

Route::get('/cities', function (\Illuminate\Http\Request $request) {
    $province = Province::where('referensi_id', $request->province_referensi_id)->first();
    if (! $province) return response()->json([]);

    return City::where('province_id', $province->id)
        ->orderBy('nama')
        ->get(['referensi_id', 'nama']);
});
