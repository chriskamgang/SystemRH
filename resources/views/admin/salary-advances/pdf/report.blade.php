@extends('admin.rapports.pdf.layout')

@section('title', 'Rapport des Avances sur Salaire')
@section('subtitle', 'Généré le ' . date('d/m/Y'))

@section('meta-info')
    <p><strong>Total demandes :</strong> {{ $requests->count() }}</p>
    <p><strong>Montant total approuvé :</strong> {{ number_format($requests->where('status', 'approved')->sum('amount'), 0, ',', ' ') }} FCFA</p>
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
                <th>Statut</th>
                <th>Date demande</th>
                <th>Date traitement</th>
            </tr>
        </thead>
        <tbody>
            @foreach($requests as $index => $request)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td><strong>{{ $request->user->full_name }}</strong></td>
                <td class="text-right font-bold">{{ number_format($request->amount, 0, ',', ' ') }}</td>
                <td>{{ \Illuminate\Support\Str::limit($request->reason, 40) }}</td>
                <td class="{{ $request->status === 'approved' ? 'text-green' : ($request->status === 'rejected' ? 'text-red' : 'text-orange') }}">
                    @if($request->status === 'approved') Approuvée
                    @elseif($request->status === 'rejected') Rejetée
                    @else En attente
                    @endif
                </td>
                <td>{{ $request->created_at->format('d/m/Y') }}</td>
                <td>{{ $request->reviewed_at ? \Carbon\Carbon::parse($request->reviewed_at)->format('d/m/Y') : '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endsection
