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
        Schema::create('transaksis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_downline')->constrained('downlines')->onDelete('cascade');
            $table->foreignId('id_sales')->constrained('users')->onDelete('cascade');
            $table->string('kode_hari');
            $table->integer('minggu');
            $table->integer('bulan');
            $table->integer('tahun');
            $table->decimal('minus_pagi', 15, 2)->default(0);
            $table->decimal('bayar', 15, 2)->default(0)->nullable();
            $table->decimal('sisa', 15, 2)->default(0)->nullable();
            $table->date('tanggal_transaksi')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksis');
    }
};
