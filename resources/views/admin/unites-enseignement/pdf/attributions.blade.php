@extends('admin.rapports.pdf.layout')

@section('title', 'Fiche de Controle des Attributions de Cours')
@section('subtitle', 'Document de verification - Edite le ' . date('d/m/Y'))

@section('meta-info')
    <p><strong>Total UE attribuees :</strong> {{ $totalUes }} ({{ $totalActivees }} activees)</p>
    <p><strong>Enseignants :</strong> {{ $totalEnseignants }}</p>
    <p><strong>Volume horaire total :</strong> {{ number_format($totalVolume, 0, ',', ' ') }}h</p>
    @if($filters)<p><strong>Filtres :</strong> {{ $filters }}</p>@endif
@endsection

@section('content')
    {{-- ===== SECTION 1 : RECAPITULATIF PAR SPECIALITE / NIVEAU ===== --}}
    <h2 style="font-size: 12px; color: #1e40af; margin: 15px 0 8px; border-bottom: 1px solid #2563eb; padding-bottom: 4px;">
        1. Recapitulatif par Specialite / Niveau
    </h2>
    <table>
        <thead>
            <tr>
                <th>Specialite / Niveau</th>
                <th class="text-center">Nb UE</th>
                <th class="text-center">Volume total (h)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bySpecialite as $label => $ues)
            <tr>
                <td><strong>{{ $label }}</strong></td>
                <td class="text-center">{{ $ues->count() }}</td>
                <td class="text-center">{{ number_format($ues->sum('volume_horaire_total'), 0, ',', ' ') }}h</td>
            </tr>
            @endforeach
            <tr style="background: #eff6ff; font-weight: bold;">
                <td>TOTAL</td>
                <td class="text-center">{{ $totalUes }}</td>
                <td class="text-center">{{ number_format($totalVolume, 0, ',', ' ') }}h</td>
            </tr>
        </tbody>
    </table>

    {{-- ===== SECTION 2 : DETAIL PAR ENSEIGNANT ===== --}}
    <h2 style="font-size: 12px; color: #1e40af; margin: 20px 0 8px; border-bottom: 1px solid #2563eb; padding-bottom: 4px;">
        2. Detail des Attributions par Enseignant
    </h2>

    @foreach($byEnseignant as $group)
    <div style="margin-bottom: 12px; page-break-inside: avoid;">
        {{-- En-tete enseignant --}}
        <table>
            <thead>
                <tr>
                    <th colspan="8" style="background: #374151; text-align: left; padding: 6px 8px;">
                        {{ $group->enseignant->full_name }}
                        <span style="font-weight: normal; margin-left: 10px;">
                            ({{ $group->enseignant->employee_type === 'enseignant_vacataire' ? 'Vacataire' : ($group->enseignant->employee_type === 'semi_permanent' ? 'Semi-permanent' : 'Titulaire') }})
                        </span>
                        <span style="float: right; font-weight: normal;">
                            {{ $group->total_ues }} UE | Vol: {{ number_format($group->total_volume, 0) }}h | Eff: {{ number_format($group->total_effectuees, 1) }}h
                            @if($group->total_montant > 0)
                            | Montant: {{ number_format($group->total_montant, 0, ',', ' ') }} FCFA
                            @endif
                        </span>
                    </th>
                </tr>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 10%;">Code UE</th>
                    <th style="width: 22%;">Matiere</th>
                    <th style="width: 12%;">Specialite</th>
                    <th style="width: 8%;">Niveau</th>
                    <th class="text-center" style="width: 8%;">Vol. (h)</th>
                    <th class="text-center" style="width: 10%;">Effectuees</th>
                    <th class="text-center" style="width: 8%;">Statut</th>
                </tr>
            </thead>
            <tbody>
                @foreach($group->ues as $index => $ue)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td><strong>{{ $ue->code_ue ?: '-' }}</strong></td>
                    <td>{{ $ue->nom_matiere }}</td>
                    <td>{{ $ue->specialite ?: '-' }}</td>
                    <td>{{ $ue->niveau ?: '-' }}</td>
                    <td class="text-center">{{ number_format($ue->volume_horaire_total, 0) }}h</td>
                    <td class="text-center">
                        {{ number_format($ue->heures_effectuees, 1) }}h
                        <span style="font-size: 7px; color: #666;">
                            ({{ number_format($ue->pourcentage_progression, 0) }}%)
                        </span>
                    </td>
                    <td class="text-center">
                        @if($ue->statut === 'activee')
                            <span class="text-green">Active</span>
                        @else
                            <span class="text-orange">Inactive</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endforeach

    {{-- ===== SECTION 3 : SIGNATURES ===== --}}
    <div style="margin-top: 30px; page-break-inside: avoid;">
        <table style="border: none;">
            <tr>
                <td style="border: none; width: 33%; text-align: center; padding-top: 40px;">
                    <div style="border-top: 1px solid #333; padding-top: 5px; margin: 0 20px;">
                        <strong>Le Directeur Academique</strong><br>
                        <span style="font-size: 8px; color: #666;">Date et signature</span>
                    </div>
                </td>
                <td style="border: none; width: 33%; text-align: center; padding-top: 40px;">
                    <div style="border-top: 1px solid #333; padding-top: 5px; margin: 0 20px;">
                        <strong>Le Responsable RH</strong><br>
                        <span style="font-size: 8px; color: #666;">Date et signature</span>
                    </div>
                </td>
                <td style="border: none; width: 33%; text-align: center; padding-top: 40px;">
                    <div style="border-top: 1px solid #333; padding-top: 5px; margin: 0 20px;">
                        <strong>Le Directeur General</strong><br>
                        <span style="font-size: 8px; color: #666;">Date et signature</span>
                    </div>
                </td>
            </tr>
        </table>
    </div>
@endsection
