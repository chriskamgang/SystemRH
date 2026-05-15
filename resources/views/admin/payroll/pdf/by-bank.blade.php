@extends('admin.rapports.pdf.layout')

@section('title', 'Salaires par Banque')
@section('subtitle', \Carbon\Carbon::create($year, $month)->locale('fr')->isoFormat('MMMM YYYY'))

@section('meta-info')
    <p><strong>Periode :</strong> {{ \Carbon\Carbon::create($year, $month)->locale('fr')->isoFormat('MMMM YYYY') }}</p>
    <p><strong>Jours ouvrables :</strong> {{ number_format($workingDays, 1) }}</p>
    <p><strong>Nombre de banques :</strong> {{ $totalBanks }}</p>
    <p><strong>Nombre d'employes :</strong> {{ $totalEmployees }}</p>
    @if($selectedBank)
        <p><strong>Banque :</strong> {{ $selectedBank }}</p>
    @endif
@endsection

@section('content')
    <!-- Statistiques globales -->
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

    <!-- Recapitulatif par banque -->
    <h3 style="font-size: 12px; font-weight: bold; color: #1e40af; margin: 15px 0 8px;">Recapitulatif par Banque</h3>
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
                <td>
                    <strong>{{ $group['bank_name'] }}</strong>
                    @if($group['is_unassigned'])
                        <span style="color: #dc2626; font-size: 7px;">(aucune banque)</span>
                    @endif
                </td>
                <td class="text-center">{{ $group['count'] }}</td>
                <td class="text-right">{{ number_format($group['total_gross'], 0, ',', ' ') }}</td>
                <td class="text-right text-red">{{ number_format($group['total_deductions'], 0, ',', ' ') }}</td>
                <td class="text-right text-green font-bold">{{ number_format($group['total_net'], 0, ',', ' ') }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background: #1e40af; color: white; font-weight: bold;">
                <td colspan="2" style="padding: 8px 5px;">TOTAL</td>
                <td class="text-center" style="padding: 8px 5px;">{{ $totalEmployees }}</td>
                <td class="text-right" style="padding: 8px 5px;">{{ number_format($totalGrossSalary, 0, ',', ' ') }}</td>
                <td class="text-right" style="padding: 8px 5px;">{{ number_format($totalGrossSalary - $totalNetSalary, 0, ',', ' ') }}</td>
                <td class="text-right" style="padding: 8px 5px;">{{ number_format($totalNetSalary, 0, ',', ' ') }}</td>
            </tr>
        </tfoot>
    </table>

    <!-- Detail par banque -->
    @foreach($bankGroups as $group)
    <div style="page-break-inside: avoid; margin-top: 20px;">
        <div style="background: #eff6ff; padding: 8px 10px; border-left: 4px solid #2563eb; margin-bottom: 5px;">
            <strong style="font-size: 11px; color: #1e40af;">{{ $group['bank_name'] }}</strong>
            <span style="font-size: 9px; color: #6b7280; margin-left: 10px;">{{ $group['count'] }} employe(s) | Total: {{ number_format($group['total_net'], 0, ',', ' ') }} FCFA</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Matricule</th>
                    <th>Nom & Prenom</th>
                    <th>Type</th>
                    <th>N Compte</th>
                    <th class="text-right">Salaire Brut</th>
                    <th class="text-right">Deductions</th>
                    <th class="text-right">Salaire Net</th>
                </tr>
            </thead>
            <tbody>
                @foreach($group['employees'] as $empIndex => $employee)
                <tr>
                    <td>{{ $empIndex + 1 }}</td>
                    <td>{{ $employee->employee_id }}</td>
                    <td><strong>{{ $employee->full_name }}</strong></td>
                    <td>
                        @if($employee->employee_type == 'enseignant_titulaire')
                            Permanent
                        @elseif($employee->employee_type == 'semi_permanent')
                            Semi-perm.
                        @else
                            {{ ucfirst($employee->employee_type) }}
                        @endif
                    </td>
                    <td>{{ $employee->numero_compte ?: '-' }}</td>
                    <td class="text-right">{{ number_format($employee->gross_salary, 0, ',', ' ') }}</td>
                    <td class="text-right text-red">{{ number_format($employee->total_deductions, 0, ',', ' ') }}</td>
                    <td class="text-right text-green font-bold">{{ number_format($employee->net_salary, 0, ',', ' ') }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background: #f3f4f6; font-weight: bold;">
                    <td colspan="5" class="text-right" style="padding: 6px 5px;">Sous-total {{ $group['bank_name'] }}</td>
                    <td class="text-right" style="padding: 6px 5px;">{{ number_format($group['total_gross'], 0, ',', ' ') }}</td>
                    <td class="text-right text-red" style="padding: 6px 5px;">{{ number_format($group['total_deductions'], 0, ',', ' ') }}</td>
                    <td class="text-right text-green" style="padding: 6px 5px;">{{ number_format($group['total_net'], 0, ',', ' ') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endforeach

    <!-- Signatures -->
    <div style="margin-top: 30px; display: table; width: 100%;">
        <div style="display: table-cell; width: 50%; text-align: center; padding: 20px;">
            <p style="font-size: 9px; font-weight: bold; margin-bottom: 40px;">Prepare par :</p>
            <p style="border-top: 1px solid #333; display: inline-block; padding-top: 5px; font-size: 8px;">Signature & Cachet</p>
        </div>
        <div style="display: table-cell; width: 50%; text-align: center; padding: 20px;">
            <p style="font-size: 9px; font-weight: bold; margin-bottom: 40px;">Verifie et approuve par :</p>
            <p style="border-top: 1px solid #333; display: inline-block; padding-top: 5px; font-size: 8px;">Signature & Cachet</p>
        </div>
    </div>

    <p style="font-size: 7px; color: #9ca3af; margin-top: 8px;">
        * Tous les montants sont en FCFA. Document genere automatiquement par le systeme de gestion RH - IUEs/INSAM.
    </p>
@endsection
