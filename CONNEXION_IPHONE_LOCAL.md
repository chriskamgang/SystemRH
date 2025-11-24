# 📱 Connexion iPhone au Backend (Réseau Local WiFi)

## ✅ Configuration pour connexion locale

L'iPhone et le Mac doivent être sur le **MÊME réseau WiFi**.

---

## 🚀 Étapes rapides

### 1. Démarrer le serveur Laravel avec accès réseau

```bash
cd /Users/redwolf-dark/Documents/Estuaire/AppEmployer/adminDash
php artisan serve --host=0.0.0.0 --port=8002
```

**IMPORTANT:** Le `--host=0.0.0.0` est obligatoire pour que l'iPhone puisse se connecter!

### 2. Configurer l'app Flutter avec l'IP locale

```bash
./update-mobile-local.sh
```

Ce script va:
- ✅ Détecter automatiquement l'IP de votre Mac
- ✅ Mettre à jour le fichier Flutter
- ✅ Créer un backup

### 3. Redémarrer l'app Flutter

Dans le terminal Flutter:
```bash
cd ../attendance_app
flutter run
```

OU si l'app tourne déjà, tapez `R` (majuscule) pour Hot Restart.

---

## 📝 Informations réseau actuelles

```
IP actuelle du Mac: 172.20.10.5
Port Laravel:       8002
URL de l'API:       http://172.20.10.5:8002/api
```

---

## 🔍 Test de connexion

### Depuis Safari sur l'iPhone:
```
http://172.20.10.5:8002
```

Vous devriez voir la page Laravel.

### Depuis le terminal Mac:
```bash
curl http://172.20.10.5:8002/api/campuses
```

---

## ⚠️ Problèmes fréquents

### ❌ "Failed to connect" ou timeout

**Solution 1:** Vérifiez que iPhone et Mac sont sur le même WiFi
- Mac: Icône WiFi en haut à droite
- iPhone: Réglages → WiFi

**Solution 2:** Redémarrez le serveur avec `--host=0.0.0.0`
```bash
php artisan serve --host=0.0.0.0 --port=8002
```

**Solution 3:** Désactivez le pare-feu Mac temporairement
- Préférences Système → Sécurité → Pare-feu

### ❌ L'IP a changé

Si votre Mac change de réseau WiFi, l'IP peut changer.

Relancez simplement:
```bash
./update-mobile-local.sh
```

---

## 🎯 Commandes utiles

### Vérifier l'IP actuelle:
```bash
ifconfig | grep "inet " | grep -v 127.0.0.1
```

### Configurer l'app mobile:
```bash
./update-mobile-local.sh
```

### Vérifier la configuration Flutter:
```bash
grep "baseUrl" ../attendance_app/lib/utils/constants.dart
```

---

## ✅ Récapitulatif

1. **Démarrez le serveur:**
   ```bash
   php artisan serve --host=0.0.0.0 --port=8002
   ```

2. **Configurez l'app mobile:**
   ```bash
   ./update-mobile-local.sh
   ```

3. **Redémarrez Flutter:**
   ```bash
   cd ../attendance_app && flutter run
   ```

4. **Testez sur l'iPhone!**

---

Bonne chance! 🚀
