@extends('admin.rapports.pdf.layout')

@section('title', 'Rapport des Prêts')
@section('subtitle', 'Généré le ' . date('d/m/Y'))

@section('meta-info')
    <p><strong>Total prêts :</strong> {{ $loans->count() }}</p>
    <p><strong>Montant total :</strong> {{ number_format($loans->sum('total_amount'), 0, ',', ' ') }} FCFA</p>
    <p><strong>Montant remboursé :</strong> {{ number_format($loans->sum('amount_paid'), 0, ',', ' ') }} FCFA</p>
    @if($filters)<p><strong>Filtres :</strong> {{ $filters }}</p>@endif
@endsection

@section('content')
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Employé</th>
                <th class="text-right">Montant total</th>
                <th class="text-right">Mensualité</th>
                <th class="text-right">Remboursé</th>
                <th class="text-right">Restant</th>
                <th>Motif</th>
                <th>Statut</th>
                <th>Date début</th>
            </tr>
        </thead>
        <tbody>
            @foreach($loans as $index => $loan)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td><strong>{{ $loan->user->full_name }}</strong></td>
                <td class="text-right font-bold">{{ number_format($loan->total_amount, 0, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($loan->monthly_amount, 0, ',', ' ') }}</td>
                <td class="text-right text-green">{{ number_format($loan->amount_paid, 0, ',', ' ') }}</td>
                <td class="text-right text-red">{{ number_format($loan->total_amount - $loan->amount_paid, 0, ',', ' ') }}</td>
                <td>{{ \Illuminate\Support\Str::limit($loan->reason, 30) }}</td>
                <td class="{{ $loan->status === 'active' ? 'text-orange' : ($loan->status === 'completed' ? 'text-green' : 'text-red') }}">
                    @if($loan->status === 'active') Actif
                    @elseif($loan->status === 'completed') Terminé
                    @else Annulé
                    @endif
                </td>
                <td>{{ \Carbon\Carbon::parse($loan->start_date)->format('d/m/Y') }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background: #1e40af; color: white; font-weight: bold;">
                <td colspan="2" class="text-right" style="padding: 8px 5px;">TOTAUX</td>
                <td class="text-right" style="padding: 8px 5px;">{{ number_format($loans->sum('total_amount'), 0, ',', ' ') }}</td>
                <td class="text-right" style="padding: 8px 5px;">{{ number_format($loans->sum('monthly_amount'), 0, ',', ' ') }}</td>
                <td class="text-right" style="padding: 8px 5px;">{{ number_format($loans->sum('amount_paid'), 0, ',', ' ') }}</td>
                <td class="text-right" style="padding: 8px 5px;">{{ number_format($loans->sum('total_amount') - $loans->sum('amount_paid'), 0, ',', ' ') }}</td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
    </table>
@endsection
