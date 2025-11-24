#!/bin/bash

# Script pour vérifier et afficher l'IP actuelle du Mac

echo "=================================="
echo "🔍 Vérification de l'IP réseau"
echo "=================================="
echo ""

# Obtenir l'IP actuelle
CURRENT_IP=$(ifconfig | grep -E "inet " | grep -v 127.0.0.1 | awk '{print $2}' | head -1)

if [ -z "$CURRENT_IP" ]; then
    echo "❌ Aucune IP trouvée! Vérifiez votre connexion réseau."
    exit 1
fi

echo "✅ IP actuelle détectée:"
echo "   $CURRENT_IP"
echo ""

# Vérifier la passerelle
GATEWAY=$(netstat -nr | grep default | head -1 | awk '{print $2}')
echo "📡 Passerelle (Gateway):"
echo "   $GATEWAY"
echo ""

# Vérifier le fichier Flutter
FLUTTER_FILE="../attendance_app/lib/utils/constants.dart"

if [ -f "$FLUTTER_FILE" ]; then
    echo "📱 Configuration Flutter actuelle:"
    grep "baseUrl" "$FLUTTER_FILE" | grep -v "//"
    echo ""

    # Extraire l'IP du fichier Flutter
    FLUTTER_IP=$(grep "baseUrl" "$FLUTTER_FILE" | grep -oE '[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+')

    if [ "$FLUTTER_IP" = "$CURRENT_IP" ]; then
        echo "✅ L'IP Flutter correspond à l'IP actuelle!"
        echo "   L'application devrait fonctionner correctement."
    else
        echo "⚠️  ATTENTION: L'IP a changé!"
        echo "   IP Flutter:  $FLUTTER_IP"
        echo "   IP actuelle: $CURRENT_IP"
        echo ""
        echo "💡 Pour mettre à jour, modifiez le fichier:"
        echo "   $FLUTTER_FILE"
        echo ""
        echo "   Ou utilisez la commande:"
        echo "   ./update-ip.sh"
    fi
else
    echo "⚠️  Fichier Flutter non trouvé à:"
    echo "   $FLUTTER_FILE"
fi

echo ""
echo "=================================="
echo "🚀 Commandes utiles:"
echo "=================================="
echo ""
echo "Démarrer le serveur Laravel (avec accès réseau):"
echo "  php artisan serve --host=0.0.0.0 --port=8002"
echo ""
echo "Tester la connexion:"
echo "  curl http://$CURRENT_IP:8002/api/campuses"
echo ""
echo "Démarrer l'app Flutter:"
echo "  cd ../attendance_app && flutter run"
echo ""
