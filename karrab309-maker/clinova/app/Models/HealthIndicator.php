<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthIndicator extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'heart_rate',
        'blood_glucose',
        'blood_pressure_systolic',
        'blood_pressure_diastolic',
        'pain_level',
        'temperature',
        'dressing_status',
        'recorded_at',
        'image_path',
        'recorded_by_user_id',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'blood_glucose' => 'float',
    ];

    protected $appends = ['image_url'];

    /**
     * URL publique de la photo de suivi pansement (pour Dashboard et app mobile).
     */
    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->image_path);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /** Utilisateur ayant saisi les constantes (infirmier). */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function shouldTriggerAlert(): bool
    {
        if ($this->pain_level > 7) {
            return true;
        }

        if ($this->temperature > 38 || $this->temperature < 36.0) {
            return true;
        }

        if ($this->dressing_status === 'Infected') {
            return true;
        }

        if ($this->heart_rate !== null) {
            if ($this->heart_rate > 120 || $this->heart_rate < 45) {
                return true;
            }
        }

        if ($this->blood_glucose !== null) {
            if ($this->blood_glucose > 11.1 || $this->blood_glucose < 3.0) {
                return true;
            }
        }

        if ($this->blood_pressure_systolic !== null && $this->blood_pressure_diastolic !== null) {
            if ($this->blood_pressure_systolic > 180 || $this->blood_pressure_diastolic > 110) {
                return true;
            }
            if ($this->blood_pressure_systolic < 90) {
                return true;
            }
        }

        return false;
    }

    public function getAlertMessage(): ?string
    {
        if ($this->pain_level > 7) {
            return "Douleur élevée : {$this->pain_level}/10";
        }

        if ($this->temperature > 38) {
            return "Température élevée : {$this->temperature}°C (alerte immédiate)";
        }

        if ($this->temperature < 36.0) {
            return "Température basse : {$this->temperature}°C";
        }

        if ($this->dressing_status === 'Infected') {
            return 'Pansement : signe infectieux signalé';
        }

        if ($this->heart_rate !== null && ($this->heart_rate > 120 || $this->heart_rate < 45)) {
            return "Fréquence cardiaque anormale : {$this->heart_rate} bpm";
        }

        if ($this->blood_glucose !== null && ($this->blood_glucose > 11.1 || $this->blood_glucose < 3.0)) {
            return 'Glycémie hors cible : '.$this->blood_glucose.' mmol/L';
        }

        if ($this->blood_pressure_systolic !== null && $this->blood_pressure_diastolic !== null) {
            if ($this->blood_pressure_systolic > 180 || $this->blood_pressure_diastolic > 110) {
                return "Tension élevée : {$this->blood_pressure_systolic}/{$this->blood_pressure_diastolic}";
            }
            if ($this->blood_pressure_systolic < 90) {
                return "Tension basse : {$this->blood_pressure_systolic}/{$this->blood_pressure_diastolic}";
            }
        }

        return null;
    }
}
