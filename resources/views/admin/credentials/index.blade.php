@extends('layouts.admin')

@section('title', 'Identifiants Personnel')
@section('page-title', 'Identifiants Personnel')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Identifiants du Personnel</h2>
            <p class="text-gray-600 mt-1">Téléchargez un PDF avec les emails et mots de passe de connexion</p>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
        </div>
    @endif

    <!-- Info -->
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <h3 class="font-semibold text-yellow-800 mb-2"><i class="fas fa-info-circle mr-2"></i>Information</h3>
        <p class="text-sm text-yellow-700">
            Le mot de passe par défaut est <strong>password123</strong> pour tous les comptes.
            Si un employé a changé son mot de passe, vous pouvez le réinitialiser ci-dessous avant de télécharger le PDF.
        </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Télécharger PDF -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">
                <i class="fas fa-file-pdf text-red-500 mr-2"></i>Télécharger les identifiants
            </h3>

            <form action="{{ route('admin.credentials.download') }}" method="POST">
                @csrf
                <p class="text-sm text-gray-600 mb-4">Sélectionnez les types d'employés à inclure dans le PDF :</p>

                <div class="space-y-3 mb-6">
                    @foreach($stats as $key => $data)
                    <label class="flex items-center justify-between p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                        <div class="flex items-center gap-3">
                            <input type="checkbox" name="categories[]" value="{{ $key }}" checked
                                class="w-5 h-5 text-blue-600 rounded">
                            <span class="text-sm font-medium text-gray-700">{{ $data['label'] }}</span>
                        </div>
                        <span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs font-semibold rounded-full">
                            {{ $data['count'] }}
                        </span>
                    </label>
                    @endforeach
                </div>

                <button type="submit" class="w-full px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg transition font-semibold">
                    <i class="fas fa-download mr-2"></i>Télécharger le PDF
                </button>
            </form>
        </div>

        <!-- Réinitialiser mots de passe -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">
                <i class="fas fa-key text-orange-500 mr-2"></i>Réinitialiser les mots de passe
            </h3>

            <form action="{{ route('admin.credentials.reset') }}" method="POST"
                  onsubmit="return confirm('Attention ! Tous les mots de passe des types sélectionnés seront réinitialisés à password123. Continuer ?')">
                @csrf
                <p class="text-sm text-gray-600 mb-4">
                    Réinitialiser les mots de passe à <strong>password123</strong> pour les catégories sélectionnées :
                </p>

                <div class="space-y-3 mb-6">
                    @foreach($stats as $key => $data)
                    <label class="flex items-center justify-between p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                        <div class="flex items-center gap-3">
                            <input type="checkbox" name="categories[]" value="{{ $key }}"
                                class="w-5 h-5 text-orange-600 rounded">
                            <span class="text-sm font-medium text-gray-700">{{ $data['label'] }}</span>
                        </div>
                        <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs font-semibold rounded-full">
                            {{ $data['count'] }}
                        </span>
                    </label>
                    @endforeach
                </div>

                <button type="submit" class="w-full px-6 py-3 bg-orange-600 hover:bg-orange-700 text-white rounded-lg transition font-semibold">
                    <i class="fas fa-redo mr-2"></i>Réinitialiser les mots de passe
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
