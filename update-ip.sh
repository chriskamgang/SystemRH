#!/bin/bash

# Script pour mettre à jour automatiquement l'IP dans le fichier Flutter

echo "=================================="
echo "🔄 Mise à jour de l'IP Flutter"
echo "=================================="
echo ""

# Obtenir l'IP actuelle
CURRENT_IP=$(ifconfig | grep -E "inet " | grep -v 127.0.0.1 | awk '{print $2}' | head -1)

if [ -z "$CURRENT_IP" ]; then
    echo "❌ Aucune IP trouvée! Vérifiez votre connexion réseau."
    exit 1
fi

echo "✅ IP actuelle détectée: $CURRENT_IP"
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

# Mettre à jour l'IP dans le fichier
# Cherche la ligne contenant baseUrl et remplace l'IP
sed -i '' "s|http://[0-9]\+\.[0-9]\+\.[0-9]\+\.[0-9]\+:8002/api|http://$CURRENT_IP:8002/api|g" "$FLUTTER_FILE"

# Vérifier le résultat
NEW_CONFIG=$(grep "baseUrl" "$FLUTTER_FILE" | grep -v "//")
echo "✅ Nouvelle configuration:"
echo "   $NEW_CONFIG"
echo ""

echo "=================================="
echo "🎯 Prochaines étapes:"
echo "=================================="
echo ""
echo "1. Redémarrez l'app Flutter:"
echo "   cd ../attendance_app"
echo "   flutter run"
echo ""
echo "   Ou si l'app tourne déjà, tapez 'R' pour Hot Restart"
echo ""
echo "2. Vérifiez que le serveur Laravel tourne avec:"
echo "   php artisan serve --host=0.0.0.0 --port=8002"
echo ""
echo "✅ Terminé!"
echo ""
