@extends('layouts.admin')

@section('title', $campus->name . ' - Campus')
@section('page-title', $campus->name)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">{{ $campus->name }}</h2>
            <p class="text-gray-600 mt-1">{{ $campus->address }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.campuses.edit', $campus->id) }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                <i class="fas fa-edit mr-2"></i>
                Modifier
            </a>
            <a href="{{ route('admin.campuses.index') }}" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
                <i class="fas fa-arrow-left mr-2"></i>
                Retour
            </a>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-600">Employés affectés</div>
            <div class="text-2xl font-bold text-gray-800">{{ $campus->users_count }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-600">Pointages</div>
            <div class="text-2xl font-bold text-gray-800">{{ $campus->attendances_count }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-600">Rayon</div>
            <div class="text-2xl font-bold text-gray-800">{{ $campus->radius }}m</div>
        </div>
    </div>

    <!-- Informations du campus -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Détails -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Détails du Campus</h3>
            
            <div class="space-y-4">
                <div class="flex">
                    <div class="w-32 text-gray-600">Code</div>
                    <div class="text-gray-900 font-medium">{{ $campus->code }}</div>
                </div>
                
                <div class="flex">
                    <div class="w-32 text-gray-600">Adresse</div>
                    <div class="text-gray-900">{{ $campus->address }}</div>
                </div>
                
                <div class="flex">
                    <div class="w-32 text-gray-600">Description</div>
                    <div class="text-gray-900">{{ $campus->description ?: 'Aucune description' }}</div>
                </div>
                
                <div class="flex">
                    <div class="w-32 text-gray-600">Localisation</div>
                    <div class="text-gray-900">
                        <div>Lat: {{ number_format($campus->latitude, 6) }}</div>
                        <div>Lng: {{ number_format($campus->longitude, 6) }}</div>
                    </div>
                </div>
                
                <div class="flex">
                    <div class="w-32 text-gray-600">Rayon</div>
                    <div class="text-gray-900">{{ $campus->radius }}m</div>
                </div>
                
                <div class="flex">
                    <div class="w-32 text-gray-600">Horaires</div>
                    <div class="text-gray-900">
                        <div>Entrée: {{ \Carbon\Carbon::parse($campus->start_time)->format('H:i') }}</div>
                        <div>Sortie: {{ \Carbon\Carbon::parse($campus->end_time)->format('H:i') }}</div>
                    </div>
                </div>
                
                <div class="flex">
                    <div class="w-32 text-gray-600">Tolérance retard</div>
                    <div class="text-gray-900">{{ $campus->late_tolerance }} minutes</div>
                </div>
                
                <div class="flex">
                    <div class="w-32 text-gray-600">Jours ouvrables</div>
                    <div class="text-gray-900">
                        {{ implode(', ', array_map(function($day) {
                            $days = [
                                'monday' => 'Lundi',
                                'tuesday' => 'Mardi',
                                'wednesday' => 'Mercredi',
                                'thursday' => 'Jeudi',
                                'friday' => 'Vendredi',
                                'saturday' => 'Samedi',
                                'sunday' => 'Dimanche'
                            ];
                            return $days[$day] ?? ucfirst($day);
                        }, is_string($campus->working_days) ? json_decode($campus->working_days, true) : $campus->working_days)) }}
                    </div>
                </div>
                
                <div class="flex">
                    <div class="w-32 text-gray-600">Statut</div>
                    <div class="text-gray-900">
                        <span class="px-2 py-1 rounded {{ $campus->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $campus->is_active ? 'Actif' : 'Inactif' }}
                        </span>
                    </div>
                </div>
                
                <div class="flex">
                    <div class="w-32 text-gray-600">Créé le</div>
                    <div class="text-gray-900">{{ $campus->created_at->format('d/m/Y H:i') }}</div>
                </div>
                
                <div class="flex">
                    <div class="w-32 text-gray-600">Mis à jour le</div>
                    <div class="text-gray-900">{{ $campus->updated_at->format('d/m/Y H:i') }}</div>
                </div>
            </div>
        </div>

        <!-- Carte -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Localisation sur la carte</h3>
            <div id="campus-map" class="h-64 w-full rounded-lg overflow-hidden" style="min-height: 300px;"></div>
        </div>
    </div>

    <!-- Employés affectés -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-4 border-b">
            <h3 class="text-lg font-medium text-gray-800">Employés affectés ({{ $campus->users_count }})</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Employé
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Rôle
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Type
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Campus Principal
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($campus->users as $user)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                        <span class="text-blue-600 font-bold">{{ substr($user->first_name, 0, 1) }}</span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $user->full_name }}</div>
                                    <div class="text-sm text-gray-500">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $user->role->display_name }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                {{ str_replace('_', ' ', ucfirst($user->employee_type)) }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                {{ $user->pivot->is_primary ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $user->pivot->is_primary ? 'Oui' : 'Non' }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                            Aucun employé affecté à ce campus
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pointages récents -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-4 border-b">
            <h3 class="text-lg font-medium text-gray-800">Pointages récents (10 derniers)</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Employé
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Type
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Date/Heure
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            En retard
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($recent_attendances as $attendance)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $attendance->user->full_name }}</div>
                            <div class="text-sm text-gray-500">{{ $attendance->user->email }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                {{ $attendance->type === 'check_in' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $attendance->type === 'check_in' ? 'Entrée' : 'Sortie' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $attendance->timestamp->format('d/m/Y H:i:s') }}</div>
                            <div class="text-xs text-gray-500">{{ $attendance->timestamp->diffForHumans() }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($attendance->is_late)
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                    Oui: {{ $attendance->late_minutes }} min
                                </span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Non
                                </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                            Aucun pointage récent pour ce campus
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    let mapHelper;

    // Initialiser la carte
    async function initCampusMap() {
        const location = {
            lat: {{ $campus->latitude }},
            lng: {{ $campus->longitude }}
        };

        // Initialiser MapHelper
        mapHelper = new MapHelper('campus-map', {
            lat: location.lat,
            lng: location.lng,
            zoom: 15
        });

        await mapHelper.init();

        // Ajouter le marqueur pour le campus
        mapHelper.addMarker(location.lat, location.lng, {
            title: '{{ $campus->name }}',
            popup: '<strong>{{ $campus->name }}</strong><br>{{ $campus->address }}'
        });

        // Dessiner le cercle pour le rayon
        mapHelper.addCircle(location.lat, location.lng, {{ $campus->radius }}, {
            strokeColor: '#4ade80',
            strokeOpacity: 0.8,
            strokeWeight: 2,
            fillColor: '#4ade80',
            fillOpacity: 0.2
        });
    }

    // Attendre que la page soit chargée
    window.addEventListener('load', initCampusMap);
</script>
@endpush