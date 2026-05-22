<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #333;
        }
        .page {
            position: relative;
            width: 100%;
            min-height: 100%;
            padding: 50px 60px;
        }
        .border-frame {
            border: 3px double #1A237E;
            padding: 40px 50px;
            min-height: 90vh;
            position: relative;
        }
        .header {
            text-align: center;
            margin-bottom: 10px;
        }
        .header h1 {
            font-size: 22px;
            color: #1A237E;
            margin: 0;
            letter-spacing: 2px;
        }
        .header .subtitle {
            font-size: 11px;
            color: #555;
            margin: 4px 0 2px 0;
        }
        .header .location {
            font-size: 10px;
            color: #777;
        }
        .header-line {
            border: none;
            height: 3px;
            background: linear-gradient(to right, #1A237E, #3F51B5, #1A237E);
            margin: 15px 0 30px 0;
        }
        .title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            color: #1A237E;
            margin: 25px 0 35px 0;
            text-transform: uppercase;
            letter-spacing: 3px;
            text-decoration: underline;
            text-underline-offset: 6px;
        }
        .content {
            line-height: 2;
            margin: 0 20px;
            text-align: justify;
            font-size: 12px;
        }
        .content p {
            margin-bottom: 12px;
        }
        .employee-name {
            font-size: 15px;
            font-weight: bold;
            color: #1A237E;
            margin: 15px 0;
            padding: 8px 0;
        }
        .info-block {
            margin: 15px 0;
            line-height: 2.2;
        }
        .info-block .label {
            color: #555;
        }
        .info-block .value {
            font-weight: bold;
            color: #1A237E;
        }
        .purpose {
            margin-top: 15px;
            padding: 10px 15px;
            background: #f5f5f5;
            border-left: 3px solid #1A237E;
            font-style: italic;
            color: #555;
        }
        .footer {
            margin-top: 50px;
            text-align: right;
            margin-right: 20px;
        }
        .footer .date {
            margin-bottom: 50px;
            font-size: 12px;
        }
        .footer .signature-title {
            font-weight: bold;
            font-size: 13px;
            color: #1A237E;
        }
        .footer .signature-line {
            margin-top: 50px;
            border-top: 1px solid #999;
            width: 200px;
            display: inline-block;
            padding-top: 5px;
            font-size: 10px;
            color: #999;
        }
        .stamp {
            position: absolute;
            bottom: 15px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 9px;
            color: #bbb;
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="border-frame">
            <div class="header">
                <h1>IUEs/INSAM</h1>
                <p class="subtitle">Institut Universitaire et Strategique de l'Estuaire</p>
                <p class="location">Douala, Cameroun</p>
            </div>

            <hr class="header-line">

            <div class="title">{{ $typeLabels[$cert->type] ?? 'Attestation' }}</div>

            <div class="content">
                <p>Je soussigne(e), le Directeur General de l'Institut Universitaire et Strategique de l'Estuaire
                (IUEs/INSAM), atteste par la presente que :</p>

                @php
                    $displayName = trim(($user->last_name ?? '') . ' ' . ($user->first_name ?? ''));
                    if (empty($displayName)) {
                        $displayName = $user->full_name;
                    }
                    $ancYears = (int) $years;
                    $ancMonths = (int) $months;
                @endphp

                <p class="employee-name">{{ mb_strtoupper($displayName) }}</p>

                <div class="info-block">
                    <span class="label">Matricule :</span> <span class="value">{{ $user->employee_id ?? 'N/A' }}</span><br>
                    <span class="label">Departement :</span> <span class="value">{{ $user->department?->name ?? 'N/A' }}</span><br>
                    <span class="label">Poste :</span> <span class="value">{{ $user->jobPosition?->name ?? ucfirst(str_replace('_', ' ', $user->employee_type)) }}</span><br>
                    @if($ancYears > 0 || $ancMonths > 0)
                    <span class="label">Anciennete :</span> <span class="value">{{ $ancYears > 0 ? $ancYears . ' an(s)' : '' }}{{ $ancYears > 0 && $ancMonths > 0 ? ' et ' : '' }}{{ $ancMonths > 0 ? $ancMonths . ' mois' : '' }}</span><br>
                    @endif
                </div>

                @if($cert->type === 'work')
                <p>est employe(e) au sein de notre etablissement depuis le <strong>{{ $user->created_at->format('d/m/Y') }}</strong>
                en qualite de <strong>{{ $user->jobPosition?->name ?? ucfirst(str_replace('_', ' ', $user->employee_type)) }}</strong>.</p>
                <p>Cette attestation est delivree a l'interesse(e) pour servir et valoir ce que de droit.</p>
                @elseif($cert->type === 'salary')
                <p>est employe(e) au sein de notre etablissement et percoit une remuneration mensuelle nette de
                <strong>{{ number_format($extraData['monthly_salary'] ?? 0, 0, ',', ' ') }} FCFA</strong>.</p>
                <p>Cette attestation de salaire est delivree a l'interesse(e) pour servir et valoir ce que de droit.</p>
                @elseif($cert->type === 'employment')
                <p>a ete employe(e) au sein de notre etablissement du <strong>{{ $user->created_at->format('d/m/Y') }}</strong>
                a ce jour, en qualite de <strong>{{ $user->jobPosition?->name ?? ucfirst(str_replace('_', ' ', $user->employee_type)) }}</strong>.</p>
                <p>Durant son parcours, {{ $user->first_name }} a fait preuve de serieux et de professionnalisme.</p>
                <p>Ce certificat de travail est delivre a l'interesse(e) pour servir et valoir ce que de droit.</p>
                @endif

                @if($cert->purpose)
                <div class="purpose">
                    Motif : {{ $cert->purpose }}
                </div>
                @endif
            </div>

            <div class="footer">
                <div class="date">Fait a Douala, le {{ now()->format('d/m/Y') }}</div>
                <div class="signature-title">Le Directeur General</div>
                <div class="signature-line">Signature & Cachet</div>
            </div>

            <div class="stamp">
                Document genere automatiquement - Ref: ATT-{{ str_pad($cert->id, 6, '0', STR_PAD_LEFT) }}/{{ now()->format('Y') }}
            </div>
        </div>
    </div>
</body>
</html>
