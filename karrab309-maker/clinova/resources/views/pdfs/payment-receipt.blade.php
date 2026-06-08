<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        .muted { color: #475569; }
        .title { font-size: 20px; font-weight: 800; margin: 0; }
        .sub { margin: 0; }
        .box { border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; }
        .h { font-size: 12px; font-weight: 700; color: #0f172a; margin: 0 0 6px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; border-bottom: 1px solid #e2e8f0; font-size: 12px; }
        th { text-align: left; color: #0f172a; }
        .right { text-align: right; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 999px; background: #eef2ff; color: #3730a3; font-size: 11px; font-weight: 700; }
        .totals td { border-bottom: none; }
    </style>
</head>
<body>
    <table style="margin-bottom: 14px;">
        <tr>
            <td>
                <p class="title">Clinova</p>
                <p class="sub muted">Reçu de paiement</p>
            </td>
            <td class="right">
                <p class="sub"><strong>N° Reçu:</strong> {{ $payment->receipt_number }}</p>
                <p class="sub"><strong>Date:</strong> {{ optional($payment->paid_at)->format('d/m/Y') }}</p>
            </td>
        </tr>
    </table>

    <div class="box" style="margin-bottom: 12px;">
        <p class="h">INFORMATIONS</p>
        <table>
            <tr>
                <td><strong>Nom</strong><br><span class="muted">{{ $payment->payer_name ?? '—' }}</span></td>
                <td><strong>CIN</strong><br><span class="muted">{{ $payment->national_id ?? '—' }}</span></td>
            </tr>
            <tr>
                <td><strong>Email</strong><br><span class="muted">{{ $payment->email ?? '—' }}</span></td>
                <td><strong>Tél</strong><br><span class="muted">{{ $payment->phone ?? '—' }}</span></td>
            </tr>
            <tr>
                <td><strong>Ville</strong><br><span class="muted">{{ $payment->city ?? '—' }}</span></td>
                <td><strong>Dossier</strong><br><span class="muted">{{ $payment->file_label ?? '—' }}</span></td>
            </tr>
        </table>
    </div>

    <div class="box" style="margin-bottom: 12px;">
        <p class="h">DÉTAILS DU PAIEMENT</p>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th class="right">Montant</th>
                    <th>Enregistré par</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ optional($payment->paid_at)->format('d/m/Y') }}</td>
                    <td class="right">{{ number_format((float) $payment->amount, 2, '.', '') }} {{ $payment->currency }}</td>
                    <td>{{ $payment->recordedBy?->name ?? '—' }}</td>
                </tr>
            </tbody>
        </table>

        <table style="margin-top: 10px;">
            <tbody class="totals">
                <tr>
                    <td><strong>Montant total:</strong></td>
                    <td class="right"><strong>{{ number_format((float) $payment->total_amount, 2, '.', '') }} {{ $payment->currency }}</strong></td>
                </tr>
                <tr>
                    <td><strong>Total payé:</strong></td>
                    <td class="right"><strong>{{ number_format((float) $paidSoFar, 2, '.', '') }} {{ $payment->currency }}</strong></td>
                </tr>
                <tr>
                    <td><strong>Montant restant:</strong></td>
                    <td class="right"><span class="badge">{{ number_format((float) $remaining, 2, '.', '') }} {{ $payment->currency }}</span></td>
                </tr>
            </tbody>
        </table>
    </div>

    <p class="muted" style="font-size: 11px;">
        Ce document est un reçu officiel de paiement.
    </p>
</body>
</html>

