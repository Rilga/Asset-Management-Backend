<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retrieval_evaluations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('asset_id')
                ->nullable()
                ->constrained('assets')
                ->nullOnDelete();

            $table->text('question');

            $table->string('expected_source_type')->nullable();
            $table->unsignedBigInteger('expected_source_id')->nullable();

            $table->jsonb('retrieved_results')->nullable();

            $table->integer('top_k')->default(5);

            $table->decimal('context_precision', 5, 2)->nullable();
            $table->decimal('context_recall', 5, 2)->nullable();

            $table->integer('retrieval_time_ms')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retrieval_evaluations');
    }
};