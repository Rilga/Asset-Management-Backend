<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RetrievalEvaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'question',
        'expected_source_type',
        'expected_source_id',
        'retrieved_results',
        'top_k',
        'context_precision',
        'context_recall',
        'retrieval_time_ms',
    ];

    protected $casts = [
        'retrieved_results' => 'array',
        'context_precision' => 'float',
        'context_recall' => 'float',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}