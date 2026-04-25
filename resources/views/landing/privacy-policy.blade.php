@extends('landing.layout')

@section('title', 'Politique de Confidentialité')
@section('description', 'Politique de confidentialité de l\'application IUEs/INSAM Presence')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <h1 class="text-3xl font-bold gradient-text mb-2">Politique de Confidentialité</h1>
    <p class="text-gray-500 mb-8">Dernière mise à jour : {{ date('d/m/Y') }}</p>

    <div class="bg-white rounded-2xl shadow-lg p-8 space-y-8 text-gray-700 leading-relaxed">

        {{-- Introduction --}}
        <section>
            <h2 class="text-xl font-semibold text-gray-900 mb-3">1. Introduction</h2>
            <p>
                L'application <strong>IUEs/INSAM Presence</strong> (ci-après "l'Application") est éditée par
                l'Institut Universitaire des Sciences et Management (INSAM) / ZOOMAFRIK.
                Cette politique de confidentialité décrit comment nous collectons, utilisons, stockons et protégeons
                vos données personnelles dans le cadre de l'utilisation de l'Application mobile et du système de
                gestion des ressources humaines associé.
            </p>
            <p class="mt-2">
                En utilisant l'Application, vous acceptez les pratiques décrites dans cette politique.
            </p>
        </section>

        {{-- Données collectées --}}
        <section>
            <h2 class="text-xl font-semibold text-gray-900 mb-3">2. Données collectées</h2>

            <h3 class="font-semibold text-gray-800 mt-4 mb-2">2.1 Données d'identification</h3>
            <ul class="list-disc pl-6 space-y-1">
                <li>Nom, prénom, adresse e-mail professionnelle</li>
                <li>Numéro de téléphone (si fourni)</li>
                <li>Photo de profil (si fournie)</li>
                <li>Rôle et type de contrat (permanent, semi-permanent, vacataire, étudiant)</li>
            </ul>

            <h3 class="font-semibold text-gray-800 mt-4 mb-2">2.2 Données de géolocalisation</h3>
            <ul class="list-disc pl-6 space-y-1">
                <li><strong>Localisation précise (GPS)</strong> — utilisée pour vérifier votre présence sur les campus lors des pointages d'entrée et de sortie</li>
                <li><strong>Localisation en arrière-plan</strong> — utilisée pour détecter automatiquement votre arrivée sur un campus via le géofencing, afin de vous envoyer une notification de pointage rapide</li>
                <li>Précision du signal GPS</li>
            </ul>
            <p class="mt-2 text-sm bg-blue-50 p-3 rounded-lg">
                <i class="fas fa-info-circle text-blue-600 mr-1"></i>
                La localisation en arrière-plan est uniquement utilisée pour le géofencing des campus.
                Elle n'est jamais utilisée à des fins de surveillance. Vous pouvez désactiver cette fonctionnalité
                dans les paramètres de votre appareil à tout moment.
            </p>

            <h3 class="font-semibold text-gray-800 mt-4 mb-2">2.3 Données d'appareil</h3>
            <ul class="list-disc pl-6 space-y-1">
                <li>Identifiant unique de l'appareil (pour empêcher le multi-appareil non autorisé)</li>
                <li>Modèle, fabricant et version du système d'exploitation</li>
                <li>Statut de sécurité de l'appareil (détection de root/jailbreak, GPS simulé)</li>
            </ul>
            <p class="mt-2 text-sm">
                Ces données sont collectées exclusivement pour prévenir la fraude au pointage
                (usurpation de localisation, appareils modifiés).
            </p>

            <h3 class="font-semibold text-gray-800 mt-4 mb-2">2.4 Données de réseau</h3>
            <ul class="list-disc pl-6 space-y-1">
                <li>État de la connexion réseau et WiFi (pour la détection de VPN)</li>
            </ul>

            <h3 class="font-semibold text-gray-800 mt-4 mb-2">2.5 Données de notification</h3>
            <ul class="list-disc pl-6 space-y-1">
                <li>Token Firebase Cloud Messaging (FCM) pour l'envoi de notifications push</li>
            </ul>
        </section>

        {{-- Finalités --}}
        <section>
            <h2 class="text-xl font-semibold text-gray-900 mb-3">3. Finalités du traitement</h2>
            <p>Vos données sont collectées et traitées pour les finalités suivantes :</p>
            <ul class="list-disc pl-6 space-y-1 mt-2">
                <li><strong>Gestion de la présence</strong> — pointage GPS vérifié à l'entrée et à la sortie des campus</li>
                <li><strong>Géofencing</strong> — notification automatique lorsque vous entrez dans la zone d'un campus pour faciliter le pointage</li>
                <li><strong>Vérification de présence</strong> — contrôles aléatoires pour confirmer votre présence effective sur site</li>
                <li><strong>Prévention de la fraude</strong> — détection de GPS simulé, appareils rootés/jailbreakés, et autres tentatives de contournement</li>
                <li><strong>Gestion RH</strong> — suivi des heures, calcul de la paie, gestion des congés et absences</li>
                <li><strong>Notifications</strong> — alertes de présence, rappels de pointage, informations administratives</li>
            </ul>
        </section>

        {{-- Base légale --}}
        <section>
            <h2 class="text-xl font-semibold text-gray-900 mb-3">4. Base légale</h2>
            <p>Le traitement de vos données repose sur :</p>
            <ul class="list-disc pl-6 space-y-1 mt-2">
                <li><strong>L'exécution du contrat de travail</strong> — le suivi de présence fait partie des obligations contractuelles entre l'employeur et l'employé</li>
                <li><strong>L'intérêt légitime</strong> — la prévention de la fraude et la sécurisation du système de pointage</li>
                <li><strong>Le consentement</strong> — pour les notifications push et la localisation en arrière-plan (que vous pouvez révoquer dans les paramètres de votre appareil)</li>
            </ul>
        </section>

        {{-- Partage des données --}}
        <section>
            <h2 class="text-xl font-semibold text-gray-900 mb-3">5. Partage des données</h2>
            <p>Vos données personnelles ne sont <strong>jamais vendues</strong> à des tiers. Elles peuvent être partagées avec :</p>
            <ul class="list-disc pl-6 space-y-1 mt-2">
                <li><strong>L'administration de l'INSAM/IUEs</strong> — accès aux données de présence et RH dans le cadre de la gestion du personnel</li>
                <li><strong>Firebase (Google)</strong> — pour l'envoi de notifications push uniquement (token FCM)</li>
            </ul>
            <p class="mt-2">Aucune donnée de localisation n'est partagée avec des tiers ou des services publicitaires.</p>
        </section>

        {{-- Conservation --}}
        <section>
            <h2 class="text-xl font-semibold text-gray-900 mb-3">6. Durée de conservation</h2>
            <ul class="list-disc pl-6 space-y-1">
                <li><strong>Données de présence</strong> — conservées pendant la durée du contrat de travail, puis archivées conformément aux obligations légales (5 ans)</li>
                <li><strong>Données de localisation brutes</strong> — conservées 90 jours maximum, puis automatiquement supprimées</li>
                <li><strong>Données d'appareil et de sécurité</strong> — conservées 12 mois à des fins d'audit</li>
                <li><strong>Compte utilisateur</strong> — supprimé dans les 30 jours suivant la fin du contrat ou sur demande</li>
            </ul>
        </section>

        {{-- Sécurité --}}
        <section>
            <h2 class="text-xl font-semibold text-gray-900 mb-3">7. Sécurité des données</h2>
            <p>Nous mettons en oeuvre les mesures suivantes pour protéger vos données :</p>
            <ul class="list-disc pl-6 space-y-1 mt-2">
                <li>Communication chiffrée via HTTPS/TLS</li>
                <li>Authentification par token sécurisé (Laravel Sanctum)</li>
                <li>Stockage sécurisé des mots de passe (hachage bcrypt)</li>
                <li>Contrôle d'accès basé sur les rôles</li>
                <li>Détection et blocage des appareils compromis</li>
            </ul>
        </section>

        {{-- Droits --}}
        <section>
            <h2 class="text-xl font-semibold text-gray-900 mb-3">8. Vos droits</h2>
            <p>Conformément à la réglementation applicable, vous disposez des droits suivants :</p>
            <ul class="list-disc pl-6 space-y-1 mt-2">
                <li><strong>Droit d'accès</strong> — consulter vos données personnelles détenues par l'Application</li>
                <li><strong>Droit de rectification</strong> — corriger des données inexactes via votre profil ou en contactant l'administration</li>
                <li><strong>Droit de suppression</strong> — demander la suppression de votre compte et données associées</li>
                <li><strong>Droit d'opposition</strong> — vous opposer au traitement de certaines données (localisation en arrière-plan)</li>
                <li><strong>Droit à la portabilité</strong> — recevoir vos données dans un format structuré</li>
            </ul>
            <p class="mt-3">
                Pour exercer vos droits, contactez-nous à l'adresse :
                <a href="mailto:contact@iues-insam.com" class="text-blue-600 hover:underline font-medium">contact@iues-insam.com</a>
            </p>
        </section>

        {{-- Permissions --}}
        <section>
            <h2 class="text-xl font-semibold text-gray-900 mb-3">9. Permissions de l'application</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm border-collapse mt-2">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="text-left p-3 font-semibold">Permission</th>
                            <th class="text-left p-3 font-semibold">Utilisation</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr>
                            <td class="p-3 font-medium">Localisation (précise)</td>
                            <td class="p-3">Vérification GPS lors du pointage de présence</td>
                        </tr>
                        <tr>
                            <td class="p-3 font-medium">Localisation (arrière-plan)</td>
                            <td class="p-3">Détection automatique d'arrivée sur un campus (géofencing)</td>
                        </tr>
                        <tr>
                            <td class="p-3 font-medium">Notifications</td>
                            <td class="p-3">Alertes de vérification de présence, rappels de pointage</td>
                        </tr>
                        <tr>
                            <td class="p-3 font-medium">Internet</td>
                            <td class="p-3">Communication avec le serveur</td>
                        </tr>
                        <tr>
                            <td class="p-3 font-medium">État du réseau / WiFi</td>
                            <td class="p-3">Détection de VPN pour la prévention de fraude</td>
                        </tr>
                        <tr>
                            <td class="p-3 font-medium">Caméra</td>
                            <td class="p-3">Photo de profil uniquement (optionnel)</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Enfants --}}
        <section>
            <h2 class="text-xl font-semibold text-gray-900 mb-3">10. Protection des mineurs</h2>
            <p>
                L'Application est destinée aux employés et étudiants majeurs de l'INSAM/IUEs.
                Nous ne collectons pas sciemment de données personnelles de mineurs de moins de 16 ans.
            </p>
        </section>

        {{-- Modifications --}}
        <section>
            <h2 class="text-xl font-semibold text-gray-900 mb-3">11. Modifications de cette politique</h2>
            <p>
                Nous nous réservons le droit de modifier cette politique de confidentialité.
                Toute modification sera publiée sur cette page avec une date de mise à jour.
                En cas de changement significatif, une notification sera envoyée via l'Application.
            </p>
        </section>

        {{-- Contact --}}
        <section>
            <h2 class="text-xl font-semibold text-gray-900 mb-3">12. Contact</h2>
            <p>Pour toute question relative à cette politique de confidentialité ou au traitement de vos données :</p>
            <div class="mt-3 bg-gray-50 p-4 rounded-lg space-y-2">
                <p><i class="fas fa-building text-blue-600 mr-2"></i><strong>INSAM / IUEs</strong></p>
                <p><i class="fas fa-envelope text-blue-600 mr-2"></i><a href="mailto:contact@iues-insam.com" class="text-blue-600 hover:underline">contact@iues-insam.com</a></p>
                <p><i class="fas fa-globe text-blue-600 mr-2"></i><a href="{{ url('/') }}" class="text-blue-600 hover:underline">{{ url('/') }}</a></p>
            </div>
        </section>

    </div>
</div>
@endsection
