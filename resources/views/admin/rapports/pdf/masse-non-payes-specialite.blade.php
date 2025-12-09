@extends('admin.rapports.pdf.layout')

@section('title', 'Masse Salariale Non Payée par Spécialité')
@section('subtitle', 'Estimation des montants à payer par spécialité')

@section('content')
<div class="stats-grid">
    <div class="stat-box">
        <div class="stat-label">Total heures à payer</div>
        <div class="stat-value">{{ number_format($totalHeures, 2) }}h</div>
    </div>
    <div class="stat-box">
        <div class="stat-label">Montant total estimé</div>
        <div class="stat-value text-red">{{ number_format($totalGeneral, 0, ',', ' ') }} FCFA</div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>Spécialité</th>
            <th class="text-right">Nombre UE</th>
            <th class="text-right">Enseignants</th>
            <th class="text-right">Total Heures</th>
            <th class="text-right">Montant Estimé</th>
            <th class="text-right">% du Total</th>
        </tr>
    </thead>
    <tbody>
        @forelse($masseSalariale as $masse)
        @php
            $pourcentage = $totalGeneral > 0 ? ($masse->montant_estime / $totalGeneral) * 100 : 0;
        @endphp
        <tr>
            <td><strong>{{ $masse->specialite }}</strong></td>
            <td class="text-right">{{ $masse->nombre_ue }}</td>
            <td class="text-right">{{ $masse->nombre_enseignants }}</td>
            <td class="text-right">{{ number_format($masse->total_heures, 2) }}h</td>
            <td class="text-right font-bold text-red">{{ number_format($masse->montant_estime, 0, ',', ' ') }}</td>
            <td class="text-right">{{ number_format($pourcentage, 1) }}%</td>
        </tr>
        @empty
        <tr>
            <td colspan="6" class="text-center" style="padding: 20px; color: #059669;">
                Aucune donnée disponible - Tous les cours sont payés !
            </td>
        </tr>
        @endforelse
    </tbody>
    @if($masseSalariale->count() > 0)
    <tfoot style="background: #f3f4f6; font-weight: bold;">
        <tr>
            <td>TOTAL</td>
            <td class="text-right">{{ $masseSalariale->sum('nombre_ue') }}</td>
            <td class="text-right">{{ $masseSalariale->sum('nombre_enseignants') }}</td>
            <td class="text-right">{{ number_format($totalHeures, 2) }}h</td>
            <td class="text-right text-red">{{ number_format($totalGeneral, 0, ',', ' ') }} FCFA</td>
            <td class="text-right">100%</td>
        </tr>
    </tfoot>
    @endif
</table>
@endsection
