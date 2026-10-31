<?php

namespace Tests\Feature;

use App\Models\Pembayaran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PembayaranTest extends TestCase
{
    use RefreshDatabase;

    private const PESANAN_ID = '5f1c2a6e-8a47-4d52-9b1e-3c7f0d2a9e11';

    private function payload(): array
    {
        return ['pesanan_id' => self::PESANAN_ID, 'nominal' => 185000, 'metode' => 'transfer_bank'];
    }

    public function test_membuat_pembayaran_berhasil(): void
    {
        Http::fake(['*/pesanan/*' => Http::response(['id' => self::PESANAN_ID], 200)]);

        $respons = $this->postJson('/api/v1/pembayaran', $this->payload());

        $respons->assertCreated()
            ->assertJsonPath('status', 'menunggu_konfirmasi')
            ->assertJsonPath('nominal', 185000)
            ->assertJsonPath('dikonfirmasi_pada', null);
        $this->assertDatabaseHas('pembayaran', ['pesanan_id' => self::PESANAN_ID]);
    }

    public function test_pesanan_tidak_ditemukan_menghasilkan_404_problem_details(): void
    {
        Http::fake(['*/pesanan/*' => Http::response(null, 404)]);

        $respons = $this->postJson('/api/v1/pembayaran', $this->payload());

        $respons->assertNotFound()->assertJsonPath('status', 404);
        $this->assertStringStartsWith('application/problem+json', $respons->headers->get('Content-Type'));
    }

    public function test_data_tidak_valid_menghasilkan_422(): void
    {
        $respons = $this->postJson('/api/v1/pembayaran', []);

        $respons->assertStatus(422)->assertJsonStructure([
            'type', 'title', 'status', 'detail',
            'errors' => [['field', 'pesan']],
        ]);
    }

    public function test_pembayaran_ganda_untuk_satu_pesanan_menghasilkan_409(): void
    {
        Http::fake(['*/pesanan/*' => Http::response(['id' => self::PESANAN_ID], 200)]);
        Pembayaran::factory()->create(['pesanan_id' => self::PESANAN_ID]);

        $this->postJson('/api/v1/pembayaran', $this->payload())
            ->assertStatus(409)
            ->assertJsonPath('status', 409);
    }

    public function test_mengambil_satu_pembayaran(): void
    {
        $pembayaran = Pembayaran::factory()->create();

        $this->getJson("/api/v1/pembayaran/{$pembayaran->id}")
            ->assertOk()
            ->assertJsonPath('id', $pembayaran->id);
    }

    public function test_pembayaran_tidak_ada_menghasilkan_404(): void
    {
        $this->getJson('/api/v1/pembayaran/0b6d1e7c-4f2a-4a3e-8f55-1d9c7a2b3e44')
            ->assertNotFound()
            ->assertJsonPath('status', 404);
    }
}