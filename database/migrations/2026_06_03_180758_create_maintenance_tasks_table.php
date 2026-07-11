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
        Schema::create('maintenance_tasks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('asset_id')
                ->constrained('assets')
                ->cascadeOnDelete();

            $table->foreignId('assigned_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('assigned_to')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->date('tanggal_tugas');
            $table->text('catatan_tugas')->nullable();

            $table->enum('status', [
                'assigned',
                'in_progress',
                'submitted',
                'completed',
                'cancelled',
            ])->default('assigned');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_tasks');
    }
};
