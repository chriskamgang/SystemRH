<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 25mm 20mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #333; line-height: 1.6; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #1e3a5f; padding-bottom: 15px; }
        .header h1 { font-size: 18px; color: #1e3a5f; margin: 0; letter-spacing: 2px; text-transform: uppercase; }
        .header h2 { font-size: 14px; color: #666; margin: 5px 0 0; font-weight: normal; }
        .ref { text-align: right; font-size: 11px; color: #666; margin-bottom: 20px; }
        .title { text-align: center; font-size: 16px; font-weight: bold; color: #1e3a5f; margin: 30px 0; text-decoration: underline; text-transform: uppercase; letter-spacing: 1px; }
        .section { margin-bottom: 15px; }
        .section-title { font-weight: bold; color: #1e3a5f; font-size: 13px; margin-bottom: 8px; border-left: 4px solid #2563eb; padding-left: 10px; }
        table.info { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table.info td { padding: 6px 10px; border: 1px solid #ddd; }
        table.info td.label { background-color: #f3f4f6; font-weight: bold; width: 40%; color: #555; font-size: 11px; }
        table.info td.value { font-size: 12px; }
        .signature-block { margin-top: 50px; }
        .signature-row { display: table; width: 100%; }
        .signature-cell { display: table-cell; width: 50%; text-align: center; vertical-align: top; padding: 10px; }
        .signature-cell p { margin: 3px 0; }
        .signature-line { border-top: 1px solid #999; width: 200px; margin: 40px auto 5px; }
        .footer { margin-top: 40px; text-align: center; font-size: 9px; color: #999; border-top: 1px solid #ddd; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $company->name ?? 'IUES/INSAM' }}</h1>
        <h2>Service des Ressources Humaines</h2>
    </div>

    <div class="ref">
        Réf : CONGE/{{ $leave->id }}/{{ $leave->start_date->format('Y') }}<br>
        Fait à Bafoussam, le {{ now()->format('d/m/Y') }}
    </div>

    <div class="title">Lettre de Mise en Congé</div>

    <div class="section">
        <div class="section-title">Informations du Salarié</div>
        <table class="info">
            <tr>
                <td class="label">Nom et Prénom</td>
                <td class="value">{{ $user->last_name }} {{ $user->first_name }}</td>
            </tr>
            <tr>
                <td class="label">Matricule</td>
                <td class="value">{{ $user->employee_id }}</td>
            </tr>
            @if($user->department)
            <tr>
                <td class="label">Service / Département</td>
                <td class="value">{{ $user->department->name }}</td>
            </tr>
            @endif
            <tr>
                <td class="label">Fonction</td>
                <td class="value">{{ ucfirst(str_replace('_', ' ', $user->employee_type)) }}</td>
            </tr>
            @if($user->phone)
            <tr>
                <td class="label">Téléphone</td>
                <td class="value">{{ $user->phone }}</td>
            </tr>
            @endif
        </table>
    </div>

    <div class="section">
        <div class="section-title">Détails du Congé</div>
        <table class="info">
            <tr>
                <td class="label">Type de congé</td>
                <td class="value">{{ $leave->getTypeLabel() }}</td>
            </tr>
            <tr>
                <td class="label">Date de début</td>
                <td class="value">{{ $leave->start_date->translatedFormat('l d F Y') }}</td>
            </tr>
            <tr>
                <td class="label">Date de fin</td>
                <td class="value">{{ $leave->end_date->translatedFormat('l d F Y') }}</td>
            </tr>
            <tr>
                <td class="label">Durée (jours ouvrables)</td>
                <td class="value">{{ $leave->days_count }} jour(s)</td>
            </tr>
            <tr>
                <td class="label">Date de reprise prévue</td>
                <td class="value">{{ $leave->end_date->addDay()->translatedFormat('l d F Y') }}</td>
            </tr>
            <tr>
                <td class="label">Motif</td>
                <td class="value">{{ $leave->reason }}</td>
            </tr>
        </table>
    </div>

    @if($leave->interim_name)
    <div class="section">
        <div class="section-title">Intérimaire Désigné</div>
        <table class="info">
            <tr>
                <td class="label">Nom et Fonction</td>
                <td class="value">{{ $leave->interim_name }} - {{ $leave->interim_function }}</td>
            </tr>
            @if($leave->interim_tasks)
            <tr>
                <td class="label">Dossiers transmis</td>
                <td class="value">{{ $leave->interim_tasks }}</td>
            </tr>
            @endif
        </table>
    </div>
    @endif

    <div class="section">
        <div class="section-title">Décision</div>
        <p>La présente lettre autorise le(la) salarié(e) susmentionné(e) à bénéficier de son congé pour la période indiquée ci-dessus, conformément aux dispositions du Code du Travail camerounais.</p>
        @if($leave->review_comment)
        <p><strong>Observation :</strong> {{ $leave->review_comment }}</p>
        @endif
    </div>

    <div class="signature-block">
        <div class="signature-row">
            <div class="signature-cell">
                <p><strong>Le Salarié</strong></p>
                <div class="signature-line"></div>
                <p>{{ $user->last_name }} {{ $user->first_name }}</p>
            </div>
            <div class="signature-cell">
                <p><strong>Le Directeur des Ressources Humaines</strong></p>
                <div class="signature-line"></div>
                <p>{{ $leave->reviewer?->full_name ?? '' }}</p>
            </div>
        </div>
    </div>

    <div class="footer">
        Document généré automatiquement le {{ now()->format('d/m/Y à H:i') }} — {{ $company->name ?? 'IUES/INSAM' }} — Système RH
    </div>
</body>
</html>
