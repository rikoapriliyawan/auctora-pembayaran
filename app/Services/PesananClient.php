<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PesananClient
{
    public function ambil(string $id): ?array
    {
        $respons = Http::baseUrl(config('services.pesanan.url'))
            ->acceptJson()
            ->timeout(5)
            ->get("/pesanan/{$id}");

        if ($respons->status() === 404) {
            return null;
        }

        $respons->throw();

        return $respons->json();
    }
}