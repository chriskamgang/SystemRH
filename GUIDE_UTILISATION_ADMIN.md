# 📖 Guide d'Utilisation - Interface Admin

## 🎯 Comment utiliser le système UE

### 1️⃣ Créer un Vacataire

```
1. Connecte-toi au dashboard admin
2. Va dans le menu "Vacataires"
3. Clique sur "Nouveau Vacataire" (bouton bleu en haut à droite)
4. Remplis le formulaire :
   - Prénom et Nom
   - Email (unique)
   - Téléphone (optionnel)
   - Taux horaire (ex: 2000 FCFA/h)
   - Sélectionne au moins 1 campus
5. Clique "Créer le Vacataire"
```

**Informations importantes** :
- Mot de passe par défaut : `password123`
- Le vacataire peut le changer à la première connexion
- Le type d'employé est automatiquement défini comme "enseignant_vacataire"

---

### 2️⃣ Voir la Liste des Vacataires

```
1. Menu "Vacataires"
2. Tu vois tous les enseignants vacataires
```

**Pour chaque vacataire, tu as 4 actions** :
- 📚 **Livre (violet)** : Gérer les UE
- 👁️ **Œil (bleu)** : Voir les détails
- ✏️ **Crayon (indigo)** : Modifier
- 🗑️ **Poubelle (rouge)** : Supprimer

---

### 3️⃣ Attribuer des UE à un Vacataire

#### Option A : Depuis la liste des vacataires
```
1. Clique sur l'icône 📚 (livre violet) à droite du vacataire
2. Tu arrives sur la page "Unités d'Enseignement"
3. Clique "Attribuer nouvelle UE" (bouton bleu)
```

#### Option B : Depuis le profil du vacataire
```
1. Clique sur l'icône 👁️ pour voir le profil
2. Clique "Gérer les UE" (bouton violet en haut)
3. Clique "Attribuer nouvelle UE"
```

#### Remplir le formulaire d'attribution
```
1. Vacataire : Déjà sélectionné si tu viens de son profil
2. Code UE : Optionnel (ex: MTH101)
3. Nom de la matière : Ex: Mathématiques
4. Volume horaire : Ex: 18 heures
5. Année académique : Ex: 2024-2025
6. Semestre : 1 ou 2
7. Cocher "Activer immédiatement" si tu veux que le vacataire puisse pointer tout de suite
8. Cliquer "Attribuer l'UE"
```

**Pendant la saisie** :
- Le système affiche le taux horaire du vacataire
- Il calcule automatiquement le montant maximum (volume × taux)

---

### 4️⃣ Gérer les UE d'un Vacataire

Sur la page "Unités d'Enseignement" du vacataire, tu vois :

#### Statistiques en haut
- Total UE Activées
- Heures Effectuées
- Montant Payé
- UE Non Activées

#### Section "UE ACTIVÉES" (vert)
Pour chaque UE activée :
- Nom de la matière + code
- Volume horaire total
- Heures effectuées
- Heures restantes
- Barre de progression visuelle
- Montants (payé, restant, maximum)

**Actions disponibles** :
- 👁️ Voir détails
- ✏️ Modifier
- ⏸️ Désactiver (seulement si 0 heures effectuées)

#### Section "UE NON ACTIVÉES" (orange)
Pour chaque UE non activée :
- Nom de la matière + code
- Volume horaire
- Montant potentiel
- Date d'attribution

**Actions disponibles** :
- ✅ Activer (bouton vert)
- ✏️ Modifier
- 🗑️ Supprimer

---

### 5️⃣ Activer une UE

**Pourquoi activer une UE ?**
- Une UE non activée est attribuée mais le vacataire NE PEUT PAS encore pointer
- Une fois activée, le vacataire peut la sélectionner lors du check-in

**Comment activer** :
```
1. Va sur la page UE du vacataire
2. Dans la section "UE NON ACTIVÉES"
3. Clique le bouton vert "Activer"
4. L'UE passe dans la section "UE ACTIVÉES"
```

**Le vacataire peut maintenant** :
- Voir cette UE dans son app mobile
- La sélectionner lors du check-in
- Commencer à accumuler des heures

---

### 6️⃣ Modifier une UE

```
1. Clique sur l'icône ✏️ (crayon) de l'UE
2. Modifie les informations :
   - Code UE
   - Nom de la matière
   - Volume horaire (attention si déjà des heures effectuées !)
   - Année académique
   - Semestre
3. Clique "Enregistrer"
```

**⚠️ Attention** :
- Si le vacataire a déjà effectué des heures, un avertissement s'affiche
- Tu ne peux pas mettre un volume horaire inférieur aux heures déjà effectuées

---

### 7️⃣ Désactiver une UE

```
1. Clique sur l'icône ⏸️ (pause) de l'UE
2. Confirme la désactivation
```

**Conditions** :
- ❌ Impossible si des heures ont déjà été effectuées
- ✅ Possible seulement si 0 heures effectuées

**Effet** :
- L'UE repasse en "non activée"
- Le vacataire ne peut plus pointer pour cette matière
- L'UE reste attribuée

---

### 8️⃣ Supprimer une UE

```
1. Dans la section "UE NON ACTIVÉES"
2. Clique sur l'icône 🗑️ (poubelle)
3. Confirme la suppression
```

**Conditions** :
- ❌ Impossible si des heures ont été effectuées
- ✅ Possible seulement pour les UE non activées avec 0 heures

---

### 9️⃣ Voir les Détails d'une UE

```
1. Clique sur l'icône 👁️ (œil) de l'UE
2. Tu vois :
   - Informations complètes
   - Historique des pointages
   - Progression détaillée
```

---

### 🔟 Comprendre les Calculs Automatiques

#### Le Taux Horaire
```
Défini dans le profil du vacataire
Exemple : Chris = 2000 FCFA/h
```

#### Le Volume Horaire
```
Défini dans l'UE
Exemple : Mathématiques = 18h
```

#### Montant Maximum
```
Volume × Taux
18h × 2000 = 36 000 FCFA
```

#### Heures Effectuées
```
Calculées automatiquement quand le vacataire :
1. Fait check-in en sélectionnant la matière
2. Donne cours
3. Fait check-out

Exemple : 4h de cours
```

#### Montant Payé
```
Heures effectuées × Taux
4h × 2000 = 8 000 FCFA
```

#### Heures Restantes
```
Volume - Heures effectuées
18h - 4h = 14h
```

#### Montant Restant
```
Heures restantes × Taux
14h × 2000 = 28 000 FCFA
```

#### Pourcentage de Progression
```
(Heures effectuées / Volume) × 100
(4h / 18h) × 100 = 22%
```

**Tout est calculé automatiquement, aucun calcul manuel !**

---

## 🎨 Navigation Rapide

### Menu Principal
```
Dashboard
├── Vacataires
│   ├── Liste des vacataires
│   ├── Nouveau vacataire
│   └── [Vacataire X]
│       ├── Détails
│       ├── Modifier
│       └── Gérer les UE ← NOUVEAU !
│           ├── Liste des UE
│           ├── Attribuer nouvelle UE
│           └── [UE X]
│               ├── Voir détails
│               ├── Modifier
│               ├── Activer/Désactiver
│               └── Supprimer
```

---

## ❓ Questions Fréquentes

### Pourquoi je ne vois pas mon vacataire dans la liste ?

**Causes possibles** :
1. Il n'a pas le bon `employee_type`
2. Il a été créé avec un autre système

**Solution** :
- J'ai corrigé le controller
- Maintenant tous les vacataires avec `employee_type = 'enseignant_vacataire'` s'affichent
- Les nouveaux vacataires sont automatiquement créés avec le bon type

### Où attribuer les UE ?

**2 chemins** :
1. Liste vacataires → Icône 📚 (livre violet) → Attribuer UE
2. Profil vacataire → "Gérer les UE" → Attribuer UE

### Quelle est la différence entre "attribué" et "activé" ?

- **Attribué** : L'UE est enregistrée mais pas encore utilisable
- **Activé** : Le vacataire peut pointer pour cette matière

### Puis-je modifier une UE après activation ?

**Oui**, mais :
- ✅ Tu peux modifier le nom, code, année, semestre
- ⚠️ Attention au volume horaire si des heures sont déjà effectuées
- ❌ Tu ne peux pas réduire le volume en-dessous des heures effectuées

### Puis-je supprimer une UE activée ?

**Non**, seulement si :
- L'UE est non activée
- ET aucune heure n'a été effectuée

**Sinon**, tu peux :
- La désactiver (si 0 heures)
- Ou la garder active jusqu'à la fin

---

## 🚨 Messages d'Erreur Courants

### "Cette UE est déjà activée"
Tu essaies d'activer une UE déjà active.

### "Impossible de désactiver une UE avec des heures déjà pointées"
Le vacataire a déjà effectué des heures. Tu ne peux pas désactiver.

### "Impossible de supprimer une UE avec des heures déjà pointées"
Des heures sont enregistrées. Suppression impossible.

### "Cette UE ne vous appartient pas"
(Côté mobile) Le vacataire essaie d'utiliser une UE qui ne lui est pas attribuée.

### "Cette UE n'est pas encore activée"
(Côté mobile) Le vacataire essaie de pointer pour une UE non activée.

---

## ✅ Workflow Complet Exemple

### Scenario : Embaucher Chris comme vacataire

```
1. Créer le compte vacataire
   ✅ Prénom: Chris
   ✅ Nom: Professeur
   ✅ Email: chris@example.com
   ✅ Taux: 2000 FCFA/h
   ✅ Campus: Campus Principal

2. Attribuer des UE
   ✅ Mathématiques : 18h
   ✅ Physique : 12h
   ☑️ Activer immédiatement

3. Chris donne cours
   📱 Check-in → Sélectionne "Mathématiques"
   🎓 Donne cours 4h
   📱 Check-out

4. Vérifier la progression
   📊 Heures effectuées : 4h / 18h (22%)
   💰 Montant payé : 8 000 FCFA
   📈 Reste : 14h → 28 000 FCFA

5. Paiement fin de mois
   💵 Total Chris : Somme de toutes ses UE
   🧾 Fiche de paie automatique
```

---

## 📞 Aide

Si quelque chose ne fonctionne pas :
1. Vérifie que le vacataire a `employee_type = 'enseignant_vacataire'`
2. Vérifie que l'UE est activée
3. Consulte les logs Laravel : `tail -f storage/logs/laravel.log`

**Fichiers de documentation** :
- `README_UE.md` - Vue d'ensemble
- `TEST_QUICK_UE.md` - Tests
- `API_DOCUMENTATION_UE.md` - API mobile
- `FINAL_SUMMARY_UE_COMPLETE.md` - Résumé complet

---

**Date** : 22 novembre 2024
**Version** : 1.0.0
