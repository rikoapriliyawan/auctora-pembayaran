<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pembayaran extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'pembayaran';

    protected $fillable = [
        'pesanan_id',
        'nominal',
        'metode',
        'status',
        'dikonfirmasi_pada',
    ];

    protected function casts(): array
    {
        return [
            'nominal' => 'integer',
            'dikonfirmasi_pada' => 'datetime',
        ];
    }
}