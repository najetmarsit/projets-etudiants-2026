<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    use HasFactory;

    /**
     * Normalise le CIN / identifiant national : alphanumérique seul, majuscules (aligné avec la validation API).
     */
    public static function normalizeNationalId(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $value));
    }

    protected $fillable = [
        'user_id',
        'assigned_doctor_id',
        'assigned_nurse_id',
        'first_name',
        'last_name',
        'birth_date',
        'national_id',
        'age',
        'gender',
        'phone',
        'address',
        'medical_history',
        'status',
        'room_number',
        'bed_number',
        'chamber_number',
        'diagnosis',
        'current_illness',
        'prescribed_treatment',
        'doctor_observations',
        'pre_op_report',
        'post_op_report',
        'qr_public_token',
        'admission_at',
        'discharge_at',
        'billing_notes',
        'billing_total_due',
        'billing_breakdown',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'admission_at' => 'datetime',
        'discharge_at' => 'datetime',
        'billing_total_due' => 'decimal:2',
        'billing_breakdown' => 'array',
    ];

    /**
     * Get the user associated with this patient.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Médecin principal assigné par l'admin.
     */
    public function assignedDoctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_doctor_id');
    }

    /**
     * Infirmier assigné (optionnel).
     */
    public function assignedNurse(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_nurse_id');
    }

    /**
     * Get the operations for this patient.
     */
    public function operations(): HasMany
    {
        return $this->hasMany(Operation::class);
    }

    /**
     * Get the health indicators for this patient.
     */
    public function healthIndicators(): HasMany
    {
        return $this->hasMany(HealthIndicator::class);
    }

    /**
     * Get the alerts for this patient.
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    /**
     * Get the reports for this patient.
     */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    /**
     * Analyses PDF envoyées par le laboratoire vers le dossier patient.
     */
    public function labDocuments(): HasMany
    {
        return $this->hasMany(LabDocument::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function labAppointments(): HasMany
    {
        return $this->hasMany(LabAppointment::class);
    }

    /**
     * Lignes d'actes facturables (bilan automatique).
     */
    public function billableItems(): HasMany
    {
        return $this->hasMany(PatientBillableItem::class);
    }
}
