@extends('admin.rapports.pdf.layout')

@section('title', 'Rapport des Violations de Sécurité')
@section('subtitle', 'Généré le ' . date('d/m/Y'))

@section('meta-info')
    <p><strong>Total :</strong> {{ $stats['total'] }} | <strong>En attente :</strong> {{ $stats['pending'] }} | <strong>Sévérité haute :</strong> {{ $stats['high_severity'] }} | <strong>Aujourd'hui :</strong> {{ $stats['today'] }}</p>
    @if($filters)<p><strong>Filtres :</strong> {{ $filters }}</p>@endif
@endsection

@section('content')
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Employé</th>
                <th>Type</th>
                <th>Sévérité</th>
                <th>Statut</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($violations as $index => $violation)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td><strong>{{ $violation->user->full_name }}</strong></td>
                <td>{{ $violation->violation_type }}</td>
                <td class="{{ in_array($violation->severity, ['high', 'critical']) ? 'text-red font-bold' : ($violation->severity === 'medium' ? 'text-orange' : '') }}">
                    {{ ucfirst($violation->severity) }}
                </td>
                <td>
                    @if($violation->status === 'pending') En attente
                    @elseif($violation->status === 'reviewed') Examiné
                    @else {{ ucfirst($violation->status) }}
                    @endif
                </td>
                <td>{{ $violation->created_at->format('d/m/Y H:i') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endsection
