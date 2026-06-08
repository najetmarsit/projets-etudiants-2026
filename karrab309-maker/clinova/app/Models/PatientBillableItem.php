<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientBillableItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'kind',
        'label',
        'amount',
        'performed_at',
        'created_by_user_id',
        'source_type',
        'source_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'performed_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}

