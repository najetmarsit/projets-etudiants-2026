# Dépannage – Rien ne fonctionne

## Checklist rapide

### 1. MySQL doit être démarré
- Ouvrez **XAMPP Control Panel**
- Cliquez sur **Start** à côté de **MySQL**
- Le voyant doit être vert

### 2. Démarrer l’API Laravel
```bash
cd c:\xampp\htdocs\medical-api-main
php artisan serve
```
Vous devez voir : `Server running on [http://127.0.0.1:8000]`

### 3. Démarrer Angular
```bash
cd c:\xampp\htdocs\medical-api-main\angular-app
npm start
```
Puis ouvrez : **http://localhost:4200**

### 4. Script de démarrage automatique
Double-cliquez sur **`demarrer.bat`** à la racine du projet.

---

## Erreurs fréquentes

| Erreur | Solution |
|--------|----------|
| `SQLSTATE[HY000] [2002]` | MySQL n’est pas démarré → XAMPP → Start MySQL |
| `Connection refused` | L’API n’est pas démarrée → `php artisan serve` |
| Page blanche | Vérifier la console (F12) pour les erreurs |
| CORS / 404 | L’API doit tourner sur le port 8000 |

---

## URLs

- **Site web** : http://localhost:4200
- **API** : http://127.0.0.1:8000/api
- **Test API** : http://127.0.0.1:8000/api/auth/login (POST)
