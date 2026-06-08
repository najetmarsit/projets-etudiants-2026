<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: sans-serif; line-height: 1.5; color: #1e293b;">
    <p>Bonjour {{ $user->name }},</p>

    <p>Votre dossier patient a été créé sur la plateforme de suivi.</p>

    <p>
        <strong>Identifiant :</strong> {{ $user->username }}<br>
        <strong>Mot de passe :</strong> {{ $plainPassword }}<br>
        <strong>Numéro de chambre :</strong> {{ $chamberNumber }}
    </p>

    <p>Veuillez vous connecter puis <strong>modifier votre mot de passe</strong> dès que possible (écran Profil / paramètres).</p>
    <p style="font-size: 12px; color: #64748b;">Ce message est confidentiel.</p>
</body>
</html>

