# 🧪 Test Rapide - Système UE

## Test manuel rapide pour vérifier que tout fonctionne

---

## 🎯 Objectif

Tester rapidement le système UE sans créer de vues Blade, uniquement avec :
- Tinker (CLI Laravel)
- API (Postman/Insomnia)

---

## 📝 Étape 1 : Créer des données de test

### Via Tinker

```bash
php artisan tinker
```

```php
// 1. Créer un enseignant vacataire de test
$vacataire = \App\Models\User::create([
    'employee_id' => 'VAC001',
    'first_name' => 'Chris',
    'last_name' => 'Professeur',
    'email' => 'chris.prof@test.com',
    'password' => bcrypt('password'),
    'phone' => '123456789',
    'employee_type' => 'enseignant_vacataire',
    'hourly_rate' => 2000, // 2000 FCFA/h
    'role_id' => 1, // Ajustez selon votre base
    'department_id' => 1, // Ajustez selon votre base
    'is_active' => true,
]);

echo "Vacataire créé avec ID: " . $vacataire->id . "\n";

// 2. Créer un admin pour attribuer les UE
$admin = \App\Models\User::where('employee_type', 'administratif')->first();
if (!$admin) {
    $admin = \App\Models\User::create([
        'employee_id' => 'ADM001',
        'first_name' => 'Admin',
        'last_name' => 'Test',
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
        'employee_type' => 'administratif',
        'role_id' => 1,
        'is_active' => true,
    ]);
}

echo "Admin ID: " . $admin->id . "\n";

// 3. Créer des UE pour le vacataire
$ue1 = \App\Models\UniteEnseignement::create([
    'vacataire_id' => $vacataire->id,
    'code_ue' => 'MTH101',
    'nom_matiere' => 'Mathématiques',
    'volume_horaire_total' => 18,
    'statut' => 'activee', // Déjà activée
    'annee_academique' => '2024-2025',
    'semestre' => 1,
    'created_by' => $admin->id,
    'activated_by' => $admin->id,
    'date_activation' => now(),
]);

echo "UE Mathématiques créée avec ID: " . $ue1->id . "\n";

$ue2 = \App\Models\UniteEnseignement::create([
    'vacataire_id' => $vacataire->id,
    'code_ue' => 'PHY201',
    'nom_matiere' => 'Physique',
    'volume_horaire_total' => 12,
    'statut' => 'activee',
    'annee_academique' => '2024-2025',
    'semestre' => 1,
    'created_by' => $admin->id,
    'activated_by' => $admin->id,
    'date_activation' => now(),
]);

echo "UE Physique créée avec ID: " . $ue2->id . "\n";

$ue3 = \App\Models\UniteEnseignement::create([
    'vacataire_id' => $vacataire->id,
    'code_ue' => 'CHM301',
    'nom_matiere' => 'Chimie',
    'volume_horaire_total' => 10,
    'statut' => 'non_activee', // Non activée
    'annee_academique' => '2024-2025',
    'semestre' => 2,
    'created_by' => $admin->id,
]);

echo "UE Chimie créée avec ID: " . $ue3->id . " (non activée)\n";

echo "\n✅ Données de test créées avec succès !\n";
echo "Email vacataire: chris.prof@test.com\n";
echo "Password: password\n";
```

---

## 🔐 Étape 2 : Obtenir un token API

### Avec Postman/Insomnia

```http
POST http://localhost:8000/api/login
Content-Type: application/json

{
  "email": "chris.prof@test.com",
  "password": "password"
}
```

**Réponse attendue** :
```json
{
  "success": true,
  "token": "1|abcdefghijklmnopqrstuvwxyz...",
  "user": {
    "id": 1,
    "first_name": "Chris",
    "last_name": "Professeur",
    "email": "chris.prof@test.com",
    "employee_type": "enseignant_vacataire",
    "hourly_rate": 2000
  }
}
```

Copier le token pour les requêtes suivantes.

---

## 📱 Étape 3 : Tester les endpoints API

### 3.1 Liste complète des UE

```http
GET http://localhost:8000/api/unites-enseignement
Authorization: Bearer {votre_token}
```

**Réponse attendue** :
```json
{
  "success": true,
  "data": {
    "unites_activees": [
      {
        "id": 1,
        "code_ue": "MTH101",
        "nom_matiere": "Mathématiques",
        "volume_horaire_total": 18.0,
        "heures_effectuees": 0.0,
        "heures_restantes": 18.0,
        "pourcentage_progression": 0.0,
        "montant_paye": 0.0,
        "montant_restant": 36000.0,
        "montant_max": 36000.0,
        "taux_horaire": 2000.0,
        "statut": "activee"
      },
      {
        "id": 2,
        "code_ue": "PHY201",
        "nom_matiere": "Physique",
        "volume_horaire_total": 12.0,
        "heures_effectuees": 0.0,
        "heures_restantes": 12.0,
        "pourcentage_progression": 0.0,
        "montant_paye": 0.0,
        "montant_restant": 24000.0,
        "montant_max": 24000.0,
        "taux_horaire": 2000.0,
        "statut": "activee"
      }
    ],
    "unites_non_activees": [
      {
        "id": 3,
        "code_ue": "CHM301",
        "nom_matiere": "Chimie",
        "volume_horaire_total": 10.0,
        "montant_potentiel": 20000.0,
        "taux_horaire": 2000.0,
        "statut": "non_activee"
      }
    ],
    "totaux": {
      "heures_effectuees": 0.0,
      "montant_paye": 0.0,
      "montant_restant": 60000.0,
      "taux_horaire": 2000.0
    }
  }
}
```

✅ **Vérifier** :
- 2 UE activées
- 1 UE non activée
- Montants calculés automatiquement

---

### 3.2 UE actives (pour check-in)

```http
GET http://localhost:8000/api/unites-enseignement/actives
Authorization: Bearer {votre_token}
```

**Réponse attendue** :
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "code_ue": "MTH101",
      "nom_matiere": "Mathématiques",
      "heures_effectuees": 0.0,
      "heures_restantes": 18.0,
      "volume_total": 18.0,
      "pourcentage": 0.0,
      "taux_horaire": 2000.0
    },
    {
      "id": 2,
      "code_ue": "PHY201",
      "nom_matiere": "Physique",
      "heures_effectuees": 0.0,
      "heures_restantes": 12.0,
      "volume_total": 12.0,
      "pourcentage": 0.0,
      "taux_horaire": 2000.0
    }
  ]
}
```

✅ **Vérifier** :
- Seulement les UE activées
- Chimie n'apparaît PAS (non activée)

---

### 3.3 Statistiques globales

```http
GET http://localhost:8000/api/unites-enseignement/statistiques
Authorization: Bearer {votre_token}
```

**Réponse attendue** :
```json
{
  "success": true,
  "data": {
    "nombre_ue_activees": 2,
    "volume_horaire_total": 30.0,
    "heures_effectuees": 0.0,
    "heures_restantes": 30.0,
    "pourcentage_global": 0.0,
    "montant_paye": 0.0,
    "montant_potentiel_max": 60000.0,
    "montant_restant": 60000.0,
    "taux_horaire": 2000.0
  }
}
```

✅ **Vérifier** :
- Total : 30h (18 + 12)
- Montant max : 60 000 FCFA

---

### 3.4 Détails d'une UE

```http
GET http://localhost:8000/api/unites-enseignement/1
Authorization: Bearer {votre_token}
```

**Réponse attendue** :
```json
{
  "success": true,
  "data": {
    "id": 1,
    "code_ue": "MTH101",
    "nom_matiere": "Mathématiques",
    "volume_horaire_total": 18.0,
    "heures_effectuees": 0.0,
    "heures_restantes": 18.0,
    "pourcentage_progression": 0.0,
    "montant_paye": 0.0,
    "montant_restant": 36000.0,
    "montant_max": 36000.0,
    "taux_horaire": 2000.0,
    "statut": "activee",
    "annee_academique": "2024-2025",
    "semestre": 1,
    "historique_pointages": []
  }
}
```

✅ **Vérifier** :
- Détails complets
- Historique vide (pas encore de pointages)

---

## 🎯 Étape 4 : Simuler des heures de cours (via Tinker)

Retour dans Tinker pour simuler des heures :

```php
// Trouver le vacataire et l'UE Mathématiques
$vacataire = \App\Models\User::where('email', 'chris.prof@test.com')->first();
$ueMaths = \App\Models\UniteEnseignement::where('nom_matiere', 'Mathématiques')->first();
$campus = \App\Models\Campus::first();

// Créer des incidents de présence pour simuler les heures
$incident1 = \App\Models\PresenceIncident::create([
    'user_id' => $vacataire->id,
    'campus_id' => $campus->id,
    'unite_enseignement_id' => $ueMaths->id,
    'incident_date' => now()->subDays(2),
    'notification_sent_at' => now()->subDays(2)->format('H:i:s'),
    'response_deadline' => now()->subDays(2)->addHour()->format('H:i:s'),
    'has_responded' => true,
    'responded_at' => now()->subDays(2)->addMinutes(30),
    'was_in_zone' => true,
    'status' => 'validated',
    'penalty_hours' => 4, // 4 heures de cours
]);

$incident2 = \App\Models\PresenceIncident::create([
    'user_id' => $vacataire->id,
    'campus_id' => $campus->id,
    'unite_enseignement_id' => $ueMaths->id,
    'incident_date' => now()->subDay(),
    'notification_sent_at' => now()->subDay()->format('H:i:s'),
    'response_deadline' => now()->subDay()->addHour()->format('H:i:s'),
    'has_responded' => true,
    'responded_at' => now()->subDay()->addMinutes(30),
    'was_in_zone' => true,
    'status' => 'validated',
    'penalty_hours' => 3, // 3 heures de cours
]);

echo "✅ 2 incidents créés (4h + 3h = 7h pour Mathématiques)\n";
echo "Montant calculé : 7h × 2000 = 14 000 FCFA\n";
```

---

## 🔄 Étape 5 : Vérifier les calculs

### Relancer l'API :

```http
GET http://localhost:8000/api/unites-enseignement
Authorization: Bearer {votre_token}
```

**Résultat attendu** :
```json
{
  "unites_activees": [
    {
      "nom_matiere": "Mathématiques",
      "heures_effectuees": 7.0,
      "heures_restantes": 11.0,
      "pourcentage_progression": 38.89,
      "montant_paye": 14000.0,
      "montant_restant": 22000.0,
      "montant_max": 36000.0
    }
  ]
}
```

✅ **Vérifier** :
- Heures effectuées : 7h (4 + 3)
- Heures restantes : 11h (18 - 7)
- Progression : 38.89% (7/18)
- Montant payé : 14 000 FCFA (7 × 2000)
- Montant restant : 22 000 FCFA (11 × 2000)

---

## ✅ Checklist de validation

- [ ] Données de test créées (vacataire + UE)
- [ ] Login API fonctionne
- [ ] Endpoint `/api/unites-enseignement` retourne les UE
- [ ] Endpoint `/actives` ne retourne que les UE activées
- [ ] Endpoint `/statistiques` calcule correctement
- [ ] Les montants sont calculés automatiquement
- [ ] Les heures restantes sont correctes
- [ ] La progression est en %

---

## 🎉 Si tous les tests passent

**Le système est 100% fonctionnel !** 🚀

Tu peux maintenant :
1. Créer les vues Blade (optionnel)
2. Intégrer au module paiements
3. Donner la doc au dev mobile

---

## 🐛 En cas d'erreur

### Erreur 500
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

### Relations non trouvées
Vérifier que les models sont bien importés :
```bash
composer dump-autoload
```

### Token invalide
Regénérer le token via `/api/login`

---

## 📞 Notes

- Les calculs se basent sur `penalty_hours` dans `presence_incidents`
- Le taux horaire vient toujours du profil utilisateur
- Les UE non activées n'apparaissent pas dans `/actives`

**Date** : 22 novembre 2024
