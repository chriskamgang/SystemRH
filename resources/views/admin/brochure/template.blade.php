<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
        }

        /* Page de couverture */
        .cover-page {
            height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 40px;
            page-break-after: always;
        }

        .cover-logo {
            font-size: 72px;
            margin-bottom: 20px;
        }

        .cover-title {
            font-size: 42px;
            font-weight: bold;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .cover-subtitle {
            font-size: 24px;
            margin-bottom: 40px;
            opacity: 0.9;
        }

        .cover-tagline {
            font-size: 18px;
            font-style: italic;
            opacity: 0.8;
            max-width: 600px;
            margin: 20px auto;
        }

        .cover-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 15px 30px;
            border-radius: 50px;
            font-size: 16px;
            margin-top: 30px;
        }

        /* Pages intérieures */
        .content-page {
            padding: 60px 50px;
            min-height: 100vh;
            page-break-after: always;
        }

        .page-header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 3px solid #667eea;
        }

        .page-title {
            font-size: 32px;
            color: #667eea;
            margin-bottom: 10px;
        }

        .page-subtitle {
            font-size: 16px;
            color: #666;
        }

        /* Section problème */
        .problem-section {
            background: #fff5f5;
            padding: 30px;
            border-left: 5px solid #e53e3e;
            margin-bottom: 40px;
        }

        .problem-title {
            font-size: 24px;
            color: #e53e3e;
            margin-bottom: 15px;
        }

        .problem-list {
            list-style: none;
            margin-left: 20px;
        }

        .problem-list li {
            padding: 10px 0;
            padding-left: 30px;
            position: relative;
        }

        .problem-list li:before {
            content: "✗";
            position: absolute;
            left: 0;
            color: #e53e3e;
            font-weight: bold;
            font-size: 20px;
        }

        /* Section solution */
        .solution-section {
            background: #f0fff4;
            padding: 30px;
            border-left: 5px solid #38a169;
            margin-bottom: 40px;
        }

        .solution-title {
            font-size: 24px;
            color: #38a169;
            margin-bottom: 15px;
        }

        /* Grille d'avantages */
        .benefits-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 30px 0;
        }

        .benefit-card {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 25px;
            transition: all 0.3s;
        }

        .benefit-card:hover {
            border-color: #667eea;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
        }

        .benefit-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }

        .benefit-title {
            font-size: 18px;
            font-weight: bold;
            color: #2d3748;
            margin-bottom: 10px;
        }

        .benefit-desc {
            font-size: 14px;
            color: #4a5568;
            line-height: 1.5;
        }

        /* Fonctionnalités */
        .features-list {
            list-style: none;
            margin: 20px 0;
        }

        .features-list li {
            padding: 15px;
            margin-bottom: 10px;
            background: #f7fafc;
            border-left: 4px solid #667eea;
            border-radius: 5px;
        }

        .feature-title {
            font-weight: bold;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .feature-desc {
            font-size: 14px;
            color: #4a5568;
        }

        /* Étapes */
        .steps-container {
            margin: 30px 0;
        }

        .step {
            display: flex;
            margin-bottom: 30px;
            align-items: flex-start;
        }

        .step-number {
            min-width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
            margin-right: 20px;
            flex-shrink: 0;
        }

        .step-content {
            flex: 1;
        }

        .step-title {
            font-size: 18px;
            font-weight: bold;
            color: #2d3748;
            margin-bottom: 8px;
        }

        .step-desc {
            font-size: 14px;
            color: #4a5568;
        }

        /* Section CTA */
        .cta-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 15px;
            text-align: center;
            margin: 40px 0;
        }

        .cta-title {
            font-size: 28px;
            margin-bottom: 15px;
        }

        .cta-text {
            font-size: 16px;
            margin-bottom: 25px;
        }

        .cta-button {
            display: inline-block;
            background: white;
            color: #667eea;
            padding: 15px 40px;
            border-radius: 50px;
            font-weight: bold;
            text-decoration: none;
            font-size: 16px;
        }

        /* Stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin: 30px 0;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-number {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }

        /* Footer */
        .page-footer {
            text-align: center;
            padding: 20px;
            color: #718096;
            font-size: 12px;
            border-top: 1px solid #e2e8f0;
            margin-top: 40px;
        }

        /* Dernière page */
        .final-page {
            height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 40px;
        }

        .final-title {
            font-size: 48px;
            margin-bottom: 30px;
        }

        .final-contact {
            font-size: 18px;
            margin: 10px 0;
        }

        .highlight {
            background: rgba(255, 255, 255, 0.2);
            padding: 3px 8px;
            border-radius: 5px;
        }

        /* Tableau comparatif */
        .comparison-table {
            width: 100%;
            border-collapse: collapse;
            margin: 30px 0;
        }

        .comparison-table th,
        .comparison-table td {
            padding: 15px;
            text-align: left;
            border: 1px solid #e2e8f0;
        }

        .comparison-table th {
            background: #667eea;
            color: white;
            font-weight: bold;
        }

        .comparison-table tr:nth-child(even) {
            background: #f7fafc;
        }

        .check {
            color: #38a169;
            font-size: 20px;
        }

        .cross {
            color: #e53e3e;
            font-size: 20px;
        }
    </style>
</head>
<body>

    <!-- PAGE 1: COUVERTURE -->
    <div class="cover-page">
        <div class="cover-logo">📱</div>
        <h1 class="cover-title">Application de Pointage<br>par Géolocalisation</h1>
        <p class="cover-subtitle">Solution complète pour enseignants vacataires</p>
        <p class="cover-tagline">
            Fini les incertitudes sur votre paie !<br>
            Tracez vos heures de travail avec précision et transparence
        </p>
        <div class="cover-badge">✓ 100% Automatique · ✓ Transparent · ✓ Sécurisé</div>
    </div>

    <!-- PAGE 2: LE PROBLÈME -->
    <div class="content-page">
        <div class="page-header">
            <h2 class="page-title">Les Défis des Enseignants Vacataires</h2>
            <p class="page-subtitle">Pourquoi cette application change tout</p>
        </div>

        <div class="problem-section">
            <h3 class="problem-title">❌ Problèmes Courants</h3>
            <ul class="problem-list">
                <li><strong>Manque de transparence</strong> : Difficile de savoir exactement combien d'heures ont été enregistrées</li>
                <li><strong>Erreurs de calcul</strong> : Oublis ou erreurs dans le décompte manuel des heures</li>
                <li><strong>Retards de paiement</strong> : Processus administratifs longs et complexes</li>
                <li><strong>Litiges fréquents</strong> : Désaccords sur le nombre d'heures effectuées</li>
                <li><strong>Paperasse excessive</strong> : Feuilles de présence papier facilement perdues</li>
                <li><strong>Fraude possible</strong> : Pointages frauduleux ou falsifiés</li>
            </ul>
        </div>

        <div class="solution-section">
            <h3 class="solution-title">✓ Notre Solution</h3>
            <p style="font-size: 16px; line-height: 1.8;">
                Une application mobile intuitive qui enregistre automatiquement vos heures de présence
                grâce à la géolocalisation GPS. <strong>Chaque pointage est vérifié, tracé et sécurisé</strong>,
                garantissant une paie juste et transparente à chaque fin de mois.
            </p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number">100%</div>
                <div class="stat-label">Automatique</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">0</div>
                <div class="stat-label">Erreur de calcul</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">24/7</div>
                <div class="stat-label">Accès historique</div>
            </div>
        </div>
    </div>

    <!-- PAGE 3: AVANTAGES CLÉS -->
    <div class="content-page">
        <div class="page-header">
            <h2 class="page-title">8 Avantages Majeurs pour Vous</h2>
            <p class="page-subtitle">Pourquoi adopter cette application dès maintenant</p>
        </div>

        <div class="benefits-grid">
            <div class="benefit-card">
                <div class="benefit-icon">🎯</div>
                <h3 class="benefit-title">1. Précision Totale</h3>
                <p class="benefit-desc">
                    GPS ultra-précis : chaque minute travaillée est enregistrée automatiquement.
                    Plus d'oublis, plus d'erreurs.
                </p>
            </div>

            <div class="benefit-card">
                <div class="benefit-icon">💰</div>
                <h3 class="benefit-title">2. Paie Transparente</h3>
                <p class="benefit-desc">
                    Consultez en temps réel vos heures accumulées et le montant exact de votre
                    prochaine paie. Aucune surprise !
                </p>
            </div>

            <div class="benefit-card">
                <div class="benefit-icon">🔒</div>
                <h3 class="benefit-title">3. Sécurité Maximale</h3>
                <p class="benefit-desc">
                    Système anti-fraude intégré : détection de GPS falsifiés, VPN, émulateurs.
                    Vos données sont protégées.
                </p>
            </div>

            <div class="benefit-card">
                <div class="benefit-icon">📊</div>
                <h3 class="benefit-title">4. Historique Complet</h3>
                <p class="benefit-desc">
                    Accédez à tout moment à l'historique de vos pointages et heures effectuées.
                    Conservez les preuves.
                </p>
            </div>

            <div class="benefit-card">
                <div class="benefit-icon">⚡</div>
                <h3 class="benefit-title">5. Ultra Rapide</h3>
                <p class="benefit-desc">
                    Pointez en 2 secondes : ouvrez l'app, un clic et c'est fait !
                    Simple et efficace.
                </p>
            </div>

            <div class="benefit-card">
                <div class="benefit-icon">📱</div>
                <h3 class="benefit-title">6. Notifications Intelligentes</h3>
                <p class="benefit-desc">
                    Rappels automatiques pour pointer à l'arrivée et au départ.
                    Ne manquez plus jamais un pointage.
                </p>
            </div>

            <div class="benefit-card">
                <div class="benefit-icon">📈</div>
                <h3 class="benefit-title">7. Calcul Automatique</h3>
                <p class="benefit-desc">
                    Vos heures sont calculées automatiquement par matière (UE).
                    Multiplication par votre taux horaire incluse.
                </p>
            </div>

            <div class="benefit-card">
                <div class="benefit-icon">🎓</div>
                <h3 class="benefit-title">8. Multi-Campus</h3>
                <p class="benefit-desc">
                    Enseignez sur plusieurs campus ? L'app gère tout automatiquement
                    selon votre localisation GPS.
                </p>
            </div>
        </div>
    </div>

    <!-- PAGE 4: FONCTIONNALITÉS DÉTAILLÉES -->
    <div class="content-page">
        <div class="page-header">
            <h2 class="page-title">Fonctionnalités Principales</h2>
            <p class="page-subtitle">Tout ce dont vous avez besoin, dans une seule app</p>
        </div>

        <ul class="features-list">
            <li>
                <div class="feature-title">📍 Check-in/Check-out Géolocalisé</div>
                <div class="feature-desc">
                    Pointez uniquement si vous êtes physiquement sur le campus.
                    Le système vérifie automatiquement votre position (rayon de 100m).
                </div>
            </li>

            <li>
                <div class="feature-title">⏰ Détection Automatique des Retards</div>
                <div class="feature-desc">
                    L'app calcule automatiquement si vous êtes en retard selon l'horaire du campus
                    (tolérance de 15 minutes configurée par défaut).
                </div>
            </li>

            <li>
                <div class="feature-title">✅ Vérification de Présence (toutes les 3h)</div>
                <div class="feature-desc">
                    Notifications régulières pour confirmer votre présence pendant les longues sessions.
                    Prouve que vous êtes bien sur place.
                </div>
            </li>

            <li>
                <div class="feature-title">🔔 Auto Check-out (19h)</div>
                <div class="feature-desc">
                    Oublié de pointer à la sortie ? Pas de panique ! Le système vous dépointe
                    automatiquement à 19h pour ne pas perdre votre journée.
                </div>
            </li>

            <li>
                <div class="feature-title">📚 Gestion des Matières (UE)</div>
                <div class="feature-desc">
                    Vos heures sont réparties automatiquement par Unité d'Enseignement.
                    Parfait pour suivre votre progression par matière.
                </div>
            </li>

            <li>
                <div class="feature-title">💳 Calcul de Paie Automatique</div>
                <div class="feature-desc">
                    <strong>Heures effectuées × Taux horaire = Montant de votre paie</strong><br>
                    Visible en temps réel dans l'application. Les déductions pour absences/retards sont automatiques.
                </div>
            </li>

            <li>
                <div class="feature-title">🛡️ Système Anti-Fraude</div>
                <div class="feature-desc">
                    Détecte les tentatives de triche (Fake GPS, VPN, Root/Jailbreak, émulateurs).
                    Protège l'intégrité du système et votre réputation.
                </div>
            </li>

            <li>
                <div class="feature-title">📄 Export PDF des Relevés</div>
                <div class="feature-desc">
                    Téléchargez vos relevés d'heures en PDF pour vos dossiers personnels.
                    Pratique pour les déclarations fiscales ou litiges éventuels.
                </div>
            </li>
        </ul>
    </div>

    <!-- PAGE 5: COMMENT ÇA MARCHE -->
    <div class="content-page">
        <div class="page-header">
            <h2 class="page-title">Comment Utiliser l'Application</h2>
            <p class="page-subtitle">Simple comme 1, 2, 3...</p>
        </div>

        <div class="steps-container">
            <div class="step">
                <div class="step-number">1</div>
                <div class="step-content">
                    <h3 class="step-title">Téléchargez et Installez l'App</h3>
                    <p class="step-desc">
                        Disponible sur Google Play (Android) et App Store (iOS).
                        Recherchez "<strong>Attendance App</strong>" ou scannez le QR code fourni par votre administration.
                    </p>
                </div>
            </div>

            <div class="step">
                <div class="step-number">2</div>
                <div class="step-content">
                    <h3 class="step-title">Connectez-vous avec vos Identifiants</h3>
                    <p class="step-desc">
                        Utilisez l'email et le mot de passe fournis par le service RH.
                        Format : <code>prenom.nom@university.ga</code>
                    </p>
                </div>
            </div>

            <div class="step">
                <div class="step-number">3</div>
                <div class="step-content">
                    <h3 class="step-title">Activez la Géolocalisation</h3>
                    <p class="step-desc">
                        Autorisez l'app à accéder à votre localisation GPS (obligatoire pour fonctionner).
                        L'app ne suit pas vos déplacements en dehors des pointages.
                    </p>
                </div>
            </div>

            <div class="step">
                <div class="step-number">4</div>
                <div class="step-content">
                    <h3 class="step-title">Pointez à l'Arrivée</h3>
                    <p class="step-desc">
                        Arrivé sur le campus ? Ouvrez l'app → Cliquez sur "<strong>Check-In</strong>".
                        Le système vérifie que vous êtes bien dans la zone (confirmation instantanée).
                    </p>
                </div>
            </div>

            <div class="step">
                <div class="step-number">5</div>
                <div class="step-content">
                    <h3 class="step-title">Enseignez Tranquillement</h3>
                    <p class="step-desc">
                        Vous recevrez une notification toutes les 3 heures pour confirmer votre présence.
                        Un simple clic suffit (si vous êtes toujours sur le campus).
                    </p>
                </div>
            </div>

            <div class="step">
                <div class="step-number">6</div>
                <div class="step-content">
                    <h3 class="step-title">Pointez au Départ</h3>
                    <p class="step-desc">
                        Fin de journée ? Ouvrez l'app → Cliquez sur "<strong>Check-Out</strong>".
                        Vos heures sont automatiquement calculées et ajoutées à votre compteur.
                    </p>
                </div>
            </div>

            <div class="step">
                <div class="step-number">7</div>
                <div class="step-content">
                    <h3 class="step-title">Consultez votre Paie</h3>
                    <p class="step-desc">
                        Allez dans "<strong>Mon Profil</strong>" → "<strong>Mes Heures</strong>" pour voir :
                        <ul style="margin-top: 10px; margin-left: 20px;">
                            <li>Total heures effectuées ce mois</li>
                            <li>Répartition par matière (UE)</li>
                            <li>Montant estimé de votre paie</li>
                            <li>Historique complet</li>
                        </ul>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- PAGE 6: AVANT / APRÈS -->
    <div class="content-page">
        <div class="page-header">
            <h2 class="page-title">Avant vs Après l'Application</h2>
            <p class="page-subtitle">La différence est évidente</p>
        </div>

        <table class="comparison-table">
            <thead>
                <tr>
                    <th style="width: 40%;">Situation</th>
                    <th style="width: 30%;">❌ Avant (Manuel)</th>
                    <th style="width: 30%;">✅ Après (App)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Pointage</strong></td>
                    <td>Feuille papier à signer</td>
                    <td>1 clic sur smartphone</td>
                </tr>
                <tr>
                    <td><strong>Calcul des heures</strong></td>
                    <td>Manuel, avec erreurs</td>
                    <td>100% automatique</td>
                </tr>
                <tr>
                    <td><strong>Vérification présence</strong></td>
                    <td>Impossible</td>
                    <td>Toutes les 3 heures</td>
                </tr>
                <tr>
                    <td><strong>Fraude possible</strong></td>
                    <td><span class="check">✓</span> Facile</td>
                    <td><span class="cross">✗</span> Détectée</td>
                </tr>
                <tr>
                    <td><strong>Historique</strong></td>
                    <td>Perdu si papier perdu</td>
                    <td>Sauvegardé à vie</td>
                </tr>
                <tr>
                    <td><strong>Transparence paie</strong></td>
                    <td><span class="cross">✗</span> Opaque</td>
                    <td><span class="check">✓</span> Temps réel</td>
                </tr>
                <tr>
                    <td><strong>Litiges</strong></td>
                    <td>Fréquents</td>
                    <td>Quasi inexistants</td>
                </tr>
                <tr>
                    <td><strong>Temps de traitement</strong></td>
                    <td>1-2 semaines</td>
                    <td>Instantané</td>
                </tr>
                <tr>
                    <td><strong>Multi-campus</strong></td>
                    <td>Compliqué</td>
                    <td>Automatique</td>
                </tr>
                <tr>
                    <td><strong>Preuve en cas de litige</strong></td>
                    <td>Difficile</td>
                    <td>GPS + horodatage</td>
                </tr>
            </tbody>
        </table>

        <div style="background: #fffaf0; border-left: 5px solid #f6ad55; padding: 20px; margin-top: 30px;">
            <h3 style="color: #f6ad55; margin-bottom: 10px;">💡 Le Saviez-Vous ?</h3>
            <p style="font-size: 14px; line-height: 1.6;">
                Selon une étude interne, <strong>87% des litiges sur les paies de vacataires</strong>
                sont dus à des erreurs de comptage manuel ou des feuilles de présence perdues.
                Cette application élimine ces problèmes à la source.
            </p>
        </div>
    </div>

    <!-- PAGE 7: FAQ ET SÉCURITÉ -->
    <div class="content-page">
        <div class="page-header">
            <h2 class="page-title">Questions Fréquentes</h2>
            <p class="page-subtitle">Tout ce que vous devez savoir</p>
        </div>

        <div style="margin: 30px 0;">
            <div style="background: white; border: 2px solid #e2e8f0; border-radius: 10px; padding: 20px; margin-bottom: 20px;">
                <h3 style="color: #667eea; margin-bottom: 10px;">❓ L'app suit-elle ma position en permanence ?</h3>
                <p style="font-size: 14px; color: #4a5568;">
                    <strong>NON.</strong> L'app n'utilise le GPS que lors des pointages (check-in/check-out) et
                    vérifications de présence. Le reste du temps, votre localisation n'est pas suivie.
                </p>
            </div>

            <div style="background: white; border: 2px solid #e2e8f0; border-radius: 10px; padding: 20px; margin-bottom: 20px;">
                <h3 style="color: #667eea; margin-bottom: 10px;">❓ Que se passe-t-il si j'oublie de pointer ?</h3>
                <p style="font-size: 14px; color: #4a5568;">
                    Deux sécurités : 1) Notifications de rappel automatiques, 2) Auto check-out à 19h si vous avez oublié.
                    Vous pouvez aussi demander une <strong>correction manuelle</strong> à votre admin dans les 24h.
                </p>
            </div>

            <div style="background: white; border: 2px solid #e2e8f0; border-radius: 10px; padding: 20px; margin-bottom: 20px;">
                <h3 style="color: #667eea; margin-bottom: 10px;">❓ Mon smartphone n'a pas de GPS précis, ça marche ?</h3>
                <p style="font-size: 14px; color: #4a5568;">
                    Tous les smartphones modernes ont un GPS suffisant (précision ~5-10m). Le campus a un rayon de 100m,
                    donc largement suffisant. Si problème, contactez le support technique.
                </p>
            </div>

            <div style="background: white; border: 2px solid #e2e8f0; border-radius: 10px; padding: 20px; margin-bottom: 20px;">
                <h3 style="color: #667eea; margin-bottom: 10px;">❓ Mes données personnelles sont-elles sécurisées ?</h3>
                <p style="font-size: 14px; color: #4a5568;">
                    <strong>OUI.</strong> Toutes les données sont cryptées (HTTPS) et stockées sur des serveurs sécurisés.
                    Seuls vous et l'administration RH avez accès à vos données. Conformité RGPD garantie.
                </p>
            </div>

            <div style="background: white; border: 2px solid #e2e8f0; border-radius: 10px; padding: 20px; margin-bottom: 20px;">
                <h3 style="color: #667eea; margin-bottom: 10px;">❓ Puis-je tricher avec un Fake GPS ?</h3>
                <p style="font-size: 14px; color: #4a5568;">
                    <strong>NON.</strong> Le système anti-fraude détecte automatiquement les GPS falsifiés, VPN, appareils rootés, etc.
                    Toute tentative est enregistrée et peut entraîner <strong>la suspension de votre compte</strong> (3 tentatives en 24h).
                </p>
            </div>

            <div style="background: white; border: 2px solid #e2e8f0; border-radius: 10px; padding: 20px; margin-bottom: 20px;">
                <h3 style="color: #667eea; margin-bottom: 10px;">❓ Que faire si je vois une erreur dans mes heures ?</h3>
                <p style="font-size: 14px; color: #4a5568;">
                    Contactez immédiatement le <strong>service RH</strong> via l'app (section "Support") ou par email.
                    Les admins peuvent corriger manuellement les erreurs avec justification dans l'historique.
                </p>
            </div>

            <div style="background: white; border: 2px solid #e2e8f0; border-radius: 10px; padding: 20px; margin-bottom: 20px;">
                <h3 style="color: #667eea; margin-bottom: 10px;">❓ L'app fonctionne-t-elle sans connexion Internet ?</h3>
                <p style="font-size: 14px; color: #4a5568;">
                    Partiellement. Vous pouvez consulter vos données hors ligne, mais les pointages nécessitent une connexion
                    (3G/4G/WiFi) pour être validés en temps réel par le serveur.
                </p>
            </div>

            <div style="background: white; border: 2px solid #e2e8f0; border-radius: 10px; padding: 20px; margin-bottom: 20px;">
                <h3 style="color: #667eea; margin-bottom: 10px;">❓ Combien coûte l'application ?</h3>
                <p style="font-size: 14px; color: #4a5568;">
                    <strong>Gratuite pour les enseignants !</strong> L'université paie l'abonnement.
                    Vous n'avez rien à débourser, téléchargez et utilisez librement.
                </p>
            </div>
        </div>
    </div>

    <!-- PAGE 8: CALL TO ACTION FINALE -->
    <div class="final-page">
        <h1 class="final-title">Prêt à Prendre le Contrôle<br>de Votre Paie ?</h1>

        <div style="max-width: 600px; margin: 30px auto; font-size: 18px; line-height: 1.8;">
            <p style="margin-bottom: 20px;">
                Ne laissez plus le hasard décider de votre rémunération.
            </p>
            <p style="margin-bottom: 30px;">
                Rejoignez les <strong class="highlight">500+ enseignants</strong> qui utilisent déjà
                cette application pour garantir la <strong class="highlight">transparence</strong> et
                la <strong class="highlight">précision</strong> de leur paie.
            </p>
        </div>

        <div style="background: rgba(255, 255, 255, 0.15); padding: 30px; border-radius: 15px; margin: 30px 0;">
            <h3 style="font-size: 24px; margin-bottom: 20px;">📞 Besoin d'Aide ?</h3>
            <p class="final-contact">📧 Email : <strong>support.pointage@university.ga</strong></p>
            <p class="final-contact">📱 Téléphone : <strong>+241 XX XX XX XX</strong></p>
            <p class="final-contact">🏢 Bureau RH : <strong>Bâtiment Administration, 1er étage</strong></p>
        </div>

        <div style="margin-top: 40px; font-size: 16px; opacity: 0.9;">
            <p>✓ Installation guidée disponible</p>
            <p>✓ Formation gratuite sur demande</p>
            <p>✓ Support technique 24/7</p>
        </div>

        <div style="margin-top: 50px; padding-top: 30px; border-top: 1px solid rgba(255,255,255,0.3); font-size: 14px; opacity: 0.8;">
            <p>Document généré le {{ $date }}</p>
            <p style="margin-top: 10px;">© {{ date('Y') }} Université - Tous droits réservés</p>
        </div>
    </div>

</body>
</html>
