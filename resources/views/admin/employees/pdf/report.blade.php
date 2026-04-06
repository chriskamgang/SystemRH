@extends('admin.rapports.pdf.layout')

@section('title', 'Liste des Employés')
@section('subtitle', 'Généré le ' . date('d/m/Y'))

@section('meta-info')
    <p><strong>Total employés :</strong> {{ $totalEmployees }}</p>
    @if($filters)<p><strong>Filtres :</strong> {{ $filters }}</p>@endif
@endsection

@section('content')
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Nom complet</th>
                <th>Email</th>
                <th>ID</th>
                <th>Type</th>
                <th>Campus</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            @foreach($employees as $index => $employee)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td><strong>{{ $employee->full_name }}</strong></td>
                <td>{{ $employee->email }}</td>
                <td>{{ $employee->employee_id }}</td>
                <td>
                    @if($employee->employee_type == 'enseignant_titulaire') Permanent
                    @elseif($employee->employee_type == 'semi_permanent') Semi-perm.
                    @elseif($employee->employee_type == 'enseignant_vacataire') Vacataire
                    @else {{ ucfirst($employee->employee_type) }}
                    @endif
                </td>
                <td>{{ $employee->campuses->pluck('name')->join(', ') }}</td>
                <td class="{{ $employee->is_active ? 'text-green' : 'text-red' }}">{{ $employee->is_active ? 'Actif' : 'Inactif' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endsection
