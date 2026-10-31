<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pembayaran', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->uuid('pesanan_id')->unique();
        $table->unsignedInteger('nominal');
        $table->string('metode')->default('transfer_bank');
        $table->string('status')->default('menunggu_konfirmasi');
        $table->timestamp('dikonfirmasi_pada')->nullable();
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembayaran');
    }
};
