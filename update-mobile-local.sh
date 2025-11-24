#!/bin/bash

# Script pour configurer l'app mobile avec l'IP locale (WiFi)

echo "=========================================="
echo "📱 Configuration pour réseau LOCAL"
echo "=========================================="
echo ""

# Obtenir l'IP locale actuelle
CURRENT_IP=$(ifconfig | grep -E "inet " | grep -v 127.0.0.1 | awk '{print $2}' | head -1)

if [ -z "$CURRENT_IP" ]; then
    echo "❌ Aucune IP locale trouvée! Vérifiez votre connexion WiFi."
    exit 1
fi

echo "✅ IP locale détectée: $CURRENT_IP"
echo ""

# Chemin du fichier Flutter
FLUTTER_FILE="../attendance_app/lib/utils/constants.dart"

if [ ! -f "$FLUTTER_FILE" ]; then
    echo "❌ Fichier Flutter non trouvé:"
    echo "   $FLUTTER_FILE"
    exit 1
fi

echo "📱 Mise à jour du fichier Flutter..."
echo ""

# Sauvegarder l'ancien fichier
cp "$FLUTTER_FILE" "$FLUTTER_FILE.backup"
echo "💾 Backup créé: $FLUTTER_FILE.backup"
echo ""

# Extraire l'ancienne URL
OLD_URL=$(grep "static const String baseUrl" "$FLUTTER_FILE" | grep -o "http[^'\"]*")
echo "Ancienne URL: $OLD_URL"
echo "Nouvelle URL: http://$CURRENT_IP:8002/api"
echo ""

# Mettre à jour le fichier avec l'IP locale
sed -i '' "s|static const String baseUrl = '[^']*'|static const String baseUrl = 'http://$CURRENT_IP:8002/api'|g" "$FLUTTER_FILE"

# Vérifier le résultat
NEW_CONFIG=$(grep "baseUrl" "$FLUTTER_FILE" | grep -v "//")
echo "✅ Nouvelle configuration:"
echo "   $NEW_CONFIG"
echo ""

echo "=========================================="
echo "🎯 Prochaines étapes:"
echo "=========================================="
echo ""
echo "1. Vérifiez que le serveur Laravel tourne avec:"
echo "   php artisan serve --host=0.0.0.0 --port=8002"
echo ""
echo "2. Vérifiez que iPhone et Mac sont sur le MÊME WiFi"
echo ""
echo "3. Redémarrez l'app Flutter:"
echo "   cd ../attendance_app"
echo "   flutter run"
echo ""
echo "   OU si l'app tourne déjà, tapez 'R' pour Hot Restart"
echo ""
echo "📡 Mode: RÉSEAU LOCAL (WiFi uniquement)"
echo "   iPhone et Mac doivent être sur le même réseau!"
echo ""
echo "✅ Terminé!"
echo ""
