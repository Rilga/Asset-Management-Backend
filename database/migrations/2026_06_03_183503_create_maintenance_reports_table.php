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
        Schema::create('maintenance_reports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('maintenance_task_id')
                ->constrained('maintenance_tasks')
                ->cascadeOnDelete();

            $table->foreignId('asset_id')
                ->constrained('assets')
                ->cascadeOnDelete();

            $table->foreignId('mechanic_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('operating_hour_id')
                ->constrained('operating_hours')
                ->cascadeOnDelete();
                    
            $table->date('tanggal_pemeliharaan');

            $table->text('catatan_pemeliharaan')->nullable();
            
            $table->string('bukti_foto')->nullable();

            $table->text('llm_suggestion')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_reports');
    }
};
