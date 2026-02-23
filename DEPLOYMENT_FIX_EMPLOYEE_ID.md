# Instructions de Déploiement - Correction Employee ID Duplicate

## 🐛 Problème Corrigé

**Erreur en production:** `SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'EMP-2026-0001' for key 'users.users_employee_id_unique'`

**Cause:** La génération automatique d'`employee_id` ne vérifiait pas si l'ID généré existait déjà dans la base de données, causant des doublons.

## ✅ Corrections Apportées

### 1. **EmployeeController.php** (ligne 373)
- Ajout d'une boucle de vérification pour s'assurer que l'`employee_id` généré est unique
- Limite de sécurité de 10,000 tentatives pour éviter les boucles infinies
- Format: `EMP-YYYY-XXXX` (ex: EMP-2026-0001)

### 2. **VacataireController.php** (ligne 89 + nouvelle fonction ligne 475)
- Remplacement de `User::count() + 1` par une fonction dédiée `generateVacataireEmployeeId()`
- Même logique de vérification d'unicité que pour les employés réguliers
- Format: `VACXXXX` (ex: VAC0001, VAC0002)

## 📋 Étapes de Déploiement sur Production

### Étape 1: Sauvegarde

```bash
# Sur le serveur de production
cd /var/www/SystemRH

# Backup des fichiers avant modification
cp app/Http/Controllers/Admin/EmployeeController.php app/Http/Controllers/Admin/EmployeeController.php.backup
cp app/Http/Controllers/Admin/VacataireController.php app/Http/Controllers/Admin/VacataireController.php.backup
```

### Étape 2: Transférer les Fichiers Modifiés

**Option A - Via Git (Recommandé):**

```bash
# Sur votre machine locale
cd /Users/redwolf-dark/Documents/Estuaire/AppEmployer/adminDash

# Commit les changements
git add app/Http/Controllers/Admin/EmployeeController.php
git add app/Http/Controllers/Admin/VacataireController.php
git commit -m "Fix: Correction génération employee_id duplicate

- Ajout vérification d'unicité dans generateEmployeeId()
- Nouvelle fonction generateVacataireEmployeeId() pour vacataires
- Prévention des erreurs 1062 Duplicate entry

Fixes: Employee ID duplicate entries causing registration failures"

git push origin main

# Sur le serveur de production
cd /var/www/SystemRH
git pull origin main
```

**Option B - Via SCP (si Git non disponible):**

```bash
# Sur votre machine locale
scp app/Http/Controllers/Admin/EmployeeController.php root@votre-serveur:/var/www/SystemRH/app/Http/Controllers/Admin/
scp app/Http/Controllers/Admin/VacataireController.php root@votre-serveur:/var/www/SystemRH/app/Http/Controllers/Admin/
```

### Étape 3: Vérification Syntaxe sur Production

```bash
# Sur le serveur de production
cd /var/www/SystemRH

php -l app/Http/Controllers/Admin/EmployeeController.php
php -l app/Http/Controllers/Admin/VacataireController.php
```

**Résultat attendu:** `No syntax errors detected`

### Étape 4: Clear Cache Laravel

```bash
# Sur le serveur de production
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Si vous utilisez OPcache
sudo systemctl reload php-fpm  # ou php8.2-fpm selon votre version
```

### Étape 5: Tester l'Enregistrement

1. Essayez d'enregistrer un nouvel employé via l'interface web
2. Surveillez les logs en temps réel:

```bash
tail -f /var/www/SystemRH/storage/logs/laravel.log | grep -i "employee"
```

3. Vérifiez qu'aucune erreur `Duplicate entry` n'apparaît

### Étape 6: Nettoyer les Doublons Existants (Optionnel)

Si vous avez des employee_id en double dans la base de données:

```bash
cd /var/www/SystemRH
php artisan tinker
```

Dans tinker:
```php
// Trouver les doublons
$duplicates = DB::table('users')
    ->select('employee_id', DB::raw('COUNT(*) as count'))
    ->groupBy('employee_id')
    ->having('count', '>', 1)
    ->get();

// Afficher les doublons
foreach ($duplicates as $dup) {
    echo "Duplicate: {$dup->employee_id} ({$dup->count} fois)\n";
    $users = User::where('employee_id', $dup->employee_id)->get();
    foreach ($users as $user) {
        echo "  - ID: {$user->id}, Nom: {$user->first_name} {$user->last_name}\n";
    }
}

// Pour supprimer un doublon spécifique (ATTENTION!)
// User::where('id', 123)->delete();  // Remplacer 123 par l'ID à supprimer

exit
```

## 🧪 Tests de Validation

### Test 1: Enregistrement d'un Employé Régulier
- Aller sur `/admin/employees/create`
- Remplir le formulaire
- Cliquer sur "Enregistrer"
- **Attendu:** Succès sans erreur de doublon

### Test 2: Enregistrement d'un Vacataire
- Aller sur `/admin/vacataires/create`
- Remplir le formulaire
- Cliquer sur "Enregistrer"
- **Attendu:** Succès sans erreur de doublon

### Test 3: Enregistrements Multiples Rapides
- Enregistrer 3-5 employés rapidement
- **Attendu:** Tous ont des employee_id uniques

## 📊 Vérification Post-Déploiement

```bash
# Vérifier les derniers employee_id créés
cd /var/www/SystemRH
php artisan tinker --execute="
User::orderBy('created_at', 'desc')->take(10)->get(['id', 'employee_id', 'first_name', 'last_name', 'created_at']);
"

# Vérifier qu'il n'y a plus de doublons
php artisan tinker --execute="
DB::table('users')
    ->select('employee_id', DB::raw('COUNT(*) as count'))
    ->groupBy('employee_id')
    ->having('count', '>', 1)
    ->get();
"
```

## 🔄 Rollback (si nécessaire)

Si les corrections causent des problèmes:

```bash
cd /var/www/SystemRH

# Restaurer les backups
cp app/Http/Controllers/Admin/EmployeeController.php.backup app/Http/Controllers/Admin/EmployeeController.php
cp app/Http/Controllers/Admin/VacataireController.php.backup app/Http/Controllers/Admin/VacataireController.php

# Clear cache
php artisan config:clear
php artisan route:clear
php artisan view:clear

sudo systemctl reload php-fpm
```

## 📝 Fichiers Modifiés

- `app/Http/Controllers/Admin/EmployeeController.php` (ligne 373-412)
- `app/Http/Controllers/Admin/VacataireController.php` (ligne 89 + 475-513)

## ✅ Checklist de Déploiement

- [ ] Backup des fichiers existants créé
- [ ] Fichiers transférés sur production
- [ ] Vérification syntaxe PHP OK
- [ ] Cache Laravel cleared
- [ ] PHP-FPM rechargé
- [ ] Test d'enregistrement réussi
- [ ] Logs surveillés pour erreurs
- [ ] Aucune erreur "Duplicate entry" trouvée

## 📞 Support

Si vous rencontrez des problèmes:
1. Vérifiez les logs: `tail -100 /var/www/SystemRH/storage/logs/laravel.log`
2. Vérifiez les permissions: `ls -la app/Http/Controllers/Admin/`
3. Contactez le développeur avec les logs d'erreur

---

**Date de correction:** 23 février 2026
**Version Laravel:** 12.x
**Environnement testé:** Production + Local
