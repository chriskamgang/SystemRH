@extends('admin.rapports.pdf.layout')

@section('title', 'Masse Salariale Payée par Cycle')
@section('subtitle', 'Répartition des paiements d\'enseignement par niveau/cycle')

@section('content')
<div class="stats-grid">
    <div class="stat-box">
        <div class="stat-label">Total heures payées</div>
        <div class="stat-value">{{ number_format($totalHeures, 2) }}h</div>
    </div>
    <div class="stat-box">
        <div class="stat-label">Montant total payé</div>
        <div class="stat-value text-green">{{ number_format($totalGeneral, 0, ',', ' ') }} FCFA</div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>Niveau (Cycle)</th>
            <th class="text-right">Nombre UE</th>
            <th class="text-right">Enseignants</th>
            <th class="text-right">Total Heures</th>
            <th class="text-right">Montant Payé</th>
            <th class="text-right">% du Total</th>
        </tr>
    </thead>
    <tbody>
        @forelse($masseSalariale as $masse)
        @php
            $pourcentage = $totalGeneral > 0 ? ($masse->total_paye / $totalGeneral) * 100 : 0;
        @endphp
        <tr>
            <td><strong>{{ $masse->niveau }}</strong></td>
            <td class="text-right">{{ $masse->nombre_ue }}</td>
            <td class="text-right">{{ $masse->nombre_enseignants }}</td>
            <td class="text-right">{{ number_format($masse->total_heures, 2) }}h</td>
            <td class="text-right font-bold text-green">{{ number_format($masse->total_paye, 0, ',', ' ') }}</td>
            <td class="text-right">{{ number_format($pourcentage, 1) }}%</td>
        </tr>
        @empty
        <tr>
            <td colspan="6" class="text-center" style="padding: 20px; color: #9ca3af;">
                Aucune donnée disponible
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
            <td class="text-right text-green">{{ number_format($totalGeneral, 0, ',', ' ') }} FCFA</td>
            <td class="text-right">100%</td>
        </tr>
    </tfoot>
    @endif
</table>
@endsection
