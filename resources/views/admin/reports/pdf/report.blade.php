@extends('admin.rapports.pdf.layout')

@section('title', 'Rapport de Présence - Statistiques')
@section('subtitle', $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y'))

@section('meta-info')
    <p><strong>Période :</strong> {{ $startDate->format('d/m/Y') }} au {{ $endDate->format('d/m/Y') }}</p>
    <p><strong>Total pointages :</strong> {{ number_format($overallStats['total_checkins']) }} | <strong>Retards :</strong> {{ number_format($overallStats['total_late']) }} | <strong>Taux de ponctualité :</strong> {{ $overallStats['punctuality_rate'] }}%</p>
@endsection

@section('content')
    <h3 style="font-size: 12px; margin: 10px 0 5px; color: #1e40af;">Statistiques par Employé</h3>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Employé</th>
                <th class="text-center">Jours</th>
                <th class="text-center">Ponctualité</th>
                <th class="text-center">Retards</th>
                <th class="text-center">Moy. retard (min)</th>
                <th class="text-center">Heures travaillées</th>
            </tr>
        </thead>
        <tbody>
            @foreach($employeeStats->filter(fn($s) => $s['total_days'] > 0)->sortByDesc('total_days') as $index => $stat)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td><strong>{{ $stat['user']->full_name }}</strong></td>
                <td class="text-center">{{ $stat['total_days'] }}</td>
                <td class="text-center {{ $stat['punctuality_rate'] >= 90 ? 'text-green' : ($stat['punctuality_rate'] >= 70 ? 'text-orange' : 'text-red') }} font-bold">{{ $stat['punctuality_rate'] }}%</td>
                <td class="text-center text-red">{{ $stat['late_days'] }}</td>
                <td class="text-center">{{ $stat['avg_late_minutes'] }}</td>
                <td class="text-center">{{ $stat['total_work_hours'] }}h</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <h3 style="font-size: 12px; margin: 15px 0 5px; color: #1e40af;">Statistiques par Campus</h3>
    <table>
        <thead>
            <tr>
                <th>Campus</th>
                <th class="text-center">Pointages</th>
                <th class="text-center">Retards</th>
                <th class="text-center">Ponctualité</th>
            </tr>
        </thead>
        <tbody>
            @foreach($campusStats as $stat)
            <tr>
                <td><strong>{{ $stat['campus']->name }}</strong></td>
                <td class="text-center">{{ $stat['total_checkins'] }}</td>
                <td class="text-center text-red">{{ $stat['late_checkins'] }}</td>
                <td class="text-center {{ $stat['punctuality_rate'] >= 90 ? 'text-green' : ($stat['punctuality_rate'] >= 70 ? 'text-orange' : 'text-red') }} font-bold">{{ $stat['punctuality_rate'] }}%</td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endsection
