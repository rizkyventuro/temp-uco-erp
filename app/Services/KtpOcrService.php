<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class KtpOcrService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = env('KTP_OCR_URL', 'http://10.20.0.111:5000');
    }

    public function extract($foto): array
    {
        $response = Http::timeout(120)
            ->attach(
                'foto',
                file_get_contents($foto->getRealPath()),
                $foto->getClientOriginalName() . '.jpg', // pastikan ekstensi jpg
            )
            ->post("{$this->baseUrl}/extract");

        if ($response->failed()) {
            throw new \Exception('OCR service error: ' . $response->body());
        }

        return $response->json();
    }
}