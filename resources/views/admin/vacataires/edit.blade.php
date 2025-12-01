@extends('layouts.admin')

@section('title', 'Modifier Vacataire')
@section('page-title', 'Modifier un Vacataire')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Modifier Enseignant Vacataire</h3>
            <p class="text-sm text-gray-600 mt-1">Modifier les informations de {{ $vacataire->full_name }}</p>
        </div>

        <form action="{{ route('admin.vacataires.update', $vacataire->id) }}" method="POST" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <!-- Informations personnelles -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">
                        Prénom <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="first_name"
                        id="first_name"
                        value="{{ old('first_name', $vacataire->first_name) }}"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('first_name') border-red-500 @enderror"
                    >
                    @error('first_name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">
                        Nom <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="last_name"
                        id="last_name"
                        value="{{ old('last_name', $vacataire->last_name) }}"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('last_name') border-red-500 @enderror"
                    >
                    @error('last_name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Contact -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        Email <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="email"
                        name="email"
                        id="email"
                        value="{{ old('email', $vacataire->email) }}"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('email') border-red-500 @enderror"
                    >
                    @error('email')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                        Téléphone
                    </label>
                    <input
                        type="text"
                        name="phone"
                        id="phone"
                        value="{{ old('phone', $vacataire->phone) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('phone') border-red-500 @enderror"
                    >
                    @error('phone')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Taux horaire -->
            <div>
                <label for="hourly_rate" class="block text-sm font-medium text-gray-700 mb-2">
                    Taux Horaire (FCFA) <span class="text-red-500">*</span>
                </label>
                <input
                    type="number"
                    name="hourly_rate"
                    id="hourly_rate"
                    value="{{ old('hourly_rate', $vacataire->hourly_rate) }}"
                    min="0"
                    step="100"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('hourly_rate') border-red-500 @enderror"
                >
                @error('hourly_rate')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
                <p class="text-sm text-gray-600 mt-1">
                    <i class="fas fa-info-circle"></i>
                    Le taux horaire utilisé pour calculer les paiements
                </p>
            </div>

            <!-- Campus -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Campus assignés <span class="text-red-500">*</span>
                </label>
                <div class="space-y-2 max-h-48 overflow-y-auto border border-gray-300 rounded-lg p-4">
                    @forelse($campuses as $campus)
                        <label class="flex items-center cursor-pointer hover:bg-gray-50 p-2 rounded">
                            <input
                                type="checkbox"
                                name="campuses[]"
                                value="{{ $campus->id }}"
                                {{ in_array($campus->id, old('campuses', $vacataire->campuses->pluck('id')->toArray())) ? 'checked' : '' }}
                                class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500"
                            >
                            <span class="ml-3 text-sm text-gray-700">{{ $campus->name }}</span>
                        </label>
                    @empty
                        <p class="text-gray-500 text-sm">Aucun campus disponible</p>
                    @endforelse
                </div>
                @error('campuses')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Statut actif -->
            <div class="flex items-center">
                <input
                    type="checkbox"
                    name="is_active"
                    id="is_active"
                    value="1"
                    {{ old('is_active', $vacataire->is_active) ? 'checked' : '' }}
                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500"
                >
                <label for="is_active" class="ml-2 text-sm text-gray-700">
                    Compte actif
                </label>
            </div>

            <!-- Info modification mot de passe -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-yellow-500 mt-1"></i>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-yellow-800">Modification du mot de passe</p>
                        <p class="text-sm text-yellow-700 mt-1">
                            Le mot de passe ne sera pas modifié. Pour changer le mot de passe, l'utilisateur doit utiliser la fonction "Mot de passe oublié".
                        </p>
                    </div>
                </div>
            </div>

            <!-- Boutons -->
            <div class="flex items-center justify-end space-x-4 pt-4 border-t border-gray-200">
                <a href="{{ route('admin.vacataires.show', $vacataire->id) }}" class="px-6 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition">
                    Annuler
                </a>
                <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition font-medium">
                    <i class="fas fa-save mr-2"></i> Enregistrer les modifications
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
