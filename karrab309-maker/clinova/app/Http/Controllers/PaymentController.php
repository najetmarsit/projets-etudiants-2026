<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\PatientBillableItem;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    /**
     * Synthèse montant total dû / déjà payé / reste (patient connecté ou admin/compta avec patient_id).
     */
    public function balance(Request $request)
    {
        $user = Auth::user();
        $patientId = $request->get('patient_id');

        if ($user->isPatient()) {
            $patient = Patient::where('user_id', $user->id)->first();
            if (! $patient) {
                return response()->json(['success' => true, 'data' => $this->emptyBalance()]);
            }
            $patientId = $patient->id;
        } elseif ($user->isAdmin() || $user->isAccountant() || $user->isSecretary() || $user->isDoctor() || $user->isNurse()) {
            $patientId = $patientId ? (int) $patientId : null;
            if (! $patientId) {
                return response()->json(['success' => false, 'message' => 'patient_id requis'], 422);
            }
        } else {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $patient = Patient::find($patientId);
        if (! $patient) {
            return response()->json(['success' => false, 'message' => 'Patient not found'], 404);
        }

        // Contrôles d'accès staff
        if ($user->isDoctor() && (int) $patient->assigned_doctor_id !== (int) $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        // Infirmier: accès lecture global (comme ailleurs dans le projet).
        // Réception: accès lecture global (bilan) pour préparer le paiement.

        return response()->json([
            'success' => true,
            'data' => $this->computeBalancePayload($patient),
        ]);
    }

    /**
     * Patients sortis avec solde restant (caisse — tableau de bord comptable).
     */
    public function cashierDischargePending(Request $request)
    {
        $user = Auth::user();
        if (! $user->isAdmin() && ! $user->isAccountant()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $limit = min((int) $request->get('limit', 30), 100);
        $patients = Patient::with('user')
            ->whereNotNull('discharge_at')
            ->orderByDesc('discharge_at')
            ->limit(200)
            ->get();

        $rows = [];
        foreach ($patients as $p) {
            $b = $this->computeBalancePayload($p);
            if ($b['remaining'] > 0.009 || ($b['total_due'] > 0 && $b['total_paid'] < $b['total_due'])) {
                $rows[] = [
                    'patient' => [
                        'id' => $p->id,
                        'name' => $p->user?->name,
                        'discharge_at' => $p->discharge_at?->toIso8601String(),
                    ],
                    'balance' => $b,
                ];
            }
            if (count($rows) >= $limit) {
                break;
            }
        }

        return response()->json(['success' => true, 'data' => $rows]);
    }

    /**
     * Crée une intention de paiement en ligne (Stripe ou PayPal) pour l’app mobile.
     * Stripe : retourne client_secret si STRIPE_SECRET est configuré.
     */
    public function onlineIntent(Request $request)
    {
        $user = Auth::user();
        if (! $user->isPatient()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $patient = Patient::where('user_id', $user->id)->first();
        if (! $patient) {
            return response()->json(['success' => false, 'message' => 'Patient not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'provider' => 'required|string|in:stripe,paypal',
            'amount' => 'nullable|numeric|min:0.01',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $balance = $this->computeBalancePayload($patient);
        $amount = $request->filled('amount') ? (float) $request->amount : (float) $balance['remaining'];
        if ($amount <= 0) {
            return response()->json(['success' => false, 'message' => 'Aucun montant à payer'], 422);
        }

        $currency = strtolower((string) config('services.stripe.currency', 'eur'));

        if ($request->input('provider') === 'stripe') {
            $secret = config('services.stripe.secret');
            if (empty($secret)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Paiement Stripe non configuré (STRIPE_SECRET).',
                    'code' => 'stripe_not_configured',
                ], 503);
            }

            $amountCents = (int) round($amount * 100);
            $response = Http::withBasicAuth($secret, '')
                ->asForm()
                ->post('https://api.stripe.com/v1/payment_intents', [
                    'amount' => $amountCents,
                    'currency' => $currency,
                    'metadata[patient_id]' => (string) $patient->id,
                    'metadata[user_id]' => (string) $user->id,
                ]);

            if (! $response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stripe: '.$response->body(),
                ], 502);
            }

            $pi = $response->json();

            return response()->json([
                'success' => true,
                'data' => [
                    'provider' => 'stripe',
                    'payment_intent_id' => $pi['id'] ?? null,
                    'client_secret' => $pi['client_secret'] ?? null,
                    'publishable_key' => config('services.stripe.key'),
                    'amount' => $amount,
                    'currency' => $currency,
                ],
            ]);
        }

        // PayPal : création d’ordre (nécessite PAYPAL_CLIENT_ID + PAYPAL_SECRET + PAYPAL_MODE)
        $clientId = config('services.paypal.client_id');
        $secret = config('services.paypal.secret');
        if (empty($clientId) || empty($secret)) {
            return response()->json([
                'success' => false,
                'message' => 'PayPal non configuré (PAYPAL_CLIENT_ID, PAYPAL_SECRET).',
                'code' => 'paypal_not_configured',
            ], 503);
        }

        $base = config('services.paypal.mode') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';

        $tokenRes = Http::withBasicAuth($clientId, $secret)
            ->asForm()
            ->post($base.'/v1/oauth2/token', ['grant_type' => 'client_credentials']);

        if (! $tokenRes->successful()) {
            return response()->json(['success' => false, 'message' => 'PayPal auth failed'], 502);
        }

        $accessToken = $tokenRes->json('access_token');
        $orderRes = Http::withToken($accessToken)
            ->post($base.'/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'amount' => [
                        'currency_code' => strtoupper(config('services.paypal.currency', 'EUR')),
                        'value' => number_format($amount, 2, '.', ''),
                    ],
                    'custom_id' => 'patient_'.$patient->id,
                ]],
            ]);

        if (! $orderRes->successful()) {
            return response()->json(['success' => false, 'message' => 'PayPal: '.$orderRes->body()], 502);
        }

        $order = $orderRes->json();
        $approve = collect($order['links'] ?? [])->firstWhere('rel', 'approve');

        return response()->json([
            'success' => true,
            'data' => [
                'provider' => 'paypal',
                'order_id' => $order['id'] ?? null,
                'approval_url' => $approve['href'] ?? null,
                'amount' => $amount,
            ],
        ]);
    }

    /**
     * Après paiement Stripe réussi côté mobile : enregistre le paiement (vérifie l’intent auprès de Stripe).
     */
    public function confirmStripe(Request $request)
    {
        $user = Auth::user();
        if (! $user->isPatient()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $patient = Patient::where('user_id', $user->id)->first();
        if (! $patient) {
            return response()->json(['success' => false, 'message' => 'Patient not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'payment_intent_id' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $secret = config('services.stripe.secret');
        if (empty($secret)) {
            return response()->json(['success' => false, 'message' => 'Stripe non configuré'], 503);
        }

        $piId = $request->input('payment_intent_id');
        $res = Http::withBasicAuth($secret, '')->get('https://api.stripe.com/v1/payment_intents/'.$piId);
        if (! $res->successful()) {
            return response()->json(['success' => false, 'message' => 'Intent introuvable'], 404);
        }

        $pi = $res->json();
        if (($pi['status'] ?? '') !== 'succeeded') {
            return response()->json(['success' => false, 'message' => 'Paiement non finalisé'], 422);
        }

        $metaPid = $pi['metadata']['patient_id'] ?? null;
        if ((string) $metaPid !== (string) $patient->id) {
            return response()->json(['success' => false, 'message' => 'Intent invalide pour ce patient'], 403);
        }

        $amount = ((int) ($pi['amount_received'] ?? $pi['amount'] ?? 0)) / 100;
        $balance = $this->computeBalancePayload($patient);
        $totalDue = (float) $balance['total_due'];

        if (Payment::where('external_id', $piId)->where('provider', 'stripe')->exists()) {
            return response()->json(['success' => true, 'message' => 'Déjà enregistré', 'data' => ['duplicate' => true]]);
        }

        $payment = Payment::create([
            'receipt_number' => (string) Str::uuid(),
            'patient_id' => $patient->id,
            'recorded_by' => null,
            'payer_name' => $user->name,
            'email' => $user->email,
            'total_amount' => $totalDue > 0 ? $totalDue : $amount,
            'amount' => $amount,
            'currency' => strtoupper($pi['currency'] ?? 'eur'),
            'paid_at' => now(),
            'status' => 'paid',
            'provider' => 'stripe',
            'external_id' => $piId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Paiement enregistré',
            'data' => $payment->load(['patient.user', 'recordedBy']),
        ], 201);
    }

    private function emptyBalance(): array
    {
        return [
            'total_due' => 0,
            'total_paid' => 0,
            'remaining' => 0,
            'currency' => (string) config('clinova_pricing.currency', 'TND'),
            'billing_breakdown' => [],
            'payments' => [],
        ];
    }

    private function computeBalancePayload(Patient $patient): array
    {
        $currency = (string) config('clinova_pricing.currency', 'TND');
        $paidQuery = Payment::where('patient_id', $patient->id)->where('status', 'paid');
        $paid = (float) (clone $paidQuery)->sum('amount');

        // Nouveau bilan automatique : somme des actes enregistrés.
        $items = PatientBillableItem::query()
            ->where('patient_id', (int) $patient->id)
            ->orderByRaw('COALESCE(performed_at, created_at) ASC')
            ->orderBy('id')
            ->get(['id', 'kind', 'label', 'amount', 'performed_at', 'created_at']);

        $autoTotal = (float) $items->sum(fn ($i) => (float) $i->amount);
        $useAuto = $autoTotal > 0.009;

        $billingBreakdown = $useAuto
            ? $items->map(function ($i) {
                $dt = $i->performed_at ?? $i->created_at;
                $when = $dt ? Carbon::parse($dt)->format('d/m/Y H:i') : '';
                $prefix = $i->kind ? strtoupper((string) $i->kind) : 'ACTE';
                return [
                    'label' => trim($prefix . ($when ? " • $when" : '') . ' — ' . (string) $i->label),
                    'amount' => (float) $i->amount,
                ];
            })->values()->all()
            : ($patient->billing_breakdown ?? []);

        $totalDue = $useAuto ? $autoTotal : $this->resolveBillingTotalDue($patient);

        $remaining = max(0, round($totalDue - $paid, 2));

        $last = (clone $paidQuery)->orderByDesc('paid_at')->orderByDesc('id')->first();
        if ($last && $last->currency) {
            $currency = $last->currency;
        }

        return [
            'total_due' => round($totalDue, 2),
            'total_paid' => round($paid, 2),
            'remaining' => $remaining,
            'currency' => $currency,
            'billing_breakdown' => $billingBreakdown,
            'billing_notes' => $patient->billing_notes,
            'payments' => Payment::where('patient_id', $patient->id)
                ->orderByRaw('COALESCE(paid_at, created_at) DESC')
                ->orderByDesc('id')
                ->take(20)
                ->get(['id', 'amount', 'total_amount', 'currency', 'paid_at', 'status', 'provider', 'receipt_number'])
                ->values(),
        ];
    }

    /**
     * Montant total dû affiché pour le patient (facturation).
     */
    private function resolveBillingTotalDue(Patient $patient): float
    {
        $fromPatient = $patient->billing_total_due !== null ? (float) $patient->billing_total_due : null;
        $fromPayments = (float) Payment::where('patient_id', $patient->id)
            ->where('status', 'paid')
            ->max('total_amount');
        $totalDue = $fromPatient ?? ($fromPayments > 0 ? $fromPayments : 0);

        if ($totalDue <= 0 && $patient->billing_breakdown && is_array($patient->billing_breakdown)) {
            $sumLines = collect($patient->billing_breakdown)->sum(fn ($l) => (float) ($l['amount'] ?? 0));
            if ($sumLines > 0) {
                $totalDue = $sumLines;
            }
        }

        return round((float) $totalDue, 2);
    }

    /**
     * List payments. Admin/Accountant: all or by patient_id. Patient: own only.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Payment::with(['patient.user', 'recordedBy'])->orderByDesc('paid_at')->orderByDesc('created_at');

        if ($request->filled('patient_id')) {
            $query->where('patient_id', (int) $request->patient_id);
        }

        if ($user->isPatient()) {
            $patient = Patient::where('user_id', $user->id)->first();
            $query->where('patient_id', $patient?->id ?? 0);
        } elseif (! $user->isAdmin() && ! $user->isAccountant()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $query->get(),
        ]);
    }

    /**
     * Enregistrement manuel d’un paiement (admin uniquement — le comptable consulte les listes et bilans).
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (! $user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'patient_id' => 'required|exists:patients,id',
            'amount' => 'required|numeric|min:0.01',
            'total_amount' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:8',
            'paid_at' => 'nullable|date',
            'status' => 'nullable|string|in:paid,pending',

            'payer_name' => 'nullable|string|max:255',
            'national_id' => 'nullable|string|max:100',
            'email' => 'nullable|string|email|max:255',
            'phone' => 'nullable|string|max:50',
            'city' => 'nullable|string|max:255',
            'file_label' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $patient = Patient::with('user')->find((int) $request->patient_id);
        if (! $patient) {
            return response()->json(['success' => false, 'message' => 'Patient not found'], 404);
        }

        $status = $request->input('status', 'paid');
        if (! in_array($status, ['paid', 'pending'], true)) {
            $status = 'paid';
        }

        $amount = round((float) $request->amount, 2);
        $billingDue = $this->resolveBillingTotalDue($patient);
        $totalAmount = $request->filled('total_amount')
            ? round((float) $request->total_amount, 2)
            : max($billingDue, $amount);
        if ($totalAmount < $amount) {
            $totalAmount = $amount;
        }

        $paidAt = null;
        if ($status === 'paid') {
            $paidAt = $request->filled('paid_at')
                ? Carbon::parse($request->input('paid_at'))
                : now();
        }

        $payment = Payment::create([
            'receipt_number' => (string) Str::uuid(),
            'patient_id' => $patient->id,
            'recorded_by' => $user->id,
            'payer_name' => $request->payer_name ?: ($patient->user?->name),
            'national_id' => $request->national_id,
            'email' => $request->email ?: ($patient->user?->email),
            'phone' => $request->phone ?: ($patient->phone),
            'city' => $request->city,
            'file_label' => $request->file_label,
            'total_amount' => $totalAmount,
            'amount' => $amount,
            'currency' => $request->currency ?: 'TND',
            'paid_at' => $paidAt,
            'status' => $status,
            'provider' => 'manual',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Paiement enregistré',
            'data' => $payment->load(['patient.user', 'recordedBy']),
        ], 201);
    }

    /**
     * Download receipt PDF.
     */
    public function receipt(string $id)
    {
        $user = Auth::user();
        $payment = Payment::with(['patient.user', 'recordedBy'])->find($id);

        if (! $payment) {
            return response()->json(['success' => false, 'message' => 'Payment not found'], 404);
        }

        if ($payment->status !== 'paid' || ! $payment->paid_at) {
            return response()->json([
                'success' => false,
                'message' => 'Reçu disponible uniquement pour un paiement confirmé (statut payé).',
            ], 422);
        }

        if ($user->isPatient()) {
            $patient = Patient::where('user_id', $user->id)->first();
            if (! $patient || (int) $payment->patient_id !== (int) $patient->id) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
        } elseif (! $user->isAdmin() && ! $user->isAccountant()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $paidSoFar = Payment::where('patient_id', $payment->patient_id)
            ->where('status', 'paid')
            ->whereNotNull('paid_at')
            ->where('paid_at', '<=', $payment->paid_at)
            ->sum('amount');

        $remaining = max(0, (float) $payment->total_amount - (float) $paidSoFar);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdfs.payment-receipt', [
            'payment' => $payment,
            'paidSoFar' => $paidSoFar,
            'remaining' => $remaining,
        ])->setPaper('a4', 'portrait');

        $filename = 'recu_paiement_' . $payment->receipt_number . '.pdf';

        return $pdf->download($filename);
    }
}

