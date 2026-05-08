@extends('admin.rapports.pdf.layout')

@section('title', 'Rapport Utilisation Application Mobile')
@section('subtitle', 'Employes utilisant l\'app pour le pointage')

@section('meta-info')
    <p><strong>Periode :</strong> {{ $period }}</p>
    <p><strong>Utilisateurs actifs :</strong> {{ $total_users }} sur {{ $total_active_employees }} employes ({{ $total_active_employees > 0 ? round(($total_users / $total_active_employees) * 100, 1) : 0 }}%)</p>
    <p><strong>Moyenne jours check-in :</strong> {{ $avg_checkin_days }} jours/employe</p>
@endsection

@section('content')
    <div class="stats-grid">
        <div class="stat-box">
            <div class="stat-label">UTILISATEURS ACTIFS</div>
            <div class="stat-value">{{ $total_users }}</div>
        </div>
        <div class="stat-box">
            <div class="stat-label">TOTAL JOURS COMPLETS</div>
            <div class="stat-value">{{ $total_complete_days }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Nom complet</th>
                <th>Matricule</th>
                <th>Type</th>
                <th>Departement</th>
                <th class="text-center">Check-in</th>
                <th class="text-center">Check-out</th>
                <th class="text-center">Complets</th>
                <th class="text-center">Retards</th>
                <th class="text-center">Ponctualite</th>
            </tr>
        </thead>
        <tbody>
            @foreach($employees as $index => $emp)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td class="font-bold">{{ $emp['full_name'] }}</td>
                <td>{{ $emp['employee_id'] ?? '-' }}</td>
                <td>{{ $emp['employee_type_label'] }}</td>
                <td>{{ $emp['department'] ?? '-' }}</td>
                <td class="text-center text-green">{{ $emp['checkin_days'] }}</td>
                <td class="text-center">{{ $emp['checkout_days'] }}</td>
                <td class="text-center font-bold">{{ $emp['complete_days'] }}</td>
                <td class="text-center {{ $emp['late_count'] > 0 ? 'text-red' : '' }}">{{ $emp['late_count'] }}</td>
                <td class="text-center {{ $emp['punctuality_rate'] >= 90 ? 'text-green' : ($emp['punctuality_rate'] >= 70 ? 'text-orange' : 'text-red') }}">
                    {{ $emp['punctuality_rate'] }}%
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endsection
