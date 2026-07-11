<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // The ivfflat index clusters vectors into 100 lists, but the current
        // knowledge base only has a handful of chunks. With that little data
        // spread across 100 lists, Postgres' default probes=1 only scans one
        // (mostly empty) list, so real matches get missed almost entirely.
        // Drop it and rely on an exact sequential scan, which stays fast at
        // this scale; re-add an ANN index once the table has grown enough
        // (thousands of rows) to actually benefit from it.
        DB::statement('DROP INDEX IF EXISTS knowledge_chunks_embedding_idx');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement(
            'CREATE INDEX knowledge_chunks_embedding_idx
            ON knowledge_chunks
            USING ivfflat (embedding vector_cosine_ops)
            WITH (lists = 100)'
        );
    }
};
