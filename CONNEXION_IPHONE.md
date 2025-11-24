# 📱 Configuration pour connexion iPhone → Backend Laravel

## ✅ Configuration terminée!

Votre application Flutter est maintenant configurée pour se connecter au backend Laravel depuis votre iPhone physique.

---

## 🔧 Étapes à suivre:

### 1️⃣ **Démarrer le serveur Laravel avec accès réseau**

**IMPORTANT:** Au lieu de `php artisan serve --port=8002`, utilisez:

```bash
cd /Users/redwolf-dark/Documents/Estuaire/AppEmployer/adminDash
php artisan serve --host=0.0.0.0 --port=8002
```

**Explication:**
- `--host=0.0.0.0` permet au serveur d'accepter les connexions depuis n'importe quelle IP du réseau local
- Sans cette option, seul localhost (127.0.0.1) peut se connecter

Vous devriez voir:
```
INFO  Server running on [http://0.0.0.0:8002].
```

---

### 2️⃣ **Vérifier que iPhone et Mac sont sur le même réseau WiFi**

**CRITIQUE:** Votre iPhone et votre Mac doivent être connectés au **MÊME réseau WiFi**.

Pour vérifier:
- **Mac:** Cliquez sur l'icône WiFi en haut à droite → notez le nom du réseau
- **iPhone:** Réglages → WiFi → vérifiez que c'est le même réseau

---

### 3️⃣ **Redémarrer l'application Flutter**

Dans le terminal où vous exécutez Flutter:

```bash
# Arrêtez l'application (Ctrl+C si elle tourne)
cd /Users/redwolf-dark/Documents/Estuaire/AppEmployer/attendance_app

# Redémarrez avec hot restart complet
flutter run
```

OU si l'app tourne déjà, dans le terminal Flutter tapez:
- `R` (majuscule) pour Hot Restart complet

---

### 4️⃣ **Tester la connexion**

Sur l'iPhone, ouvrez l'application et essayez de vous connecter.

**Si ça ne fonctionne pas**, testez la connexion manuellement:

#### Test 1: Depuis le navigateur Safari de l'iPhone

Ouvrez Safari et allez sur:
```
http://172.20.10.5:8002
```

Vous devriez voir la page d'accueil Laravel.

#### Test 2: Depuis le terminal du Mac

```bash
curl http://172.20.10.5:8002/api/campuses
```

Si ça fonctionne, vous verrez du JSON.

---

## 🔍 Dépannage

### ❌ Erreur: "Failed to connect" ou "Network Error"

**Solution 1:** Vérifiez le firewall du Mac

```bash
# Ouvrir les préférences système
# Aller dans: Sécurité et confidentialité → Pare-feu
# Si le pare-feu est activé, ajoutez une exception pour PHP
```

**Solution 2:** Redémarrez le serveur Laravel

Arrêtez avec `Ctrl+C` puis relancez:
```bash
php artisan serve --host=0.0.0.0 --port=8002
```

**Solution 3:** Vérifiez que l'IP n'a pas changé

Si votre Mac change de réseau WiFi, l'IP peut changer. Pour vérifier l'IP actuelle:

```bash
ifconfig | grep "inet " | grep -v 127.0.0.1
```

Si l'IP a changé, modifiez à nouveau le fichier:
```
attendance_app/lib/utils/constants.dart
```

---

### ❌ Erreur: "Connection refused"

Le serveur Laravel n'écoute probablement que sur 127.0.0.1.

**Solution:** Redémarrez avec `--host=0.0.0.0`

---

### ❌ L'application Flutter crash ou timeout

**Solution:** Augmentez le timeout dans api_service.dart

Le timeout actuel est probablement trop court pour le réseau WiFi.

---

## 📝 Résumé des changements effectués:

### ✅ Fichier modifié:
```
attendance_app/lib/utils/constants.dart
```

**Ancien:**
```dart
static const String baseUrl = 'http://127.0.0.1:8002/api';
```

**Nouveau:**
```dart
static const String baseUrl = 'http://172.20.10.5:8002/api';
```

### ✅ IP détectée:
```
172.20.10.5
```

---

## 🎯 Commandes rapides

### Démarrer le backend (avec accès réseau):
```bash
cd /Users/redwolf-dark/Documents/Estuaire/AppEmployer/adminDash
php artisan serve --host=0.0.0.0 --port=8002
```

### Démarrer l'app Flutter sur iPhone:
```bash
cd /Users/redwolf-dark/Documents/Estuaire/AppEmployer/attendance_app
flutter run
```

### Vérifier l'IP actuelle du Mac:
```bash
ifconfig | grep "inet " | grep -v 127.0.0.1
```

---

## ⚠️ Notes importantes:

1. **L'IP peut changer** si vous changez de réseau WiFi ou redémarrez votre Mac
2. **Utilisez toujours `--host=0.0.0.0`** pour le serveur Laravel quand vous testez sur appareil physique
3. **iPhone et Mac doivent être sur le même WiFi**
4. **Le firewall** peut bloquer les connexions - vérifiez les paramètres de sécurité

---

Bonne chance! 🚀
