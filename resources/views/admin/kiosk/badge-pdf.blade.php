<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 0;
            padding: 0;
        }
    </style>
</head>
<body>
    <table style="width: 88mm; border: 2px solid #1e3a5f; border-collapse: collapse; border-radius: 4px;">
        {{-- En-tete avec nom entreprise --}}
        <tr>
            <td colspan="2" style="background-color: #1e3a5f; color: white; padding: 8px 12px; text-align: center;">
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" style="height: 18px; margin-bottom: 2px;"><br>
                @endif
                <span style="font-size: 11px; font-weight: bold; letter-spacing: 2px; text-transform: uppercase;">{{ $companyName }}</span>
            </td>
        </tr>

        {{-- Bande titre --}}
        <tr>
            <td colspan="2" style="background-color: #2563eb; color: white; text-align: center; padding: 3px 0;">
                <span style="font-size: 8px; font-weight: bold; letter-spacing: 3px; text-transform: uppercase;">BADGE DE POINTAGE</span>
            </td>
        </tr>

        {{-- Corps : infos + QR --}}
        <tr>
            <td style="padding: 10px 12px; vertical-align: top; width: 55%;">
                {{-- Nom complet --}}
                <span style="font-size: 14px; font-weight: bold; color: #1e3a5f; line-height: 1.3;">
                    {{ strtoupper($user->last_name) }}
                </span><br>
                <span style="font-size: 12px; color: #333; line-height: 1.3;">
                    {{ $user->first_name }}
                </span>
                <br><br>

                {{-- Matricule --}}
                <table style="border-collapse: collapse;">
                    <tr>
                        <td style="font-size: 7px; color: #888; padding: 1px 0;">MATRICULE</td>
                    </tr>
                    <tr>
                        <td style="font-size: 10px; font-weight: bold; color: #333; padding: 0 0 4px 0;">{{ $user->employee_id }}</td>
                    </tr>

                    {{-- Departement --}}
                    @if($user->department)
                    <tr>
                        <td style="font-size: 7px; color: #888; padding: 1px 0;">DEPARTEMENT</td>
                    </tr>
                    <tr>
                        <td style="font-size: 9px; color: #333; padding: 0 0 4px 0;">{{ $user->department->name }}</td>
                    </tr>
                    @endif

                    {{-- Type --}}
                    <tr>
                        <td style="font-size: 7px; color: #888; padding: 1px 0;">FONCTION</td>
                    </tr>
                    <tr>
                        <td style="font-size: 9px; color: #333;">{{ ucfirst(str_replace('_', ' ', $user->employee_type)) }}</td>
                    </tr>
                </table>
            </td>

            {{-- QR Code --}}
            <td style="padding: 10px 12px; vertical-align: middle; text-align: center; width: 45%;">
                <table style="border: 2px solid #e5e7eb; border-collapse: collapse; margin: 0 auto;">
                    <tr>
                        <td style="padding: 6px;">
                            <img src="data:image/png;base64,{{ $qrBase64 }}" width="95" height="95">
                        </td>
                    </tr>
                </table>
                <span style="font-size: 6px; color: #999; display: block; margin-top: 3px;">Scanner pour pointer</span>
            </td>
        </tr>

        {{-- Pied de page --}}
        <tr>
            <td colspan="2" style="background-color: #1e3a5f; color: #ccc; text-align: center; padding: 4px 8px;">
                <span style="font-size: 6px;">Badge personnel - Ne pas partager | En cas de perte, contactez l'administration</span>
            </td>
        </tr>
    </table>
</body>
</html>
