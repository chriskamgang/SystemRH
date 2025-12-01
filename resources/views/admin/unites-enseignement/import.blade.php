@extends('layouts.admin')
@section('title', 'Importer des UE')
@section('page-title', 'Importer des Unités d\'Enseignement')

@section('content')
<div class="max-w-3xl mx-auto">
    <!-- Afficher les erreurs d'import détaillées -->
    @if(session('import_errors') && count(session('import_errors')) > 0)
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle text-red-500 text-xl"></i>
                </div>
                <div class="ml-3 flex-1">
                    <h3 class="text-sm font-medium text-red-800 mb-2">Erreurs détectées lors de l'import :</h3>
                    <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
                        @foreach(session('import_errors') as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow-lg p-8">
        <div class="mb-6">
            <h3 class="text-lg font-semibold mb-4">Télécharger le template</h3>
            <p class="text-sm text-gray-600 mb-4">Pour importer vos UE, utilisez le template Excel avec les colonnes suivantes :</p>
            <div class="bg-gray-50 p-4 rounded-lg text-sm font-mono mb-4">
                code_ue | nom_matiere | volume_horaire_total | annee_academique | semestre | specialite | niveau
            </div>
            <a href="{{ route('admin.unites-enseignement.download-template') }}" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg">
                <i class="fas fa-download mr-2"></i> Télécharger le template Excel
            </a>
        </div>

        <hr class="my-6">

        <form method="POST" action="{{ route('admin.unites-enseignement.import.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Fichier Excel/CSV *</label>
                <input type="file" name="file" required accept=".xlsx,.xls,.csv" class="w-full px-4 py-2 border rounded-lg @error('file') border-red-500 @enderror">
                @error('file')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                <p class="text-xs text-gray-500 mt-1">Formats acceptés: .xlsx, .xls, .csv (max 10 Mo)</p>
            </div>

            <div class="p-4 bg-blue-50 border-l-4 border-blue-500 text-blue-700 mb-4">
                <p class="text-sm"><i class="fas fa-info-circle mr-2"></i> <strong>Format du champ semestre :</strong></p>
                <ul class="text-sm mt-2 ml-6 list-disc">
                    <li>Vous pouvez utiliser : <code class="bg-blue-100 px-1 rounded">1</code>, <code class="bg-blue-100 px-1 rounded">2</code>, ..., <code class="bg-blue-100 px-1 rounded">9</code></li>
                    <li>Ou : <code class="bg-blue-100 px-1 rounded">Semestre 1</code>, <code class="bg-blue-100 px-1 rounded">Semestre 7</code>, etc. (le numéro sera extrait automatiquement)</li>
                </ul>
            </div>

            <div class="p-4 bg-yellow-50 border-l-4 border-yellow-500 text-yellow-700 mb-6">
                <p class="text-sm"><i class="fas fa-exclamation-triangle mr-2"></i> <strong>Important :</strong></p>
                <ul class="text-sm mt-2 ml-6 list-disc">
                    <li>Les UE avec des codes déjà existants seront ignorées</li>
                    <li>Les UE importées seront créées avec le statut "Non activée"</li>
                    <li>Vous devrez ensuite les attribuer aux enseignants</li>
                </ul>
            </div>

            <div class="flex justify-end gap-4 pt-6 border-t">
                <a href="{{ route('admin.unites-enseignement.catalog') }}" class="px-6 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg">Annuler</a>
                <button type="submit" class="px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg">
                    <i class="fas fa-file-import mr-2"></i> Importer
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
