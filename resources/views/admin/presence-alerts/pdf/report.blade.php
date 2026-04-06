@extends('admin.rapports.pdf.layout')

@section('title', 'Rapport des Alertes de Présence')
@section('subtitle', 'Généré le ' . date('d/m/Y'))

@section('meta-info')
    <p><strong>Total incidents :</strong> {{ $incidents->count() }}</p>
    @if($filters)<p><strong>Filtres :</strong> {{ $filters }}</p>@endif
@endsection

@section('content')
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Employé</th>
                <th>Campus</th>
                <th>Date</th>
                <th>Notification</th>
                <th>Réponse</th>
                <th>Statut</th>
                <th>Pénalité</th>
            </tr>
        </thead>
        <tbody>
            @foreach($incidents as $index => $incident)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td><strong>{{ $incident->user->full_name }}</strong></td>
                <td>{{ $incident->campus->name }}</td>
                <td>{{ $incident->incident_date->format('d/m/Y') }}</td>
                <td>{{ substr($incident->notification_sent_at, 0, 5) }}</td>
                <td class="{{ $incident->has_responded ? 'text-green' : 'text-red' }}">
                    {{ $incident->has_responded ? 'Oui' : 'Non' }}
                    @if($incident->has_responded && $incident->responded_at)
                        ({{ $incident->responded_at->format('H:i') }})
                    @endif
                </td>
                <td>
                    @if($incident->status === 'pending') En attente
                    @elseif($incident->status === 'validated') Validé
                    @else Ignoré
                    @endif
                </td>
                <td>{{ $incident->status === 'validated' ? $incident->penalty_hours . 'h' : '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endsection
