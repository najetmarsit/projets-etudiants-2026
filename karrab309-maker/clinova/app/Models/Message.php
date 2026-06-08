<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'content',
        'read_status',
        'attachment_path',
    ];

    protected $casts = [
        'read_status' => 'boolean',
    ];

    protected $appends = ['attachment_url'];

    /**
     * URL publique de la pièce jointe (fichier envoyé au médecin/patient).
     */
    public function getAttachmentUrlAttribute(): ?string
    {
        if (!$this->attachment_path) {
            return null;
        }
        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->attachment_path);
    }

    /**
     * Get the sender of this message.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Get the receiver of this message.
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * Mark message as read
     */
    public function markAsRead(): void
    {
        $this->update(['read_status' => true]);
    }

    /**
     * Check if message is read
     */
    public function isRead(): bool
    {
        return $this->read_status;
    }
}
