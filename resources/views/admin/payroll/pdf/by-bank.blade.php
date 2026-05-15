<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Salaires par Banque - {{ \Carbon\Carbon::create($year, $month)->locale('fr')->isoFormat('MMMM YYYY') }}</title>
    <style>
        @page {
            margin-top: 120px;
            margin-bottom: 50px;
            margin-left: 30px;
            margin-right: 30px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9px;
            line-height: 1.3;
            color: #333;
        }
        .meta-info {
            background: #f3f4f6;
            padding: 8px;
            margin-bottom: 10px;
            border-radius: 3px;
        }
        .meta-info p { margin: 2px 0; font-size: 8px; }
        .stats-grid { display: table; width: 100%; margin-bottom: 10px; }
        .stat-box {
            display: table-cell;
            width: 50%;
            padding: 8px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            text-align: center;
        }
        .stat-box:first-child { border-right: none; }
        .stat-label { font-size: 7px; color: #6b7280; margin-bottom: 2px; }
        .stat-value { font-size: 14px; font-weight: bold; color: #1e40af; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        thead { background: #1e40af; color: white; }
        th { padding: 5px 4px; text-align: left; font-size: 8px; font-weight: bold; }
        td { padding: 4px; border-bottom: 1px solid #e5e7eb; font-size: 8px; }
        tbody tr:nth-child(even) { background: #f9fafb; }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 7px;
            color: #9ca3af;
            padding: 8px;
        }
        .page-number:before { content: "Page " counter(page); }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .text-green { color: #059669; }
        .text-red { color: #dc2626; }
    </style>
</head>
<body>
    <div class="meta-info">
        <p><strong>Salaires par Banque</strong> - {{ \Carbon\Carbon::create($year, $month)->locale('fr')->isoFormat('MMMM YYYY') }} | Jours ouvrables : {{ number_format($workingDays, 1) }} | Employes : {{ $totalEmployees }}@if($selectedBank) | Banque : {{ $selectedBank }}@endif | Edition : {{ date('d/m/Y H:i') }}</p>
    </div>

    <!-- Stats compactes -->
    <div class="stats-grid">
        <div class="stat-box">
            <div class="stat-label">SALAIRES BRUTS</div>
            <div class="stat-value">{{ number_format($totalGrossSalary, 0, ',', ' ') }} FCFA</div>
        </div>
        <div class="stat-box" style="background: #ecfdf5; border-color: #a7f3d0;">
            <div class="stat-label">MONTANT NET A VIRER</div>
            <div class="stat-value text-green">{{ number_format($totalNetSalary, 0, ',', ' ') }} FCFA</div>
        </div>
    </div>

    @if($totalBanks > 1)
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Banque</th>
                <th class="text-center">Employes</th>
                <th class="text-right">Salaires Bruts</th>
                <th class="text-right">Deductions</th>
                <th class="text-right">Montant a Virer</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bankGroups as $index => $group)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td><strong>{{ $group['bank_name'] }}</strong></td>
                <td class="text-center">{{ $group['count'] }}</td>
                <td class="text-right">{{ number_format($group['total_gross'], 0, ',', ' ') }}</td>
                <td class="text-right text-red">{{ number_format($group['total_deductions'], 0, ',', ' ') }}</td>
                <td class="text-right text-green font-bold">{{ number_format($group['total_net'], 0, ',', ' ') }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background: #1e40af; color: white; font-weight: bold;">
                <td colspan="2" style="padding: 5px 4px;">TOTAL</td>
                <td class="text-center" style="padding: 5px 4px;">{{ $totalEmployees }}</td>
                <td class="text-right" style="padding: 5px 4px;">{{ number_format($totalGrossSalary, 0, ',', ' ') }}</td>
                <td class="text-right" style="padding: 5px 4px;">{{ number_format($totalGrossSalary - $totalNetSalary, 0, ',', ' ') }}</td>
                <td class="text-right" style="padding: 5px 4px;">{{ number_format($totalNetSalary, 0, ',', ' ') }}</td>
            </tr>
        </tfoot>
    </table>
    @endif

    @foreach($bankGroups as $group)
    <div style="margin-top: 6px;">
        <div style="background: #eff6ff; padding: 4px 8px; border-left: 3px solid #2563eb; margin-bottom: 2px;">
            <strong style="font-size: 9px; color: #1e40af;">{{ $group['bank_name'] }}</strong>
            <span style="font-size: 7px; color: #6b7280; margin-left: 6px;">{{ $group['count'] }} employe(s) | Total: {{ number_format($group['total_net'], 0, ',', ' ') }} FCFA</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Matricule</th>
                    <th>Nom & Prenom</th>
                    <th>N Compte</th>
                    <th class="text-center">Jrs Trav.</th>
                    <th class="text-center">Heures</th>
                    <th class="text-center">Retards</th>
                    <th class="text-right">Sal. Brut</th>
                    <th class="text-right">Ded.</th>
                    <th class="text-right">Sal. Net</th>
                </tr>
            </thead>
            <tbody>
                @foreach($group['employees'] as $empIndex => $employee)
                <tr>
                    <td>{{ $empIndex + 1 }}</td>
                    <td>{{ $employee->employee_id }}</td>
                    <td><strong>{{ $employee->full_name }}</strong></td>
                    <td>{{ $employee->numero_compte ?: '-' }}</td>
                    <td class="text-center">{{ number_format($employee->days_worked, 1) }}/{{ number_format($employee->working_days ?? 0, 1) }}</td>
                    <td class="text-center">{{ number_format($employee->total_hours_worked ?? 0, 1) }}h</td>
                    <td class="text-center {{ ($employee->total_late_minutes ?? 0) > 0 ? 'text-orange' : '' }}">{{ $employee->total_late_minutes ?? 0 }}min</td>
                    <td class="text-right">{{ number_format($employee->gross_salary, 0, ',', ' ') }}</td>
                    <td class="text-right text-red">{{ number_format($employee->total_deductions, 0, ',', ' ') }}</td>
                    <td class="text-right text-green font-bold">{{ number_format($employee->net_salary, 0, ',', ' ') }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background: #f3f4f6; font-weight: bold;">
                    <td colspan="7" class="text-right" style="padding: 4px;">Sous-total</td>
                    <td class="text-right" style="padding: 4px;">{{ number_format($group['total_gross'], 0, ',', ' ') }}</td>
                    <td class="text-right text-red" style="padding: 4px;">{{ number_format($group['total_deductions'], 0, ',', ' ') }}</td>
                    <td class="text-right text-green" style="padding: 4px;">{{ number_format($group['total_net'], 0, ',', ' ') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endforeach

    <div style="margin-top: 20px; display: table; width: 100%;">
        <div style="display: table-cell; width: 50%; text-align: center; padding: 10px;">
            <p style="font-size: 8px; font-weight: bold; margin-bottom: 30px;">Prepare par :</p>
            <p style="border-top: 1px solid #333; display: inline-block; padding-top: 4px; font-size: 7px;">Signature & Cachet</p>
        </div>
        <div style="display: table-cell; width: 50%; text-align: center; padding: 10px;">
            <p style="font-size: 8px; font-weight: bold; margin-bottom: 30px;">Verifie et approuve par :</p>
            <p style="border-top: 1px solid #333; display: inline-block; padding-top: 4px; font-size: 7px;">Signature & Cachet</p>
        </div>
    </div>

    <div class="footer">
        <p class="page-number"></p>
    </div>
</body>
</html>
