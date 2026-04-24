@extends('layouts.admin')

@section('title', 'Gestion des Étudiants')
@section('page-title', 'Gestion des Étudiants')

@section('content')
<div class="space-y-6" x-data="{ openImport: false, openBulkCampus: false }">
    <!-- Header/Actions -->
    <div class="flex justify-between items-center text-gray-800">
        <div>
            <h2 class="text-2xl font-bold">Liste des Étudiants</h2>
            <p class="text-gray-600 mt-1">Gérez les comptes des étudiants ({{ $allStudents->count() }} au total)</p>
        </div>
        <div class="flex gap-3">
            <button @click="openBulkCampus = true" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg transition shadow-sm">
                <i class="fas fa-university mr-2"></i>
                Assigner Campus (Masse)
            </button>
            <button @click="openImport = true" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition shadow-sm">
                <i class="fas fa-file-import mr-2"></i>
                Importer Excel/CSV
            </button>
            <a href="{{ route('admin.students.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition shadow-sm">
                <i class="fas fa-plus mr-2"></i>
                Nouvel Étudiant
            </a>
        </div>
    </div>

    <!-- Recherche -->
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <form method="GET" action="{{ route('admin.students.index') }}" class="flex gap-4">
            <div class="flex-1 relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                    <i class="fas fa-search"></i>
                </span>
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Rechercher par nom, email ou matricule..."
                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
            </div>
            <button type="submit" class="px-6 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition font-medium">
                Filtrer
            </button>
            @if(request('search'))
                <a href="{{ route('admin.students.index') }}" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition flex items-center">
                    Réinitialiser
                </a>
            @endif
        </form>
    </div>

    <!-- Liste des Étudiants Groupés -->
    <div class="space-y-4">
        @forelse($groupedStudents as $level => $specialties)
            <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 text-gray-800" x-data="{ openLevel: false }">
                <div @click="openLevel = !openLevel" class="bg-gray-800 px-6 py-4 flex justify-between items-center cursor-pointer hover:bg-gray-700 transition">
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right mr-3 text-blue-400 transition-transform duration-200" :class="openLevel ? 'rotate-90' : ''"></i>
                        <h3 class="text-white font-bold text-lg">
                            <i class="fas fa-layer-group mr-2 text-blue-400"></i>
                            {{ $level }}
                        </h3>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="bg-blue-600 text-white text-xs font-bold px-3 py-1 rounded-full">
                            {{ $specialties->flatten()->count() }} étudiant(s)
                        </span>
                        <a href="{{ route('admin.students.create', ['niveau' => $level]) }}" @click.stop class="bg-white/10 hover:bg-white/20 text-white text-xs px-2 py-1 rounded border border-white/20 transition">
                            <i class="fas fa-plus mr-1"></i> Ajouter à {{ $level }}
                        </a>
                    </div>
                </div>

                <div x-show="openLevel" x-collapse>
                    @foreach($specialties as $specialty => $students)
                        <div class="border-b border-gray-100 last:border-0" x-data="{ openSpec: true }">
                            <div @click="openSpec = !openSpec" class="bg-gray-50 px-6 py-3 border-b border-gray-200 flex justify-between items-center cursor-pointer hover:bg-gray-100 transition">
                                <h4 class="text-gray-700 font-semibold text-sm flex items-center">
                                    <i class="fas fa-chevron-right mr-2 text-gray-400 text-[10px] transition-transform duration-200" :class="openSpec ? 'rotate-90' : ''"></i>
                                    <i class="fas fa-graduation-cap mr-2 text-gray-400"></i>
                                    Spécialité : {{ $specialty }} 
                                    <span class="ml-2 text-xs text-gray-400 font-normal">({{ $students->count() }})</span>
                                </h4>
                                <a href="{{ route('admin.students.create', ['niveau' => $level, 'specialite' => $specialty]) }}" @click.stop class="text-blue-600 hover:text-blue-800 text-xs font-bold transition">
                                    <i class="fas fa-plus-circle mr-1"></i> Ajouter étudiant
                                </a>
                            </div>
                            
                            <div x-show="openSpec" x-collapse class="overflow-x-auto">
                                <table class="w-full text-left">
                                    <thead class="bg-white border-b border-gray-100">
                                        <tr>
                                            <th class="px-6 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest w-32">Matricule</th>
                                            <th class="px-6 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Nom Complet</th>
                                            <th class="px-6 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Email / Tel</th>
                                            <th class="px-6 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest text-center">Statut</th>
                                            <th class="px-6 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-50">
                                        @foreach($students as $student)
                                            <tr class="hover:bg-blue-50/30 transition">
                                                <td class="px-6 py-4 font-mono text-xs text-gray-500">
                                                    <span class="bg-gray-100 px-2 py-1 rounded">{{ $student->employee_id }}</span>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <div class="flex items-center">
                                                        <div class="h-8 w-8 rounded-lg bg-blue-100 border border-white shadow-sm flex items-center justify-center text-blue-700 text-xs font-bold mr-3 overflow-hidden">
                                                            @if($student->photo)
                                                                <img src="{{ asset('storage/' . $student->photo) }}" class="h-full w-full object-cover">
                                                            @else
                                                                {{ substr($student->first_name, 0, 1) }}{{ substr($student->last_name, 0, 1) }}
                                                            @endif
                                                        </div>
                                                        <div class="font-bold text-gray-800 text-sm">{{ $student->full_name }}</div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 text-xs text-gray-600">
                                                    <div class="flex items-center"><i class="far fa-envelope mr-1.5 text-gray-300"></i> {{ $student->email }}</div>
                                                    <div class="flex items-center mt-1"><i class="fas fa-phone-alt mr-1.5 text-gray-300 text-[10px]"></i> {{ $student->phone ?? '—' }}</div>
                                                </td>
                                                <td class="px-6 py-4 text-center">
                                                    @if($student->is_active)
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-green-50 text-green-700 border border-green-100">
                                                            ACTIF
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-red-50 text-red-700 border border-red-100">
                                                            INACTIF
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 text-right">
                                                    <div class="flex justify-end gap-1">
                                                        <a href="{{ route('admin.employees.show', $student->id) }}" class="p-1.5 text-gray-400 hover:text-blue-600 transition" title="Voir profil">
                                                            <i class="fas fa-eye text-sm"></i>
                                                        </a>
                                                        <a href="{{ route('admin.students.edit', $student->id) }}" class="p-1.5 text-gray-400 hover:text-yellow-600 transition" title="Modifier">
                                                            <i class="fas fa-edit text-sm"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-16 text-center text-gray-500">
                <div class="flex flex-col items-center">
                    <div class="h-16 w-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-user-graduate text-2xl text-gray-300"></i>
                    </div>
                    <p class="text-lg font-medium">Aucun étudiant trouvé</p>
                    <p class="text-sm mt-1">Commencez par en ajouter un ou importer une liste.</p>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Modal Import (Unchanged) -->
    <template x-teleport="body">
        <div x-show="openImport" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div @click="openImport = false" class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen"></span>&#8203;
                <div x-show="openImport" x-transition class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-100">
                    <form action="{{ route('admin.students.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="px-4 pt-5 pb-4 bg-white sm:p-6 sm:pb-4">
                            <h3 class="text-xl font-bold text-gray-900 mb-4 border-b pb-3">Importer une liste d'étudiants</h3>
                            
                            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6 rounded-r-lg">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-info-circle text-blue-500"></i>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-blue-700">
                                            Utilisez notre template Excel pour garantir le bon format des données.
                                        </p>
                                        <a href="{{ route('admin.students.download-template') }}" class="inline-flex items-center text-sm font-bold text-blue-700 underline mt-2 hover:text-blue-800 transition">
                                            <i class="fas fa-download mr-1.5"></i> Télécharger le template
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Fichier de données (.xlsx, .xls, .csv)</label>
                                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-blue-400 transition cursor-pointer relative bg-gray-50">
                                        <div class="space-y-1 text-center">
                                            <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                                            <div class="flex text-sm text-gray-600">
                                                <span class="font-medium text-blue-600 hover:text-blue-500">Cliquez pour choisir</span>
                                                <p class="pl-1">ou glissez-déposez</p>
                                            </div>
                                            <p class="text-xs text-gray-500">Max. 5 Mo</p>
                                        </div>
                                        <input type="file" name="file" required class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                    </div>
                                </div>

                                <div class="bg-gray-50 rounded-lg p-4 text-xs text-gray-600 border border-gray-200">
                                    <p class="font-bold text-gray-700 mb-2 uppercase tracking-wider">Guide de formatage :</p>
                                    <ul class="space-y-1">
                                        <li class="flex items-center"><i class="fas fa-check-circle text-green-500 mr-2"></i> <strong>Requis :</strong> prenom, nom, email, matricule</li>
                                        <li class="flex items-center text-gray-400"><i class="fas fa-plus-circle mr-2"></i> <strong>Optionnel :</strong> telephone, specialite, niveau</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="px-4 py-4 bg-gray-50 sm:px-6 sm:flex sm:flex-row-reverse gap-2 border-t">
                            <button type="submit" class="inline-flex justify-center w-full px-6 py-2.5 text-sm font-bold text-white bg-green-600 border border-transparent rounded-lg shadow-sm hover:bg-green-700 sm:w-auto transition">
                                Lancer l'importation
                            </button>
                            <button @click="openImport = false" type="button" class="inline-flex justify-center w-full px-6 py-2.5 mt-3 text-sm font-bold text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 sm:mt-0 sm:w-auto transition">
                                Annuler
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </template>

    <!-- Modal Assigner Campus (Masse) (Unchanged) -->
    <template x-teleport="body">
        <div x-show="openBulkCampus" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div @click="openBulkCampus = false" class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen"></span>&#8203;
                <div x-show="openBulkCampus" x-transition class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-100">
                    <form action="{{ route('admin.students.assign-campuses-bulk') }}" method="POST">
                        @csrf
                        <div class="px-4 pt-5 pb-4 bg-white sm:p-6 sm:pb-4 text-gray-800">
                            <h3 class="text-xl font-bold text-gray-900 mb-4 border-b pb-3 text-gray-800">Assigner des Campus à TOUS les étudiants</h3>
                            
                            <input type="hidden" name="assign_to" value="all_students">

                            <div class="space-y-4">
                                <p class="text-sm text-gray-600 mb-4">
                                    Cette action va assigner les campus sélectionnés à <strong>TOUS les étudiants</strong> actuellement enregistrés dans le système.
                                </p>

                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Sélectionnez les Campus :</label>
                                    <div class="grid grid-cols-1 gap-2 max-h-60 overflow-y-auto p-2 border rounded-lg bg-gray-50">
                                        @foreach($campuses as $campus)
                                            <label class="flex items-center p-2 hover:bg-white rounded cursor-pointer transition">
                                                <input type="checkbox" name="campus_ids[]" value="{{ $campus->id }}" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                                <span class="ml-3 text-sm text-gray-700 font-medium">{{ $campus->name }}</span>
                                                <span class="ml-auto text-xs text-gray-400 font-mono">{{ $campus->code }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @error('campus_ids')
                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="px-4 py-4 bg-gray-50 sm:px-6 sm:flex sm:flex-row-reverse gap-2 border-t">
                            <button type="submit" class="inline-flex justify-center w-full px-6 py-2.5 text-sm font-bold text-white bg-indigo-600 border border-transparent rounded-lg shadow-sm hover:bg-indigo-700 sm:w-auto transition">
                                Confirmer l'Assignation
                            </button>
                            <button @click="openBulkCampus = false" type="button" class="inline-flex justify-center w-full px-6 py-2.5 mt-3 text-sm font-bold text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 sm:mt-0 sm:w-auto transition">
                                Annuler
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection
