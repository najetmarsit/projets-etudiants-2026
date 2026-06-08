<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\InventoryMovement;
use App\Models\LabAppointment;
use App\Models\LabDocument;
use App\Models\Operation;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\SpecialistAppointment;
use App\Models\User;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    private CacheService $cache;

    public function __construct(CacheService $cache)
    {
        $this->cache = $cache;
    }

    public function stats(Request $request)
    {
        $user = Auth::user();

        if (!$user->isAdmin() && !$user->isDoctor()) {
            return $this->unauthorized();
        }

        $data = $this->cache->remember($this->cache->getDashboardStatsKey(), function () use ($user) {
            $patientsCount = Patient::count();
            $doctorsCount = User::where('role', 'Doctor')->count();
            $operationsCount = Operation::count();
            $alertsCount = Alert::where('status', '!=', 'acknowledged')->count();
            $secretariesCount = User::where('role', 'Secretary')->count();

            $data = [
                'patients' => $patientsCount,
                'doctors' => $doctorsCount,
                'appointments' => $operationsCount,
                'alerts' => $alertsCount,
                'secretaries' => $secretariesCount,
            ];

            if ($user->isAdmin()) {
                $today = now()->startOfDay();
                $endToday = now()->endOfDay();
                $monthStart = now()->copy()->startOfMonth();
                $monthEnd = now()->copy()->endOfMonth();

                $data['payments_today'] = round((float) Payment::query()
                    ->where('status', 'paid')
                    ->whereBetween('paid_at', [$today, $endToday])
                    ->sum('amount'), 2);

                $data['payments_month'] = round((float) Payment::query()
                    ->where('status', 'paid')
                    ->whereBetween('paid_at', [$monthStart, $monthEnd])
                    ->sum('amount'), 2);

                $data['recent_payments'] = Payment::with(['patient.user:id,name', 'recordedBy:id,name'])
                    ->orderByRaw('COALESCE(paid_at, created_at) DESC')
                    ->orderByDesc('id')
                    ->limit(10)
                    ->get(['id', 'patient_id', 'amount', 'total_amount', 'currency', 'paid_at', 'status', 'provider', 'receipt_number', 'recorded_by', 'created_at']);
            }

            return $data;
        }, 120);

        return $this->success($data);
    }

    public function chartData(Request $request)
    {
        $user = Auth::user();

        if (!$user->isAdmin() && !$user->isDoctor()) {
            return $this->unauthorized();
        }

        $days = (int) $request->get('days', 30);
        $days = min(max($days, 7), 90);

        $result = $this->cache->remember(
            $this->cache->getDashboardChartKey($days),
            function () use ($days) {
                $start = now()->subDays($days);

                $operationsByDay = Operation::query()
                    ->where('operation_date', '>=', $start->format('Y-m-d'))
                    ->select(DB::raw('DATE(operation_date) as date'), DB::raw('COUNT(*) as count'))
                    ->groupBy('date')
                    ->orderBy('date')
                    ->pluck('count', 'date')
                    ->toArray();

                $labels = [];
                $values = [];
                for ($i = $days - 1; $i >= 0; $i--) {
                    $d = now()->subDays($i)->format('Y-m-d');
                    $labels[] = Carbon::parse($d)->translatedFormat('d M');
                    $values[] = $operationsByDay[$d] ?? 0;
                }

                return [
                    'labels' => $labels,
                    'consultations' => $values,
                ];
            },
            300
        );

        return $this->success($result);
    }

    public function recentAppointments(Request $request)
    {
        $user = Auth::user();

        if (!$user->isAdmin() && !$user->isDoctor()) {
            return $this->unauthorized();
        }

        $limit = min((int) $request->get('limit', 10), 50);
        $operations = Operation::with(['patient.user:id,name', 'doctor:id,name'])
            ->orderBy('operation_date', 'desc')
            ->limit($limit)
            ->get();

        return $this->success($operations);
    }

    public function doctors()
    {
        $user = Auth::user();

        if (!$user->isAdmin() && !$user->isDoctor() && !$user->isSecretary()) {
            return $this->unauthorized();
        }

        $doctors = $this->cache->remember($this->cache->getDoctorListKey(), function () {
            $doctors = User::query()
                ->where('role', 'Doctor')
                ->leftJoin('doctor_availabilities as da', 'da.doctor_id', '=', 'users.id')
                ->orderBy('users.name')
                ->get([
                    'users.id',
                    'users.name',
                    'users.username',
                    'users.email',
                    'users.specialty',
                    'users.locale',
                    'users.profile_photo_path',
                    'da.status as availability_status',
                    'da.last_seen_at as availability_last_seen_at',
                ]);

            $activeCounts = Patient::query()
                ->select('assigned_doctor_id', DB::raw('COUNT(*) as cnt'))
                ->whereNotNull('assigned_doctor_id')
                ->where('status', 'admitted')
                ->groupBy('assigned_doctor_id')
                ->pluck('cnt', 'assigned_doctor_id');

            $now = Carbon::now();
            return $doctors->map(function ($d) use ($activeCounts, $now) {
                $lastSeen = $d->availability_last_seen_at ? Carbon::parse($d->availability_last_seen_at) : null;
                $isStale = $lastSeen ? $lastSeen->diffInSeconds($now) > 180 : true;

                $status = $d->availability_status ?: 'offline';
                if ($isStale) {
                    $status = 'offline';
                }

                $d->availability_status = $status;
                $d->active_patients_count = (int) ($activeCounts[$d->id] ?? 0);
                return $d;
            });
        }, 60);

        return $this->success($doctors);
    }

    public function nurses()
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            return $this->unauthorized();
        }

        $nurses = User::where('role', 'Nurse')
            ->orderBy('name')
            ->get(['id', 'name', 'username', 'email']);

        return $this->success($nurses);
    }

    public function admissionsStats(Request $request)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isAccountant()) {
            return $this->unauthorized();
        }

        $from = $request->get('from') ? Carbon::parse($request->get('from'))->startOfDay() : now()->startOfMonth();
        $to = $request->get('to') ? Carbon::parse($request->get('to'))->endOfDay() : now()->endOfDay();

        $entrants = Patient::query()
            ->whereNotNull('admission_at')
            ->whereBetween('admission_at', [$from, $to])
            ->count();

        $sortants = Patient::query()
            ->whereNotNull('discharge_at')
            ->whereBetween('discharge_at', [$from, $to])
            ->count();

        return $this->success([
            'from' => $from->toIso8601String(),
            'to' => $to->toIso8601String(),
            'entrants' => $entrants,
            'sortants' => $sortants,
        ]);
    }

    public function financialOverview(Request $request)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isAccountant()) {
            return $this->unauthorized();
        }

        $from = $request->get('from') ? Carbon::parse($request->get('from'))->startOfDay() : now()->startOfMonth();
        $to = $request->get('to') ? Carbon::parse($request->get('to'))->endOfDay() : now()->endOfDay();

        $result = $this->cache->remember(
            $this->cache->getFinancialOverviewKey($from->toIso8601String(), $to->toIso8601String()),
            function () use ($from, $to) {
                $paymentsIn = (float) Payment::query()
                    ->where('status', 'paid')
                    ->whereNotNull('paid_at')
                    ->whereBetween('paid_at', [$from, $to])
                    ->sum('amount');

                $inventoryPurchasesOut = (float) InventoryMovement::query()
                    ->where('direction', 'in')
                    ->whereBetween('movement_date', [$from->toDateString(), $to->toDateString()])
                    ->sum('total_value');

                $inventoryConsumptionValue = (float) InventoryMovement::query()
                    ->where('direction', 'out')
                    ->whereBetween('movement_date', [$from->toDateString(), $to->toDateString()])
                    ->sum('total_value');

                $netCashLike = round($paymentsIn - $inventoryPurchasesOut, 2);

                $paymentsByPatient = Payment::query()
                    ->where('status', 'paid')
                    ->whereNotNull('paid_at')
                    ->whereBetween('paid_at', [$from, $to])
                    ->selectRaw('patient_id, SUM(amount) as total_paid')
                    ->groupBy('patient_id')
                    ->get();

                $patientIds = $paymentsByPatient->pluck('patient_id')->filter()->unique()->values();
                $patients = Patient::with('user:id,name')->whereIn('id', $patientIds)->get()->keyBy('id');

                $byClient = $paymentsByPatient->map(function ($row) use ($patients) {
                    $p = $patients->get($row->patient_id);
                    return [
                        'patient_id' => $row->patient_id,
                        'total_paid' => round((float) $row->total_paid, 2),
                        'patient_name' => $p?->user?->name,
                    ];
                })->values();

                return [
                    'from' => $from->toIso8601String(),
                    'to' => $to->toIso8601String(),
                    'cash_in_from_patients' => round($paymentsIn, 2),
                    'cash_out_inventory_purchases' => round($inventoryPurchasesOut, 2),
                    'inventory_consumption_value' => round($inventoryConsumptionValue, 2),
                    'net_estimated' => $netCashLike,
                    'profit_loss_simple' => round($paymentsIn - $inventoryPurchasesOut - $inventoryConsumptionValue, 2),
                    'payments_by_patient' => $byClient,
                ];
            },
            300
        );

        return $this->success($result);
    }

    /**
     * Agrégats pour le dashboard analytics (Admin / Comptable).
     * Compatible avec les graphiques Angular existants (labels + values).
     */
    public function analytics(Request $request)
    {
        $user = Auth::user();

        if (!$user->isAdmin() && !$user->isAccountant()) {
            return $this->unauthorized();
        }

        $ttl = (int) config('optimization.cache.dashboard_ttl', 120);

        $payload = $this->cache->remember(
            $this->cache->getDashboardAnalyticsKey(),
            function () {
                $alertsByStatus = Alert::query()
                    ->select('status', DB::raw('COUNT(*) as total'))
                    ->groupBy('status')
                    ->pluck('total', 'status');

                $patientsByStatus = Patient::query()
                    ->select('status', DB::raw('COUNT(*) as total'))
                    ->groupBy('status')
                    ->pluck('total', 'status');

                $doctorLoad = User::query()
                    ->where('role', 'Doctor')
                    ->leftJoin('patients', 'patients.assigned_doctor_id', '=', 'users.id')
                    ->groupBy('users.id', 'users.name')
                    ->orderBy('users.name')
                    ->get([
                        'users.name',
                        DB::raw('COUNT(patients.id) as patients_count'),
                    ]);

                $revenueByMonth = Payment::query()
                    ->where('status', 'paid')
                    ->whereNotNull('paid_at')
                    ->where('paid_at', '>=', now()->subMonths(11)->startOfMonth())
                    ->select(
                        DB::raw('YEAR(paid_at) as y'),
                        DB::raw('MONTH(paid_at) as m'),
                        DB::raw('SUM(amount) as total')
                    )
                    ->groupBy('y', 'm')
                    ->orderBy('y')
                    ->orderBy('m')
                    ->get();

                $revenueLabels = [];
                $revenueValues = [];
                for ($i = 11; $i >= 0; $i--) {
                    $date = now()->subMonths($i);
                    $key = $date->format('Y-n');
                    $revenueLabels[] = $date->translatedFormat('M Y');
                    $row = $revenueByMonth->first(fn ($r) => (int) $r->y === (int) $date->year && (int) $r->m === (int) $date->month);
                    $revenueValues[] = round((float) ($row->total ?? 0), 2);
                }

                $days = 30;
                $start = now()->subDays($days);
                $operationsByDay = Operation::query()
                    ->where('operation_date', '>=', $start->format('Y-m-d'))
                    ->select(DB::raw('DATE(operation_date) as date'), DB::raw('COUNT(*) as count'))
                    ->groupBy('date')
                    ->orderBy('date')
                    ->pluck('count', 'date')
                    ->toArray();

                $consultationLabels = [];
                $consultationValues = [];
                for ($i = $days - 1; $i >= 0; $i--) {
                    $d = now()->subDays($i)->format('Y-m-d');
                    $consultationLabels[] = Carbon::parse($d)->translatedFormat('d M');
                    $consultationValues[] = (int) ($operationsByDay[$d] ?? 0);
                }

                $from = now()->startOfMonth();
                $to = now()->endOfDay();
                $entrants = Patient::query()
                    ->whereNotNull('admission_at')
                    ->whereBetween('admission_at', [$from, $to])
                    ->count();
                $sortants = Patient::query()
                    ->whereNotNull('discharge_at')
                    ->whereBetween('discharge_at', [$from, $to])
                    ->count();

                return [
                    'alerts_by_status' => [
                        'labels' => $alertsByStatus->keys()->values()->all(),
                        'values' => $alertsByStatus->values()->map(fn ($v) => (int) $v)->all(),
                    ],
                    'patients_by_status' => [
                        'labels' => $patientsByStatus->keys()->values()->all(),
                        'values' => $patientsByStatus->values()->map(fn ($v) => (int) $v)->all(),
                    ],
                    'doctor_load' => [
                        'labels' => $doctorLoad->pluck('name')->all(),
                        'values' => $doctorLoad->pluck('patients_count')->map(fn ($v) => (int) $v)->all(),
                    ],
                    'revenue_by_month' => [
                        'labels' => $revenueLabels,
                        'values' => $revenueValues,
                    ],
                    'consultations_30d' => [
                        'labels' => $consultationLabels,
                        'values' => $consultationValues,
                    ],
                    'admissions_month' => [
                        'labels' => ['Entrées', 'Sorties'],
                        'values' => [$entrants, $sortants],
                    ],
                ];
            },
            $ttl
        );

        return $this->success($payload);
    }

    /**
     * Analytics léger pour le médecin connecté (consultations, charge patients).
     */
    public function doctorAnalytics(Request $request)
    {
        $user = Auth::user();
        if (!$user->isDoctor()) {
            return $this->unauthorized();
        }

        $doctorId = (int) $user->id;
        $ttl = (int) config('optimization.cache.dashboard_ttl', 120);

        $payload = $this->cache->remember(
            $this->cache->getDoctorAnalyticsKey($doctorId),
            function () use ($doctorId) {
                $assignedPatients = Patient::query()
                    ->where('assigned_doctor_id', $doctorId)
                    ->count();

                $activePatients = Patient::query()
                    ->where('assigned_doctor_id', $doctorId)
                    ->where('status', 'admitted')
                    ->count();

                $pendingAlerts = Alert::query()
                    ->whereHas('patient', fn ($q) => $q->where('assigned_doctor_id', $doctorId))
                    ->where('status', '!=', 'acknowledged')
                    ->count();

                $days = 30;
                $start = now()->subDays($days);
                $operationsByDay = Operation::query()
                    ->where('doctor_id', $doctorId)
                    ->where('operation_date', '>=', $start->format('Y-m-d'))
                    ->select(DB::raw('DATE(operation_date) as date'), DB::raw('COUNT(*) as count'))
                    ->groupBy('date')
                    ->orderBy('date')
                    ->pluck('count', 'date')
                    ->toArray();

                $labels = [];
                $values = [];
                for ($i = $days - 1; $i >= 0; $i--) {
                    $d = now()->subDays($i)->format('Y-m-d');
                    $labels[] = Carbon::parse($d)->translatedFormat('d M');
                    $values[] = (int) ($operationsByDay[$d] ?? 0);
                }

                return [
                    'assigned_patients' => $assignedPatients,
                    'active_patients' => $activePatients,
                    'pending_alerts' => $pendingAlerts,
                    'consultations_30d' => [
                        'labels' => $labels,
                        'values' => $values,
                    ],
                ];
            },
            $ttl
        );

        return $this->success($payload);
    }

    /**
     * Analytics laboratoire : documents, rendez-vous, activité mensuelle.
     */
    public function labAnalytics(Request $request)
    {
        $user = Auth::user();
        if (!$user->isLaboratory() && !$user->isAdmin()) {
            return $this->unauthorized();
        }

        $ttl = (int) config('optimization.cache.dashboard_ttl', 120);

        $payload = $this->cache->remember(
            $this->cache->getLabAnalyticsKey(),
            function () {
                $documentsTotal = LabDocument::query()->count();
                $documentsMonth = LabDocument::query()
                    ->where('created_at', '>=', now()->startOfMonth())
                    ->count();

                $appointmentsByStatus = LabAppointment::query()
                    ->select('status', DB::raw('COUNT(*) as total'))
                    ->groupBy('status')
                    ->pluck('total', 'status');

                $patientsReferenced = Patient::query()->count();

                $labels = [];
                $values = [];
                for ($i = 5; $i >= 0; $i--) {
                    $date = now()->subMonths($i);
                    $labels[] = $date->translatedFormat('M Y');
                    $values[] = LabDocument::query()
                        ->whereYear('created_at', $date->year)
                        ->whereMonth('created_at', $date->month)
                        ->count();
                }

                return [
                    'documents_total' => $documentsTotal,
                    'documents_month' => $documentsMonth,
                    'patients_referenced' => $patientsReferenced,
                    'appointments_by_status' => [
                        'labels' => $appointmentsByStatus->keys()->values()->all(),
                        'values' => $appointmentsByStatus->values()->map(fn ($v) => (int) $v)->all(),
                    ],
                    'documents_by_month' => [
                        'labels' => $labels,
                        'values' => $values,
                    ],
                ];
            },
            $ttl
        );

        return $this->success($payload);
    }

    /**
     * Analytics réception : admissions, rendez-vous spécialiste, médecins.
     */
    public function secretaryAnalytics(Request $request)
    {
        $user = Auth::user();
        if (!$user->isSecretary() && !$user->isAdmin()) {
            return $this->unauthorized();
        }

        $ttl = (int) config('optimization.cache.dashboard_ttl', 120);

        $payload = $this->cache->remember(
            $this->cache->getSecretaryAnalyticsKey(),
            function () {
                $from = now()->startOfMonth();
                $to = now()->endOfDay();

                $entrants = Patient::query()
                    ->whereNotNull('admission_at')
                    ->whereBetween('admission_at', [$from, $to])
                    ->count();

                $pendingSpecialist = SpecialistAppointment::query()
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->where('scheduled_at', '>=', now())
                    ->count();

                $doctorsAvailable = User::query()
                    ->where('role', 'Doctor')
                    ->leftJoin('doctor_availabilities as da', 'da.doctor_id', '=', 'users.id')
                    ->where('da.status', 'available')
                    ->where('da.last_seen_at', '>=', now()->subMinutes(3))
                    ->count();

                $specialistByStatus = SpecialistAppointment::query()
                    ->select('status', DB::raw('COUNT(*) as total'))
                    ->groupBy('status')
                    ->pluck('total', 'status');

                return [
                    'admissions_month' => $entrants,
                    'pending_specialist_appointments' => $pendingSpecialist,
                    'doctors_available' => $doctorsAvailable,
                    'specialist_by_status' => [
                        'labels' => $specialistByStatus->keys()->values()->all(),
                        'values' => $specialistByStatus->values()->map(fn ($v) => (int) $v)->all(),
                    ],
                    'admissions_chart' => [
                        'labels' => ['Entrées (mois)', 'RDV spécialiste à venir', 'Médecins dispo.'],
                        'values' => [$entrants, $pendingSpecialist, $doctorsAvailable],
                    ],
                ];
            },
            $ttl
        );

        return $this->success($payload);
    }
}
