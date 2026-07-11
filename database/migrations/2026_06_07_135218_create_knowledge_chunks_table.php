<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector');

        Schema::create('knowledge_chunks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('knowledge_document_id')
                ->constrained('knowledge_documents')
                ->cascadeOnDelete();

            $table->foreignId('asset_id')
                ->nullable()
                ->constrained('assets')
                ->nullOnDelete();

            $table->longText('chunk_text');

            $table->jsonb('metadata')->nullable();

            $table->timestamps();
        });

        DB::statement('ALTER TABLE knowledge_chunks ADD COLUMN embedding vector(768)');

        DB::statement(
            'CREATE INDEX knowledge_chunks_embedding_idx 
            ON knowledge_chunks 
            USING ivfflat (embedding vector_cosine_ops) 
            WITH (lists = 100)'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('knowledge_chunks');
    }
};
