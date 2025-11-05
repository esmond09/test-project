<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FileUpload extends Model
{
    protected $fillable = [
        'filename',
        'original_filename',
        'status',
        'total_rows',
        'processed_rows',
        'error_message',
        'user_id',
    ];

    protected $casts = [
        'total_rows' => 'integer',
        'processed_rows' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
