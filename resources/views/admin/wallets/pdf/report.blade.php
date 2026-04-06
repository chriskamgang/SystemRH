@extends('admin.rapports.pdf.layout')

@section('title', 'Rapport des Portefeuilles')
@section('subtitle', 'Généré le ' . date('d/m/Y'))

@section('meta-info')
    <p><strong>Total employés :</strong> {{ $employees->count() }}</p>
    <p><strong>Solde total :</strong> {{ number_format($totalBalance, 0, ',', ' ') }} FCFA</p>
    @if($filters)<p><strong>Filtres :</strong> {{ $filters }}</p>@endif
@endsection

@section('content')
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Employé</th>
                <th>Email</th>
                <th>Type</th>
                <th class="text-right">Solde (FCFA)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($employees as $index => $employee)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td><strong>{{ $employee->full_name }}</strong></td>
                <td>{{ $employee->email }}</td>
                <td>
                    @if($employee->employee_type == 'enseignant_titulaire') Permanent
                    @elseif($employee->employee_type == 'semi_permanent') Semi-perm.
                    @elseif($employee->employee_type == 'enseignant_vacataire') Vacataire
                    @else {{ ucfirst($employee->employee_type ?? 'N/A') }}
                    @endif
                </td>
                <td class="text-right font-bold {{ ($employee->wallet->balance ?? 0) > 0 ? 'text-green' : '' }}">
                    {{ number_format($employee->wallet->balance ?? 0, 0, ',', ' ') }}
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background: #1e40af; color: white; font-weight: bold;">
                <td colspan="4" class="text-right" style="padding: 8px 5px;">TOTAL</td>
                <td class="text-right" style="padding: 8px 5px;">{{ number_format($totalBalance, 0, ',', ' ') }}</td>
            </tr>
        </tfoot>
    </table>
@endsection
