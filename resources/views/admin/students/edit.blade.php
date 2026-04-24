@extends('layouts.admin')

@section('title', 'Modifier Étudiant')
@section('page-title', 'Modifier l\'Étudiant')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Erreurs de validation -->
    @if ($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4">
            <p class="font-bold">Erreurs de validation:</p>
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow-lg p-8">
        <form id="student-form" method="POST" action="{{ route('admin.students.update', $student->id) }}" enctype="multipart/form-data" novalidate>
            @csrf
            @method('PUT')

            <!-- Informations Personnelles -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
                    <i class="fas fa-user-graduate mr-2"></i> Informations Étudiant
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Matricule -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Matricule *</label>
                        <input
                            type="text"
                            name="employee_id"
                            value="{{ old('employee_id', $student->employee_id) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('employee_id') border-red-500 @enderror"
                        >
                        @error('employee_id')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Photo -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Photo</label>
                        @if($student->photo)
                            <div class="mb-2">
                                <img src="{{ asset('storage/' . $student->photo) }}" alt="Photo actuelle" class="h-20 w-20 rounded-full object-cover">
                            </div>
                        @endif
                        <input
                            type="file"
                            name="photo"
                            accept="image/jpeg,image/png,image/jpg"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('photo') border-red-500 @enderror"
                        >
                        @error('photo')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Département -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Département *</label>
                        <select
                            name="department_id"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('department_id') border-red-500 @enderror"
                        >
                            <option value="">Sélectionnez un département</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ old('department_id', $student->department_id) == $dept->id ? 'selected' : '' }}>
                                    {{ $dept->name }} ({{ $dept->code }})
                                </option>
                            @endforeach
                        </select>
                        @error('department_id')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Prénom -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Prénom *</label>
                        <input
                            type="text"
                            name="first_name"
                            value="{{ old('first_name', $student->first_name) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('first_name') border-red-500 @enderror"
                        >
                        @error('first_name')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Nom -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nom *</label>
                        <input
                            type="text"
                            name="last_name"
                            value="{{ old('last_name', $student->last_name) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('last_name') border-red-500 @enderror"
                        >
                        @error('last_name')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                        <input
                            type="email"
                            name="email"
                            value="{{ old('email', $student->email) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('email') border-red-500 @enderror"
                        >
                        @error('email')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Téléphone -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Téléphone</label>
                        <input
                            type="tel"
                            name="phone"
                            value="{{ old('phone', $student->phone) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('phone') border-red-500 @enderror"
                        >
                        @error('phone')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Spécialité -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Spécialité</label>
                        <select
                            name="specialite"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('specialite') border-red-500 @enderror"
                        >
                            <option value="">Sélectionnez une spécialité</option>
                            @foreach($specialties as $spec)
                                <option value="{{ $spec->name }}" {{ old('specialite', $student->specialite) == $spec->name ? 'selected' : '' }}>
                                    {{ $spec->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('specialite')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Niveau -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Niveau</label>
                        <select
                            name="niveau"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('niveau') border-red-500 @enderror"
                        >
                            <option value="">Sélectionnez un niveau</option>
                            @foreach($levels as $level)
                                <option value="{{ $level->name }}" {{ old('niveau', $student->niveau) == $level->name ? 'selected' : '' }}>
                                    {{ $level->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('niveau')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Appareil lié -->
            @if($student->device_id)
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
                    <i class="fas fa-mobile-alt mr-2"></i> Appareil Lié
                </h3>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-800">{{ $student->device_model }}</p>
                            <p class="text-xs text-gray-600">{{ $student->device_os }}</p>
                            <p class="text-xs text-gray-500 mt-1">ID: {{ substr($student->device_id, 0, 20) }}...</p>
                        </div>
                        <button type="button" class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg transition text-sm"
                            onclick="if(confirm('Réinitialiser l\'appareil ? L\'étudiant devra se reconnecter.')) { document.getElementById('reset-device-form').submit(); }">
                            <i class="fas fa-redo mr-2"></i> Réinitialiser
                        </button>
                    </div>
                </div>
            </div>
            @endif

            <!-- Authentification -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
                    <i class="fas fa-lock mr-2"></i> Authentification
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Mot de passe -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nouveau mot de passe</label>
                        <input
                            type="password"
                            name="password"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('password') border-red-500 @enderror"
                        >
                        <p class="text-xs text-gray-500 mt-1">Laisser vide pour conserver l'actuel</p>
                        @error('password')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Confirmation mot de passe -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Confirmer le nouveau mot de passe</label>
                        <input
                            type="password"
                            name="password_confirmation"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                    </div>
                </div>
            </div>

            <!-- Statut -->
            <div class="mb-8">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">
                    <i class="fas fa-toggle-on mr-2"></i> Statut du compte
                </h3>
                <div class="flex items-center">
                    <label class="inline-flex items-center cursor-pointer">
                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            {{ old('is_active', $student->is_active) ? 'checked' : '' }}
                            class="form-checkbox h-6 w-6 text-blue-600 rounded focus:ring-2 focus:ring-blue-500"
                        >
                        <span class="ml-3 text-gray-700 font-medium">Compte actif</span>
                    </label>
                </div>
            </div>

            <!-- Boutons -->
            <div class="flex justify-end gap-4 pt-6 border-t">
                <a href="{{ route('admin.students.index') }}" class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition">
                    <i class="fas fa-times mr-2"></i> Annuler
                </a>
                <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition font-bold">
                    <i class="fas fa-save mr-2"></i> Mettre à jour
                </button>
            </div>
        </form>
        <!-- Form reset-device hors du form principal -->
        <form id="reset-device-form" method="POST" action="{{ route('admin.employees.reset-device', $student->id) }}" style="display:none;">
            @csrf
        </form>
    </div>
</div>
@endsection
