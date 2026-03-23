<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fiche de Paie - {{ $user->full_name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; color: #333; }

        .page { padding: 20px 30px; }

        /* Header */
        .header { display: table; width: 100%; margin-bottom: 15px; border-bottom: 3px solid #1e40af; padding-bottom: 12px; }
        .header-left { display: table-cell; width: 60%; vertical-align: middle; }
        .header-right { display: table-cell; width: 40%; text-align: right; vertical-align: middle; }
        .company-name { font-size: 20px; font-weight: bold; color: #1e40af; }
        .company-sub { font-size: 9px; color: #666; margin-top: 3px; }
        .doc-title { font-size: 14px; font-weight: bold; color: #1e40af; }
        .doc-period { font-size: 11px; color: #555; margin-top: 3px; }

        /* Employee info */
        .employee-info { background: #f0f4ff; border: 1px solid #c7d6ff; border-radius: 4px; padding: 12px; margin-bottom: 15px; }
        .info-grid { display: table; width: 100%; }
        .info-col { display: table-cell; width: 50%; vertical-align: top; }
        .info-label { font-size: 8px; color: #666; text-transform: uppercase; letter-spacing: 0.5px; }
        .info-value { font-size: 10px; font-weight: bold; color: #333; margin-bottom: 6px; }

        /* Tables */
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .section-title { background: #1e40af; color: white; padding: 6px 10px; font-size: 11px; font-weight: bold; }
        th { background: #e8edf5; padding: 5px 8px; text-align: left; font-size: 9px; font-weight: bold; color: #444; border-bottom: 1px solid #ccc; }
        td { padding: 5px 8px; border-bottom: 1px solid #eee; font-size: 9px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }

        /* Totals */
        .total-row { background: #f0f4ff; font-weight: bold; }
        .total-row td { border-top: 2px solid #1e40af; padding: 8px; }
        .grand-total { background: #1e40af; color: white; }
        .grand-total td { padding: 10px 8px; font-size: 12px; border: none; }

        /* Colors */
        .text-green { color: #059669; }
        .text-red { color: #dc2626; }
        .text-orange { color: #ea580c; }
        .text-blue { color: #1e40af; }

        /* Deduction detail */
        .deduction-label { padding-left: 20px; color: #666; }

        /* Footer */
        .footer { margin-top: 20px; padding-top: 10px; border-top: 1px solid #ddd; }
        .footer p { font-size: 8px; color: #999; text-align: center; }
        .signature-area { display: table; width: 100%; margin-top: 25px; }
        .signature-box { display: table-cell; width: 50%; text-align: center; padding: 10px; }
        .signature-line { border-top: 1px solid #333; width: 150px; margin: 30px auto 5px; }
        .signature-label { font-size: 9px; color: #555; }
    </style>
</head>
<body>
<div class="page">
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <div class="company-name">IUEs/INSAM</div>
            <div class="company-sub">Institut Universitaire de l'Estuaire / Institut Supérieur des Arts et Métiers</div>
        </div>
        <div class="header-right">
            <div class="doc-title">FICHE DE PAIE</div>
            <div class="doc-period">{{ $periodName }}</div>
        </div>
    </div>

    <!-- Employee Info -->
    <div class="employee-info">
        <div class="info-grid">
            <div class="info-col">
                <div class="info-label">Nom complet</div>
                <div class="info-value">{{ $user->full_name }}</div>

                <div class="info-label">Email</div>
                <div class="info-value">{{ $user->email }}</div>

                @if($user->employee_id)
                <div class="info-label">Matricule</div>
                <div class="info-value">{{ $user->employee_id }}</div>
                @endif
            </div>
            <div class="info-col">
                <div class="info-label">Type d'employé</div>
                <div class="info-value">{{ $employeeTypeLabel }}</div>

                <div class="info-label">Période</div>
                <div class="info-value">{{ $periodName }}</div>

                <div class="info-label">Date d'édition</div>
                <div class="info-value">{{ date('d/m/Y') }}</div>
            </div>
        </div>
    </div>

    @if($isVacataire)
        {{-- ===== FICHE VACATAIRE ===== --}}
        <table>
            <tr><td colspan="4" class="section-title">Rémunération par UE</td></tr>
            <tr>
                <th>UE / Matière</th>
                <th class="text-center">Niveau</th>
                <th class="text-right">Heures × Taux</th>
                <th class="text-right">Montant</th>
            </tr>
            @if(!empty($payroll['ue_breakdown']))
                @foreach($payroll['ue_breakdown'] as $ue)
                <tr>
                    <td>{{ $ue['code_ue'] ? $ue['code_ue'] . ' - ' : '' }}{{ $ue['nom_matiere'] }}</td>
                    <td class="text-center">{{ $ue['niveau'] ?? 'BTS' }}</td>
                    <td class="text-right">{{ number_format($ue['heures'], 1) }}h × {{ number_format($ue['taux_horaire'], 0, ',', ' ') }}</td>
                    <td class="text-right">{{ number_format($ue['montant'], 0, ',', ' ') }} FCFA</td>
                </tr>
                @endforeach
            @else
                <tr>
                    <td>Heures travaillées</td>
                    <td class="text-center">-</td>
                    <td class="text-right">{{ number_format($payroll['hours_worked'], 1) }}h × {{ number_format($payroll['hourly_rate'], 0, ',', ' ') }}</td>
                    <td class="text-right">{{ number_format($payroll['gross_amount'], 0, ',', ' ') }} FCFA</td>
                </tr>
            @endif
            <tr class="total-row">
                <td colspan="2">Montant brut total</td>
                <td class="text-right">{{ number_format($payroll['hours_worked'], 1) }}h</td>
                <td class="text-right">{{ number_format($payroll['gross_amount'], 0, ',', ' ') }} FCFA</td>
            </tr>
        </table>

        <table>
            <tr><td colspan="3" class="section-title">Déductions</td></tr>
            <tr>
                <th>Désignation</th>
                <th class="text-right">Détail</th>
                <th class="text-right">Montant</th>
            </tr>
            @if($manualDeductionsTotal > 0)
            <tr>
                <td>Déductions manuelles</td>
                <td class="text-right">-</td>
                <td class="text-right text-red">-{{ number_format($manualDeductionsTotal, 0, ',', ' ') }} FCFA</td>
            </tr>
            @endif
            <tr class="total-row">
                <td>Total déductions</td>
                <td></td>
                <td class="text-right text-red">-{{ number_format($manualDeductionsTotal, 0, ',', ' ') }} FCFA</td>
            </tr>
        </table>

    @else
        {{-- ===== FICHE PERMANENT / SEMI-PERMANENT ===== --}}
        <table>
            <tr><td colspan="3" class="section-title">Rémunération</td></tr>
            <tr>
                <th>Désignation</th>
                <th class="text-right">Détail</th>
                <th class="text-right">Montant</th>
            </tr>
            <tr>
                <td>Salaire mensuel de base</td>
                <td class="text-right">-</td>
                <td class="text-right">{{ number_format($payroll['monthly_salary'], 0, ',', ' ') }} FCFA</td>
            </tr>
            <tr>
                <td>Jours ouvrables du mois</td>
                <td class="text-right">{{ $payroll['working_days'] }} jours</td>
                <td class="text-right">-</td>
            </tr>
            <tr>
                <td>Heures travaillées</td>
                <td class="text-right">{{ number_format($payroll['total_hours_worked'] ?? 0, 1) }}h</td>
                <td class="text-right">-</td>
            </tr>
            <tr>
                <td>Jours travaillés (proportionnel)</td>
                <td class="text-right">{{ number_format($payroll['days_worked'], 2) }} jours</td>
                <td class="text-right">-</td>
            </tr>
            <tr class="total-row">
                <td>Salaire brut (proportionnel aux heures)</td>
                <td></td>
                <td class="text-right">{{ number_format($payroll['salary_based_on_days_worked'] ?? $payroll['net_salary'], 0, ',', ' ') }} FCFA</td>
            </tr>
        </table>

        <table>
            <tr><td colspan="3" class="section-title">Présence &amp; Retards</td></tr>
            <tr>
                <th>Désignation</th>
                <th class="text-right">Détail</th>
                <th class="text-right">Montant</th>
            </tr>
            <tr>
                <td>Retards cumulés</td>
                <td class="text-right">{{ $payroll['total_late_minutes'] }} min</td>
                <td class="text-right text-orange">-{{ number_format($payroll['late_penalty_amount'] ?? 0, 0, ',', ' ') }} FCFA</td>
            </tr>
            @if(($payroll['days_justified'] ?? 0) > 0)
            <tr>
                <td>Jours justifiés</td>
                <td class="text-right">{{ $payroll['days_justified'] }} jours</td>
                <td class="text-right">-</td>
            </tr>
            @endif
        </table>

        <table>
            <tr><td colspan="3" class="section-title">Déductions</td></tr>
            <tr>
                <th>Désignation</th>
                <th class="text-right">Détail</th>
                <th class="text-right">Montant</th>
            </tr>
            <tr>
                <td>Pénalité retards</td>
                <td class="text-right">{{ $payroll['total_late_minutes'] }} min</td>
                <td class="text-right text-red">-{{ number_format($payroll['late_penalty_amount'] ?? 0, 0, ',', ' ') }} FCFA</td>
            </tr>
            @if(($payroll['manual_deductions'] ?? 0) > 0)
            <tr>
                <td>Déductions manuelles</td>
                <td class="text-right">-</td>
                <td class="text-right text-red">-{{ number_format($payroll['manual_deductions'], 0, ',', ' ') }} FCFA</td>
            </tr>
            @endif
            @if(($payroll['loan_deductions'] ?? 0) > 0)
            <tr>
                <td>Remboursement prêt</td>
                <td class="text-right">-</td>
                <td class="text-right text-red">-{{ number_format($payroll['loan_deductions'], 0, ',', ' ') }} FCFA</td>
            </tr>
            @endif
            <tr class="total-row">
                <td>Total déductions</td>
                <td></td>
                <td class="text-right text-red">-{{ number_format($payroll['total_deductions'] ?? 0, 0, ',', ' ') }} FCFA</td>
            </tr>
        </table>
    @endif

    <!-- NET À PAYER -->
    <table>
        <tr class="grand-total">
            <td>NET À PAYER</td>
            <td class="text-right" style="font-size: 14px;">{{ number_format($netSalary, 0, ',', ' ') }} FCFA</td>
        </tr>
    </table>

    <!-- Signatures -->
    <div class="signature-area">
        <div class="signature-box">
            <div class="signature-label">L'Employé</div>
            <div class="signature-line"></div>
            <div class="signature-label">{{ $user->full_name }}</div>
        </div>
        <div class="signature-box">
            <div class="signature-label">La Direction</div>
            <div class="signature-line"></div>
            <div class="signature-label">IUEs/INSAM</div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>Document généré automatiquement le {{ date('d/m/Y à H:i') }} - IUEs/INSAM - Système de Gestion RH</p>
        <p>Ce document est confidentiel et destiné uniquement à l'employé concerné.</p>
    </div>
</div>
</body>
</html>
