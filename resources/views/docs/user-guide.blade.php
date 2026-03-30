<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Guide Utilisateur - INSAM Présence</title>
    <style>
        @page {
            margin: 15mm 15mm 20mm 15mm;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #1e293b;
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }

        /* ===== PAGE DE COUVERTURE ===== */
        .cover {
            text-align: center;
            padding-top: 100px;
        }
        .cover-logo {
            width: 80px;
            height: 80px;
            background-color: #2563eb;
            border-radius: 20px;
            margin: 0 auto 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .cover h1 {
            font-size: 28px;
            color: #1e40af;
            margin: 0 0 10px 0;
            letter-spacing: 1px;
        }
        .cover h2 {
            font-size: 18px;
            color: #3b82f6;
            font-weight: normal;
            margin: 0 0 40px 0;
        }
        .cover .subtitle {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 5px;
        }
        .cover .version {
            display: inline-block;
            background-color: #dbeafe;
            color: #1e40af;
            padding: 5px 15px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: bold;
            margin-top: 20px;
        }
        .cover .date {
            color: #94a3b8;
            font-size: 10px;
            margin-top: 15px;
        }
        .cover-footer {
            position: absolute;
            bottom: 40px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 9px;
            color: #94a3b8;
        }

        /* ===== MISE EN PAGE ===== */
        .page-break {
            page-break-before: always;
        }

        h2 {
            font-size: 18px;
            color: #1e40af;
            border-bottom: 3px solid #3b82f6;
            padding-bottom: 8px;
            margin-top: 5px;
            margin-bottom: 15px;
        }
        h3 {
            font-size: 14px;
            color: #1e40af;
            margin-top: 20px;
            margin-bottom: 8px;
        }
        h4 {
            font-size: 12px;
            color: #334155;
            margin-top: 12px;
            margin-bottom: 5px;
        }

        /* ===== TABLE DES MATIÈRES ===== */
        .toc {
            margin: 20px 0;
        }
        .toc-item {
            padding: 8px 0;
            border-bottom: 1px dotted #cbd5e1;
            font-size: 12px;
        }
        .toc-item a {
            color: #1e40af;
            text-decoration: none;
        }
        .toc-number {
            display: inline-block;
            width: 25px;
            font-weight: bold;
            color: #3b82f6;
        }

        /* ===== CAPTURES D'ÉCRAN ===== */
        .screenshot-container {
            text-align: center;
            margin: 15px 0;
        }
        .screenshot {
            max-width: 220px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .screenshot-caption {
            font-size: 9px;
            color: #64748b;
            font-style: italic;
            margin-top: 5px;
        }
        .screenshots-row {
            display: table;
            width: 100%;
            margin: 15px 0;
        }
        .screenshots-row .col {
            display: table-cell;
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 0 10px;
        }
        .screenshots-row .screenshot {
            max-width: 190px;
        }

        /* ===== BOÎTES INFO ===== */
        .info-box {
            border-radius: 8px;
            padding: 10px 14px;
            margin: 12px 0;
            font-size: 10px;
        }
        .info-box.blue {
            background-color: #dbeafe;
            border-left: 4px solid #2563eb;
            color: #1e40af;
        }
        .info-box.green {
            background-color: #dcfce7;
            border-left: 4px solid #16a34a;
            color: #166534;
        }
        .info-box.orange {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            color: #92400e;
        }
        .info-box.red {
            background-color: #fee2e2;
            border-left: 4px solid #dc2626;
            color: #991b1b;
        }
        .info-box strong {
            display: block;
            margin-bottom: 3px;
            font-size: 11px;
        }

        /* ===== ÉTAPES ===== */
        .step {
            display: table;
            width: 100%;
            margin: 10px 0;
        }
        .step-number {
            display: table-cell;
            width: 30px;
            vertical-align: top;
        }
        .step-number span {
            display: inline-block;
            width: 24px;
            height: 24px;
            background-color: #2563eb;
            color: white;
            text-align: center;
            line-height: 24px;
            border-radius: 50%;
            font-size: 12px;
            font-weight: bold;
        }
        .step-content {
            display: table-cell;
            vertical-align: top;
            padding-left: 10px;
        }
        .step-content strong {
            color: #1e40af;
        }

        /* ===== TABLEAU ===== */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 10px;
        }
        table.data-table th {
            background-color: #1e40af;
            color: white;
            padding: 6px 10px;
            text-align: left;
            font-size: 10px;
        }
        table.data-table td {
            padding: 6px 10px;
            border: 1px solid #e2e8f0;
        }
        table.data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }

        /* ===== FOOTER ===== */
        .page-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 5px;
        }

        /* ===== LISTES ===== */
        ul {
            margin: 5px 0;
            padding-left: 20px;
        }
        li {
            margin-bottom: 4px;
        }
    </style>
</head>
<body>

    <!-- ========================================= -->
    <!-- PAGE DE COUVERTURE                        -->
    <!-- ========================================= -->
    <div class="cover">
        <div style="font-size: 60px; color: #2563eb; margin-bottom: 20px;">&#128205;</div>
        <h1>INSAM PRESENCE</h1>
        <h2>Guide Utilisateur - Application Mobile</h2>
        <p class="subtitle">Systeme de Pointage par Geolocalisation</p>
        <p class="subtitle">IUEs/INSAM - Institut Universitaire Strategique de l'Estuaire</p>
        <div class="version">Version 2.0.1</div>
        <p class="date">{{ now()->format('d/m/Y') }}</p>
    </div>
    <div class="cover-footer">
        Document confidentiel - IUEs/INSAM | Tous droits reserves
    </div>

    <!-- ========================================= -->
    <!-- TABLE DES MATIÈRES                        -->
    <!-- ========================================= -->
    <div class="page-break"></div>
    <h2>Table des Matieres</h2>

    <div class="toc">
        <div class="toc-item"><span class="toc-number">1.</span> Presentation de l'application</div>
        <div class="toc-item"><span class="toc-number">2.</span> Installation et premier lancement</div>
        <div class="toc-item"><span class="toc-number">3.</span> Connexion</div>
        <div class="toc-item"><span class="toc-number">4.</span> Ecran d'accueil</div>
        <div class="toc-item"><span class="toc-number">5.</span> Pointer sa presence (Check-in)</div>
        <div class="toc-item"><span class="toc-number">6.</span> Pointer sa sortie (Check-out)</div>
        <div class="toc-item"><span class="toc-number">7.</span> Historique des presences</div>
        <div class="toc-item"><span class="toc-number">8.</span> Profil et fiche de paie</div>
        <div class="toc-item"><span class="toc-number">9.</span> Notifications</div>
        <div class="toc-item"><span class="toc-number">10.</span> Questions frequentes (FAQ)</div>
    </div>

    <!-- ========================================= -->
    <!-- 1. PRÉSENTATION                           -->
    <!-- ========================================= -->
    <div class="page-break"></div>
    <h2>1. Presentation de l'application</h2>

    <p>
        <strong>INSAM Presence</strong> est l'application mobile officielle de pointage de l'IUEs/INSAM.
        Elle permet a chaque employe de pointer son entree et sa sortie directement depuis son telephone,
        grace a la geolocalisation (GPS).
    </p>

    <h3>Fonctionnalites principales</h3>
    <ul>
        <li><strong>Pointage GPS</strong> : Check-in et check-out automatiques bases sur votre position</li>
        <li><strong>Emploi du temps</strong> : Consultez vos cours et creneaux programmes</li>
        <li><strong>Historique</strong> : Suivez vos presences, retards et heures travaillees</li>
        <li><strong>Fiche de paie</strong> : Telechargez votre fiche de paie mensuelle en PDF</li>
        <li><strong>Notifications</strong> : Recevez des alertes de verification de presence</li>
    </ul>

    <h3>Conditions requises</h3>
    <table class="data-table">
        <tr>
            <th>Element</th>
            <th>Requis</th>
        </tr>
        <tr>
            <td>Systeme</td>
            <td>Android 8.0+ ou iOS 13.0+</td>
        </tr>
        <tr>
            <td>GPS</td>
            <td>Actif et autorise (obligatoire)</td>
        </tr>
        <tr>
            <td>Internet</td>
            <td>Connexion data ou Wi-Fi</td>
        </tr>
        <tr>
            <td>Espace</td>
            <td>~50 Mo minimum</td>
        </tr>
    </table>

    <div class="info-box orange">
        <strong>Important</strong>
        Le GPS doit etre active en permanence pendant le pointage. Sans GPS, l'application ne pourra pas
        verifier votre position et le pointage sera refuse.
    </div>

    <!-- ========================================= -->
    <!-- 2. INSTALLATION                           -->
    <!-- ========================================= -->
    <div class="page-break"></div>
    <h2>2. Installation et premier lancement</h2>

    <h3>Android</h3>
    <div class="step">
        <div class="step-number"><span>1</span></div>
        <div class="step-content">
            <strong>Telechargez le fichier APK</strong><br>
            L'administrateur vous fournira le lien de telechargement ou le fichier APK directement.
        </div>
    </div>
    <div class="step">
        <div class="step-number"><span>2</span></div>
        <div class="step-content">
            <strong>Autorisez les sources inconnues</strong><br>
            Allez dans Parametres > Securite > Sources inconnues et activez l'option pour votre navigateur.
        </div>
    </div>
    <div class="step">
        <div class="step-number"><span>3</span></div>
        <div class="step-content">
            <strong>Installez l'application</strong><br>
            Ouvrez le fichier APK telecharge et suivez les instructions d'installation.
        </div>
    </div>

    <h3>iOS (iPhone)</h3>
    <div class="step">
        <div class="step-number"><span>1</span></div>
        <div class="step-content">
            <strong>Installez TestFlight</strong><br>
            Telechargez l'application "TestFlight" depuis l'App Store (gratuit).
        </div>
    </div>
    <div class="step">
        <div class="step-number"><span>2</span></div>
        <div class="step-content">
            <strong>Acceptez l'invitation</strong><br>
            L'administrateur vous enverra un lien d'invitation TestFlight. Cliquez dessus pour rejoindre le programme de test.
        </div>
    </div>
    <div class="step">
        <div class="step-number"><span>3</span></div>
        <div class="step-content">
            <strong>Installez l'application</strong><br>
            Ouvrez TestFlight et appuyez sur "Installer" a cote de "INSAM Presence".
        </div>
    </div>

    <h3>Premier lancement</h3>
    <p>Au premier lancement, l'application vous demandera plusieurs autorisations :</p>
    <ul>
        <li><strong>Localisation</strong> : Choisissez "Toujours autoriser" pour le pointage en arriere-plan</li>
        <li><strong>Notifications</strong> : Autorisez pour recevoir les alertes de presence</li>
    </ul>

    <div class="info-box red">
        <strong>Attention</strong>
        Si vous refusez l'acces a la localisation, vous ne pourrez pas utiliser l'application pour pointer.
        Vous pouvez modifier ces autorisations dans les parametres de votre telephone a tout moment.
    </div>

    <!-- ========================================= -->
    <!-- 3. CONNEXION                              -->
    <!-- ========================================= -->
    <div class="page-break"></div>
    <h2>3. Connexion</h2>

    <div class="screenshots-row">
        <div class="col">
            <img src="{{ public_path('docs/screenshots/01_login.jpeg') }}" class="screenshot">
            <div class="screenshot-caption">Ecran de connexion</div>
        </div>
        <div class="col" style="text-align: left; vertical-align: middle; padding-top: 30px;">
            <h3>Comment se connecter</h3>

            <div class="step">
                <div class="step-number"><span>1</span></div>
                <div class="step-content">
                    Saisissez votre <strong>adresse email</strong> fournie par l'administration
                    (ex: 24@iuesinsam.com)
                </div>
            </div>

            <div class="step">
                <div class="step-number"><span>2</span></div>
                <div class="step-content">
                    Saisissez votre <strong>mot de passe</strong>
                    (par defaut : <strong>password123</strong>)
                </div>
            </div>

            <div class="step">
                <div class="step-number"><span>3</span></div>
                <div class="step-content">
                    Appuyez sur <strong>"Se connecter"</strong>
                </div>
            </div>

            <div class="info-box blue">
                <strong>Identifiants</strong>
                Vos identifiants (email + mot de passe) sont fournis par
                l'administration. Contactez votre responsable si vous ne les avez pas recus.
            </div>

            <div class="info-box orange">
                <strong>Securite</strong>
                Un telephone ne peut etre utilise que par <strong>un seul employe par jour</strong>.
                Ne pretez pas votre telephone pour que quelqu'un d'autre pointe.
            </div>
        </div>
    </div>

    <!-- ========================================= -->
    <!-- 4. ÉCRAN D'ACCUEIL                        -->
    <!-- ========================================= -->
    <div class="page-break"></div>
    <h2>4. Ecran d'accueil</h2>

    <p>Apres connexion, vous arrivez sur l'ecran d'accueil qui affiche toutes les informations importantes.</p>

    <div class="screenshots-row">
        <div class="col">
            <img src="{{ public_path('docs/screenshots/02_accueil.jpeg') }}" class="screenshot">
            <div class="screenshot-caption">Partie haute : statut et statistiques</div>
        </div>
        <div class="col">
            <img src="{{ public_path('docs/screenshots/03_accueil_campus.jpeg') }}" class="screenshot">
            <div class="screenshot-caption">Partie basse : UE et campus</div>
        </div>
    </div>

    <h3>Elements de l'ecran d'accueil</h3>

    <h4>En-tete</h4>
    <ul>
        <li>Votre <strong>nom</strong> et <strong>type d'employe</strong> (Vacataire, Semi-Permanent, Permanent...)</li>
        <li>Bouton <strong>rafraichir</strong> (icone rotation) pour mettre a jour les donnees</li>
        <li>Bouton <strong>deconnexion</strong> (icone porte) en haut a droite</li>
    </ul>

    <h4>Statut de pointage</h4>
    <ul>
        <li><strong>"Pas de check-in actif"</strong> : Vous n'etes pas encore pointe aujourd'hui</li>
        <li><strong>"Check-in actif sur Campus X"</strong> : Vous etes actuellement pointe</li>
    </ul>

    <h4>Statistiques du mois</h4>
    <ul>
        <li><strong>Check-ins</strong> : Nombre de pointages d'entree ce mois</li>
        <li><strong>Retards</strong> : Nombre de retards (arrivee apres l'heure limite)</li>
        <li><strong>Jours</strong> : Nombre de jours travailles ce mois</li>
    </ul>

    <h4>Emploi du temps</h4>
    <p>Affiche vos cours du jour. Cliquez sur "Semaine" pour voir la semaine complete.</p>

    <h4>Mes Unites d'Enseignement (Enseignants)</h4>
    <p>Resume de vos heures effectuees et montant paye. Affiche la liste de vos UE attribuees.</p>

    <h4>Mes Campus</h4>
    <p>Liste des campus auxquels vous etes assigne, avec adresse et horaires. Cliquez sur un campus pour acceder au pointage.</p>

    <!-- ========================================= -->
    <!-- 5. CHECK-IN                               -->
    <!-- ========================================= -->
    <div class="page-break"></div>
    <h2>5. Pointer sa presence (Check-in)</h2>

    <div class="screenshots-row">
        <div class="col">
            <img src="{{ public_path('docs/screenshots/04_campus_detail.jpeg') }}" class="screenshot">
            <div class="screenshot-caption">Detail du campus avec position GPS</div>
        </div>
        <div class="col" style="text-align: left; vertical-align: middle; padding-top: 20px;">
            <h3>Etapes du check-in</h3>

            <div class="step">
                <div class="step-number"><span>1</span></div>
                <div class="step-content">
                    Depuis l'accueil, appuyez sur le <strong>campus</strong> ou vous vous trouvez
                </div>
            </div>

            <div class="step">
                <div class="step-number"><span>2</span></div>
                <div class="step-content">
                    Verifiez que votre <strong>position GPS</strong> est correcte.
                    Le message doit indiquer "Vous etes dans la zone"
                </div>
            </div>

            <div class="step">
                <div class="step-number"><span>3</span></div>
                <div class="step-content">
                    Si vous etes enseignant, selectionnez votre <strong>Unite d'Enseignement</strong>
                </div>
            </div>

            <div class="step">
                <div class="step-number"><span>4</span></div>
                <div class="step-content">
                    Appuyez sur le bouton <strong>"Check-in"</strong> pour valider votre pointage
                </div>
            </div>
        </div>
    </div>

    <h3>Informations du campus</h3>
    <table class="data-table">
        <tr>
            <th>Information</th>
            <th>Description</th>
        </tr>
        <tr>
            <td>Horaires</td>
            <td>Plage horaire du campus (ex: 08:00 - 18:00)</td>
        </tr>
        <tr>
            <td>Tolerance retard</td>
            <td>Duree apres laquelle vous etes considere en retard (ex: 10 min = retard apres 08:10)</td>
        </tr>
        <tr>
            <td>Rayon</td>
            <td>Distance maximale du campus pour pouvoir pointer (ex: 100 metres)</td>
        </tr>
        <tr>
            <td>Distance</td>
            <td>Votre distance actuelle par rapport au campus</td>
        </tr>
    </table>

    <div class="info-box red">
        <strong>Vous etes hors de la zone</strong>
        Si ce message apparait en rouge, vous etes trop loin du campus. Rapprochez-vous pour pouvoir pointer.
        La distance et le rayon autorise sont affiches.
    </div>

    <div class="info-box green">
        <strong>Vous etes dans la zone</strong>
        Si ce message apparait en vert, vous pouvez pointer. Le bouton "Check-in" sera actif.
    </div>

    <h3>Gestion des retards</h3>
    <table class="data-table">
        <tr>
            <th>Situation</th>
            <th>Statut</th>
        </tr>
        <tr>
            <td>Arrivee avant l'heure limite (ex: avant 08:15)</td>
            <td style="color: #16a34a; font-weight: bold;">A l'heure</td>
        </tr>
        <tr>
            <td>Arrivee apres l'heure limite (ex: 08:30)</td>
            <td style="color: #dc2626; font-weight: bold;">Retard (15 min)</td>
        </tr>
        <tr>
            <td>Arrivee tres tardive (apres 10h15)</td>
            <td style="color: #ea580c; font-weight: bold;">Demi-journee</td>
        </tr>
    </table>

    <!-- ========================================= -->
    <!-- 6. CHECK-OUT                              -->
    <!-- ========================================= -->
    <div class="page-break"></div>
    <h2>6. Pointer sa sortie (Check-out)</h2>

    <h3>Etapes du check-out</h3>

    <div class="step">
        <div class="step-number"><span>1</span></div>
        <div class="step-content">
            Depuis l'accueil, appuyez sur le <strong>campus</strong> ou vous avez pointe votre entree
        </div>
    </div>
    <div class="step">
        <div class="step-number"><span>2</span></div>
        <div class="step-content">
            Verifiez que vous etes <strong>dans la zone GPS</strong> du campus
        </div>
    </div>
    <div class="step">
        <div class="step-number"><span>3</span></div>
        <div class="step-content">
            Appuyez sur le bouton <strong>"Check-out"</strong> pour enregistrer votre sortie
        </div>
    </div>

    <div class="info-box orange">
        <strong>Pas de check-out ?</strong>
        Si vous oubliez de faire votre check-out, le systeme cloturera automatiquement votre journee
        a la fin de la plage horaire et elle sera comptabilisee comme une <strong>demi-journee</strong>.
        Pensez toujours a faire votre check-out !
    </div>

    <h3>Calcul de la duree</h3>
    <p>La duree de travail est calculee automatiquement :</p>
    <ul>
        <li>Heure d'arrivee avant 8h00 : comptee a partir de 8h00</li>
        <li>Heure de depart apres 18h00 : plafonnee a 18h00 (sauf cours du soir : 21h30)</li>
        <li>La pause dejeuner est deduite automatiquement si applicable</li>
    </ul>

    <!-- ========================================= -->
    <!-- 7. HISTORIQUE                             -->
    <!-- ========================================= -->
    <div class="page-break"></div>
    <h2>7. Historique des presences</h2>

    <div class="screenshots-row">
        <div class="col">
            <img src="{{ public_path('docs/screenshots/05_historique.jpeg') }}" class="screenshot">
            <div class="screenshot-caption">Ecran Historique</div>
        </div>
        <div class="col" style="text-align: left; vertical-align: middle; padding-top: 30px;">
            <p>L'onglet <strong>"Historique"</strong> est accessible via la barre de navigation en bas de l'ecran.</p>

            <h3>Fonctionnalites</h3>
            <ul>
                <li><strong>Vue par mois</strong> : Voir tous vos pointages du mois selectionne</li>
                <li><strong>Vue par jour</strong> : Voir le detail d'une journee specifique</li>
                <li><strong>Selecteur de periode</strong> : Choisissez le mois a consulter</li>
            </ul>

            <h3>Informations affichees</h3>
            <p>Pour chaque journee, vous verrez :</p>
            <ul>
                <li>Date et jour de la semaine</li>
                <li>Campus de pointage</li>
                <li>Heure d'entree et de sortie</li>
                <li>Duree de travail</li>
                <li>Statut : A l'heure, Retard ou Demi-journee</li>
            </ul>

            <div class="info-box blue">
                <strong>Rafraichir</strong>
                Appuyez sur l'icone de rafraichissement en haut a droite pour mettre a jour les donnees.
            </div>
        </div>
    </div>

    <!-- ========================================= -->
    <!-- 8. PROFIL ET FICHE DE PAIE               -->
    <!-- ========================================= -->
    <div class="page-break"></div>
    <h2>8. Profil et fiche de paie</h2>

    <div class="screenshots-row">
        <div class="col">
            <img src="{{ public_path('docs/screenshots/06_profil.jpeg') }}" class="screenshot">
            <div class="screenshot-caption">Profil - Remuneration</div>
        </div>
        <div class="col">
            <img src="{{ public_path('docs/screenshots/07_profil_details.jpeg') }}" class="screenshot">
            <div class="screenshot-caption">Profil - Presences et deductions</div>
        </div>
    </div>

    <p>L'onglet <strong>"Profil"</strong> est accessible via la barre de navigation en bas de l'ecran.</p>

    <h3>Informations du profil</h3>

    <h4>En-tete</h4>
    <ul>
        <li>Votre nom, type d'employe et adresse email</li>
        <li><strong>Net a Percevoir</strong> : Le montant total que vous recevrez ce mois</li>
    </ul>

    <h4>Fiche de paie</h4>
    <ul>
        <li>Selectionnez le <strong>mois</strong> souhaite dans le menu deroulant</li>
        <li>Appuyez sur <strong>"Telecharger la Fiche de Paie"</strong> pour obtenir votre fiche en PDF</li>
    </ul>

    <h4>Remuneration</h4>
    <table class="data-table">
        <tr>
            <th>Element</th>
            <th>Description</th>
        </tr>
        <tr>
            <td>Taux Horaire</td>
            <td>Votre taux horaire en FCFA (vacataires)</td>
        </tr>
        <tr>
            <td>Heures Travaillees</td>
            <td>Total d'heures effectuees ce mois</td>
        </tr>
        <tr>
            <td>Montant Brut</td>
            <td>Taux horaire x Heures travaillees</td>
        </tr>
        <tr>
            <td>Deductions</td>
            <td>Retenues (deductions manuelles + remboursement prets)</td>
        </tr>
    </table>

    <h4>Presence (Emploi du temps)</h4>
    <ul>
        <li><strong>Programmes</strong> : Nombre de cours programmes ce mois</li>
        <li><strong>Travailles</strong> : Cours effectivement assures</li>
        <li><strong>Manques</strong> : Cours non assures</li>
        <li><strong>Heures</strong> : Total d'heures effectuees</li>
    </ul>

    <h4>Detail des deductions</h4>
    <ul>
        <li><strong>Deductions Manuelles</strong> : Retenues decidees par l'administration</li>
        <li><strong>Remboursement Prets</strong> : Montant preleve pour le remboursement de prets en cours</li>
    </ul>

    <!-- ========================================= -->
    <!-- 9. NOTIFICATIONS                          -->
    <!-- ========================================= -->
    <div class="page-break"></div>
    <h2>9. Notifications</h2>

    <p>L'application envoie differents types de notifications :</p>

    <h3>Types de notifications</h3>

    <table class="data-table">
        <tr>
            <th>Type</th>
            <th>Description</th>
            <th>Action requise</th>
        </tr>
        <tr>
            <td><strong>Verification de presence</strong></td>
            <td>"Etes-vous toujours en place ?" - Le systeme verifie que vous etes bien present</td>
            <td>Ouvrez l'application et confirmez votre presence dans le delai imparti</td>
        </tr>
        <tr>
            <td><strong>Rappel de cours</strong></td>
            <td>Vous rappelle qu'un cours commence bientot</td>
            <td>Rendez-vous sur le campus et pointez</td>
        </tr>
        <tr>
            <td><strong>Zone de pointage</strong></td>
            <td>"Vous etes a proximite du campus" - Detection automatique</td>
            <td>Ouvrez l'application pour faire votre check-in</td>
        </tr>
    </table>

    <div class="info-box red">
        <strong>Verification de presence</strong>
        Lorsque vous recevez une notification de verification, vous devez repondre dans le delai
        indique. Si vous ne repondez pas, un <strong>incident</strong> sera cree dans le systeme.
    </div>

    <div class="info-box blue">
        <strong>Activer les notifications</strong>
        Assurez-vous que les notifications sont autorisees pour l'application INSAM Presence
        dans les parametres de votre telephone. Sans notifications, vous ne recevrez pas les alertes de presence.
    </div>

    <!-- ========================================= -->
    <!-- 10. FAQ                                   -->
    <!-- ========================================= -->
    <div class="page-break"></div>
    <h2>10. Questions frequentes (FAQ)</h2>

    <h4>Je n'arrive pas a me connecter</h4>
    <ul>
        <li>Verifiez que votre email et mot de passe sont corrects</li>
        <li>Le mot de passe par defaut est <strong>password123</strong></li>
        <li>Si votre compte est desactive, contactez l'administration</li>
    </ul>

    <h4>Le pointage est refuse : "Vous etes hors de la zone"</h4>
    <ul>
        <li>Rapprochez-vous du campus (la distance maximale est affichee)</li>
        <li>Verifiez que votre GPS est active et en mode "Haute precision"</li>
        <li>Appuyez sur "Rafraichir ma position" et attendez quelques secondes</li>
        <li>Sortez a l'exterieur si vous etes dans un batiment (le GPS fonctionne mieux en plein air)</li>
    </ul>

    <h4>"Ce telephone a deja ete utilise par un autre employe"</h4>
    <ul>
        <li>Chaque telephone est limite a <strong>un seul employe par jour</strong></li>
        <li>Vous ne pouvez pas utiliser le telephone d'un collegue pour pointer</li>
        <li>Si c'est une erreur, contactez l'administration pour debloquer votre appareil</li>
    </ul>

    <h4>J'ai oublie de faire mon check-out</h4>
    <ul>
        <li>Le systeme cloturera automatiquement votre journee en fin de plage horaire</li>
        <li>La journee sera comptabilisee comme une <strong>demi-journee</strong></li>
        <li>Pensez toujours a faire votre check-out avant de quitter le campus</li>
    </ul>

    <h4>Ma fiche de paie affiche 0 FCFA</h4>
    <ul>
        <li>Verifiez que vous avez bien effectue des pointages ce mois</li>
        <li>Pour les vacataires : verifiez que vos UE sont bien attribuees et activees</li>
        <li>Les fiches de paie sont generees par l'administration en fin de mois</li>
    </ul>

    <h4>Je ne recois pas les notifications</h4>
    <ul>
        <li>Verifiez que les notifications sont autorisees dans Parametres > Applications > INSAM Presence</li>
        <li>Desactivez l'economiseur de batterie pour cette application</li>
        <li>Sur certains telephones (Xiaomi, Huawei), ajoutez l'app aux "Apps protegees" ou "Demarrage automatique"</li>
    </ul>

    <h4>L'application me demande de mettre a jour</h4>
    <ul>
        <li>Suivez les instructions a l'ecran pour telecharger la derniere version</li>
        <li>Sur Android : le telechargement se fait automatiquement</li>
        <li>Sur iOS : ouvrez TestFlight pour installer la mise a jour</li>
    </ul>

    <!-- ========================================= -->
    <!-- FOOTER                                    -->
    <!-- ========================================= -->
    <div style="margin-top: 40px; padding-top: 15px; border-top: 2px solid #2563eb; text-align: center;">
        <p style="font-size: 11px; color: #1e40af; font-weight: bold; margin: 0;">
            IUEs/INSAM - Systeme de Pointage par Geolocalisation
        </p>
        <p style="font-size: 9px; color: #94a3b8; margin: 5px 0 0 0;">
            Document genere le {{ now()->format('d/m/Y') }} | Version 2.0.1
            | Pour toute assistance, contactez l'administration
        </p>
    </div>

</body>
</html>
