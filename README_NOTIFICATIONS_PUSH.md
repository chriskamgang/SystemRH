# 📱 Système de Notifications Push - Mode d'Emploi Rapide

## ✅ Statut Actuel: TOUT EST CONFIGURÉ!

Firebase API V1 est **100% opérationnel** et prêt à envoyer des notifications.

---

## 🎯 Ce Que Le Système Fait

### 1. Envoi Automatique de Notifications
Tous les jours ouvrables:
- **13h00** → Employés permanents/semi-permanents
- **14h00** → Employés temporaires (vacataires)

**Message:** "Êtes-vous toujours en place au [Campus]?"
**Bouton:** "OUI, je suis en place"

### 2. Suivi des Réponses
- ✅ Employé clique → Incident résolu
- ❌ Pas de réponse → Incident créé (admin doit valider)

### 3. Gestion Admin
- Valider ou ignorer les incidents
- Voir les statistiques
- Configurer les heures et pénalités

---

## 🚀 Démarrage Rapide

### 1. Activer le Système
```
1. Aller sur: http://localhost:8000/admin/presence-alerts/settings
2. Cocher "Système actif"
3. Cliquer "Enregistrer"
```

### 2. Tester avec un Employé
```bash
php artisan tinker
```

```php
// Récupérer un utilisateur
$user = App\Models\User::first();

// Envoyer une notification de test
$service = new App\Services\PushNotificationService();
$service->sendTestNotification($user);

exit
```

### 3. Configurer le Cron (Production)
Ajouter dans le crontab:
```bash
* * * * * cd /chemin/vers/adminDash && php artisan schedule:run >> /dev/null 2>&1
```

---

## 📊 Interface Admin

### Configuration
**URL:** `/admin/presence-alerts/settings`

- Heures d'envoi (13h00, 14h00)
- Délai de réponse (45 min)
- Pénalité (1 heure)
- Activer/Désactiver

### Liste des Incidents
**URL:** `/admin/presence-alerts/index`

- Onglets: En attente, Validés, Ignorés
- Filtres: Recherche, Campus, Date
- Actions: Voir détails

### Détails d'un Incident
**URL:** `/admin/presence-alerts/show/{id}`

- Timeline complète
- Infos employé
- Boutons: Valider ou Ignorer

### Statistiques
**URL:** `/admin/presence-alerts/statistics`

- Total incidents
- Taux de réponse
- Pénalités appliquées
- Top 10 employés

---

## 🔧 Commandes Artisan

```bash
# Envoyer les notifications maintenant (test)
php artisan presence:send-notifications

# Créer les incidents pour non-réponse
php artisan presence:process-expired

# Vérifier la configuration
php artisan tinker
>>> (new App\Services\PushNotificationService())->isConfigured()
```

---

## 📱 Application Mobile

### Prérequis
L'employé doit:
1. Installer l'app Flutter
2. Se connecter (enregistre automatiquement le FCM token)
3. Autoriser les notifications

### Réception
Quand la notification arrive:
```
╔════════════════════════════════════╗
║ 🔔 Confirmation de présence        ║
║                                    ║
║ Êtes-vous toujours en place        ║
║ au Campus Nord ?                   ║
║                                    ║
║ [ OUI, je suis en place ]          ║
╚════════════════════════════════════╝
```

L'employé clique sur le bouton → Réponse envoyée automatiquement!

---

## 🔥 Configuration Firebase

**Fichier:** `storage/firebase-credentials.json`

**Projet:** attendance-6156f
**API:** Firebase Cloud Messaging V1

**Statut:** ✅ Configuré et testé

⚠️ **Ne supprimez JAMAIS ce fichier!** Il contient toutes les credentials Firebase.

---

## ✅ Checklist de Vérification

Avant de mettre en production:

- [ ] Fichier `storage/firebase-credentials.json` présent
- [ ] Page admin accessible: `/admin/presence-alerts/settings`
- [ ] Badge vert "Firebase API V1 Configuré" visible
- [ ] Système activé (case cochée)
- [ ] Heures configurées (13h00 / 14h00)
- [ ] Cron configuré en production
- [ ] Au moins 1 employé avec FCM token
- [ ] Test d'envoi réussi

---

## 🐛 Problèmes Courants

### "Aucune notification reçue"
```
1. Vérifier que l'employé a un FCM token:
   php artisan tinker
   >>> App\Models\User::whereNotNull('fcm_token')->count()

2. Vérifier les logs:
   tail -f storage/logs/laravel.log

3. Vérifier que le système est actif:
   http://localhost:8000/admin/presence-alerts/settings
```

### "Firebase not configured"
```
# Vérifier le fichier
ls -la storage/firebase-credentials.json

# S'il manque, le recopier
cp /path/to/attendance-6156f-2a1a23ba78dc.json storage/firebase-credentials.json
```

### "Token invalide"
```
# L'employé doit se reconnecter à l'app mobile
# Les FCM tokens expirent après plusieurs mois
```

---

## 📞 Support

**Documentation complète:**
- `FIREBASE_V1_MIGRATION_COMPLETE.md` - Guide technique
- `FRONTEND_COMPLETE.md` - Interface admin
- `REPONSE_FIREBASE_CONFIGURATION.md` - Réponses aux questions

**Logs:**
```bash
tail -f storage/logs/laravel.log
```

**Test rapide:**
```bash
php artisan tinker --execute="echo (new App\Services\PushNotificationService())->isConfigured() ? '✅ OK' : '❌ Erreur';"
```

---

## 🎉 Résumé

| Composant | Statut |
|-----------|--------|
| Firebase API V1 | ✅ Configuré |
| Backend Laravel | ✅ Fonctionnel |
| Interface Admin | ✅ Complète |
| App Mobile | ✅ Prête |
| Documentation | ✅ Complète |

**Le système est prêt à être utilisé en production!** 🚀

---

*Dernière mise à jour: 21/11/2025*
*API: Firebase Cloud Messaging V1*
*Projet: attendance-6156f*
