<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 5mm; size: A4 portrait; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 0;
            padding: 0;
        }
        .page-table {
            width: 100%;
            border-collapse: collapse;
        }
        .page-table td {
            width: 50%;
            padding: 2mm;
            vertical-align: top;
        }
        .badge {
            width: 100%;
            border: 2px solid #1e3a5f;
            border-collapse: collapse;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    @foreach($badges->chunk($perPage) as $pageIndex => $pageBadges)
    <table class="page-table" @if(!$loop->last) style="page-break-after: always;" @endif>
        @foreach($pageBadges->chunk(2) as $row)
        <tr>
            @foreach($row as $badge)
            <td>
                <table class="badge">
                    {{-- En-tete entreprise --}}
                    <tr>
                        <td colspan="2" style="background-color: #1e3a5f; color: white; padding: 3px 6px; text-align: center;">
                            @if($badge['logoBase64'])
                                <img src="{{ $badge['logoBase64'] }}" style="height: 12px; margin-bottom: 1px;"><br>
                            @endif
                            <span style="font-size: 7px; font-weight: bold; letter-spacing: 2px; text-transform: uppercase;">{{ $badge['companyName'] }}</span>
                        </td>
                    </tr>

                    {{-- Bande titre --}}
                    <tr>
                        <td colspan="2" style="background-color: #2563eb; color: white; text-align: center; padding: 2px 0;">
                            <span style="font-size: 6px; font-weight: bold; letter-spacing: 2px; text-transform: uppercase;">BADGE DE POINTAGE</span>
                        </td>
                    </tr>

                    {{-- Corps --}}
                    <tr>
                        <td style="padding: 4px 6px; vertical-align: top; width: 55%;">
                            <span style="font-size: 9px; font-weight: bold; color: #1e3a5f;">{{ strtoupper($badge['user']->last_name) }}</span><br>
                            <span style="font-size: 8px; color: #333;">{{ $badge['user']->first_name }}</span>
                            <br>
                            <table style="border-collapse: collapse;">
                                <tr><td style="font-size: 5px; color: #888; padding: 1px 0;">MATRICULE</td></tr>
                                <tr><td style="font-size: 7px; font-weight: bold; color: #333; padding: 0 0 2px 0;">{{ $badge['user']->employee_id }}</td></tr>
                                @if($badge['user']->department)
                                <tr><td style="font-size: 5px; color: #888; padding: 1px 0;">DEPARTEMENT</td></tr>
                                <tr><td style="font-size: 7px; color: #333; padding: 0 0 2px 0;">{{ $badge['user']->department->name }}</td></tr>
                                @endif
                                <tr><td style="font-size: 5px; color: #888; padding: 1px 0;">FONCTION</td></tr>
                                <tr><td style="font-size: 7px; color: #333;">{{ ucfirst(str_replace('_', ' ', $badge['user']->employee_type)) }}</td></tr>
                            </table>
                        </td>

                        {{-- QR Code --}}
                        <td style="padding: 4px 6px; vertical-align: middle; text-align: center; width: 45%;">
                            <table style="border: 1px solid #e5e7eb; border-collapse: collapse; margin: 0 auto;">
                                <tr>
                                    <td style="padding: 3px;">
                                        <img src="data:image/png;base64,{{ $badge['qrBase64'] }}" width="55" height="55">
                                    </td>
                                </tr>
                            </table>
                            <span style="font-size: 5px; color: #999; display: block; margin-top: 2px;">Scanner pour pointer</span>
                        </td>
                    </tr>

                    {{-- Pied --}}
                    <tr>
                        <td colspan="2" style="background-color: #1e3a5f; color: #ccc; text-align: center; padding: 2px 6px;">
                            <span style="font-size: 4px;">Badge personnel - Ne pas partager | En cas de perte, contactez l'administration</span>
                        </td>
                    </tr>
                </table>
            </td>
            @endforeach
            {{-- Cellule vide si nombre impair --}}
            @if($row->count() < 2)
            <td></td>
            @endif
        </tr>
        @endforeach
    </table>
    @endforeach
</body>
</html>
