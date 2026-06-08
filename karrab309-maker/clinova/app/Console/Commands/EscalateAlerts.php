<?php

namespace App\Console\Commands;

use App\Enums\AlertStatus;
use App\Models\Alert;
use App\Models\DoctorAvailability;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EscalateAlerts extends Command
{
    protected $signature = 'alerts:escalate {--minutes=5 : Délai (minutes) avant escalade si non ACK}';

    protected $description = 'Réassigne automatiquement les alertes urgentes non prises en charge.';

    public function handle(): int
    {
        $minutes = max(1, (int) $this->option('minutes'));
        $deadline = now()->subMinutes($minutes);

        $alerts = Alert::query()
            ->where('status', AlertStatus::Sent)
            ->where('priority', 'urgent')
            ->where('created_at', '<=', $deadline)
            ->orderBy('created_at')
            ->limit(100)
            ->get();

        $count = 0;

        foreach ($alerts as $alert) {
            $newDoctorId = $this->pickDoctorId((int) $alert->assigned_doctor_id);
            if (! $newDoctorId) {
                continue;
            }

            DB::transaction(function () use ($alert, $newDoctorId, &$count) {
                $alert->refresh();
                if ($alert->status !== AlertStatus::Sent || $alert->priority !== 'urgent') {
                    return;
                }

                if ((int) $alert->assigned_doctor_id === (int) $newDoctorId) {
                    return;
                }

                $alert->forceFill([
                    'assigned_doctor_id' => $newDoctorId,
                    'assigned_at' => now(),
                    'escalated_at' => now(),
                    'reassigned_count' => ((int) $alert->reassigned_count) + 1,
                    'status' => AlertStatus::Escalated,
                ])->save();

                $count++;
            });
        }

        $this->info("Escalated: {$count}");
        return self::SUCCESS;
    }

    private function pickDoctorId(int $excludeDoctorId = 0): ?int
    {
        // médecins "disponibles" = available ou on_call, vus récemment
        $seenDeadline = now()->subMinutes(15);

        $candidateIds = DoctorAvailability::query()
            ->whereIn('status', ['available', 'on_call'])
            ->where(function ($q) use ($seenDeadline) {
                $q->whereNull('last_seen_at')->orWhere('last_seen_at', '>=', $seenDeadline);
            })
            ->pluck('doctor_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0 && $id !== $excludeDoctorId)
            ->values()
            ->all();

        if (empty($candidateIds)) {
            return null;
        }

        // choisir le médecin avec le moins d'alertes urgentes actives
        $loads = Alert::query()
            ->select('assigned_doctor_id', DB::raw('COUNT(*) as c'))
            ->whereIn('status', [AlertStatus::Sent->value, AlertStatus::Escalated->value, AlertStatus::Acknowledged->value, AlertStatus::InProgress->value])
            ->where('priority', 'urgent')
            ->whereIn('assigned_doctor_id', $candidateIds)
            ->groupBy('assigned_doctor_id')
            ->pluck('c', 'assigned_doctor_id')
            ->toArray();

        $bestId = null;
        $bestLoad = PHP_INT_MAX;
        foreach ($candidateIds as $id) {
            $load = (int) ($loads[$id] ?? 0);
            if ($load < $bestLoad) {
                $bestLoad = $load;
                $bestId = $id;
            }
        }

        // garder une safety check: le user doit être Doctor
        if (! $bestId) {
            return null;
        }
        $u = User::find($bestId);
        return ($u && $u->isDoctor()) ? $bestId : null;
    }
}

