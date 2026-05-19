<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Salaires par Banque - {{ \Carbon\Carbon::create($year, $month)->locale('fr')->isoFormat('MMMM YYYY') }}</title>
    <style>
        @page {
            margin: 0;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 8px;
            line-height: 1.3;
            color: #333;
        }
        .page {
            position: relative;
            width: 100%;
            min-height: 100%;
            page-break-after: always;
        }
        .page:last-child {
            page-break-after: auto;
        }
        .bg-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1000;
        }
        .content {
            padding: 140px 40px 80px 40px;
        }
        .content-no-bg {
            padding: 30px 40px 80px 40px;
        }
        .content-continuation {
            padding: 30px 40px 80px 40px;
        }
        .continuation-header {
            margin-bottom: 8px;
            font-size: 8px;
            color: #555;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 5px;
        }
        .authorization {
            margin-bottom: 8px;
            font-size: 10px;
            line-height: 1.5;
            text-align: justify;
        }
        .authorization .bank-name {
            font-weight: bold;
        }
        .meta-info {
            margin-bottom: 6px;
            font-size: 8px;
            color: #555;
        }
        .stats-grid { display: table; width: 100%; margin-bottom: 8px; }
        .stat-box {
            display: table-cell;
            width: 50%;
            padding: 5px;
            background: rgba(239, 246, 255, 0.9);
            border: 1px solid #bfdbfe;
            text-align: center;
        }
        .stat-box:first-child { border-right: none; }
        .stat-label { font-size: 7px; color: #6b7280; margin-bottom: 2px; }
        .stat-value { font-size: 12px; font-weight: bold; color: #1e40af; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        thead { background: #1e40af; color: white; }
        th { padding: {{ ($showDetails ?? false) ? '4px 3px' : '6px 5px' }}; text-align: left; font-size: {{ ($showDetails ?? false) ? '7px' : '9px' }}; font-weight: bold; }
        td { padding: {{ ($showDetails ?? false) ? '3px' : '5px' }}; border-bottom: 1px solid #e5e7eb; font-size: {{ ($showDetails ?? false) ? '7px' : '9px' }}; }
        tbody tr:nth-child(even) { background: rgba(249, 250, 251, 0.8); }
        .footer-zone {
            margin-top: 15px;
            display: table;
            width: 100%;
        }
        .signature-box {
            display: table-cell;
            width: 50%;
            text-align: center;
            padding: 8px;
        }
        .page-number {
            text-align: center;
            font-size: 7px;
            color: #9ca3af;
            margin-top: 10px;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .text-green { color: #059669; }
        .text-red { color: #dc2626; }
    </style>
</head>
<body>
    @foreach($bankGroups as $groupIndex => $group)
    @php
        $hasBg = isset($bankHeaderPaths[$group['bank_name']]);
        $employees = $group['employees'];
        $totalEmployees = count($employees);
        // First page fits fewer rows due to header/stats
        $firstPageLimit = $hasBg ? 14 : 22;
        $nextPageLimit = 25;
        $pages = [];
        if ($totalEmployees <= $firstPageLimit) {
            $pages[] = $employees instanceof \Illuminate\Support\Collection
                ? $employees->values()->all()
                : array_values(is_array($employees) ? $employees : iterator_to_array($employees));
        } else {
            $allEmployees = $employees instanceof \Illuminate\Support\Collection
                ? $employees->values()->all()
                : array_values(is_array($employees) ? $employees : iterator_to_array($employees));
            $pages[] = array_slice($allEmployees, 0, $firstPageLimit);
            $remaining = array_slice($allEmployees, $firstPageLimit);
            while (count($remaining) > 0) {
                $pages[] = array_slice($remaining, 0, $nextPageLimit);
                $remaining = array_slice($remaining, $nextPageLimit);
            }
        }
        $totalPages = count($pages);
    @endphp

    @foreach($pages as $pageIndex => $pageEmployees)
    <div class="page">
        @if($pageIndex === 0)
            {{-- FIRST PAGE: background image + header + stats --}}
            @if($hasBg)
            <img src="{{ $bankHeaderPaths[$group['bank_name']] }}" class="bg-image">
            @endif

            <div class="{{ $hasBg ? 'content' : 'content-no-bg' }}">
                {{-- Texte d'autorisation --}}
                <div class="authorization">
                    <p>
                        Je soussigne M FOYET Stephane Lionel donne l'ordre a
                        <span class="bank-name">{{ $group['bank_name'] }}</span>
                        de bien vouloir payer les noms suivant contre quitus signe par le directeur :
                    </p>
                </div>

                {{-- Info periode --}}
                <div class="meta-info">
                    <strong>Periode :</strong> {{ \Carbon\Carbon::create($year, $month)->locale('fr')->isoFormat('MMMM YYYY') }}
                    | Jours ouvrables : {{ number_format($workingDays, 1) }}
                    | Employes : {{ $group['count'] }}
                    | Edition : {{ date('d/m/Y H:i') }}
                </div>

                {{-- Stats compactes --}}
                <div class="stats-grid">
                    <div class="stat-box">
                        <div class="stat-label">SALAIRES BRUTS</div>
                        <div class="stat-value">{{ number_format($group['total_gross'], 0, ',', ' ') }} FCFA</div>
                    </div>
                    <div class="stat-box" style="background: rgba(236, 253, 245, 0.9); border-color: #a7f3d0;">
                        <div class="stat-label">MONTANT NET A VIRER</div>
                        <div class="stat-value text-green">{{ number_format($group['total_net'], 0, ',', ' ') }} FCFA</div>
                    </div>
                </div>

                {{-- Tableau --}}
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Matricule</th>
                            <th>Nom & Prenom</th>
                            <th>N Compte</th>
                            @if($showDetails ?? false)
                            <th class="text-center">Jrs Trav.</th>
                            <th class="text-center">Heures</th>
                            <th class="text-center">Retards</th>
                            @endif
                            <th class="text-right">Sal. Brut</th>
                            <th class="text-right">Ded.</th>
                            <th class="text-right">Sal. Net</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pageEmployees as $empIdx => $employee)
                        <tr>
                            <td>{{ $empIdx + 1 }}</td>
                            <td>{{ $employee->employee_id }}</td>
                            <td><strong>{{ $employee->full_name }}</strong></td>
                            <td>{{ $employee->numero_compte ?: '-' }}</td>
                            @if($showDetails ?? false)
                            <td class="text-center">{{ number_format($employee->days_worked, 1) }}/{{ number_format($employee->working_days ?? 0, 1) }}</td>
                            <td class="text-center">{{ number_format($employee->total_hours_worked ?? 0, 1) }}h</td>
                            <td class="text-center">{{ $employee->total_late_minutes ?? 0 }}min</td>
                            @endif
                            <td class="text-right">{{ number_format($employee->gross_salary, 0, ',', ' ') }}</td>
                            <td class="text-right text-red">{{ number_format($employee->total_deductions, 0, ',', ' ') }}</td>
                            <td class="text-right text-green font-bold">{{ number_format($employee->net_salary, 0, ',', ' ') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    @if($totalPages === 1)
                    <tfoot>
                        <tr style="background: #1e40af; color: white; font-weight: bold;">
                            <td colspan="{{ ($showDetails ?? false) ? 7 : 4 }}" class="text-right" style="padding: 4px 3px;">TOTAL</td>
                            <td class="text-right" style="padding: 4px 3px;">{{ number_format($group['total_gross'], 0, ',', ' ') }}</td>
                            <td class="text-right" style="padding: 4px 3px;">{{ number_format($group['total_deductions'], 0, ',', ' ') }}</td>
                            <td class="text-right" style="padding: 4px 3px;">{{ number_format($group['total_net'], 0, ',', ' ') }}</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>

                @if($totalPages === 1)
                {{-- Signatures only on single-page or last page --}}
                <div class="footer-zone">
                    <div class="signature-box">
                        <p style="font-size: 8px; font-weight: bold; margin-bottom: 25px;">Prepare par :</p>
                        <p style="border-top: 1px solid #333; display: inline-block; padding-top: 4px; font-size: 7px;">Signature & Cachet</p>
                    </div>
                    <div class="signature-box">
                        <p style="font-size: 8px; font-weight: bold; margin-bottom: 25px;">Verifie et approuve par :</p>
                        <p style="border-top: 1px solid #333; display: inline-block; padding-top: 4px; font-size: 7px;">Signature & Cachet</p>
                    </div>
                </div>
                @endif

                <div class="page-number">Page {{ $pageIndex + 1 }}/{{ $totalPages }} — {{ $group['bank_name'] }}</div>
            </div>
        @else
            {{-- CONTINUATION PAGES: no background image, clean layout --}}
            <div class="content-continuation">
                <div class="continuation-header">
                    <strong>{{ $group['bank_name'] }}</strong> —
                    {{ \Carbon\Carbon::create($year, $month)->locale('fr')->isoFormat('MMMM YYYY') }}
                    (suite)
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Matricule</th>
                            <th>Nom & Prenom</th>
                            <th>N Compte</th>
                            @if($showDetails ?? false)
                            <th class="text-center">Jrs Trav.</th>
                            <th class="text-center">Heures</th>
                            <th class="text-center">Retards</th>
                            @endif
                            <th class="text-right">Sal. Brut</th>
                            <th class="text-right">Ded.</th>
                            <th class="text-right">Sal. Net</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pageEmployees as $empIdx => $employee)
                        <tr>
                            <td>{{ $firstPageLimit + ($pageIndex - 1) * $nextPageLimit + $empIdx + 1 }}</td>
                            <td>{{ $employee->employee_id }}</td>
                            <td><strong>{{ $employee->full_name }}</strong></td>
                            <td>{{ $employee->numero_compte ?: '-' }}</td>
                            @if($showDetails ?? false)
                            <td class="text-center">{{ number_format($employee->days_worked, 1) }}/{{ number_format($employee->working_days ?? 0, 1) }}</td>
                            <td class="text-center">{{ number_format($employee->total_hours_worked ?? 0, 1) }}h</td>
                            <td class="text-center">{{ $employee->total_late_minutes ?? 0 }}min</td>
                            @endif
                            <td class="text-right">{{ number_format($employee->gross_salary, 0, ',', ' ') }}</td>
                            <td class="text-right text-red">{{ number_format($employee->total_deductions, 0, ',', ' ') }}</td>
                            <td class="text-right text-green font-bold">{{ number_format($employee->net_salary, 0, ',', ' ') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    @if($pageIndex === $totalPages - 1)
                    <tfoot>
                        <tr style="background: #1e40af; color: white; font-weight: bold;">
                            <td colspan="{{ ($showDetails ?? false) ? 7 : 4 }}" class="text-right" style="padding: 4px 3px;">TOTAL</td>
                            <td class="text-right" style="padding: 4px 3px;">{{ number_format($group['total_gross'], 0, ',', ' ') }}</td>
                            <td class="text-right" style="padding: 4px 3px;">{{ number_format($group['total_deductions'], 0, ',', ' ') }}</td>
                            <td class="text-right" style="padding: 4px 3px;">{{ number_format($group['total_net'], 0, ',', ' ') }}</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>

                @if($pageIndex === $totalPages - 1)
                {{-- Signatures on last page --}}
                <div class="footer-zone">
                    <div class="signature-box">
                        <p style="font-size: 8px; font-weight: bold; margin-bottom: 25px;">Prepare par :</p>
                        <p style="border-top: 1px solid #333; display: inline-block; padding-top: 4px; font-size: 7px;">Signature & Cachet</p>
                    </div>
                    <div class="signature-box">
                        <p style="font-size: 8px; font-weight: bold; margin-bottom: 25px;">Verifie et approuve par :</p>
                        <p style="border-top: 1px solid #333; display: inline-block; padding-top: 4px; font-size: 7px;">Signature & Cachet</p>
                    </div>
                </div>
                @endif

                <div class="page-number">Page {{ $pageIndex + 1 }}/{{ $totalPages }} — {{ $group['bank_name'] }}</div>
            </div>
        @endif
    </div>
    @endforeach
    @endforeach
</body>
</html>
