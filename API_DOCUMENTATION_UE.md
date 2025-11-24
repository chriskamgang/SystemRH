# 📚 Documentation API - Unités d'Enseignement (UE)

## Vue d'ensemble

Cette API permet aux **enseignants vacataires** de :
- Voir leurs unités d'enseignement (UE) attribuées
- Consulter la progression de leurs UE
- Voir les heures effectuées et les montants gagnés
- Sélectionner une UE lors du check-in

---

## 🔐 Authentification

Toutes les routes nécessitent un token Bearer Sanctum :

```
Authorization: Bearer {token}
```

---

## 📋 Endpoints disponibles

### 1. Liste des UE du vacataire connecté

**GET** `/api/unites-enseignement`

Retourne toutes les UE (activées et non activées) du vacataire connecté.

#### Réponse (200 OK)

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
        "heures_effectuees": 9.0,
        "heures_restantes": 9.0,
        "pourcentage_progression": 50.0,
        "montant_paye": 18000.0,
        "montant_restant": 18000.0,
        "montant_max": 36000.0,
        "taux_horaire": 2000.0,
        "annee_academique": "2024-2025",
        "semestre": 1,
        "statut": "activee",
        "date_activation": "2024-11-20 10:30:00"
      }
    ],
    "unites_non_activees": [
      {
        "id": 2,
        "code_ue": "CHM301",
        "nom_matiere": "Chimie",
        "volume_horaire_total": 10.0,
        "montant_potentiel": 20000.0,
        "taux_horaire": 2000.0,
        "annee_academique": "2024-2025",
        "semestre": 1,
        "statut": "non_activee",
        "date_attribution": "2024-11-15 14:20:00"
      }
    ],
    "totaux": {
      "heures_effectuees": 9.0,
      "montant_paye": 18000.0,
      "montant_restant": 18000.0,
      "taux_horaire": 2000.0
    }
  }
}
```

#### Erreurs possibles

- **403 Forbidden** : L'utilisateur n'est pas un enseignant vacataire

```json
{
  "success": false,
  "message": "Accès réservé aux enseignants vacataires"
}
```

---

### 2. UE activées (pour check-in)

**GET** `/api/unites-enseignement/actives`

Retourne uniquement les UE activées avec des heures restantes disponibles.
**Utiliser cette route lors du check-in** pour afficher les matières disponibles.

#### Réponse (200 OK)

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "code_ue": "MTH101",
      "nom_matiere": "Mathématiques",
      "heures_effectuees": 9.0,
      "heures_restantes": 9.0,
      "volume_total": 18.0,
      "pourcentage": 50.0,
      "taux_horaire": 2000.0
    },
    {
      "id": 3,
      "code_ue": "PHY201",
      "nom_matiere": "Physique",
      "heures_effectuees": 7.0,
      "heures_restantes": 5.0,
      "volume_total": 12.0,
      "pourcentage": 58.33,
      "taux_horaire": 2000.0
    }
  ]
}
```

#### Utilisation

```dart
// Flutter/Dart example
Future<List<UE>> getActiveUEs() async {
  final response = await http.get(
    Uri.parse('$baseUrl/api/unites-enseignement/actives'),
    headers: {'Authorization': 'Bearer $token'},
  );

  if (response.statusCode == 200) {
    final data = json.decode(response.body);
    return (data['data'] as List)
        .map((ue) => UE.fromJson(ue))
        .toList();
  }
  throw Exception('Failed to load UEs');
}
```

---

### 3. Détails d'une UE spécifique

**GET** `/api/unites-enseignement/{id}`

Retourne les détails d'une UE avec l'historique des pointages.

#### Paramètres

- `id` (integer) : ID de l'UE

#### Réponse (200 OK)

```json
{
  "success": true,
  "data": {
    "id": 1,
    "code_ue": "MTH101",
    "nom_matiere": "Mathématiques",
    "volume_horaire_total": 18.0,
    "heures_effectuees": 9.0,
    "heures_restantes": 9.0,
    "pourcentage_progression": 50.0,
    "montant_paye": 18000.0,
    "montant_restant": 18000.0,
    "montant_max": 36000.0,
    "taux_horaire": 2000.0,
    "statut": "activee",
    "annee_academique": "2024-2025",
    "semestre": 1,
    "historique_pointages": [
      {
        "id": 5,
        "date": "2024-11-22",
        "heures": 4.0,
        "status": "validated",
        "campus": "Campus Principal"
      },
      {
        "id": 3,
        "date": "2024-11-20",
        "heures": 3.0,
        "status": "validated",
        "campus": "Campus Principal"
      }
    ]
  }
}
```

#### Erreurs possibles

- **404 Not Found** : UE introuvable ou n'appartient pas au vacataire

```json
{
  "success": false,
  "message": "UE non trouvée"
}
```

---

### 4. Statistiques globales

**GET** `/api/unites-enseignement/statistiques`

Retourne les statistiques globales de toutes les UE activées du vacataire.

#### Réponse (200 OK)

```json
{
  "success": true,
  "data": {
    "nombre_ue_activees": 2,
    "volume_horaire_total": 30.0,
    "heures_effectuees": 16.0,
    "heures_restantes": 14.0,
    "pourcentage_global": 53.33,
    "montant_paye": 32000.0,
    "montant_potentiel_max": 60000.0,
    "montant_restant": 28000.0,
    "taux_horaire": 2000.0
  }
}
```

---

## 🎯 Workflow d'utilisation

### Écran "Mes UE" dans l'app mobile

1. **Au chargement de l'écran** :
   ```
   GET /api/unites-enseignement
   ```

2. **Afficher deux sections** :
   - UE activées (avec progression)
   - UE non activées (en attente)

3. **Pour chaque UE activée, afficher** :
   - Nom de la matière
   - Barre de progression (pourcentage_progression)
   - Heures effectuées / Volume total
   - Montant gagné
   - Montant restant potentiel

### Lors du check-in

1. **Récupérer les UE disponibles** :
   ```
   GET /api/unites-enseignement/actives
   ```

2. **Afficher la liste des matières** avec :
   - Nom de la matière
   - Heures restantes
   - Pourcentage de progression

3. **L'utilisateur sélectionne une UE**

4. **Lors du check-in, envoyer l'ID de l'UE** :
   ```
   POST /api/attendance/check-in
   {
     "campus_id": 1,
     "latitude": 4.0511,
     "longitude": 9.7679,
     "unite_enseignement_id": 1  // ← Important !
   }
   ```

5. **Lors du check-out, le montant est calculé automatiquement** :
   - Heures travaillées × Taux horaire du vacataire

---

## 💡 Exemples d'interface mobile

### Card UE activée

```
┌─────────────────────────────────────┐
│ 📘 Mathématiques (MTH101)            │
│                                     │
│ Volume: 18h                         │
│ Effectué: 9h (50%)                  │
│ Reste: 9h                           │
│ ████████████░░░░░░░░░░░░             │
│                                     │
│ 💰 Gagné: 18 000 FCFA               │
│ 📊 Potentiel restant: 18 000 FCFA   │
│                                     │
│ [Voir détails]                      │
└─────────────────────────────────────┘
```

### Card UE non activée

```
┌─────────────────────────────────────┐
│ 🟠 Chimie (CHM301)                   │
│                                     │
│ Volume: 10h                         │
│ Statut: En attente d'activation     │
│                                     │
│ 💰 Potentiel: 20 000 FCFA           │
└─────────────────────────────────────┘
```

### Sélection UE lors du check-in

```
┌─────────────────────────────────────┐
│ Sélectionner la matière enseignée   │
├─────────────────────────────────────┤
│                                     │
│ ○ 📘 Mathématiques                   │
│   Reste: 9h / 18h (50%)             │
│                                     │
│ ○ 📗 Physique                        │
│   Reste: 5h / 12h (58%)             │
│                                     │
│ [Confirmer]                         │
└─────────────────────────────────────┘
```

---

## 🔄 Calcul automatique des paiements

### Comment ça fonctionne ?

1. **L'admin attribue une UE** :
   - Matière : Mathématiques
   - Volume : 18h
   - Pas de taux dans l'UE !

2. **Le taux horaire vient du profil du vacataire** :
   - Exemple : Chris = 2000 FCFA/h

3. **Le vacataire fait check-in** :
   - Sélectionne "Mathématiques"
   - Travaille 4h
   - Check-out

4. **Le montant est calculé automatiquement** :
   - 4h × 2000 FCFA = 8 000 FCFA
   - Progression : 4h / 18h = 22%
   - Reste : 14h

5. **L'admin voit immédiatement** :
   - Dans le module paiements
   - Total à payer pour Chris
   - Sans calcul manuel !

---

## ⚠️ Règles importantes

1. **Seuls les enseignants vacataires** peuvent accéder à ces endpoints
2. **Les UE non activées** ne peuvent pas être utilisées pour le check-in
3. **Le taux horaire** est toujours celui du profil du vacataire
4. **Une UE ne peut pas être pointée** si `heures_restantes <= 0`
5. **L'historique des pointages** est lié aux `presence_incidents`

---

## 🧪 Tests avec Postman/Insomnia

### 1. Obtenir un token

```http
POST /api/login
Content-Type: application/json

{
  "email": "chris@example.com",
  "password": "password"
}
```

### 2. Lister les UE

```http
GET /api/unites-enseignement
Authorization: Bearer {token}
```

### 3. UE actives pour check-in

```http
GET /api/unites-enseignement/actives
Authorization: Bearer {token}
```

### 4. Détails d'une UE

```http
GET /api/unites-enseignement/1
Authorization: Bearer {token}
```

### 5. Statistiques

```http
GET /api/unites-enseignement/statistiques
Authorization: Bearer {token}
```

---

## 📝 Modèles de données Flutter/Dart

```dart
class UniteEnseignement {
  final int id;
  final String? codeUe;
  final String nomMatiere;
  final double volumeHoraireTotal;
  final double heuresEffectuees;
  final double heuresRestantes;
  final double pourcentageProgression;
  final double montantPaye;
  final double montantRestant;
  final double montantMax;
  final double tauxHoraire;
  final String statut;
  final String? anneeAcademique;
  final int? semestre;

  UniteEnseignement({
    required this.id,
    this.codeUe,
    required this.nomMatiere,
    required this.volumeHoraireTotal,
    required this.heuresEffectuees,
    required this.heuresRestantes,
    required this.pourcentageProgression,
    required this.montantPaye,
    required this.montantRestant,
    required this.montantMax,
    required this.tauxHoraire,
    required this.statut,
    this.anneeAcademique,
    this.semestre,
  });

  factory UniteEnseignement.fromJson(Map<String, dynamic> json) {
    return UniteEnseignement(
      id: json['id'],
      codeUe: json['code_ue'],
      nomMatiere: json['nom_matiere'],
      volumeHoraireTotal: (json['volume_horaire_total'] as num).toDouble(),
      heuresEffectuees: (json['heures_effectuees'] as num).toDouble(),
      heuresRestantes: (json['heures_restantes'] as num).toDouble(),
      pourcentageProgression: (json['pourcentage_progression'] as num).toDouble(),
      montantPaye: (json['montant_paye'] as num).toDouble(),
      montantRestant: (json['montant_restant'] as num).toDouble(),
      montantMax: (json['montant_max'] as num).toDouble(),
      tauxHoraire: (json['taux_horaire'] as num).toDouble(),
      statut: json['statut'],
      anneeAcademique: json['annee_academique'],
      semestre: json['semestre'],
    );
  }
}
```

---

## 📞 Support

Pour toute question ou problème, contactez l'équipe backend.

**Version** : 1.0.0
**Date** : 22 novembre 2024
