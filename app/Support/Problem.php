<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class Problem
{
    private const BASE = 'https://auctora.example/problems/';

    public static function buat(Request $request, string $slug, string $title, int $status, string $detail, array $tambahan = []): JsonResponse
    {
        return response()->json(array_merge([
            'type' => self::BASE.$slug,
            'title' => $title,
            'status' => $status,
            'detail' => $detail,
            'instance' => $request->getPathInfo(),
        ], $tambahan), $status, ['Content-Type' => 'application/problem+json']);
    }

    public static function tidakDitemukan(Request $request, string $title, string $detail): JsonResponse
    {
        return self::buat($request, 'tidak-ditemukan', $title, 404, $detail);
    }

    public static function konflik(Request $request, string $slug, string $title, string $detail): JsonResponse
    {
        return self::buat($request, $slug, $title, 409, $detail);
    }

    public static function validasi(Request $request, ValidationException $e): JsonResponse
    {
        $errors = [];
        foreach ($e->errors() as $field => $daftarPesan) {
            foreach ($daftarPesan as $pesan) {
                $errors[] = ['field' => $field, 'pesan' => $pesan];
            }
        }

        return self::buat($request, 'validasi-gagal', 'Data tidak valid', 422,
            'Data yang dikirim tidak valid.', ['errors' => $errors]);
    }
}