@extends('admin.rapports.pdf.layout')

@section('title', 'État des Cours Non Payés')
@section('subtitle', 'Liste des unités d\'enseignement (UE) en attente de paiement')

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
            <th class="text-right">Montant estimé</th>
        </tr>
    </thead>
    <tbody>
        @forelse($cours as $ue)
        @php
            $montantEstime = 0;
            if ($ue->enseignant && !$ue->enseignant->isSemiPermanent()) {
                $montantEstime = $ue->volume_horaire_total * ($ue->enseignant->hourly_rate ?? 0);
            }
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
            <td class="text-right font-bold text-orange">
                @if($ue->enseignant && $ue->enseignant->isSemiPermanent())
                    <span style="color: #9ca3af; font-style: italic;">Salaire fixe</span>
                @else
                    {{ number_format($montantEstime, 0, ',', ' ') }}
                @endif
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="7" class="text-center" style="padding: 20px; color: #059669;">
                Aucun cours non payé trouvé
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
@endsection
