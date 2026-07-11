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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();

            $table->string('kategori');
            $table->string('nomor_peralatan')->unique();
            $table->string('nama_mesin');
            $table->string('area_mesin');
            $table->string('merek')->nullable();
            $table->year('tahun_pembelian')->nullable();

            $table->string('foto_kondisi')->nullable();
            $table->string('manual_book')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
