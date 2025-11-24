@extends('layouts.admin')

@section('title', 'Gestion des Campus')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Gestion des Campus</h1>
        <a href="{{ route('admin.campuses.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
            <i class="fas fa-plus mr-2"></i>
            Nouveau Campus
        </a>
    </div>

    <!-- Success Message -->
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    <!-- Campus Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($campuses as $campus)
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <!-- Campus Header -->
                <div class="p-6 {{ $campus->is_active ? 'bg-green-50' : 'bg-gray-50' }}">
                    <div class="flex justify-between items-start mb-2">
                        <h3 class="text-xl font-bold text-gray-800">{{ $campus->name }}</h3>
                        <span class="px-2 py-1 text-xs rounded {{ $campus->is_active ? 'bg-green-500 text-white' : 'bg-gray-400 text-white' }}">
                            {{ $campus->is_active ? 'Actif' : 'Inactif' }}
                        </span>
                    </div>
                    <p class="text-gray-600 text-sm">
                        <i class="fas fa-map-marker-alt mr-1"></i>
                        {{ $campus->address }}
                    </p>
                </div>

                <!-- Campus Stats -->
                <div class="p-6">
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="text-center">
                            <p class="text-2xl font-bold text-blue-600">{{ $campus->users_count }}</p>
                            <p class="text-xs text-gray-600">Employés</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-purple-600">{{ $campus->attendances_count }}</p>
                            <p class="text-xs text-gray-600">Présences</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2 mb-4 text-sm text-gray-600">
                        <div>
                            <i class="fas fa-map-pin mr-1 text-gray-400"></i>
                            Lat: {{ number_format($campus->latitude, 6) }}
                        </div>
                        <div>
                            <i class="fas fa-map-pin mr-1 text-gray-400"></i>
                            Lng: {{ number_format($campus->longitude, 6) }}
                        </div>
                    </div>

                    <div class="text-sm text-gray-600 mb-4">
                        <i class="fas fa-circle-notch mr-1 text-gray-400"></i>
                        Rayon: {{ $campus->radius }}m
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-2">
                        <a href="{{ route('admin.campuses.show', $campus->id) }}"
                           class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded text-center text-sm">
                            <i class="fas fa-eye mr-1"></i>
                            Voir
                        </a>
                        <a href="{{ route('admin.campuses.edit', $campus->id) }}"
                           class="flex-1 bg-blue-100 hover:bg-blue-200 text-blue-700 px-3 py-2 rounded text-center text-sm">
                            <i class="fas fa-edit mr-1"></i>
                            Modifier
                        </a>
                        <form action="{{ route('admin.campuses.destroy', $campus->id) }}"
                              method="POST"
                              onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce campus ?')"
                              class="flex-1">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="w-full bg-red-100 hover:bg-red-200 text-red-700 px-3 py-2 rounded text-sm">
                                <i class="fas fa-trash mr-1"></i>
                                Supprimer
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-12">
                <i class="fas fa-building text-6xl text-gray-300 mb-4"></i>
                <p class="text-gray-500 text-lg">Aucun campus trouvé</p>
                <a href="{{ route('admin.campuses.create') }}" class="text-blue-600 hover:text-blue-800 mt-2 inline-block">
                    Créer le premier campus
                </a>
            </div>
        @endforelse
    </div>
</div>
@endsection
