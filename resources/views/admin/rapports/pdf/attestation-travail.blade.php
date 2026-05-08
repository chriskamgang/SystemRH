<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; margin: 40px; }
        .header { text-align: center; margin-bottom: 40px; border-bottom: 2px solid #1A237E; padding-bottom: 20px; }
        .header h1 { font-size: 18px; color: #1A237E; margin: 0; }
        .header p { margin: 5px 0; color: #666; }
        .title { text-align: center; font-size: 16px; font-weight: bold; color: #1A237E; margin: 30px 0; text-transform: uppercase; text-decoration: underline; }
        .content { line-height: 1.8; margin: 20px 40px; text-align: justify; }
        .content strong { color: #1A237E; }
        .footer { margin-top: 60px; text-align: right; margin-right: 40px; }
        .footer .date { margin-bottom: 40px; }
        .footer .signature { font-weight: bold; }
        .stamp { border-top: 1px solid #ccc; margin-top: 80px; padding-top: 10px; font-size: 10px; color: #999; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>IUEs/INSAM</h1>
        <p>Institut Universitaire des Etudes Superieures</p>
        <p>Douala, Cameroun</p>
    </div>

    <div class="title">{{ $typeLabels[$cert->type] ?? 'Attestation' }}</div>

    <div class="content">
        <p>Je soussigne(e), le Directeur General de l'Institut Universitaire des Etudes Superieures (IUEs/INSAM),
        atteste par la presente que :</p>

        <p><strong>{{ $user->full_name }}</strong></p>

        <p>
            Matricule : <strong>{{ $user->employee_id ?? 'N/A' }}</strong><br>
            Departement : <strong>{{ $user->department?->name ?? 'N/A' }}</strong><br>
            Poste : <strong>{{ $user->jobPosition?->name ?? $user->employee_type }}</strong><br>
            @if($years > 0 || $months > 0)
            Anciennete : <strong>{{ $years > 0 ? $years . ' an(s)' : '' }}{{ $years > 0 && $months > 0 ? ' et ' : '' }}{{ $months > 0 ? $months . ' mois' : '' }}</strong><br>
            @endif
        </p>

        @if($cert->type === 'work')
        <p>est employe(e) au sein de notre etablissement depuis le <strong>{{ $user->created_at->format('d/m/Y') }}</strong>
        en qualite de <strong>{{ $user->jobPosition?->name ?? $user->employee_type }}</strong>.</p>
        <p>Cette attestation est delivree a l'interesse(e) pour servir et valoir ce que de droit.</p>
        @elseif($cert->type === 'salary')
        <p>est employe(e) au sein de notre etablissement et percoit une remuneration mensuelle nette de
        <strong>{{ number_format($extraData['monthly_salary'] ?? 0, 0, ',', ' ') }} FCFA</strong>.</p>
        <p>Cette attestation de salaire est delivree a l'interesse(e) pour servir et valoir ce que de droit.</p>
        @elseif($cert->type === 'employment')
        <p>a ete employe(e) au sein de notre etablissement du <strong>{{ $user->created_at->format('d/m/Y') }}</strong>
        a ce jour, en qualite de <strong>{{ $user->jobPosition?->name ?? $user->employee_type }}</strong>.</p>
        <p>Durant son parcours, {{ $user->first_name }} a fait preuve de serieux et de professionnalisme.</p>
        <p>Ce certificat de travail est delivre a l'interesse(e) pour servir et valoir ce que de droit.</p>
        @endif

        @if($cert->purpose)
        <p><em>Motif : {{ $cert->purpose }}</em></p>
        @endif
    </div>

    <div class="footer">
        <div class="date">Fait a Douala, le {{ now()->format('d/m/Y') }}</div>
        <div class="signature">Le Directeur General</div>
    </div>

    <div class="stamp">
        Document genere automatiquement - Ref: ATT-{{ str_pad($cert->id, 6, '0', STR_PAD_LEFT) }}/{{ now()->format('Y') }}
    </div>
</body>
</html>
