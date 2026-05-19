<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Quitus de Paiement - {{ $employee->full_name }}</title>
    <style>
        @page { margin: 30px 40px; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            line-height: 1.5;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #1e40af;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 18px;
            color: #1e40af;
            margin-bottom: 3px;
        }
        .header p {
            color: #666;
            font-size: 10px;
        }
        .title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            color: #1e40af;
            margin: 25px 0;
            text-transform: uppercase;
            text-decoration: underline;
            letter-spacing: 2px;
        }
        .ref {
            text-align: right;
            font-size: 9px;
            color: #888;
            margin-bottom: 15px;
        }
        .content {
            margin: 0 20px;
            text-align: justify;
            line-height: 1.8;
        }
        .content p {
            margin-bottom: 12px;
        }
        .content strong {
            color: #1e40af;
        }
        .info-box {
            background: #f0f4ff;
            border: 1px solid #bfdbfe;
            border-radius: 4px;
            padding: 12px 15px;
            margin: 15px 20px;
        }
        .info-box table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-box td {
            padding: 4px 8px;
            font-size: 11px;
        }
        .info-box td.label {
            font-weight: bold;
            color: #555;
            width: 40%;
        }
        .info-box td.value {
            color: #1e40af;
            font-weight: bold;
        }
        .amount-box {
            background: #ecfdf5;
            border: 2px solid #059669;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 20px;
            text-align: center;
        }
        .amount-label {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .amount-value {
            font-size: 22px;
            font-weight: bold;
            color: #059669;
        }
        .amount-words {
            font-size: 10px;
            color: #555;
            font-style: italic;
            margin-top: 5px;
        }
        .warning {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 4px;
            padding: 10px 15px;
            margin: 20px 20px;
            font-size: 9px;
            color: #92400e;
        }
        .warning strong {
            color: #92400e;
        }
        .signatures {
            margin-top: 40px;
            display: table;
            width: 100%;
        }
        .sig-box {
            display: table-cell;
            width: 50%;
            text-align: center;
            padding: 0 20px;
        }
        .sig-box p.sig-title {
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 50px;
        }
        .sig-box p.sig-line {
            border-top: 1px solid #333;
            display: inline-block;
            padding-top: 5px;
            font-size: 9px;
            color: #666;
        }
        .footer {
            margin-top: 30px;
            border-top: 1px solid #ddd;
            padding-top: 8px;
            text-align: center;
            font-size: 8px;
            color: #999;
        }
        .watermark {
            position: fixed;
            top: 35%;
            left: 15%;
            font-size: 60px;
            color: rgba(30, 64, 175, 0.06);
            transform: rotate(-30deg);
            font-weight: bold;
            letter-spacing: 10px;
            z-index: -1;
        }
    </style>
</head>
<body>
    <div class="watermark">QUITUS</div>

    <div class="header">
        <h1>IUEs/INSAM</h1>
        <p>Institut Universitaire des Etudes Superieures</p>
        <p>Douala, Cameroun</p>
    </div>

    <div class="ref">
        Ref : QUITUS/{{ $year }}/{{ str_pad($month, 2, '0', STR_PAD_LEFT) }}/{{ $employee->employee_id ?? $employee->id }}
        <br>Date d'emission : {{ date('d/m/Y H:i') }}
    </div>

    <div class="title">Quitus de Paiement</div>

    <div class="content">
        <p>
            Je soussigne, M. FOYET Stephane Lionel, Directeur General de l'Institut Universitaire
            des Etudes Superieures (IUEs/INSAM), autorise par la presente le paiement du salaire
            ci-dessous a l'employe(e) designe(e).
        </p>

        <p>
            La banque <strong>{{ $bankName }}</strong> est priee de proceder au paiement du montant indique
            ci-dessous, sur presentation de ce quitus accompagne d'une piece d'identite valide.
        </p>
    </div>

    <div class="info-box">
        <table>
            <tr>
                <td class="label">Nom & Prenom :</td>
                <td class="value">{{ $employee->full_name }}</td>
            </tr>
            <tr>
                <td class="label">Matricule :</td>
                <td class="value">{{ $employee->employee_id ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">N° Compte :</td>
                <td class="value">{{ $employee->numero_compte ?: 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Banque :</td>
                <td class="value">{{ $bankName }}</td>
            </tr>
            <tr>
                <td class="label">Periode :</td>
                <td class="value">{{ \Carbon\Carbon::create($year, $month)->locale('fr')->isoFormat('MMMM YYYY') }}</td>
            </tr>
            <tr>
                <td class="label">Jours travailles :</td>
                <td class="value">{{ number_format($payroll['days_worked'], 1) }} / {{ number_format($payroll['working_days'], 1) }} jours ouvrables</td>
            </tr>
            <tr>
                <td class="label">Salaire Brut :</td>
                <td class="value">{{ number_format($payroll['gross_salary'], 0, ',', ' ') }} FCFA</td>
            </tr>
            <tr>
                <td class="label">Deductions :</td>
                <td class="value" style="color: #dc2626;">{{ number_format($payroll['total_deductions'], 0, ',', ' ') }} FCFA</td>
            </tr>
        </table>
    </div>

    <div class="amount-box">
        <div class="amount-label">Montant Net a Payer</div>
        <div class="amount-value">{{ number_format($payroll['net_salary'], 0, ',', ' ') }} FCFA</div>
    </div>

    <div class="warning">
        <strong>IMPORTANT :</strong> Ce quitus est valable uniquement pour la periode indiquee.
        L'employe(e) doit presenter une piece d'identite valide (CNI, passeport) pour retirer le paiement.
        Ce document est strictement personnel et non transferable.
        Tout quitus non presente dans les 30 jours suivant son emission sera considere comme caduque.
    </div>

    <div class="signatures">
        <div class="sig-box">
            <p class="sig-title">Prepare par :</p>
            <p class="sig-line">Signature & Cachet</p>
        </div>
        <div class="sig-box">
            <p class="sig-title">Le Directeur General :</p>
            <p class="sig-line">Signature & Cachet</p>
        </div>
    </div>

    <div class="footer">
        Document genere le {{ date('d/m/Y a H:i') }} | IUEs/INSAM - Systeme RH
        | Ref: QUITUS/{{ $year }}/{{ str_pad($month, 2, '0', STR_PAD_LEFT) }}/{{ $employee->employee_id ?? $employee->id }}
    </div>
</body>
</html>
