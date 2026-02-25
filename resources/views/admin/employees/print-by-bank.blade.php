@extends('layouts.admin')

@section('title', 'Impression par Banque')
@section('page-title', 'Liste des Employés par Banque')

@section('content')
<div class="space-y-6">
    <!-- Header avec filtre et bouton d'impression -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Liste des Employés par Banque</h2>
                <p class="text-gray-600 mt-1">Pour dépôt bancaire</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.employees.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Retour
                </a>
                <button onclick="window.print()" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition print:hidden">
                    <i class="fas fa-print mr-2"></i>
                    Imprimer
                </button>
            </div>
        </div>

        <!-- Filtre par banque -->
        <form method="GET" action="{{ route('admin.employees.print-by-bank') }}" class="print:hidden">
            <div class="flex gap-4 items-end">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Filtrer par banque</label>
                    <select name="banque" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="this.form.submit()">
                        <option value="">Toutes les banques</option>
                        @foreach($banques as $banque)
                            <option value="{{ $banque }}" {{ $selectedBanque == $banque ? 'selected' : '' }}>
                                {{ $banque }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @if($selectedBanque)
                <div>
                    <a href="{{ route('admin.employees.print-by-bank') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition">
                        <i class="fas fa-times mr-2"></i>
                        Réinitialiser
                    </a>
                </div>
                @endif
            </div>
        </form>
    </div>

    <!-- Liste groupée par banque -->
    @if($employeesByBank->count() > 0)
        @foreach($employeesByBank as $banque => $employees)
        <div class="bg-white rounded-lg shadow overflow-hidden page-break">
            <!-- En-tête de la banque -->
            <div class="bg-blue-600 text-white px-6 py-4">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold">{{ $banque }}</h3>
                        <p class="text-blue-100 text-sm">{{ $employees->count() }} employé(s)</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm">Date: {{ now()->format('d/m/Y') }}</p>
                        <p class="text-sm">{{ now()->format('H:i') }}</p>
                    </div>
                </div>
            </div>

            <!-- Table des employés -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                #
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ID Employé
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Nom Complet
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Type
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Numéro de compte
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Téléphone
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($employees as $index => $employee)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $index + 1 }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $employee->employee_id }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $employee->full_name }}</div>
                                <div class="text-sm text-gray-500">{{ $employee->email }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $employeeTypeLabels = [
                                        'enseignant_titulaire' => 'Personnel Permanent',
                                        'semi_permanent' => 'Semi-Permanent',
                                        'enseignant_vacataire' => 'Vacataire',
                                        'administratif' => 'Administratif',
                                        'technique' => 'Technique',
                                        'direction' => 'Direction',
                                    ];
                                    $typeLabel = $employeeTypeLabels[$employee->employee_type] ?? 'Non défini';
                                @endphp
                                <span class="text-sm text-gray-900">{{ $typeLabel }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-mono text-gray-900">{{ $employee->numero_compte ?? '-' }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $employee->phone ?? '-' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="6" class="px-6 py-3 text-sm font-medium text-gray-700">
                                Total: {{ $employees->count() }} employé(s) pour {{ $banque }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Signature section (pour impression) -->
            <div class="px-6 py-4 bg-gray-50 print:block hidden">
                <div class="grid grid-cols-2 gap-8 mt-8">
                    <div>
                        <p class="text-sm text-gray-600 mb-2">Préparé par:</p>
                        <div class="border-t border-gray-400 pt-2 mt-12">
                            <p class="text-sm text-gray-600">Nom et Signature</p>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 mb-2">Vérifié par:</p>
                        <div class="border-t border-gray-400 pt-2 mt-12">
                            <p class="text-sm text-gray-600">Nom et Signature</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach

        <!-- Résumé général (si plusieurs banques) -->
        @if($employeesByBank->count() > 1 && !$selectedBanque)
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Résumé Général</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-blue-50 rounded-lg p-4">
                    <p class="text-sm text-gray-600">Nombre de banques</p>
                    <p class="text-2xl font-bold text-blue-600">{{ $employeesByBank->count() }}</p>
                </div>
                <div class="bg-green-50 rounded-lg p-4">
                    <p class="text-sm text-gray-600">Total employés</p>
                    <p class="text-2xl font-bold text-green-600">{{ $employeesByBank->flatten()->count() }}</p>
                </div>
                <div class="bg-purple-50 rounded-lg p-4">
                    <p class="text-sm text-gray-600">Date d'édition</p>
                    <p class="text-2xl font-bold text-purple-600">{{ now()->format('d/m/Y') }}</p>
                </div>
            </div>
        </div>
        @endif
    @else
        <div class="bg-white rounded-lg shadow p-12 text-center">
            <i class="fas fa-university text-6xl text-gray-400 mb-4"></i>
            <p class="text-lg text-gray-600">Aucun employé avec informations bancaires</p>
            <p class="text-sm text-gray-500 mt-2">Ajoutez les informations bancaires dans les fiches employés</p>
            <a href="{{ route('admin.employees.index') }}" class="inline-flex items-center px-4 py-2 mt-4 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                <i class="fas fa-users mr-2"></i>
                Voir les employés
            </a>
        </div>
    @endif
</div>

@push('styles')
<style>
@media print {
    /* Hide navigation, buttons, etc */
    nav, .print\:hidden {
        display: none !important;
    }

    /* Page breaks */
    .page-break {
        page-break-after: always;
        page-break-inside: avoid;
    }

    /* Show signature section only in print */
    .print\:block {
        display: block !important;
    }

    /* Optimize for print */
    body {
        background: white;
    }

    .bg-white {
        box-shadow: none !important;
    }

    /* Better table printing */
    table {
        page-break-inside: auto;
    }

    tr {
        page-break-inside: avoid;
        page-break-after: auto;
    }

    thead {
        display: table-header-group;
    }

    tfoot {
        display: table-footer-group;
    }
}
</style>
@endpush
@endsection
