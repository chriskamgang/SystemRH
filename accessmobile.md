# Accès mobile — Étudiant / Chauffeur / Enseignant

> Comptes de test présents en base locale (`users`), au 2026-09-08.
> Les mots de passe / PIN ne sont **pas** stockés en clair (hachés). Voir §4 pour les réinitialiser.

---

## 1. Comment chacun se connecte

L'app mobile bus démarre par un **aiguillage** sur l'email (`POST /api/bus/aiguillage`),
qui répond quel canal utiliser :

| Profil | Canal | Endpoint | Identifiants |
|---|---|---|---|
| **Étudiant** | PIN 4 chiffres | `POST /api/bus/connexion-pin` | email + `pin` |
| **Chauffeur** | mot de passe | `POST /api/bus/connexion` | email + `password` |
| **Enseignant** | mot de passe (espace RH) | `POST /api/login` | email + `password` |

Notes :
- Un étudiant sans PIN passe par `POST /api/bus/inscription-pin` (email + PIN choisi) — l'email n'est pas vérifié, aucun code n'est envoyé.
- Le PIN se bloque après plusieurs essais ratés (~15 min), compteur porté par le compte (`pin_essais`, `pin_bloque_jusqu_a`).
- Un compte doit avoir `actif_bus = 1` (bus) ou `is_active = 1` (RH).

---

## 2. Comptes ÉTUDIANT (`role_bus = etudiant`, PIN défini, actifs)

| ID | Nom | Email | Téléphone |
|---|---|---|---|
| 3 | Steve Boussa | `etudiant@insam.edu` | +237690123456 |
| 6 | INSAM Etudiant 1-1 | `etudiant1-1@insam.bf` | +22670200101 |
| 7 | INSAM Etudiant 1-2 | `etudiant1-2@insam.bf` | +22670200102 |
| 8 | INSAM Etudiant 1-3 | `etudiant1-3@insam.bf` | +22670200103 |
| 10 | INSAM Etudiant 2-1 | `etudiant2-1@insam.bf` | +22670200201 |
| 11 | INSAM Etudiant 2-2 | `etudiant2-2@insam.bf` | +22670200202 |
| 12 | INSAM Etudiant 2-3 | `etudiant2-3@insam.bf` | +22670200203 |
| 14 | INSAM Etudiant 3-1 | `etudiant3-1@insam.bf` | +22670200301 |
| 15 | INSAM Etudiant 3-2 | `etudiant3-2@insam.bf` | +22670200302 |
| 16 | INSAM Etudiant 3-3 | `etudiant3-3@insam.bf` | +22670200303 |
| 18 | INSAM Etudiant 4-1 | `etudiant4-1@insam.bf` | +22670200401 |
| 19 | INSAM Etudiant 4-2 | `etudiant4-2@insam.bf` | +22670200402 |
| 20 | INSAM Etudiant 4-3 | `etudiant4-3@insam.bf` | +22670200403 |
| 22 | INSAM Etudiant 5-1 | `etudiant5-1@insam.bf` | +22670200501 |
| 23 | INSAM Etudiant 5-2 | `etudiant5-2@insam.bf` | +22670200502 |
| 24 | INSAM Etudiant 5-3 | `etudiant5-3@insam.bf` | +22670200503 |
| 39 | Jr Kira | `jrkira@gmail.com` | 690112233 |
| 40 | Jr Kira | `jr@gmail.com` | 677889900 |

**Compte de démo conseillé : `etudiant@insam.edu`** (rattaché à une ligne / point de ramassage).

---

## 3. Comptes CHAUFFEUR (`role_bus = chauffeur`, actifs)

| ID | Nom | Email | Téléphone |
|---|---|---|---|
| 4 | Paul Mbarga | `chauffeur@insam.edu` | 699001122 |
| 5 | INSAM Chauffeur 1 | *(aucun email)* | +22670100001 |
| 9 | INSAM Chauffeur 2 | *(aucun email)* | +22670100002 |
| 13 | INSAM Chauffeur 3 | *(aucun email)* | +22670100003 |
| 17 | INSAM Chauffeur 4 | *(aucun email)* | +22670100004 |
| 21 | INSAM Chauffeur 5 | *(aucun email)* | +22670100005 |
| 36 | Steve Bou | *(aucun email)* | 658895572 |

**Compte de démo conseillé : `chauffeur@insam.edu`** — c'est le seul chauffeur avec un email,
donc le seul utilisable tel quel via l'aiguillage par email. Les 6 autres n'ont pas d'email
et ne peuvent pas se connecter en l'état : leur donner une adresse au back-office d'abord.

---

## 4. Comptes ENSEIGNANT (`employee_type = enseignant_titulaire`, espace `rh`)

2 comptes existent en base. La requête de listing a été bloquée avant que je puisse
extraire noms et emails — à compléter avec :

```sql
SELECT id, first_name, last_name, email, employee_id, espace, is_active
FROM users WHERE employee_type LIKE 'enseignant%';
```

Comptes RH connus par le seeder `Phase1234TestSeeder` (mot de passe `password123`) :

| Email | Rôle |
|---|---|
| `thomas.kamga@insam.cm` | Employé permanent |
| `clarisse.tchoumi@insam.cm` | Semi-permanent |
| `jean.mbongo@university.ga` | Chef de département (manager, évaluateur) |
| `admin@gmail.com` | Admin |

---

## 5. Définir / réinitialiser un accès

Les secrets étant hachés, les valeurs ci-dessous sont à **poser** vous-même :

```bash
php artisan tinker
```

```php
// PIN étudiant -> 1234
$u = App\Models\User::where('email','etudiant@insam.edu')->first();
$u->forceFill(['pin' => Hash::make('1234'), 'pin_essais' => 0, 'pin_bloque_jusqu_a' => null])->save();

// Mot de passe chauffeur -> password123
$c = App\Models\User::where('email','chauffeur@insam.edu')->first();
$c->forceFill(['password' => Hash::make('password123')])->save();

// Donner un email à un chauffeur qui n'en a pas (sinon connexion impossible)
$c5 = App\Models\User::find(5);
$c5->forceFill(['email' => 'chauffeur1@insam.edu', 'password' => Hash::make('password123')])->save();
```

Après quoi :

| Profil | Email | Secret |
|---|---|---|
| Étudiant | `etudiant@insam.edu` | PIN `1234` |
| Chauffeur | `chauffeur@insam.edu` | `password123` |
| Enseignant | `thomas.kamga@insam.cm` | `password123` |
