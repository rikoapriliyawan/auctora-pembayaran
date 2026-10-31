<?php

namespace Database\Factories;

use App\Models\Pembayaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pembayaran>
 */
class PembayaranFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
        'pesanan_id' => fake()->uuid(),
        'nominal' => fake()->numberBetween(50000, 500000),
        'metode' => 'transfer_bank',
        'status' => 'menunggu_konfirmasi',
        'dikonfirmasi_pada' => null,
        ];
    }
}
