<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Identifiants du Personnel</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 3px solid #2563eb;
        }
        .header h1 {
            font-size: 20px;
            color: #1e40af;
            margin: 0 0 5px 0;
        }
        .header p {
            color: #666;
            font-size: 10px;
            margin: 0;
        }
        .warning {
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 5px;
            padding: 8px 12px;
            margin-bottom: 20px;
            font-size: 10px;
            color: #92400e;
        }
        .section-title {
            background-color: #2563eb;
            color: white;
            padding: 8px 12px;
            font-size: 13px;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 0;
            border-radius: 5px 5px 0 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th {
            background-color: #f1f5f9;
            padding: 6px 10px;
            text-align: left;
            font-size: 10px;
            color: #475569;
            border: 1px solid #e2e8f0;
            text-transform: uppercase;
        }
        td {
            padding: 5px 10px;
            border: 1px solid #e2e8f0;
            font-size: 10px;
        }
        tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .email {
            font-family: 'DejaVu Sans Mono', monospace;
            color: #2563eb;
        }
        .password {
            font-family: 'DejaVu Sans Mono', monospace;
            background-color: #fef3c7;
            padding: 2px 6px;
            border-radius: 3px;
            color: #92400e;
        }
        .count-badge {
            background-color: #dbeafe;
            color: #1e40af;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: bold;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            font-size: 9px;
            color: #94a3b8;
        }
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>IDENTIFIANTS DE CONNEXION DU PERSONNEL</h1>
        <p>IUEs/INSAM - Application de Pointage | Document confidentiel | {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <div class="warning">
        <strong>CONFIDENTIEL</strong> - Ce document contient des identifiants de connexion. Ne pas diffuser.
        Le mot de passe par defaut est <strong>password123</strong> pour tous les comptes.
    </div>

    @php $totalEmployees = 0; @endphp

    @foreach($grouped as $type => $employees)
        @if(!$loop->first && $employees->count() > 30)
            <div class="page-break"></div>
        @endif

        <div class="section-title">
            {{ $typeLabels[$type] ?? ucfirst($type) }}
            <span style="float: right; font-size: 11px;">{{ $employees->count() }} personne(s)</span>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 30%;">Nom Complet</th>
                    <th style="width: 35%;">Email</th>
                    <th style="width: 15%;">Mot de passe</th>
                    <th style="width: 15%;">Telephone</th>
                </tr>
            </thead>
            <tbody>
                @foreach($employees as $index => $employee)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td><strong>{{ $employee->last_name }} {{ $employee->first_name }}</strong></td>
                    <td class="email">{{ $employee->email }}</td>
                    <td><span class="password">password123</span></td>
                    <td>{{ $employee->phone ?? '-' }}</td>
                </tr>
                @php $totalEmployees++; @endphp
                @endforeach
            </tbody>
        </table>
    @endforeach

    <div class="footer">
        Total : {{ $totalEmployees }} employe(s) | Genere le {{ now()->format('d/m/Y') }} a {{ now()->format('H:i') }}
        | IUEs/INSAM - Systeme de Pointage
    </div>
</body>
</html>
