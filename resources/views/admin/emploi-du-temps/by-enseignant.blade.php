@extends('layouts.admin')

@section('title', 'Emploi du temps - ' . $enseignant->last_name . ' ' . $enseignant->first_name)

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center">
            <a href="{{ route('admin.emploi-du-temps.index') }}" class="text-gray-500 hover:text-gray-700 mr-4">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Emploi du temps</h1>
                <p class="text-gray-500">{{ $enseignant->last_name }} {{ $enseignant->first_name }} — {{ $enseignant->employee_type === 'enseignant_vacataire' ? 'Vacataire' : 'Semi-permanent' }}</p>
            </div>
        </div>
        <a href="{{ route('admin.emploi-du-temps.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
            <i class="fas fa-plus mr-2"></i>Ajouter
        </a>
    </div>

    <!-- Grille hebdomadaire -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @foreach($jours as $jour)
            <div class="bg-white rounded-lg shadow">
                <div class="bg-gray-800 text-white px-4 py-3 rounded-t-lg">
                    <h3 class="font-bold text-center">{{ ucfirst($jour) }}</h3>
                </div>
                <div class="p-4 space-y-3">
                    @if(isset($schedules[$jour]) && $schedules[$jour]->count() > 0)
                        @foreach($schedules[$jour] as $schedule)
                            <div class="border rounded-lg p-3 hover:bg-blue-50 transition-colors">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-bold text-blue-600">
                                        {{ substr($schedule->heure_debut, 0, 5) }} - {{ substr($schedule->heure_fin, 0, 5) }}
                                    </span>
                                    <div class="flex space-x-1">
                                        <a href="{{ route('admin.emploi-du-temps.edit', $schedule->id) }}" class="text-gray-400 hover:text-blue-600 text-xs">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </div>
                                <p class="text-sm font-medium text-gray-800">{{ $schedule->uniteEnseignement->code_ue }}</p>
                                <p class="text-xs text-gray-500">{{ $schedule->uniteEnseignement->nom_matiere }}</p>
                                <div class="mt-2 flex items-center justify-between text-xs text-gray-400">
                                    <span><i class="fas fa-building mr-1"></i>{{ $schedule->campus->name }}</span>
                                    @if($schedule->salle)
                                        <span><i class="fas fa-door-open mr-1"></i>{{ $schedule->salle }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p class="text-center text-gray-400 text-sm py-4">Pas de cours</p>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
