# 🚀 GUIDE D'INSTALLATION - Attendance System Backend

## ✅ ÉTAPES DÉJÀ COMPLÉTÉES

1. ✅ Projet Laravel 12 créé
2. ✅ Configuration `.env` modifiée pour MySQL (XAMPP)

---

## 📋 PROCHAINES ÉTAPES

### 1. Créer la base de données dans XAMPP

1. Ouvre **XAMPP Control Panel**
2. Démarre **Apache** et **MySQL**
3. Clique sur **Admin** à côté de MySQL (ouvre phpMyAdmin)
4. Dans phpMyAdmin :
   - Clique sur "**New**" (Nouvelle base de données)
   - Nom : `attendance_system`
   - Collation : `utf8mb4_unicode_ci`
   - Clique sur "**Create**"

**OU** exécute ce fichier SQL (disponible dans `database/create_database.sql`) :

```sql
CREATE DATABASE IF NOT EXISTS attendance_system
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

### 2. Tester la connexion

Depuis le terminal, dans le dossier `adminDash/` :

```bash
php artisan migrate:status
```

Si la connexion fonctionne, tu verras la liste des migrations.

### 3. Exécuter les migrations

Une fois que toutes les migrations seront créées :

```bash
php artisan migrate
```

### 4. Remplir la base de données avec les données initiales

```bash
php artisan db:seed
```

### 5. Lancer le serveur de développement

```bash
php artisan serve
```

Le backend sera accessible sur : `http://localhost:8000`

---

## 🔧 CONFIGURATION ACTUELLE

### Base de données (`.env`)

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=attendance_system
DB_USERNAME=root
DB_PASSWORD=
```

### Application

```env
APP_NAME="Attendance System"
APP_URL=http://localhost:8000
```

---

## 📦 PROCHAINES TÂCHES

1. ⏭️ Créer les migrations pour toutes les 14 tables
2. ⏭️ Créer les seeders pour les données initiales (roles, permissions, etc.)
3. ⏭️ Créer les Models avec relations Eloquent
4. ⏭️ Créer les Controllers pour l'API
5. ⏭️ Définir les routes API
6. ⏭️ Configurer Laravel Sanctum pour l'authentification
7. ⏭️ Installer et configurer Firebase Cloud Messaging

---

## 🛠️ COMMANDES UTILES

### Migrations

```bash
# Créer une nouvelle migration
php artisan make:migration create_table_name

# Voir le statut des migrations
php artisan migrate:status

# Exécuter les migrations
php artisan migrate

# Revenir en arrière (rollback)
php artisan migrate:rollback

# Réinitialiser et re-migrer
php artisan migrate:fresh
```

### Models

```bash
# Créer un Model
php artisan make:model ModelName

# Créer un Model avec migration
php artisan make:model ModelName -m

# Créer un Model avec migration, controller et factory
php artisan make:model ModelName -mcf
```

### Seeders

```bash
# Créer un Seeder
php artisan make:seeder SeederName

# Exécuter tous les seeders
php artisan db:seed

# Exécuter un seeder spécifique
php artisan db:seed --class=SeederName
```

### Controllers

```bash
# Créer un controller
php artisan make:controller ControllerName

# Créer un API resource controller
php artisan make:controller API/ControllerName --api
```

### Cache & Optimisation

```bash
# Vider le cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Optimiser pour production
php artisan optimize
```

---

**Date de création** : 2025-11-18
