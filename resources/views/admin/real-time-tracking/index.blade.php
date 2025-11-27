@extends('layouts.admin')

@section('title', 'Suivi en Temps Réel')

@section('page-title', 'Suivi en Temps Réel')

@section('content')
<div class="space-y-4">
    <!-- Statistiques en haut -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-blue-500 text-white rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm">Employés Connectés</p>
                    <p class="text-3xl font-bold" id="stat-active">{{ $stats['total_active'] }}</p>
                </div>
                <i class="fas fa-users text-4xl text-blue-200"></i>
            </div>
        </div>

        <div class="bg-green-500 text-white rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm">Check-in Actifs</p>
                    <p class="text-3xl font-bold" id="stat-checked-in">{{ $stats['total_checked_in'] }}</p>
                </div>
                <i class="fas fa-check-circle text-4xl text-green-200"></i>
            </div>
        </div>

        <div class="bg-purple-500 text-white rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm">Dans les Zones</p>
                    <p class="text-3xl font-bold" id="stat-in-zone">{{ $stats['total_in_zone'] }}</p>
                </div>
                <i class="fas fa-map-marker-alt text-4xl text-purple-200"></i>
            </div>
        </div>

        <div class="bg-red-500 text-white rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-red-100 text-sm">Hors des Zones</p>
                    <p class="text-3xl font-bold" id="stat-out-zone">{{ $stats['total_out_of_zone'] }}</p>
                </div>
                <i class="fas fa-exclamation-triangle text-4xl text-red-200"></i>
            </div>
        </div>
    </div>

    <!-- Filtres et contrôles -->
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex flex-wrap items-center gap-4">
            <!-- Filtre type -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Afficher</label>
                <select id="filter-type" class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="all">Tous les employés connectés</option>
                    <option value="checked_in">Seulement check-in actifs</option>
                </select>
            </div>

            <!-- Filtre campus -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Campus</label>
                <select id="filter-campus" class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Tous les campus</option>
                    @foreach($campuses as $campus)
                        <option value="{{ $campus->id }}">{{ $campus->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Filtre département -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Département</label>
                <select id="filter-department" class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Tous les départements</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Auto-refresh toggle -->
            <div class="ml-auto">
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" id="auto-refresh-toggle" checked class="sr-only peer">
                    <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    <span class="ms-3 text-sm font-medium text-gray-700">
                        <i class="fas fa-sync-alt"></i> Auto-refresh (10s)
                    </span>
                </label>
            </div>

            <!-- Dernière mise à jour -->
            <div class="text-sm text-gray-600">
                <i class="fas fa-clock"></i>
                Dernière mise à jour : <span id="last-update" class="font-semibold">-</span>
            </div>
        </div>
    </div>

    <!-- Légende -->
    <div class="bg-white rounded-lg shadow p-4">
        <h3 class="text-sm font-semibold text-gray-700 mb-2">
            <i class="fas fa-info-circle"></i> Légende
        </h3>
        <div class="flex flex-wrap gap-4 text-sm">
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 rounded-full bg-green-500"></div>
                <span class="text-gray-600">Check-in actif (dans la zone)</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 rounded-full bg-blue-500"></div>
                <span class="text-gray-600">Connecté (dans la zone, pas de check-in)</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 rounded-full bg-red-500"></div>
                <span class="text-gray-600">Hors zone</span>
            </div>
        </div>
    </div>

    <!-- Carte en plein écran -->
    <div class="bg-white rounded-lg shadow overflow-hidden" style="height: calc(100vh - 450px); min-height: 500px;">
        <div id="map" class="w-full h-full"></div>
    </div>
</div>

<!-- Loading overlay -->
<div id="loading-overlay" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-6 flex items-center gap-3">
        <i class="fas fa-spinner fa-spin text-2xl text-blue-500"></i>
        <span class="text-gray-700">Chargement des positions...</span>
    </div>
</div>

@push('scripts')
<script>
// Configuration globale
const REFRESH_INTERVAL = 10000; // 10 secondes
const API_URL = '{{ route('admin.real-time-tracking.get-locations') }}';
const CAMPUSES = @json($campuses);

// Variables globales
let map;
let markers = {};
let campusCircles = {};
let autoRefreshInterval;
let autoRefreshEnabled = true;

// Initialiser la carte
function initMap() {
    // Centrer sur Libreville, Gabon
    const centerLat = 0.4162;
    const centerLng = 9.4673;

    // Créer la carte
    map = L.map('map').setView([centerLat, centerLng], 12);

    // Ajouter la couche OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19,
    }).addTo(map);

    // Dessiner les zones campus
    CAMPUSES.forEach(campus => {
        const circle = L.circle([campus.latitude, campus.longitude], {
            color: getCampusColor(campus.id),
            fillColor: getCampusColor(campus.id),
            fillOpacity: 0.1,
            radius: campus.radius
        }).addTo(map);

        // Popup pour le campus
        circle.bindPopup(`
            <div class="p-2">
                <h3 class="font-bold text-lg">${campus.name}</h3>
                <p class="text-sm text-gray-600">${campus.code}</p>
                <p class="text-xs text-gray-500 mt-1">Rayon: ${campus.radius}m</p>
            </div>
        `);

        campusCircles[campus.id] = circle;
    });

    console.log('✓ Carte initialisée');
}

// Obtenir la couleur du campus
function getCampusColor(campusId) {
    const colors = {
        1: '#3B82F6', // Bleu
        2: '#10B981', // Vert
        3: '#F59E0B', // Orange
        4: '#EF4444', // Rouge
        5: '#8B5CF6', // Violet
        6: '#EC4899', // Rose
    };
    return colors[campusId] || '#6B7280';
}

// Obtenir l'icône du marqueur selon la couleur
function getMarkerIcon(color) {
    const colors = {
        'green': '#10B981',
        'blue': '#3B82F6',
        'red': '#EF4444',
    };

    return L.divIcon({
        className: 'custom-marker',
        html: `
            <div style="
                background-color: ${colors[color]};
                width: 30px;
                height: 30px;
                border-radius: 50%;
                border: 3px solid white;
                box-shadow: 0 2px 5px rgba(0,0,0,0.3);
                display: flex;
                align-items: center;
                justify-content: center;
            ">
                <i class="fas fa-user text-white" style="font-size: 14px;"></i>
            </div>
        `,
        iconSize: [30, 30],
        iconAnchor: [15, 15],
    });
}

// Charger et afficher les positions
async function loadLocations() {
    try {
        const filterType = document.getElementById('filter-type').value;
        const campusId = document.getElementById('filter-campus').value;
        const departmentId = document.getElementById('filter-department').value;

        const url = new URL(API_URL);
        url.searchParams.append('filter', filterType);
        if (campusId) url.searchParams.append('campus_id', campusId);
        if (departmentId) url.searchParams.append('department_id', departmentId);

        const response = await fetch(url);
        const result = await response.json();

        if (result.success) {
            updateMarkers(result.data.users);
            updateStats(result.data);
            updateLastUpdate();
        }
    } catch (error) {
        console.error('Erreur lors du chargement des positions:', error);
    }
}

// Mettre à jour les marqueurs sur la carte
function updateMarkers(users) {
    // Retirer les marqueurs qui n'existent plus
    Object.keys(markers).forEach(userId => {
        if (!users.find(u => u.user.id == userId)) {
            map.removeLayer(markers[userId]);
            delete markers[userId];
        }
    });

    // Ajouter ou mettre à jour les marqueurs
    users.forEach(user => {
        const userId = user.user.id;
        const position = [user.position.latitude, user.position.longitude];

        if (markers[userId]) {
            // Mettre à jour la position
            markers[userId].setLatLng(position);
            markers[userId].setIcon(getMarkerIcon(user.marker_color));
        } else {
            // Créer un nouveau marqueur
            const marker = L.marker(position, {
                icon: getMarkerIcon(user.marker_color)
            }).addTo(map);

            // Popup avec infos utilisateur
            marker.bindPopup(`
                <div class="p-3 min-w-[250px]">
                    <h3 class="font-bold text-lg text-gray-800">${user.user.name}</h3>
                    <div class="mt-2 space-y-1 text-sm">
                        <p class="text-gray-600">
                            <i class="fas fa-briefcase w-4"></i>
                            ${user.user.employee_type_label}
                        </p>
                        ${user.user.department ? `
                            <p class="text-gray-600">
                                <i class="fas fa-building w-4"></i>
                                ${user.user.department}
                            </p>
                        ` : ''}
                        ${user.campus ? `
                            <p class="text-gray-600">
                                <i class="fas fa-map-marker-alt w-4"></i>
                                ${user.campus.name}
                            </p>
                        ` : `
                            <p class="text-red-600">
                                <i class="fas fa-exclamation-triangle w-4"></i>
                                Hors zone
                            </p>
                        `}
                        <p class="text-gray-500 text-xs">
                            <i class="fas fa-clock w-4"></i>
                            Dernière position: ${user.last_updated}
                        </p>
                    </div>
                </div>
            `);

            markers[userId] = marker;
        }
    });

    console.log(`✓ ${users.length} employés affichés sur la carte`);
}

// Mettre à jour les statistiques
function updateStats(data) {
    // Les stats sont déjà calculées côté serveur
    // On pourrait les récupérer via une API séparée, mais pour l'instant
    // on compte juste les utilisateurs retournés
    document.getElementById('stat-active').textContent = data.total;
}

// Mettre à jour l'heure de dernière mise à jour
function updateLastUpdate() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('fr-FR');
    document.getElementById('last-update').textContent = timeString;
}

// Démarrer l'auto-refresh
function startAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
    }

    autoRefreshInterval = setInterval(() => {
        if (autoRefreshEnabled) {
            loadLocations();
        }
    }, REFRESH_INTERVAL);

    console.log('✓ Auto-refresh activé (10s)');
}

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
    // Initialiser la carte
    initMap();

    // Charger les positions initiales
    loadLocations();

    // Démarrer l'auto-refresh
    startAutoRefresh();

    // Event listeners pour les filtres
    document.getElementById('filter-type').addEventListener('change', loadLocations);
    document.getElementById('filter-campus').addEventListener('change', loadLocations);
    document.getElementById('filter-department').addEventListener('change', loadLocations);

    // Toggle auto-refresh
    document.getElementById('auto-refresh-toggle').addEventListener('change', (e) => {
        autoRefreshEnabled = e.target.checked;
        console.log('Auto-refresh:', autoRefreshEnabled ? 'ON' : 'OFF');
    });

    console.log('✓ Suivi en temps réel initialisé');
});
</script>
@endpush
@endsection
