<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use App\Services\PesananClient;
use App\Support\Problem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PembayaranController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => ['sometimes', Rule::in(['menunggu_konfirmasi', 'dikonfirmasi'])],
        ]);

        $query = Pembayaran::query()->orderBy('created_at');
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        return response()->json([
            'data' => $query->get()->map(fn ($p) => $this->bentuk($p))->all(),
        ]);
    }

    public function store(Request $request, PesananClient $pesanan): JsonResponse
    {
        $data = $request->validate([
            'pesanan_id' => ['required', 'uuid'],
            'nominal' => ['required', 'integer', 'min:1'],
            'metode' => ['required', Rule::in(['transfer_bank'])],
        ]);

        if ($pesanan->ambil($data['pesanan_id']) === null) {
            return Problem::tidakDitemukan($request, 'Pesanan tidak ditemukan',
                "Tidak ada pesanan dengan id {$data['pesanan_id']}.");
        }

        if (Pembayaran::where('pesanan_id', $data['pesanan_id'])->exists()) {
            return Problem::konflik($request, 'pembayaran-sudah-ada', 'Pembayaran sudah ada',
                'Pesanan ini sudah memiliki pembayaran.');
        }

        $pembayaran = Pembayaran::create($data + ['status' => 'menunggu_konfirmasi']);

        return response()->json($this->bentuk($pembayaran), 201)
            ->header('Location', url("/api/v1/pembayaran/{$pembayaran->id}"));
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $pembayaran = Pembayaran::find($id);

        if ($pembayaran === null) {
            return Problem::tidakDitemukan($request, 'Pembayaran tidak ditemukan',
                "Tidak ada pembayaran dengan id {$id}.");
        }

        return response()->json($this->bentuk($pembayaran));
    }

    private function bentuk(Pembayaran $p): array
    {
        return [
            'id' => $p->id,
            'pesanan_id' => $p->pesanan_id,
            'nominal' => $p->nominal,
            'metode' => $p->metode,
            'status' => $p->status,
            'dibuat_pada' => $p->created_at->toIso8601ZuluString(),
            'dikonfirmasi_pada' => $p->dikonfirmasi_pada?->toIso8601ZuluString(),
        ];
    }
}