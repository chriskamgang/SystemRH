@extends('layouts.admin')

@section('title', 'Emploi du Temps')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Emploi du Temps</h1>
        <div class="flex space-x-2">
            <a href="{{ route('admin.emploi-du-temps.bulk-create') }}" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                <i class="fas fa-layer-group mr-2"></i>Création en lot
            </a>
            <a href="{{ route('admin.emploi-du-temps.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-plus mr-2"></i>Ajouter un créneau
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session('warning'))
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
            {{ session('warning') }}
        </div>
    @endif

    <!-- Filtres -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">Enseignant</label>
                <select name="enseignant_id" class="w-full border rounded-lg px-3 py-2">
                    <option value="">Tous</option>
                    @foreach($enseignants as $enseignant)
                        <option value="{{ $enseignant->id }}" {{ request('enseignant_id') == $enseignant->id ? 'selected' : '' }}>
                            {{ $enseignant->last_name }} {{ $enseignant->first_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">Campus</label>
                <select name="campus_id" class="w-full border rounded-lg px-3 py-2">
                    <option value="">Tous</option>
                    @foreach($campuses as $campus)
                        <option value="{{ $campus->id }}" {{ request('campus_id') == $campus->id ? 'selected' : '' }}>
                            {{ $campus->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1 min-w-[150px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">Jour</label>
                <select name="jour_semaine" class="w-full border rounded-lg px-3 py-2">
                    <option value="">Tous</option>
                    @foreach(['lundi','mardi','mercredi','jeudi','vendredi','samedi','dimanche'] as $jour)
                        <option value="{{ $jour }}" {{ request('jour_semaine') == $jour ? 'selected' : '' }}>
                            {{ ucfirst($jour) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
                    <i class="fas fa-search mr-1"></i>Filtrer
                </button>
            </div>
            <div>
                <a href="{{ route('admin.emploi-du-temps.index') }}" class="text-gray-500 hover:text-gray-700 px-3 py-2">
                    Réinitialiser
                </a>
            </div>
        </form>
    </div>

    <!-- Tableau -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jour</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Horaire</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">UE</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Enseignant</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Campus</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Salle</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Période</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($schedules as $schedule)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                {{ ucfirst($schedule->jour_semaine) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ substr($schedule->heure_debut, 0, 5) }} - {{ substr($schedule->heure_fin, 0, 5) }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ $schedule->uniteEnseignement->code_ue }}</div>
                            <div class="text-sm text-gray-500">{{ $schedule->uniteEnseignement->nom_matiere }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            @if($schedule->uniteEnseignement->enseignant)
                                <a href="{{ route('admin.emploi-du-temps.by-enseignant', $schedule->uniteEnseignement->enseignant->id) }}" class="text-blue-600 hover:underline">
                                    {{ $schedule->uniteEnseignement->enseignant->last_name }} {{ $schedule->uniteEnseignement->enseignant->first_name }}
                                </a>
                            @else
                                <span class="text-gray-400">Non assigné</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $schedule->campus->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $schedule->salle ?? '-' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500">
                            @if($schedule->date_debut_validite && $schedule->date_fin_validite)
                                <span class="text-purple-700 font-medium">{{ $schedule->date_debut_validite->format('d/m/Y') }}</span>
                                <br>au <span class="text-purple-700 font-medium">{{ $schedule->date_fin_validite->format('d/m/Y') }}</span>
                            @elseif($schedule->date_debut_validite)
                                A partir du <span class="text-purple-700 font-medium">{{ $schedule->date_debut_validite->format('d/m/Y') }}</span>
                            @else
                                <span class="text-green-600">Récurrent</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($schedule->is_active)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Actif</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Inactif</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                            <a href="{{ route('admin.emploi-du-temps.edit', $schedule->id) }}" class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('admin.emploi-du-temps.destroy', $schedule->id) }}" method="POST" class="inline" onsubmit="return confirm('Supprimer ce créneau ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-calendar-times text-4xl mb-4 block"></i>
                            Aucun créneau trouvé. <a href="{{ route('admin.emploi-du-temps.create') }}" class="text-blue-600 hover:underline">Ajouter un créneau</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="px-6 py-4">
            {{ $schedules->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection
