<?php

namespace App\Models;

use App\Enums\AlertStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'assigned_doctor_id',
        'indicator_type',
        'value',
        'priority',
        'message',
        'status',
        'acknowledged_at',
        'assigned_at',
        'escalated_at',
        'reassigned_count',
    ];

    protected $casts = [
        'acknowledged_at' => 'datetime',
        'assigned_at' => 'datetime',
        'escalated_at' => 'datetime',
        'status' => AlertStatus::class,
    ];

    /**
     * Get the patient for this alert.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function assignedDoctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_doctor_id');
    }

    /**
     * Mark alert as acknowledged
     */
    public function acknowledge(): void
    {
        $status = $this->status instanceof AlertStatus ? $this->status : AlertStatus::tryFrom((string) $this->status);

        if ($status === AlertStatus::Resolved || $status === AlertStatus::Expired || $status === AlertStatus::Cancelled) {
            return;
        }

        $this->forceFill([
            'status' => AlertStatus::Acknowledged,
            'acknowledged_at' => $this->acknowledged_at ?: now(),
        ])->save();
    }

    /**
     * Check if alert is sent (new)
     */
    public function isSent(): bool
    {
        return (($this->status instanceof AlertStatus) ? $this->status : AlertStatus::tryFrom((string) $this->status)) === AlertStatus::Sent;
    }

    /**
     * Check if alert is acknowledged
     */
    public function isAcknowledged(): bool
    {
        return (($this->status instanceof AlertStatus) ? $this->status : AlertStatus::tryFrom((string) $this->status)) === AlertStatus::Acknowledged;
    }
}
