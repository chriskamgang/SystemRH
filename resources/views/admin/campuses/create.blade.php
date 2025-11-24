@extends('layouts.admin')

@section('title', 'Créer un Campus')
@section('page-title', 'Créer un nouveau Campus')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Créer un nouveau Campus</h2>
        <a href="{{ route('admin.campuses.index') }}" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
            <i class="fas fa-arrow-left mr-2"></i>
            Retour
        </a>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="{{ route('admin.campuses.store') }}">
            @csrf

            <div class="space-y-6">
                <!-- Informations générales -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Informations générales</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Nom du campus</label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-500 @enderror">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="code" class="block text-sm font-medium text-gray-700 mb-2">Code du campus</label>
                            <input type="text" name="code" id="code" value="{{ old('code') }}" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('code') border-red-500 @enderror">
                            <p class="mt-1 text-sm text-gray-500">Ex: CAM-SCI, CAM-LET, etc.</p>
                            @error('code')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label for="address" class="block text-sm font-medium text-gray-700 mb-2">Adresse</label>
                            <textarea name="address" id="address" rows="3" 
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('address') border-red-500 @enderror">{{ old('address') }}</textarea>
                            @error('address')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description (optionnel)</label>
                            <textarea name="description" id="description" rows="3" 
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('description') border-red-500 @enderror">{{ old('description') }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Géolocalisation -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Géolocalisation</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label for="latitude" class="block text-sm font-medium text-gray-700 mb-2">Latitude</label>
                            <input type="number" name="latitude" id="latitude" step="any" value="{{ old('latitude') }}" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('latitude') border-red-500 @enderror">
                            @error('latitude')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="longitude" class="block text-sm font-medium text-gray-700 mb-2">Longitude</label>
                            <input type="number" name="longitude" id="longitude" step="any" value="{{ old('longitude') }}" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('longitude') border-red-500 @enderror">
                            @error('longitude')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="radius" class="block text-sm font-medium text-gray-700 mb-2">Rayon (mètres)</label>
                            <select name="radius" id="radius" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('radius') border-red-500 @enderror">
                                <option value="50" {{ old('radius') == 50 ? 'selected' : '' }}>50m</option>
                                <option value="100" {{ old('radius') == 100 ? 'selected' : '' }}>100m</option>
                                <option value="150" {{ old('radius') == 150 ? 'selected' : '' }}>150m</option>
                                <option value="200" {{ old('radius') == 200 ? 'selected' : '' }}>200m</option>
                                <option value="300" {{ old('radius') == 300 ? 'selected' : '' }}>300m</option>
                                <option value="500" {{ old('radius') == 500 ? 'selected' : '' }}>500m</option>
                                <option value="1000" {{ old('radius') == 1000 ? 'selected' : '' }}>1000m</option>
                            </select>
                            @error('radius')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Carte interactive -->
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sélectionner sur la carte</label>
                        <div id="map" class="h-64 w-full rounded-lg border border-gray-300" style="min-height: 300px;"></div>
                    </div>
                </div>

                <!-- Horaires -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Horaires</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="start_time" class="block text-sm font-medium text-gray-700 mb-2">Heure d'entrée</label>
                            <input type="time" name="start_time" id="start_time" value="{{ old('start_time', '08:00') }}" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('start_time') border-red-500 @enderror">
                            @error('start_time')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="end_time" class="block text-sm font-medium text-gray-700 mb-2">Heure de sortie</label>
                            <input type="time" name="end_time" id="end_time" value="{{ old('end_time', '17:00') }}" 
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('end_time') border-red-500 @enderror">
                            @error('end_time')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="late_tolerance" class="block text-sm font-medium text-gray-700 mb-2">Tolérance retard (minutes)</label>
                            <input type="number" name="late_tolerance" id="late_tolerance" value="{{ old('late_tolerance', 15) }}" min="0" max="60"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('late_tolerance') border-red-500 @enderror">
                            @error('late_tolerance')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Jours ouvrables -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Jours ouvrables</h3>
                    
                    <div class="grid grid-cols-2 md:grid-cols-7 gap-4">
                        @php
                            $days = [
                                'monday' => 'Lundi',
                                'tuesday' => 'Mardi',
                                'wednesday' => 'Mercredi',
                                'thursday' => 'Jeudi',
                                'friday' => 'Vendredi',
                                'saturday' => 'Samedi',
                                'sunday' => 'Dimanche'
                            ];
                            $selectedDays = old('working_days', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday']);
                        @endphp
                        
                        @foreach($days as $day => $label)
                        <div class="flex items-center">
                            <input type="checkbox" 
                                   name="working_days[]" 
                                   value="{{ $day }}" 
                                   id="{{ $day }}"
                                   {{ in_array($day, $selectedDays) ? 'checked' : '' }}
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="{{ $day }}" class="ml-2 block text-sm text-gray-700">
                                {{ $label }}
                            </label>
                        </div>
                        @endforeach
                    </div>
                    
                    @error('working_days')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Statut -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Statut</h3>
                    
                    <div class="flex items-center">
                        <input type="checkbox" name="is_active" id="is_active" value="1" 
                               {{ old('is_active', true) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="is_active" class="ml-2 block text-sm text-gray-700">
                            Actif
                        </label>
                    </div>
                </div>

                <!-- Boutons -->
                <div class="flex justify-end gap-4 pt-6">
                    <a href="{{ route('admin.campuses.index') }}" class="px-6 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
                        Annuler
                    </a>
                    <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                        Créer le campus
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    let mapHelper;
    let marker;
    let circle;

    async function initMap() {
        // Centre approximatif pour une position par défaut (Bafoussam, Cameroun)
        const defaultLocation = { lat: 5.4781, lng: 10.4178 };

        // Initialiser le MapHelper
        mapHelper = new MapHelper('map', {
            lat: defaultLocation.lat,
            lng: defaultLocation.lng,
            zoom: 13
        });

        await mapHelper.init();

        // Ajouter un marqueur à la position par défaut
        marker = mapHelper.addMarker(defaultLocation.lat, defaultLocation.lng, {
            draggable: true,
            title: 'Glissez pour positionner le campus',
            popup: 'Position du campus',
            onDragEnd: (position) => {
                document.getElementById('latitude').value = position.lat.toFixed(6);
                document.getElementById('longitude').value = position.lng.toFixed(6);
                updateCircle(position.lat, position.lng);
            }
        });

        // Écouter le clic sur la carte
        mapHelper.onClick((position) => {
            // Remove old marker and create new one
            mapHelper.clearMarkers();
            marker = mapHelper.addMarker(position.lat, position.lng, {
                draggable: true,
                title: 'Glissez pour positionner le campus',
                popup: 'Position du campus',
                onDragEnd: (newPos) => {
                    document.getElementById('latitude').value = newPos.lat.toFixed(6);
                    document.getElementById('longitude').value = newPos.lng.toFixed(6);
                    updateCircle(newPos.lat, newPos.lng);
                }
            });

            document.getElementById('latitude').value = position.lat.toFixed(6);
            document.getElementById('longitude').value = position.lng.toFixed(6);
            updateCircle(position.lat, position.lng);
        });

        // Initialiser le cercle avec la valeur du rayon
        updateCircle(defaultLocation.lat, defaultLocation.lng);
    }

    function updateCircle(lat, lng) {
        // Clear existing circle
        mapHelper.clearCircles();

        const radius = parseFloat(document.getElementById('radius').value) || 100;
        circle = mapHelper.addCircle(lat, lng, radius, {
            strokeColor: '#4ade80',
            strokeOpacity: 0.8,
            strokeWeight: 2,
            fillColor: '#4ade80',
            fillOpacity: 0.2
        });
    }

    // Mettre à jour le cercle quand le rayon change
    document.getElementById('radius').addEventListener('change', function() {
        const lat = parseFloat(document.getElementById('latitude').value);
        const lng = parseFloat(document.getElementById('longitude').value);
        if (!isNaN(lat) && !isNaN(lng)) {
            updateCircle(lat, lng);
        }
    });

    // Gérer l'événement de chargement de la page
    window.addEventListener('load', initMap);
</script>
@endpush