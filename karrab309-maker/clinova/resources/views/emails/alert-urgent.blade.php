<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: sans-serif; line-height: 1.5; color: #1e293b;">
    <p><strong>Alerte patient</strong></p>
    <p>Patient : {{ $patient->user->name ?? ('#'.$patient->id) }}</p>
    <p>{{ $alert->message }}</p>
    <p style="font-size: 13px;">Type : {{ $alert->indicator_type ?? '—' }} · Valeur : {{ $alert->value }}</p>
</body>
</html>
