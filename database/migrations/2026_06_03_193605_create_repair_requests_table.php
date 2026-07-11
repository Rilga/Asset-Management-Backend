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
        Schema::create('repair_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('asset_id')
                ->constrained('assets')
                ->cascadeOnDelete();

            $table->foreignId('mechanic_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->enum('kondisi_perbaikan', [
                'ringan',
                'sedang',
                'berat',
            ]);

            $table->text('catatan_kerusakan');

            $table->string('bukti_foto')->nullable();

            $table->enum('status_verifikasi', [
                'pending',
                'approved',
                'rejected',
            ])->default('pending');

            $table->foreignId('verified_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('verified_at')->nullable();
            $table->text('catatan_verifikasi')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repair_requests');
    }
};
