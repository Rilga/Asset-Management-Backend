<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RetrievalLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_history_id',
        'user_id',
        'query',
        'top_k_results',
        'top_k',
        'retrieval_time_ms',
    ];

    protected $casts = [
        'top_k_results' => 'array',
    ];

    public function chatHistory()
    {
        return $this->belongsTo(ChatHistory::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}