<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 8mm; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 0;
            padding: 0;
        }
        .badge-wrapper {
            display: inline-block;
            width: 88mm;
            margin: 3mm;
            vertical-align: top;
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
    @foreach($badges as $badge)
    <div class="badge-wrapper">
        <table style="width: 88mm; border: 2px solid #1e3a5f; border-collapse: collapse;">
            {{-- En-tete entreprise --}}
            <tr>
                <td colspan="2" style="background-color: #1e3a5f; color: white; padding: 6px 10px; text-align: center;">
                    @if($badge['logoBase64'])
                        <img src="{{ $badge['logoBase64'] }}" style="height: 16px; margin-bottom: 2px;"><br>
                    @endif
                    <span style="font-size: 10px; font-weight: bold; letter-spacing: 2px; text-transform: uppercase;">{{ $badge['companyName'] }}</span>
                </td>
            </tr>

            {{-- Bande titre --}}
            <tr>
                <td colspan="2" style="background-color: #2563eb; color: white; text-align: center; padding: 2px 0;">
                    <span style="font-size: 7px; font-weight: bold; letter-spacing: 3px; text-transform: uppercase;">BADGE DE POINTAGE</span>
                </td>
            </tr>

            {{-- Corps --}}
            <tr>
                <td style="padding: 8px 10px; vertical-align: top; width: 55%;">
                    <span style="font-size: 12px; font-weight: bold; color: #1e3a5f;">{{ strtoupper($badge['user']->last_name) }}</span><br>
                    <span style="font-size: 10px; color: #333;">{{ $badge['user']->first_name }}</span>
                    <br><br>
                    <table style="border-collapse: collapse;">
                        <tr><td style="font-size: 6px; color: #888; padding: 1px 0;">MATRICULE</td></tr>
                        <tr><td style="font-size: 9px; font-weight: bold; color: #333; padding: 0 0 3px 0;">{{ $badge['user']->employee_id }}</td></tr>
                        @if($badge['user']->department)
                        <tr><td style="font-size: 6px; color: #888; padding: 1px 0;">DEPARTEMENT</td></tr>
                        <tr><td style="font-size: 8px; color: #333; padding: 0 0 3px 0;">{{ $badge['user']->department->name }}</td></tr>
                        @endif
                        <tr><td style="font-size: 6px; color: #888; padding: 1px 0;">FONCTION</td></tr>
                        <tr><td style="font-size: 8px; color: #333;">{{ ucfirst(str_replace('_', ' ', $badge['user']->employee_type)) }}</td></tr>
                    </table>
                </td>

                {{-- QR Code --}}
                <td style="padding: 8px 10px; vertical-align: middle; text-align: center; width: 45%;">
                    <table style="border: 2px solid #e5e7eb; border-collapse: collapse; margin: 0 auto;">
                        <tr>
                            <td style="padding: 5px;">
                                <img src="data:image/png;base64,{{ $badge['qrBase64'] }}" width="80" height="80">
                            </td>
                        </tr>
                    </table>
                    <span style="font-size: 5px; color: #999; display: block; margin-top: 2px;">Scanner pour pointer</span>
                </td>
            </tr>

            {{-- Pied --}}
            <tr>
                <td colspan="2" style="background-color: #1e3a5f; color: #ccc; text-align: center; padding: 3px 8px;">
                    <span style="font-size: 5px;">Badge personnel - Ne pas partager | En cas de perte, contactez l'administration</span>
                </td>
            </tr>
        </table>
    </div>
    @endforeach
</body>
</html>
