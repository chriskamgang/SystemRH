@extends('admin.rapports.pdf.layout')

@section('title', 'État des Cours Payés')
@section('subtitle', 'Liste des unités d\'enseignement (UE) ayant été payées')

@section('content')
<table>
    <thead>
        <tr>
            <th>Code UE</th>
            <th>Matière</th>
            <th>Enseignant</th>
            <th>Spécialité</th>
            <th>Niveau</th>
            <th class="text-right">Vol. horaire</th>
            <th class="text-right">Montant payé</th>
        </tr>
    </thead>
    <tbody>
        @forelse($cours as $ue)
        @php
            $montantPaye = $ue->paymentDetails()->whereHas('payment', function($q) {
                $q->where('status', 'paid');
            })->sum('montant');
        @endphp
        <tr>
            <td><strong>{{ $ue->code_ue }}</strong></td>
            <td>{{ $ue->nom_matiere }}</td>
            <td>
                @if($ue->enseignant)
                    {{ $ue->enseignant->full_name }}
                @else
                    <span style="color: #9ca3af;">Non attribué</span>
                @endif
            </td>
            <td>{{ $ue->specialite ?? 'N/A' }}</td>
            <td>{{ $ue->niveau ?? 'N/A' }}</td>
            <td class="text-right">{{ number_format($ue->volume_horaire_total, 2) }}h</td>
            <td class="text-right font-bold text-green">{{ number_format($montantPaye, 0, ',', ' ') }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="7" class="text-center" style="padding: 20px; color: #9ca3af;">
                Aucun cours payé trouvé
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
@endsection
