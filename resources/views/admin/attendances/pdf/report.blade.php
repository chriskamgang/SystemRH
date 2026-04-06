@extends('admin.rapports.pdf.layout')

@section('title', 'Rapport des Présences')
@section('subtitle', $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y'))

@section('meta-info')
    <p><strong>Période :</strong> {{ $startDate->format('d/m/Y') }} au {{ $endDate->format('d/m/Y') }}</p>
    <p><strong>Nombre d'employés :</strong> {{ $employees->count() }}</p>
    @if($filters)<p><strong>Filtres :</strong> {{ $filters }}</p>@endif
@endsection

@section('content')
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Employé</th>
                <th class="text-center">Jours</th>
                <th class="text-center">Entrées</th>
                <th class="text-center">Sorties</th>
                <th class="text-center">Retards</th>
                <th class="text-center">% Retard</th>
            </tr>
        </thead>
        <tbody>
            @foreach($employees as $index => $employee)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td><strong>{{ $employee->user->full_name }}</strong></td>
                <td class="text-center">{{ $employee->total_days }}</td>
                <td class="text-center">{{ $employee->total_check_ins }}</td>
                <td class="text-center">{{ $employee->total_check_outs }}</td>
                <td class="text-center text-red">{{ $employee->total_late }}</td>
                <td class="text-center {{ $employee->late_percentage > 20 ? 'text-red font-bold' : '' }}">{{ $employee->late_percentage }}%</td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endsection
