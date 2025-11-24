# 🔒 Configurer une IP Statique (Fixe) pour votre Mac

## 📋 Informations réseau actuelles détectées:

```
IP actuelle:           172.20.10.5
Masque de sous-réseau: 255.255.255.240
Passerelle (Gateway):  172.20.10.1
DNS primaire:          8.8.8.8
DNS secondaire:        8.0.8.4
```

---

## 🎯 Méthode 1: Configuration IP statique sur Mac (RECOMMANDÉE)

### Étape 1: Ouvrir les Préférences Réseau

1. Cliquez sur le menu **Pomme** () en haut à gauche
2. Cliquez sur **Réglages Système** (ou **Préférences Système** sur macOS plus ancien)
3. Cliquez sur **Réseau**

### Étape 2: Sélectionner votre connexion WiFi

1. Dans la liste de gauche, cliquez sur **Wi-Fi**
2. Cliquez sur le bouton **Détails...** (ou **Avancé...** sur macOS plus ancien)

### Étape 3: Configurer l'IP statique

**Pour macOS Ventura (13) et plus récent:**

1. Dans l'onglet **TCP/IP**
2. Changez **Configurer IPv4** de "Via DHCP" à **"Manuellement"**
3. Remplissez les champs suivants:

   ```
   Adresse IPv4:         172.20.10.5
   Masque de sous-réseau: 255.255.255.240
   Routeur:              172.20.10.1
   ```

4. Allez dans l'onglet **DNS**
5. Cliquez sur le **+** pour ajouter des serveurs DNS:
   ```
   8.8.8.8
   8.8.4.4
   ```

6. Cliquez sur **OK**
7. Cliquez sur **Appliquer**

**Pour macOS Big Sur/Monterey (11/12):**

1. Cliquez sur **Avancé...**
2. Onglet **TCP/IP**
3. **Configurer IPv4:** Sélectionnez "Manuellement"
4. Entrez les informations ci-dessus
5. Onglet **DNS** → Ajoutez les serveurs DNS
6. **OK** puis **Appliquer**

### Étape 4: Vérifier la configuration

Ouvrez le Terminal et tapez:

```bash
ifconfig | grep "inet 172.20.10.5"
```

Vous devriez voir:
```
inet 172.20.10.5 netmask 0xfffffff0 broadcast 172.20.10.15
```

Testez la connexion internet:
```bash
ping -c 3 google.com
```

✅ **Terminé!** Votre Mac aura toujours l'IP **172.20.10.5** sur ce réseau WiFi.

---

## 🎯 Méthode 2: Réservation DHCP via le routeur (ALTERNATIVE AVANCÉE)

Cette méthode est plus stable car elle laisse le routeur gérer l'IP.

### Étape 1: Trouver l'adresse MAC de votre Mac

```bash
ifconfig en0 | grep ether | awk '{print $2}'
```

Notez cette adresse MAC (format: `xx:xx:xx:xx:xx:xx`)

### Étape 2: Accéder à l'interface du routeur

1. Ouvrez un navigateur
2. Allez sur: `http://172.20.10.1`
3. Connectez-vous (identifiants du routeur - souvent sur l'étiquette du routeur)

### Étape 3: Configurer la réservation DHCP

Les étapes varient selon la marque du routeur, mais généralement:

1. Cherchez une section: **"DHCP"** ou **"Réservation DHCP"** ou **"Static Lease"**
2. Ajoutez une nouvelle réservation:
   - **Adresse MAC:** [Votre adresse MAC du Mac]
   - **Adresse IP:** 172.20.10.5
   - **Nom:** Mac Development
3. Sauvegardez et redémarrez le routeur

### Étape 4: Sur le Mac, revenez en DHCP

1. Préférences Système → Réseau → Wi-Fi → Détails
2. TCP/IP → **Configurer IPv4:** "Via DHCP"
3. Appliquer

Le routeur attribuera toujours **172.20.10.5** à votre Mac!

---

## ⚠️ Problèmes potentiels et solutions

### ❌ Problème: "Conflit d'adresse IP"

**Cause:** Un autre appareil utilise déjà 172.20.10.5

**Solution:** Choisissez une autre IP dans la plage disponible:
- Essayez: 172.20.10.6, 172.20.10.7, etc.
- Évitez: 172.20.10.1 (passerelle) et 172.20.10.15 (broadcast)

Si vous changez l'IP, **mettez à jour le fichier Flutter:**
```
attendance_app/lib/utils/constants.dart
```

### ❌ Problème: Plus d'internet après configuration

**Solution:**

1. Vérifiez que vous avez bien entré:
   - Routeur: **172.20.10.1**
   - DNS: **8.8.8.8** et **8.8.4.4**

2. Si ça ne marche pas, revenez en DHCP:
   - Préférences Réseau → Wi-Fi → Détails
   - Configurer IPv4: "Via DHCP"
   - Appliquer

### ❌ Problème: L'IP change quand même

**Cause:** Vous avez plusieurs interfaces réseau (WiFi, Ethernet, etc.)

**Solution:** Configurez l'IP statique sur la bonne interface (celle que vous utilisez)

Pour savoir quelle interface est active:
```bash
route get default | grep interface | awk '{print $2}'
```

---

## 🔄 Revenir en DHCP (annuler l'IP statique)

Si vous voulez revenir en mode automatique:

1. Préférences Système → Réseau → Wi-Fi → Détails
2. TCP/IP → **Configurer IPv4:** "Via DHCP"
3. Appliquer

L'IP sera à nouveau attribuée automatiquement par le routeur.

---

## 📱 Impact sur l'application Flutter

### ✅ Avec IP statique (172.20.10.5):

L'application Flutter fonctionne toujours car l'IP ne change jamais!

### ⚠️ Si vous changez de réseau WiFi:

Par exemple, chez vous: 192.168.1.50, au bureau: 172.20.10.5

Vous devrez avoir **2 configurations différentes** ou utiliser une solution dynamique.

### 💡 Solution avancée: IP dynamique dans Flutter

Au lieu de coder l'IP en dur, vous pourriez:

1. Créer un paramètre dans l'application
2. Permettre à l'utilisateur de changer l'IP via un menu
3. Sauvegarder dans SharedPreferences

Je peux implémenter ça si nécessaire!

---

## 🎯 Commandes utiles

### Vérifier votre IP actuelle:
```bash
ifconfig | grep "inet " | grep -v 127.0.0.1
```

### Vérifier la passerelle:
```bash
netstat -nr | grep default
```

### Vérifier les DNS:
```bash
scutil --dns | grep nameserver
```

### Tester la connexion au backend:
```bash
curl http://172.20.10.5:8002/api/campuses
```

### Redémarrer le réseau (si problème):
```bash
sudo ifconfig en0 down
sudo ifconfig en0 up
```

---

## 📝 Récapitulatif

### ✅ Avantages de l'IP statique:
- L'IP ne change JAMAIS sur ce réseau
- Pas besoin de modifier le code Flutter
- Connexion stable

### ⚠️ Inconvénients:
- À configurer sur chaque réseau différent
- Risque de conflit si mal configuré
- Moins flexible si vous changez souvent de réseau

### 🎯 Recommandation:

- **Pour développement stable à la maison:** IP statique via Mac
- **Pour plusieurs réseaux:** Réservation DHCP via routeur
- **Pour maximum de flexibilité:** IP dynamique dans l'app (je peux l'implémenter)

---

Bonne chance! 🚀
