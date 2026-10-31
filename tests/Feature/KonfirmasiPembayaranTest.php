<?php

namespace Tests\Feature;

use App\Models\Pembayaran;
use App\Services\EventPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class KonfirmasiPembayaranTest extends TestCase
{
    use RefreshDatabase;

    private const BARANG_ID = 'a91e3c55-0d4b-4c1a-8e72-6f3b9d1a2c07';

    private function fakePesanan(string $pesananId): void
    {
        Http::fake(['*/pesanan/*' => Http::response([
            'id' => $pesananId,
            'item' => [['barang_id' => self::BARANG_ID, 'jumlah' => 2]],
        ], 200)]);
    }

    public function test_konfirmasi_berhasil_dan_menerbitkan_event(): void
    {
        $pembayaran = Pembayaran::factory()->create(['nominal' => 185000]);
        $this->fakePesanan($pembayaran->pesanan_id);

        $this->mock(EventPublisher::class, function (MockInterface $mock) use ($pembayaran) {
            $mock->shouldReceive('publish')->once()->with(
                'pembayaran.dikonfirmasi',
                Mockery::on(fn ($p) => $p['nama_event'] === 'PembayaranDikonfirmasi'
                    && $p['versi'] === 1
                    && $p['data']['pembayaran_id'] === $pembayaran->id
                    && $p['data']['pesanan_id'] === $pembayaran->pesanan_id
                    && $p['data']['nominal'] === 185000
                    && $p['data']['item'][0]['barang_id'] === self::BARANG_ID
                    && $p['data']['item'][0]['jumlah'] === 2)
            );
        });

        $this->postJson("/api/v1/pembayaran/{$pembayaran->id}/konfirmasi", ['nominal_diterima' => 185000])
            ->assertOk()
            ->assertJsonPath('status', 'dikonfirmasi');

        $this->assertDatabaseHas('pembayaran', ['id' => $pembayaran->id, 'status' => 'dikonfirmasi']);
    }

    public function test_nominal_tidak_sesuai_menghasilkan_422_tanpa_event(): void
    {
        $pembayaran = Pembayaran::factory()->create(['nominal' => 185000]);
        $this->mock(EventPublisher::class, fn (MockInterface $m) => $m->shouldNotReceive('publish'));

        $this->postJson("/api/v1/pembayaran/{$pembayaran->id}/konfirmasi", ['nominal_diterima' => 100000])
            ->assertStatus(422)
            ->assertJsonPath('errors.0.field', 'nominal_diterima');

        $this->assertDatabaseHas('pembayaran', ['id' => $pembayaran->id, 'status' => 'menunggu_konfirmasi']);
    }

    public function test_konfirmasi_kedua_menghasilkan_409_tanpa_event(): void
    {
        $pembayaran = Pembayaran::factory()->create([
            'nominal' => 185000,
            'status' => 'dikonfirmasi',
            'dikonfirmasi_pada' => now(),
        ]);
        $this->mock(EventPublisher::class, fn (MockInterface $m) => $m->shouldNotReceive('publish'));

        $this->postJson("/api/v1/pembayaran/{$pembayaran->id}/konfirmasi", ['nominal_diterima' => 185000])
            ->assertStatus(409)
            ->assertJsonPath('status', 409);
    }

    public function test_pembayaran_tidak_ada_menghasilkan_404(): void
    {
        $this->postJson('/api/v1/pembayaran/0b6d1e7c-4f2a-4a3e-8f55-1d9c7a2b3e44/konfirmasi', ['nominal_diterima' => 185000])
            ->assertNotFound()
            ->assertJsonPath('status', 404);
    }
}