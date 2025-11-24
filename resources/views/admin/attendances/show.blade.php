@extends('layouts.admin')

@section('title', 'Détails de la Présence')
@section('page-title', 'Détails de la Présence')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Détails de la Présence</h2>
            <p class="text-gray-600 mt-1">Informations complètes sur le pointage</p>
        </div>
        <a href="{{ route('admin.attendances.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg transition">
            <i class="fas fa-arrow-left mr-2"></i>
            Retour
        </a>
    </div>

    <!-- Carte principale -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-6">
            <!-- Informations de base -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Informations du pointage</h3>
                    
                    <div class="space-y-3">
                        <div class="flex">
                            <div class="w-32 text-gray-600">ID</div>
                            <div class="text-gray-900">#{{ $attendance->id }}</div>
                        </div>
                        <div class="flex">
                            <div class="w-32 text-gray-600">Type</div>
                            <div class="text-gray-900">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    {{ $attendance->type === 'check_in' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $attendance->type === 'check_in' ? 'Pointage d\'entrée' : 'Pointage de sortie' }}
                                </span>
                            </div>
                        </div>
                        <div class="flex">
                            <div class="w-32 text-gray-600">Date et Heure</div>
                            <div class="text-gray-900">{{ $attendance->timestamp->format('d/m/Y H:i:s') }}</div>
                        </div>
                        <div class="flex">
                            <div class="w-32 text-gray-600">Statut</div>
                            <div class="text-gray-900">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    {{ ucfirst($attendance->status) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Employé et Campus</h3>
                    
                    <div class="space-y-3">
                        <div class="flex">
                            <div class="w-32 text-gray-600">Employé</div>
                            <div class="text-gray-900">
                                {{ $attendance->user->full_name }}
                                <div class="text-sm text-gray-500">{{ $attendance->user->email }}</div>
                            </div>
                        </div>
                        <div class="flex">
                            <div class="w-32 text-gray-600">Campus</div>
                            <div class="text-gray-900">
                                {{ $attendance->campus->name }}
                                <div class="text-sm text-gray-500">{{ $attendance->campus->code }}</div>
                            </div>
                        </div>
                        
                        @if($attendance->is_late)
                        <div class="flex">
                            <div class="w-32 text-gray-600">Retard</div>
                            <div class="text-red-600">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                    Oui: {{ $attendance->late_minutes }} minutes
                                </span>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Géolocalisation -->
            <div class="border-t pt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Géolocalisation</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-3">
                        <div class="flex">
                            <div class="w-32 text-gray-600">Latitude</div>
                            <div class="text-gray-900">{{ number_format($attendance->latitude, 6) }}</div>
                        </div>
                        <div class="flex">
                            <div class="w-32 text-gray-600">Longitude</div>
                            <div class="text-gray-900">{{ number_format($attendance->longitude, 6) }}</div>
                        </div>
                        @if($attendance->accuracy)
                        <div class="flex">
                            <div class="w-32 text-gray-600">Précision</div>
                            <div class="text-gray-900">{{ $attendance->accuracy }}m</div>
                        </div>
                        @endif
                    </div>
                    
                    <div>
                        @if($attendance->campus)
                        <div class="space-y-3">
                            <div class="flex">
                                <div class="w-32 text-gray-600">Campus</div>
                                <div class="text-gray-900">
                                    {{ $attendance->campus->name }}
                                    <div class="text-sm text-gray-500">
                                        Rayon: {{ $attendance->campus->radius }}m
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Calculer la distance entre le pointage et le centre du campus -->
                            @php
                                $earthRadius = 6371000; // en mètres
                                $latFrom = deg2rad($attendance->campus->latitude);
                                $lonFrom = deg2rad($attendance->campus->longitude);
                                $latTo = deg2rad($attendance->latitude);
                                $lonTo = deg2rad($attendance->longitude);

                                $latDelta = $latTo - $latFrom;
                                $lonDelta = $lonTo - $lonFrom;

                                $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
                                    cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

                                $distance = $angle * $earthRadius;
                            @endphp
                            
                            <div class="flex">
                                <div class="w-32 text-gray-600">Distance au centre</div>
                                <div class="text-gray-900">{{ number_format($distance, 2) }}m</div>
                            </div>
                            
                            <div class="flex">
                                <div class="w-32 text-gray-600">Dans la zone</div>
                                <div class="text-gray-900">
                                    @if($distance <= $attendance->campus->radius)
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            Oui
                                        </span>
                                    @else
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                            Non
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Informations complémentaires -->
            @if($attendance->device_info || $attendance->notes)
            <div class="border-t pt-6 mt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Informations complémentaires</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @if($attendance->device_info)
                    <div>
                        <h4 class="font-medium text-gray-700 mb-2">Informations appareil</h4>
                        <pre class="text-sm bg-gray-100 p-3 rounded">{{ json_encode($attendance->device_info, JSON_PRETTY_PRINT) }}</pre>
                    </div>
                    @endif
                    
                    @if($attendance->notes)
                    <div>
                        <h4 class="font-medium text-gray-700 mb-2">Notes</h4>
                        <div class="bg-gray-100 p-3 rounded">{{ $attendance->notes }}</div>
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Pointage associé -->
    @if($relatedCheckin || $relatedCheckout)
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">
                @if($attendance->type === 'check_in')
                    Pointage de sortie associé
                @else
                    Pointage d'entrée associé
                @endif
            </h3>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Type
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date et Heure
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Géolocalisation
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Durée
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @if($attendance->type === 'check_in' && $relatedCheckout)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                    Sortie
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $relatedCheckout->timestamp->format('d/m/Y H:i:s') }}</div>
                                <div class="text-xs text-gray-500">{{ $relatedCheckout->timestamp->diffForHumans() }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm">
                                    <div class="text-gray-900">Lat: {{ number_format($relatedCheckout->latitude, 6) }}</div>
                                    <div class="text-gray-500">Lng: {{ number_format($relatedCheckout->longitude, 6) }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $duration = $attendance->timestamp->diffInMinutes($relatedCheckout->timestamp);
                                    $hours = floor($duration / 60);
                                    $minutes = $duration % 60;
                                    $durationFormatted = sprintf('%dh%02d', $hours, $minutes);
                                @endphp
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    {{ $durationFormatted }} ({{ $duration }} min)
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('admin.attendances.show', $relatedCheckout->id) }}" class="text-blue-600 hover:text-blue-900" title="Voir détails">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @elseif($attendance->type === 'check_out' && $relatedCheckin)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Entrée
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $relatedCheckin->timestamp->format('d/m/Y H:i:s') }}</div>
                                <div class="text-xs text-gray-500">{{ $relatedCheckin->timestamp->diffForHumans() }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm">
                                    <div class="text-gray-900">Lat: {{ number_format($relatedCheckin->latitude, 6) }}</div>
                                    <div class="text-gray-500">Lng: {{ number_format($relatedCheckin->longitude, 6) }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $duration = $relatedCheckin->timestamp->diffInMinutes($attendance->timestamp);
                                    $hours = floor($duration / 60);
                                    $minutes = $duration % 60;
                                    $durationFormatted = sprintf('%dh%02d', $hours, $minutes);
                                @endphp
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    {{ $durationFormatted }} ({{ $duration }} min)
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('admin.attendances.show', $relatedCheckin->id) }}" class="text-blue-600 hover:text-blue-900" title="Voir détails">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection