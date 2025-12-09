@extends('admin.rapports.pdf.layout')

@section('title', 'État du Personnel Payé')
@section('subtitle', 'Liste des employés ayant reçu un paiement')

@section('content')
<div class="stats-grid">
    <div class="stat-box">
        <div class="stat-label">Nombre d'employés payés</div>
        <div class="stat-value">{{ $nombreEmployes }}</div>
    </div>
    <div class="stat-box">
        <div class="stat-label">Montant total payé</div>
        <div class="stat-value text-green">{{ number_format($totalPaye, 0, ',', ' ') }} FCFA</div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>Employé</th>
            <th>Département</th>
            <th>Période</th>
            <th class="text-right">Heures</th>
            <th class="text-right">Montant brut</th>
            <th class="text-right">Montant net</th>
            <th>Date paiement</th>
        </tr>
    </thead>
    <tbody>
        @forelse($paiements as $paiement)
        <tr>
            <td>
                <strong>{{ $paiement->user->full_name }}</strong><br>
                <span style="color: #6b7280;">{{ $paiement->user->email }}</span>
            </td>
            <td>{{ $paiement->department->name ?? 'N/A' }}</td>
            <td>
                {{ \Carbon\Carbon::create()->month($paiement->month)->translatedFormat('F') }} {{ $paiement->year }}
            </td>
            <td class="text-right">{{ number_format($paiement->hours_worked, 2) }}h</td>
            <td class="text-right">{{ number_format($paiement->gross_amount, 0, ',', ' ') }}</td>
            <td class="text-right font-bold text-green">{{ number_format($paiement->net_amount, 0, ',', ' ') }}</td>
            <td>{{ $paiement->paid_at ? $paiement->paid_at->format('d/m/Y') : 'N/A' }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="7" class="text-center" style="padding: 20px; color: #9ca3af;">
                Aucun paiement trouvé pour cette période
            </td>
        </tr>
        @endforelse
    </tbody>
    @if($paiements->count() > 0)
    <tfoot style="background: #f3f4f6; font-weight: bold;">
        <tr>
            <td colspan="4">TOTAL</td>
            <td class="text-right">{{ number_format($paiements->sum('gross_amount'), 0, ',', ' ') }}</td>
            <td class="text-right text-green">{{ number_format($totalPaye, 0, ',', ' ') }} FCFA</td>
            <td></td>
        </tr>
    </tfoot>
    @endif
</table>
@endsection
