@extends('layouts.admin')

@section('title', 'Carte en temps réel')
@section('page-title', 'Carte en temps réel des Présences')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Carte en temps réel</h2>
            <p class="text-gray-600 mt-1">Visualisation des employés présents sur les campus</p>
        </div>
        <button id="refreshBtn" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
            <i class="fas fa-sync-alt mr-2"></i>
            Actualiser
        </button>
    </div>

    <!-- Statistiques rapides -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-600">Campus actifs</div>
            <div class="text-2xl font-bold text-gray-800">{{ $campuses->count() }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-600">Sur site aujourd'hui</div>
            <div class="text-2xl font-bold text-gray-800">{{ $activeCheckIns->count() }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-600">Retards actuels</div>
            <div class="text-2xl font-bold text-red-600">{{ $activeCheckIns->where('is_late', true)->count() }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-sm text-gray-600">Dernière mise à jour</div>
            <div class="text-2xl font-bold text-gray-800" id="lastUpdate">{{ now()->format('H:i:s') }}</div>
        </div>
    </div>

    <!-- Carte et liste -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Carte Google Maps -->
        <div class="lg:col-span-2 bg-white rounded-lg shadow overflow-hidden">
            <div id="map" class="h-96 w-full" style="min-height: 400px;"></div>
        </div>

        <!-- Liste des présences actives -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-4 border-b">
                <h3 class="text-lg font-medium text-gray-800">
                    Présences actives ({{ $activeCheckIns->count() }})
                </h3>
            </div>
            <div class="overflow-y-auto max-h-96">
                @forelse($activeCheckIns as $attendance)
                <div class="p-4 border-b hover:bg-gray-50 {{ $attendance->is_late ? 'bg-red-50' : 'bg-white' }}">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                            <span class="text-blue-600 font-bold">{{ substr($attendance->user->first_name, 0, 1) }}</span>
                        </div>
                        <div class="ml-3 flex-1">
                            <div class="flex justify-between">
                                <div class="text-sm font-medium text-gray-900">{{ $attendance->user->full_name }}</div>
                                <div class="text-xs text-gray-500">{{ $attendance->timestamp->format('H:i') }}</div>
                            </div>
                            <div class="text-sm text-gray-500">{{ $attendance->campus->name }}</div>
                            @if($attendance->is_late)
                            <div class="text-xs text-red-600 mt-1">
                                <i class="fas fa-clock mr-1"></i>
                                Retard: {{ $attendance->late_minutes }} min
                            </div>
                            @endif
                        </div>
                        <div class="text-right">
                            <div class="text-xs text-gray-500">
                                Lat: {{ number_format($attendance->latitude, 4) }}
                            </div>
                            <div class="text-xs text-gray-500">
                                Lng: {{ number_format($attendance->longitude, 4) }}
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-users text-4xl mb-4"></i>
                    <p>Aucun employé actuellement présent</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Tableau des campus -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-4 border-b">
            <h3 class="text-lg font-medium text-gray-800">Campus actifs</h3>
        </div>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Campus
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Localisation
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Présents
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Retards
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Détails
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($campuses as $campus)
                    @php
                        $campusActiveCheckIns = $activeCheckIns->where('campus_id', $campus->id);
                        $lateCount = $campusActiveCheckIns->where('is_late', true)->count();
                    @endphp
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                                        <i class="fas fa-building text-green-600"></i>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $campus->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $campus->code }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ number_format($campus->latitude, 6) }}, {{ number_format($campus->longitude, 6) }}</div>
                            <div class="text-sm text-gray-500">Rayon: {{ $campus->radius }}m</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $campusActiveCheckIns->count() }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-red-600">{{ $lateCount }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="#" class="text-blue-600 hover:text-blue-900" onclick="centerOnCampus({{ $campus->latitude }}, {{ $campus->longitude }})">
                                <i class="fas fa-map-marker-alt"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                            Aucun campus actif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script>
    let mapHelper;
    let campusMarkers = [];
    let userMarkers = [];

    async function initMap() {
        // Centre approximatif du Cameroun (Bafoussam)
        const center = { lat: 5.4781, lng: 10.4178 };

        // Initialiser MapHelper
        mapHelper = new MapHelper('map', {
            lat: center.lat,
            lng: center.lng,
            zoom: 8
        });

        await mapHelper.init();

        // Ajouter les campus comme marqueurs
        @foreach($campuses as $campus)
        // Marker pour le campus
        const campusMarker = mapHelper.addMarker(
            {{ $campus->latitude }},
            {{ $campus->longitude }},
            {
                title: '{{ $campus->name }}',
                popup: `<strong>{{ $campus->name }}</strong><br>{{ $campus->code }}`
            }
        );
        campusMarkers.push(campusMarker);

        // Dessiner le rayon du campus
        mapHelper.addCircle(
            {{ $campus->latitude }},
            {{ $campus->longitude }},
            {{ $campus->radius }},
            {
                strokeColor: '#4ade80',
                strokeOpacity: 0.5,
                strokeWeight: 1,
                fillColor: '#4ade80',
                fillOpacity: 0.1
            }
        );
        @endforeach

        // Ajouter les employés présents comme marqueurs
        @foreach($activeCheckIns as $attendance)
        const userMarker = mapHelper.addMarker(
            {{ $attendance->latitude }},
            {{ $attendance->longitude }},
            {
                title: '{{ $attendance->user->full_name }} - {{ $attendance->campus->name }}',
                popup: `
                    <div style="padding: 8px;">
                        <h3 style="font-weight: bold; margin-bottom: 4px;">{{ $attendance->user->full_name }}</h3>
                        <p style="margin: 2px 0;">{{ $attendance->campus->name }}</p>
                        <p style="margin: 2px 0; font-size: 0.875rem;">{{ $attendance->timestamp->format('H:i') }}</p>
                        @if($attendance->is_late)
                        <p style="margin: 2px 0; color: #ef4444; font-size: 0.875rem;">Retard: {{ $attendance->late_minutes }} min</p>
                        @endif
                    </div>
                `
            }
        );
        userMarkers.push(userMarker);
        @endforeach
    }

    function centerOnCampus(lat, lng) {
        mapHelper.setCenter(lat, lng, 15);
        return false; // Prevent default link behavior
    }

    function refreshData() {
        document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString();

        fetch('{{ route('admin.api.active-checkins') }}')
            .then(response => response.json())
            .then(data => {
                // Ici, vous pourriez mettre à jour les données en temps réel
                // Pour l'instant, on recharge la page
                location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }

    // Initialiser la carte quand la page est chargée
    window.addEventListener('load', initMap);

    // Actualiser toutes les 30 secondes
    setInterval(refreshData, 30000);

    // Gérer le clic sur le bouton d'actualisation
    document.getElementById('refreshBtn').addEventListener('click', refreshData);
</script>
@endpush