# 🔄 Quand votre adresse IP change

## ✅ Solution automatique (RECOMMANDÉ)

Chaque fois que votre IP change, exécutez simplement cette commande:

```bash
cd /Users/redwolf-dark/Documents/Estuaire/AppEmployer/adminDash
./update-mobile-local.sh
```

Le script va:
- ✅ Détecter automatiquement votre nouvelle IP
- ✅ Mettre à jour l'application mobile
- ✅ Créer un backup de sécurité
- ✅ Vous montrer la nouvelle configuration

---

## 📱 Ensuite, redémarrez l'application mobile

### Si l'application Flutter tourne déjà:
Dans le terminal Flutter, tapez **R** (majuscule) pour Hot Restart

### Si l'application n'est pas lancée:
```bash
cd /Users/redwolf-dark/Documents/Estuaire/AppEmployer/attendance_app
flutter run
```

---

## 🚀 Configuration actuelle (Mise à jour: $(date))

```
Adresse IP:  192.168.1.180
Port:        8002
URL API:     http://192.168.1.180:8002/api
```

---

## ⚠️ IMPORTANT: Démarrer le serveur Laravel

N'oubliez pas de démarrer le serveur avec:

```bash
cd /Users/redwolf-dark/Documents/Estuaire/AppEmployer/adminDash
php artisan serve --host=0.0.0.0 --port=8002
```

Le `--host=0.0.0.0` est **OBLIGATOIRE** pour que l'iPhone puisse se connecter!

---

## 🔍 Vérifier votre IP actuelle

À tout moment, vous pouvez vérifier votre IP avec:

```bash
ifconfig | grep "inet " | grep -v 127.0.0.1 | head -1
```

---

## 📝 Récapitulatif rapide

Quand l'IP change:

1. **Mettez à jour l'app mobile:**
   ```bash
   cd /Users/redwolf-dark/Documents/Estuaire/AppEmployer/adminDash
   ./update-mobile-local.sh
   ```

2. **Redémarrez Flutter:**
   - Tapez **R** dans le terminal Flutter

3. **Vérifiez le serveur Laravel:**
   ```bash
   php artisan serve --host=0.0.0.0 --port=8002
   ```

4. **Testez!**

---

Voilà! Simple et rapide! 🎉
