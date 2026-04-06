@extends('admin.rapports.pdf.layout')

@section('title', 'Rapport des Déductions Manuelles')
@section('subtitle', 'Généré le ' . date('d/m/Y'))

@section('meta-info')
    <p><strong>Total déductions :</strong> {{ $deductions->count() }}</p>
    <p><strong>Montant total :</strong> {{ number_format($deductions->where('status', 'active')->sum('amount'), 0, ',', ' ') }} FCFA</p>
    @if($filters)<p><strong>Filtres :</strong> {{ $filters }}</p>@endif
@endsection

@section('content')
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Employé</th>
                <th class="text-right">Montant</th>
                <th>Motif</th>
                <th>Période</th>
                <th>Statut</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($deductions as $index => $deduction)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td><strong>{{ $deduction->user->full_name }}</strong></td>
                <td class="text-right text-red font-bold">{{ number_format($deduction->amount, 0, ',', ' ') }}</td>
                <td>{{ \Illuminate\Support\Str::limit($deduction->reason, 40) }}</td>
                <td>{{ $deduction->month }}/{{ $deduction->year }}</td>
                <td class="{{ $deduction->status === 'active' ? 'text-green' : 'text-red' }}">
                    {{ $deduction->status === 'active' ? 'Active' : 'Annulée' }}
                </td>
                <td>{{ $deduction->created_at->format('d/m/Y') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endsection
