# ✅ Migration vers Firebase API V1 - Terminée!

## 🎉 Ce Qui a Été Fait

### 1. Fichier JSON Firebase Configuré
- ✅ Fichier téléchargé depuis Firebase Console
- ✅ Copié dans `storage/firebase-credentials.json`
- ✅ Ajouté au `.gitignore` pour sécurité

**Détails du Projet:**
- **Projet Firebase:** attendance-6156f
- **Service Account:** firebase-adminsdk@attendance-6156f.iam.gserviceaccount.com
- **API:** Firebase Cloud Messaging V1 (la plus récente)

---

### 2. Package Firebase Admin SDK Installé
```bash
composer require kreait/firebase-php
```

**Version installée:** ^7.23 (la plus récente)

---

### 3. PushNotificationService Migré vers API V1

**Ancien système (Legacy API):**
- Utilisait une simple "Server Key"
- API HTTP avec URL: `https://fcm.googleapis.com/fcm/send`
- Requête HTTP manuelle avec `Authorization: key=...`

**Nouveau système (API V1):**
- Utilise un fichier JSON de compte de service
- SDK officiel Firebase Admin PHP
- Authentification OAuth 2.0 automatique
- Support Android + iOS natif

**Fichier:** `app/Services/PushNotificationService.php`

**Changements clés:**
```php
// Avant
protected $fcmServerKey;
protected $fcmUrl = 'https://fcm.googleapis.com/fcm/send';

// Après
protected $messaging; // SDK Firebase
$factory = (new Factory)->withServiceAccount('storage/firebase-credentials.json');
$this->messaging = $factory->createMessaging();
```

---

### 4. Interface Admin Simplifiée

La page de configuration n'affiche plus le champ "Firebase Server Key" car le fichier JSON le remplace.

**Page:** `http://localhost:8000/admin/presence-alerts/settings`

**Affichage:**
- ✅ Badge vert: "Firebase API V1 Configuré"
- ✅ Informations du projet
- ✅ Note explicative sur l'API V1

---

## 🧪 Comment Tester

### Test 1: Vérifier que Firebase est Configuré

```bash
# Vérifier que le fichier existe
ls -la storage/firebase-credentials.json

# Devrait afficher:
# -rw-r--r--  1 user staff  1234  21 nov storage/firebase-credentials.json
```

---

### Test 2: Tester via Tinker

```bash
php artisan tinker
```

**Dans Tinker:**
```php
// 1. Récupérer un utilisateur avec FCM token
$user = App\Models\User::whereNotNull('fcm_token')->first();

// 2. Si aucun utilisateur n'a de token, en définir un pour test
if (!$user) {
    $user = App\Models\User::first();
    $user->fcm_token = 'test_token_will_fail_but_shows_service_works';
    $user->save();
}

// 3. Initialiser le service
$service = new App\Services\PushNotificationService();

// 4. Vérifier si Firebase est configuré
$service->isConfigured(); // Devrait retourner true

// 5. Tester l'envoi (échouera si token invalide mais montre que le service fonctionne)
$service->sendTestNotification($user);

// 6. Vérifier les logs
exit
```

---

### Test 3: Vérifier les Logs

```bash
# Voir les logs en temps réel
tail -f storage/logs/laravel.log
```

**Ce que vous devriez voir:**
```
[2025-11-21 15:00:00] local.INFO: ✓ Firebase Admin SDK initialized with API V1
[2025-11-21 15:00:05] local.INFO: ✓ Push notification sent successfully to user 1 via API V1
```

**Ou si token invalide:**
```
[2025-11-21 15:00:05] local.ERROR: FCM token not found for user 1: Token not found
```

---

### Test 4: Tester avec un Vrai Appareil Mobile

**Prérequis:**
- Application Flutter installée sur un téléphone
- Utilisateur connecté dans l'app
- FCM token enregistré dans la base de données

**Commande:**
```bash
php artisan tinker
```

```php
// Récupérer un utilisateur qui s'est connecté depuis l'app mobile
$user = App\Models\User::whereNotNull('fcm_token')->first();

// Afficher son token pour vérification
echo $user->fcm_token;

// Envoyer une notification de test
$service = new App\Services\PushNotificationService();
$result = $service->sendTestNotification($user);

if ($result) {
    echo "✓ Notification envoyée avec succès!\n";
} else {
    echo "✗ Échec de l'envoi\n";
}

exit
```

**Résultat attendu:**
📱 Le téléphone reçoit la notification: **"Test de notification"**

---

### Test 5: Tester le Système Complet de Présence

```bash
# 1. Créer un incident de test
php artisan tinker
```

```php
$user = App\Models\User::whereNotNull('fcm_token')->first();
$campus = App\Models\Campus::first();
$attendance = App\Models\Attendance::where('user_id', $user->id)->latest()->first();

if (!$attendance) {
    $attendance = App\Models\Attendance::create([
        'user_id' => $user->id,
        'campus_id' => $campus->id,
        'timestamp' => now(),
        'check_type' => 'in',
        'latitude' => $campus->latitude,
        'longitude' => $campus->longitude,
        'is_within_zone' => true,
    ]);
}

// Créer un incident
$incident = App\Models\PresenceIncident::create([
    'user_id' => $user->id,
    'campus_id' => $campus->id,
    'attendance_id' => $attendance->id,
    'incident_date' => now()->toDateString(),
    'notification_sent_at' => now()->format('H:i:s'),
    'response_deadline' => now()->addMinutes(45)->format('H:i:s'),
    'has_responded' => false,
    'status' => 'pending',
    'penalty_hours' => 1.0,
]);

// Envoyer la notification
$service = new App\Services\PushNotificationService();
$service->sendPresenceCheckNotification($user, $incident->id, $campus);

exit
```

**Résultat attendu:**
📱 Le téléphone reçoit: **"Êtes-vous toujours en place au [Nom Campus]?"**
👆 Avec bouton **"OUI, je suis en place"**

---

## 📊 Vérifier dans l'Interface Admin

### 1. Aller sur la Page de Configuration
```
http://localhost:8000/admin/presence-alerts/settings
```

**Vérifier:**
- ✅ Badge vert "Firebase API V1 Configuré"
- ✅ Nom du fichier JSON affiché
- ✅ Informations du projet

---

### 2. Voir les Incidents dans la Liste
```
http://localhost:8000/admin/presence-alerts/index
```

**Vérifier:**
- ✅ L'incident de test apparaît
- ✅ Statut "En attente"
- ✅ Bouton "Voir" fonctionne

---

### 3. Détails de l'Incident
```
http://localhost:8000/admin/presence-alerts/show/{id}
```

**Vérifier:**
- ✅ Timeline affiche "Notification envoyée"
- ✅ Formulaires Valider/Ignorer présents
- ✅ Tous les détails corrects

---

## 🔧 Commandes Artisan

### Envoyer les Notifications Automatiquement
```bash
# Envoyer les notifications de présence (selon l'heure)
php artisan presence:send-notifications

# Créer des incidents pour les réponses expirées
php artisan presence:process-expired
```

**Résultat:**
```
✓ Notifications envoyées: 5
✓ 2 incidents créés pour non-réponse
```

---

## 📁 Fichiers Modifiés

### 1. Service Backend
```
app/Services/PushNotificationService.php
```
- Migration complète vers Firebase Admin SDK
- Support API V1
- Méthode `isConfigured()` ajoutée

### 2. Controller Admin
```
app/Http/Controllers/Admin/PresenceAlertController.php
```
- Suppression de la gestion de la Server Key
- Simplification de `settings()` et `updateSettings()`

### 3. Vue Admin
```
resources/views/admin/presence-alerts/settings.blade.php
```
- Suppression du champ Firebase Server Key
- Affichage du statut de configuration V1

### 4. Fichier JSON
```
storage/firebase-credentials.json
```
- Nouveau fichier (ajouté au .gitignore)
- Contient toutes les credentials Firebase

### 5. Composer
```
composer.json
```
- Ajout de `kreait/firebase-php: ^7.23`

---

## ✅ Checklist Finale

- [x] Fichier JSON téléchargé et placé dans `storage/`
- [x] Package Firebase Admin SDK installé
- [x] PushNotificationService migré vers API V1
- [x] Interface admin simplifiée
- [x] Tests réussis avec `isConfigured()`
- [x] Documentation complète créée

---

## 🚨 Dépannage

### Erreur: "Firebase credentials file not found"
```bash
# Vérifier le chemin
ls storage/firebase-credentials.json

# Si absent, le recopier
cp /path/to/attendance-6156f-2a1a23ba78dc.json storage/firebase-credentials.json
```

### Erreur: "Failed to initialize Firebase Admin SDK"
```bash
# Vérifier les permissions
chmod 644 storage/firebase-credentials.json

# Vérifier le contenu du fichier
cat storage/firebase-credentials.json | jq .
```

### Notification ne s'envoie pas
```bash
# 1. Vérifier les logs
tail -f storage/logs/laravel.log

# 2. Vérifier que l'utilisateur a un FCM token
php artisan tinker
App\Models\User::whereNotNull('fcm_token')->count();
```

### Token FCM invalide
```
# L'utilisateur doit se reconnecter à l'app mobile pour obtenir un nouveau token
# Les tokens FCM expirent après plusieurs mois d'inactivité
```

---

## 🎯 Avantages de l'API V1

| Fonctionnalité | Legacy API | API V1 |
|----------------|------------|--------|
| Authentification | Server Key simple | OAuth 2.0 automatique |
| Sécurité | Clé statique | Rotation automatique des tokens |
| Expiration | Jamais | juin 2024 (Legacy API) |
| Support Android | Oui | Oui |
| Support iOS | Oui | Oui |
| SDK officiel | Non | Oui |
| Gestion d'erreurs | Basique | Avancée |

---

## 📞 Support

En cas de problème:
1. Vérifier les logs: `tail -f storage/logs/laravel.log`
2. Vérifier la console Firebase: https://console.firebase.google.com/
3. Tester avec Tinker: `php artisan tinker`

---

**Statut:** ✅ Migration terminée avec succès!
**Date:** 21 novembre 2025
**API:** Firebase Cloud Messaging V1
