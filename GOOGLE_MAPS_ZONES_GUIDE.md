# 🗺️ Guide Google Maps - Zones Géographiques (Cercle & Carré)

## Configuration pour le Dashboard Admin

### 1. Vue Création/Édition Campus avec Zones

Créez la vue `resources/views/admin/campuses/form.blade.php` :

```html
@extends('layouts.admin')

@section('title', isset($campus) ? 'Modifier Campus' : 'Nouveau Campus')
@section('page-title', isset($campus) ? 'Modifier Campus' : 'Créer un Campus')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow-lg p-8">
        <form method="POST" action="{{ isset($campus) ? route('admin.campuses.update', $campus->id) : route('admin.campuses.store') }}">
            @csrf
            @if(isset($campus))
                @method('PUT')
            @endif

            <!-- Informations du Campus -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
                    <i class="fas fa-building mr-2"></i> Informations du Campus
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nom du Campus *</label>
                        <input type="text" name="name" value="{{ old('name', $campus->name ?? '') }}" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Type de Zone *</label>
                        <select id="zone_type" name="zone_type"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="circle" {{ old('zone_type', $campus->zone_type ?? 'circle') == 'circle' ? 'selected' : '' }}>Cercle</option>
                            <option value="rectangle" {{ old('zone_type', $campus->zone_type ?? '') == 'rectangle' ? 'selected' : '' }}>Rectangle/Carré</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Adresse</label>
                        <input type="text" id="address" name="address" value="{{ old('address', $campus->address ?? '') }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Saisir une adresse pour rechercher...">
                    </div>
                </div>
            </div>

            <!-- Carte Interactive -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
                    <i class="fas fa-map-marked-alt mr-2"></i> Définir la Zone sur la Carte
                </h3>

                <!-- Instructions -->
                <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <p class="text-sm text-blue-800">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Instructions :</strong><br>
                        - <strong>Cercle</strong> : Cliquez sur la carte pour placer le centre, puis ajustez le rayon ci-dessous<br>
                        - <strong>Rectangle</strong> : Cliquez sur la carte pour les deux coins opposés du rectangle
                    </p>
                </div>

                <!-- Carte -->
                <div id="map" class="w-full rounded-lg border border-gray-300" style="height: 500px;"></div>

                <!-- Champs cachés pour les coordonnées -->
                <input type="hidden" id="latitude" name="latitude" value="{{ old('latitude', $campus->latitude ?? '') }}">
                <input type="hidden" id="longitude" name="longitude" value="{{ old('longitude', $campus->longitude ?? '') }}">
                <input type="hidden" id="bounds_data" name="bounds_data" value="{{ old('bounds_data', $campus->bounds_data ?? '') }}">

                <!-- Rayon pour cercle -->
                <div id="radius_control" class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Rayon de la zone (mètres)
                    </label>
                    <input type="number" id="radius" name="radius"
                        value="{{ old('radius', $campus->radius ?? 100) }}"
                        min="10" max="5000" step="10"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Rayon actuel : <span id="radius_display">{{ old('radius', $campus->radius ?? 100) }}</span> mètres</p>
                </div>
            </div>

            <!-- Horaires -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
                    <i class="fas fa-clock mr-2"></i> Horaires de Travail
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Heure d'arrivée</label>
                        <input type="time" name="check_in_time" value="{{ old('check_in_time', $campus->check_in_time ?? '08:00') }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Heure de départ</label>
                        <input type="time" name="check_out_time" value="{{ old('check_out_time', $campus->check_out_time ?? '17:00') }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <!-- Statut -->
            <div class="mb-8">
                <label class="inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="is_active" value="1"
                        {{ old('is_active', $campus->is_active ?? true) ? 'checked' : '' }}
                        class="form-checkbox h-5 w-5 text-blue-600 rounded focus:ring-2 focus:ring-blue-500">
                    <span class="ml-2 text-gray-700">Campus actif</span>
                </label>
            </div>

            <!-- Boutons -->
            <div class="flex justify-end gap-4 pt-6 border-t">
                <a href="{{ route('admin.campuses.index') }}" class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition">
                    <i class="fas fa-times mr-2"></i> Annuler
                </a>
                <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                    <i class="fas fa-save mr-2"></i> {{ isset($campus) ? 'Mettre à jour' : 'Créer le campus' }}
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
let map;
let marker;
let circle;
let rectangle;
let zoneType = 'circle';
let drawingMode = false;
let rectanglePoints = [];

function initMap() {
    // Centre par défaut (Libreville, Gabon)
    const defaultCenter = {
        lat: {{ old('latitude', $campus->latitude ?? 0.4162) }},
        lng: {{ old('longitude', $campus->longitude ?? 9.4673) }}
    };

    // Initialiser la carte
    map = new google.maps.Map(document.getElementById("map"), {
        zoom: 15,
        center: defaultCenter,
        mapTypeControl: true,
        streetViewControl: false,
    });

    // Recherche d'adresse
    const input = document.getElementById('address');
    const autocomplete = new google.maps.places.Autocomplete(input);
    autocomplete.bindTo('bounds', map);

    autocomplete.addListener('place_changed', function() {
        const place = autocomplete.getPlace();
        if (!place.geometry) return;

        if (place.geometry.viewport) {
            map.fitBounds(place.geometry.viewport);
        } else {
            map.setCenter(place.geometry.location);
            map.setZoom(17);
        }

        placeMarker(place.geometry.location);
    });

    // Charger le type de zone
    zoneType = document.getElementById('zone_type').value;

    // Charger la zone existante
    @if(isset($campus) && $campus->latitude && $campus->longitude)
        const existingCenter = new google.maps.LatLng({{ $campus->latitude }}, {{ $campus->longitude }});

        @if(($campus->zone_type ?? 'circle') == 'circle')
            placeMarker(existingCenter);
        @else
            // Charger le rectangle
            @if($campus->bounds_data)
                const boundsData = {!! json_encode($campus->bounds_data) !!};
                if (boundsData) {
                    createRectangle(
                        new google.maps.LatLng(boundsData.north, boundsData.west),
                        new google.maps.LatLng(boundsData.south, boundsData.east)
                    );
                }
            @endif
        @endif
    @endif

    // Événement de clic sur la carte
    map.addListener('click', function(e) {
        if (zoneType === 'circle') {
            placeMarker(e.latLng);
        } else {
            handleRectangleClick(e.latLng);
        }
    });

    // Changement de type de zone
    document.getElementById('zone_type').addEventListener('change', function() {
        zoneType = this.value;
        clearShapes();
        updateRadiusControl();
        rectanglePoints = [];
    });

    // Changement de rayon
    document.getElementById('radius').addEventListener('input', function() {
        if (circle) {
            circle.setRadius(parseInt(this.value));
        }
        document.getElementById('radius_display').textContent = this.value;
    });

    updateRadiusControl();
}

function placeMarker(location) {
    // Supprimer l'ancien marqueur
    if (marker) {
        marker.setMap(null);
    }
    if (circle) {
        circle.setMap(null);
    }

    // Créer le marqueur
    marker = new google.maps.Marker({
        position: location,
        map: map,
        draggable: true,
        title: 'Centre du campus'
    });

    // Créer le cercle
    const radius = parseInt(document.getElementById('radius').value);
    circle = new google.maps.Circle({
        map: map,
        center: location,
        radius: radius,
        fillColor: '#4285F4',
        fillOpacity: 0.2,
        strokeColor: '#4285F4',
        strokeOpacity: 0.8,
        strokeWeight: 2,
        draggable: false,
        editable: false
    });

    // Mettre à jour les champs
    updateInputs(location);

    // Événement de déplacement du marqueur
    marker.addListener('drag', function() {
        circle.setCenter(marker.getPosition());
        updateInputs(marker.getPosition());
    });
}

function handleRectangleClick(location) {
    rectanglePoints.push(location);

    if (rectanglePoints.length === 1) {
        // Premier point - placer un marqueur temporaire
        if (marker) marker.setMap(null);
        marker = new google.maps.Marker({
            position: location,
            map: map,
            label: '1'
        });
    } else if (rectanglePoints.length === 2) {
        // Deuxième point - créer le rectangle
        createRectangle(rectanglePoints[0], rectanglePoints[1]);
        rectanglePoints = [];
        if (marker) marker.setMap(null);
    }
}

function createRectangle(point1, point2) {
    // Supprimer l'ancien rectangle
    if (rectangle) {
        rectangle.setMap(null);
    }

    // Calculer les bounds
    const bounds = new google.maps.LatLngBounds();
    bounds.extend(point1);
    bounds.extend(point2);

    // Créer le rectangle
    rectangle = new google.maps.Rectangle({
        map: map,
        bounds: bounds,
        fillColor: '#4285F4',
        fillOpacity: 0.2,
        strokeColor: '#4285F4',
        strokeOpacity: 0.8,
        strokeWeight: 2,
        editable: true,
        draggable: true
    });

    // Centrer sur le rectangle
    const center = bounds.getCenter();
    updateInputs(center, bounds);

    // Événements pour le rectangle
    rectangle.addListener('bounds_changed', function() {
        const newBounds = rectangle.getBounds();
        const newCenter = newBounds.getCenter();
        updateInputs(newCenter, newBounds);
    });
}

function updateInputs(location, bounds = null) {
    document.getElementById('latitude').value = location.lat();
    document.getElementById('longitude').value = location.lng();

    if (bounds) {
        // Sauvegarder les bounds pour le rectangle
        const boundsData = {
            north: bounds.getNorthEast().lat(),
            south: bounds.getSouthWest().lat(),
            east: bounds.getNorthEast().lng(),
            west: bounds.getSouthWest().lng()
        };
        document.getElementById('bounds_data').value = JSON.stringify(boundsData);
    } else {
        document.getElementById('bounds_data').value = '';
    }
}

function clearShapes() {
    if (marker) marker.setMap(null);
    if (circle) circle.setMap(null);
    if (rectangle) rectangle.setMap(null);
    marker = null;
    circle = null;
    rectangle = null;
}

function updateRadiusControl() {
    const radiusControl = document.getElementById('radius_control');
    if (zoneType === 'circle') {
        radiusControl.style.display = 'block';
    } else {
        radiusControl.style.display = 'none';
    }
}

// Charger la carte au chargement de la page
window.addEventListener('load', initMap);
</script>
@endpush
@endsection
```

### 2. Migration pour la Table Campuses

Ajoutez les colonnes nécessaires :

```php
php artisan make:migration add_zone_fields_to_campuses_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campuses', function (Blueprint $table) {
            $table->enum('zone_type', ['circle', 'rectangle'])->default('circle')->after('longitude');
            $table->json('bounds_data')->nullable()->after('zone_type');
        });
    }

    public function down(): void
    {
        Schema::table('campuses', function (Blueprint $table) {
            $table->dropColumn(['zone_type', 'bounds_data']);
        });
    }
};
```

### 3. Modèle Campus

Ajoutez les champs à `$fillable` et `$casts` :

```php
protected $fillable = [
    'name',
    'address',
    'latitude',
    'longitude',
    'zone_type',      // Nouveau
    'bounds_data',    // Nouveau
    'radius',
    'check_in_time',
    'check_out_time',
    'is_active',
];

protected $casts = [
    'bounds_data' => 'array',  // Nouveau
    'is_active' => 'boolean',
];
```

### 4. Vérification de Géofencing dans le Controller

Dans `AttendanceController.php`, ajoutez la logique pour les deux types de zones :

```php
private function isWithinGeofence($userLat, $userLng, $campus)
{
    if ($campus->zone_type === 'circle') {
        // Vérification par cercle (existant)
        $distance = $this->calculateDistance(
            $userLat, $userLng,
            $campus->latitude, $campus->longitude
        );
        return $distance <= $campus->radius;
    } else {
        // Vérification par rectangle
        $bounds = $campus->bounds_data;
        if (!$bounds) return false;

        return $userLat >= $bounds['south'] &&
               $userLat <= $bounds['north'] &&
               $userLng >= $bounds['west'] &&
               $userLng <= $bounds['east'];
    }
}
```

---

## Résumé des Fonctionnalités

✅ **Deux types de zones** : Cercle et Rectangle/Carré
✅ **Interface interactive** avec Google Maps
✅ **Marqueurs déplaçables** pour le cercle
✅ **Rectangle éditable et déplaçable** après création
✅ **Rayon ajustable** pour les cercles
✅ **Recherche d'adresse** avec autocomplete
✅ **Sauvegarde des bounds** pour les rectangles
✅ **Vérification géographique** pour les deux types dans l'API

Maintenant vous pouvez tracer des zones circulaires OU rectangulaires pour vos campus !
