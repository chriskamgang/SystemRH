<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #2563eb;
        }
        .header h1 {
            font-size: 18px;
            color: #1e40af;
            margin-bottom: 5px;
        }
        .header p {
            font-size: 10px;
            color: #666;
        }
        .meta-info {
            background: #f3f4f6;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .meta-info p {
            margin: 3px 0;
            font-size: 9px;
        }
        .stats-grid {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        .stat-box {
            display: table-cell;
            width: 50%;
            padding: 10px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            text-align: center;
        }
        .stat-box:first-child {
            border-right: none;
        }
        .stat-label {
            font-size: 8px;
            color: #6b7280;
            margin-bottom: 3px;
        }
        .stat-value {
            font-size: 16px;
            font-weight: bold;
            color: #1e40af;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        thead {
            background: #1e40af;
            color: white;
        }
        th {
            padding: 8px 5px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
        }
        td {
            padding: 6px 5px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 9px;
        }
        tbody tr:nth-child(even) {
            background: #f9fafb;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #9ca3af;
            padding: 10px;
            border-top: 1px solid #e5e7eb;
        }
        .page-number:before {
            content: "Page " counter(page);
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .font-bold {
            font-weight: bold;
        }
        .text-green {
            color: #059669;
        }
        .text-red {
            color: #dc2626;
        }
        .text-orange {
            color: #ea580c;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>@yield('title')</h1>
        <p>@yield('subtitle')</p>
    </div>

    <div class="meta-info">
        <p><strong>Date d'édition :</strong> {{ date('d/m/Y à H:i') }}</p>
        @yield('meta-info')
    </div>

    @yield('content')

    <div class="footer">
        <p>Document généré automatiquement - Système de Gestion de Paie</p>
        <p class="page-number"></p>
    </div>
</body>
</html>
