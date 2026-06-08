<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'audience',
        'recipient_user_id',
        'channel',
        'type',
        'title',
        'body',
        'priority',
        'data',
        'read_at',
        'acknowledged_at',
        'created_by_user_id',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'acknowledged_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}

