# Données de démonstration « hôpital » Clinova (Laravel)

## 1. Charger les données (API)

À la racine du projet backend :

```bash
php artisan db:seed
```

Cela exécute notamment :

- `ClinovaDefaultUsersSeeder` — comptes techniques (dont `accountant`)
- `MedicalDataSeeder` — jeu de données historique / démo
- **`ClinovaHospitalScenarioSeeder`** — patients et dossiers **Mourad / Khadija / Hanane / Imane** + **Fatma, Sara, Nissrine, Ahmed** (alertes, messages, RDV, facturation)
- **`AccountantDemoDataSeeder`** — **exemples pour l’espace comptable** : entrées/sorties du mois, encaissements, mouvements de stock, **file caisse** (patients sortis avec reste dû) ; comptes patients `clinova_demo_compta_*` (voir tableau ci‑dessous)

Mot de passe par défaut : variable d’environnement `CLINOVA_DEFAULT_PASSWORD`, sinon `password123`.

## 2. Comptes utiles (identifiants)

| Rôle        | Identifiant        | Notes                          |
|------------|--------------------|--------------------------------|
| Médecin    | `doctor`           | Dr Mourad Benali (après seed)  |
| Infirmier  | `nurse`            | Khadija Idrissi                |
| Réception  | `secretary`        | Hanane Filali                  |
| Comptable  | `accountant`       | Imane Cherkaoui                |
| Patient    | `fatma.elmansouri` | Hospitalisation, alerte douleur |
| Patient    | `sara.alami`       | RDV labo + consultation        |
| Patient    | `nissrine.berra`   | Glycémie + alerte              |
| Patient    | `ahmed.tazi`       | Messages + rapport             |
| *(démo compta)* | `clinova_demo_compta_queue1` | File caisse (solde restant) — connexion possible avec le même mot de passe patient si besoin |
| *(démo compta)* | `clinova_demo_compta_queue2` | Idem — dossier non réglé      |

> Les usernames `clinova_demo_compta_*` sont surtout destinés aux **données** (file d’attente, stats) visibles après connexion **`accountant`** ; vous pouvez aussi vous connecter en patient pour vérifier le dossier.

## 3. Fichiers source des seeders

- `database/seeders/ClinovaHospitalScenarioSeeder.php` — scénario hôpital principal.
- **`database/seeders/AccountantDemoDataSeeder.php`** — données d’exemple **comptabilité** (à adapter si la logique métier ou les validations évoluent).
