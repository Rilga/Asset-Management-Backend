<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retrieval_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('chat_history_id')
                ->nullable()
                ->constrained('chat_histories')
                ->nullOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('query');

            $table->jsonb('top_k_results')->nullable();

            $table->integer('top_k')->default(5);

            $table->integer('retrieval_time_ms')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retrieval_logs');
    }
};