@extends('admin.rapports.pdf.layout')

@section('title', 'Rapport de Paie')
@section('subtitle', \Carbon\Carbon::create($year, $month)->locale('fr')->isoFormat('MMMM YYYY'))

@section('meta-info')
    <p><strong>Période :</strong> {{ \Carbon\Carbon::create($year, $month)->locale('fr')->isoFormat('MMMM YYYY') }}</p>
    <p><strong>Jours ouvrables :</strong> {{ number_format($workingDays, 1) }} (Lundi-Samedi, Samedi = demi-journée)</p>
    <p><strong>Nombre d'employés :</strong> {{ $totalEmployees }}</p>
    @if($filters)
        <p><strong>Filtres :</strong> {{ $filters }}</p>
    @endif
@endsection

@section('content')
    <!-- Statistiques globales -->
    <div class="stats-grid">
        <div class="stat-box">
            <div class="stat-label">SALAIRES BRUTS</div>
            <div class="stat-value">{{ number_format($totalGrossSalary, 0, ',', ' ') }} FCFA</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">DÉDUCTIONS TOTALES</div>
            <div class="stat-value text-red">{{ number_format($totalDeductions, 0, ',', ' ') }} FCFA</div>
        </div>
    </div>
    <div class="stats-grid">
        <div class="stat-box" style="background: #ecfdf5; border-color: #a7f3d0;">
            <div class="stat-label">SALAIRES NETS À PAYER</div>
            <div class="stat-value text-green">{{ number_format($totalNetSalary, 0, ',', ' ') }} FCFA</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">TOTAL EMPLOYÉS</div>
            <div class="stat-value">{{ $totalEmployees }}</div>
        </div>
    </div>

    <!-- Tableau des employés -->
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Employé</th>
                <th>Type</th>
                <th class="text-right">Salaire Mensuel</th>
                <th class="text-center">Jours Trav.</th>
                <th class="text-center">Jours Abs.</th>
                <th class="text-center">Retards (min)</th>
                <th class="text-right">Pénalités Retard</th>
                <th class="text-right">Déd. Absences</th>
                <th class="text-right">Déd. Manuelles</th>
                <th class="text-right">Prêts</th>
                <th class="text-right">Salaire Final</th>
            </tr>
        </thead>
        <tbody>
            @foreach($employees as $index => $employee)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>
                    <strong>{{ $employee->full_name }}</strong>
                    @if(isset($employee->is_manual_adjustment) && $employee->is_manual_adjustment)
                        <span style="color: #7c3aed; font-size: 8px;">(Manuel)</span>
                    @endif
                </td>
                <td>
                    @if($employee->employee_type == 'enseignant_titulaire')
                        Permanent
                    @elseif($employee->employee_type == 'semi_permanent')
                        Semi-perm.
                    @else
                        {{ ucfirst($employee->employee_type) }}
                    @endif
                </td>
                <td class="text-right">{{ number_format($employee->monthly_salary, 0, ',', ' ') }}</td>
                <td class="text-center">{{ number_format($employee->days_worked, 1) }}</td>
                <td class="text-center {{ $employee->days_not_worked > 0 ? 'text-red font-bold' : '' }}">
                    {{ number_format($employee->days_not_worked, 1) }}
                </td>
                <td class="text-center {{ $employee->total_late_minutes > 0 ? 'text-orange font-bold' : '' }}">
                    {{ $employee->total_late_minutes }}
                </td>
                <td class="text-right text-red">{{ number_format($employee->late_penalty_amount, 0, ',', ' ') }}</td>
                <td class="text-right text-red">{{ number_format($employee->absence_deduction, 0, ',', ' ') }}</td>
                <td class="text-right text-red">{{ number_format($employee->manual_deductions, 0, ',', ' ') }}</td>
                <td class="text-right" style="color: #7c3aed;">{{ number_format($employee->loan_deductions, 0, ',', ' ') }}</td>
                <td class="text-right text-green font-bold">{{ number_format($employee->net_salary, 0, ',', ' ') }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background: #1e40af; color: white; font-weight: bold;">
                <td colspan="3" class="text-right" style="padding: 8px 5px;">TOTAUX</td>
                <td class="text-right" style="padding: 8px 5px;">{{ number_format($totalGrossSalary, 0, ',', ' ') }}</td>
                <td></td>
                <td></td>
                <td></td>
                <td class="text-right" style="padding: 8px 5px;">{{ number_format($employees->sum('late_penalty_amount'), 0, ',', ' ') }}</td>
                <td class="text-right" style="padding: 8px 5px;">{{ number_format($employees->sum('absence_deduction'), 0, ',', ' ') }}</td>
                <td class="text-right" style="padding: 8px 5px;">{{ number_format($employees->sum('manual_deductions'), 0, ',', ' ') }}</td>
                <td class="text-right" style="padding: 8px 5px;">{{ number_format($employees->sum('loan_deductions'), 0, ',', ' ') }}</td>
                <td class="text-right" style="padding: 8px 5px;">{{ number_format($totalNetSalary, 0, ',', ' ') }}</td>
            </tr>
        </tfoot>
    </table>

    <!-- Légende et explication du tableau -->
    <div style="margin-top: 15px; padding: 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px;">
        <h4 style="font-size: 10px; font-weight: bold; color: #1e40af; margin-bottom: 8px;">Légende et explication des colonnes</h4>
        <table style="width: 100%; border: none; margin: 0;">
            <tbody>
                <tr style="background: none;">
                    <td style="border: none; padding: 2px 5px; font-size: 8px; width: 50%; vertical-align: top;">
                        <strong>Salaire Mensuel :</strong> Salaire brut de base défini dans le contrat de l'employé.
                    </td>
                    <td style="border: none; padding: 2px 5px; font-size: 8px; width: 50%; vertical-align: top;">
                        <strong>Jours Trav. :</strong> Nombre de jours effectivement travaillés (pointage entrée + sortie validé).
                    </td>
                </tr>
                <tr style="background: none;">
                    <td style="border: none; padding: 2px 5px; font-size: 8px; vertical-align: top;">
                        <strong>Jours Abs. :</strong> Jours ouvrables non travaillés ({{ number_format($workingDays, 1) }} jours ouvrables - jours travaillés). <span style="color: #dc2626;">En rouge si > 0.</span>
                    </td>
                    <td style="border: none; padding: 2px 5px; font-size: 8px; vertical-align: top;">
                        <strong>Retards (min) :</strong> Total des minutes de retard cumulées sur le mois. <span style="color: #ea580c;">En orange si > 0.</span>
                    </td>
                </tr>
                <tr style="background: none;">
                    <td style="border: none; padding: 2px 5px; font-size: 8px; vertical-align: top;">
                        <strong>Pénalités Retard :</strong> Montant déduit pour les retards. Calcul : (minutes de retard / 60) &times; taux horaire.
                    </td>
                    <td style="border: none; padding: 2px 5px; font-size: 8px; vertical-align: top;">
                        <strong>Déd. Absences :</strong> Déduction pour jours non travaillés. Calcul : jours absents &times; (salaire mensuel / {{ number_format($workingDays, 1) }} jours).
                    </td>
                </tr>
                <tr style="background: none;">
                    <td style="border: none; padding: 2px 5px; font-size: 8px; vertical-align: top;">
                        <strong>Déd. Manuelles :</strong> Déductions exceptionnelles appliquées manuellement par l'administration (sanctions, ajustements, etc.).
                    </td>
                    <td style="border: none; padding: 2px 5px; font-size: 8px; vertical-align: top;">
                        <strong>Prêts :</strong> Mensualité de remboursement des prêts et avances sur salaire en cours.
                    </td>
                </tr>
                <tr style="background: none;">
                    <td colspan="2" style="border: none; padding: 2px 5px; font-size: 8px; vertical-align: top;">
                        <strong>Salaire Final :</strong> <span style="color: #059669;">Montant net à verser.</span> Calcul : Salaire Mensuel - Pénalités Retard - Déduction Absences - Déductions Manuelles - Prêts.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div style="margin-top: 10px; padding: 8px; background: #eff6ff; border-left: 3px solid #2563eb; border-radius: 2px;">
        <p style="font-size: 8px; color: #1e40af; margin: 0;">
            <strong>Mode de calcul des jours ouvrables :</strong> Du lundi au samedi, le samedi comptant comme une demi-journée (0.5 jour).
            Total pour {{ \Carbon\Carbon::create($year, $month)->locale('fr')->isoFormat('MMMM YYYY') }} : <strong>{{ number_format($workingDays, 1) }} jours</strong>.
        </p>
        <p style="font-size: 8px; color: #1e40af; margin: 2px 0 0 0;">
            <strong>Horaires de travail :</strong> 08h00 - 17h00 (8 heures effectives avec 1 heure de pause déjeuner de 12h00 à 13h00).
        </p>
    </div>

    <p style="font-size: 7px; color: #9ca3af; margin-top: 8px;">
        * Tous les montants sont en FCFA. Document généré automatiquement par le système de gestion RH - IUEs/INSAM.
    </p>
@endsection
