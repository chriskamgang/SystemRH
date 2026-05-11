@extends('layouts.admin')

@section('title', 'Parametres Tickets')

@section('content')
<div class="max-w-7xl mx-auto">

    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('admin.tickets.index') }}" class="text-blue-600 hover:underline text-sm"><i class="fas fa-arrow-left"></i> Retour aux tickets</a>
            <h1 class="text-2xl font-bold mt-1">Parametres des Tickets</h1>
            <p class="text-sm text-gray-500">Gerez les services et categories disponibles pour les tickets.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
            <ul class="list-disc list-inside text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- ═══════════════════════════════════════ --}}
        {{-- SERVICES                                --}}
        {{-- ═══════════════════════════════════════ --}}
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b flex items-center justify-between">
                <h2 class="font-bold text-lg"><i class="fas fa-building text-blue-500 mr-2"></i>Services</h2>
                <span class="text-xs text-gray-400">{{ $services->count() }} service(s)</span>
            </div>

            {{-- Service list --}}
            <div class="divide-y">
                @foreach($services as $service)
                <div x-data="{ editing: false }" class="px-6 py-3">
                    {{-- Display mode --}}
                    <div x-show="!editing" class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            @if($service->color)
                                <span class="w-3 h-3 rounded-full" style="background-color: {{ $service->color }}"></span>
                            @endif
                            <div>
                                <span class="font-medium text-sm">{{ $service->name }}</span>
                                <span class="text-xs text-gray-400 ml-1">({{ $service->slug }})</span>
                                @if($service->icon)
                                    <span class="text-xs text-gray-400 ml-1"><i class="{{ $service->icon }}"></i></span>
                                @endif
                                @if($service->department_id && $service->department)
                                    <span class="text-xs text-blue-500 ml-1"><i class="fas fa-sitemap"></i> {{ $service->department->name }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-400">{{ $service->sort_order }}</span>
                            {{-- Toggle active --}}
                            <form method="POST" action="{{ route('admin.tickets.settings.toggle-service', $service->id) }}">
                                @csrf
                                <button type="submit" class="px-2 py-1 rounded text-xs font-medium {{ $service->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                    {{ $service->is_active ? 'Actif' : 'Inactif' }}
                                </button>
                            </form>
                            <button @click="editing = true" class="text-blue-500 hover:text-blue-700 text-sm" title="Modifier">
                                <i class="fas fa-pen"></i>
                            </button>
                            <form method="POST" action="{{ route('admin.tickets.settings.destroy-service', $service->id) }}" onsubmit="return confirm('Supprimer ce service ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-400 hover:text-red-600 text-sm" title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    {{-- Edit mode --}}
                    <div x-show="editing" x-cloak>
                        <form method="POST" action="{{ route('admin.tickets.settings.update-service', $service->id) }}">
                            @csrf
                            @method('PUT')
                            <div class="grid grid-cols-2 gap-2 mb-2">
                                <input type="text" name="name" value="{{ $service->name }}" class="px-2 py-1 border rounded text-sm" placeholder="Nom" required>
                                <input type="text" name="icon" value="{{ $service->icon }}" class="px-2 py-1 border rounded text-sm" placeholder="Icone (ex: fas fa-cog)">
                            </div>
                            <div class="grid grid-cols-2 gap-2 mb-2">
                                <input type="text" name="color" value="{{ $service->color }}" class="px-2 py-1 border rounded text-sm" placeholder="Couleur (#hex)">
                                <input type="number" name="sort_order" value="{{ $service->sort_order }}" class="px-2 py-1 border rounded text-sm" placeholder="Ordre" min="0">
                            </div>
                            <div class="mb-2">
                                <select name="department_id" class="w-full px-2 py-1 border rounded text-sm">
                                    <option value="">Aucun</option>
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}" @selected($service->department_id == $department->id)>{{ $department->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex gap-2">
                                <button type="submit" class="px-3 py-1 bg-blue-600 text-white rounded text-xs hover:bg-blue-700">Enregistrer</button>
                                <button type="button" @click="editing = false" class="px-3 py-1 bg-gray-200 text-gray-600 rounded text-xs hover:bg-gray-300">Annuler</button>
                            </div>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Add new service --}}
            <div class="px-6 py-4 border-t bg-gray-50">
                <h4 class="text-sm font-medium text-gray-600 mb-2">Ajouter un service</h4>
                <form method="POST" action="{{ route('admin.tickets.settings.store-service') }}">
                    @csrf
                    <div class="grid grid-cols-2 gap-2 mb-2">
                        <input type="text" name="name" class="px-2 py-1 border rounded text-sm" placeholder="Nom du service" required>
                        <input type="text" name="icon" class="px-2 py-1 border rounded text-sm" placeholder="Icone (ex: fas fa-cog)">
                    </div>
                    <div class="grid grid-cols-2 gap-2 mb-2">
                        <input type="text" name="color" class="px-2 py-1 border rounded text-sm" placeholder="Couleur (#hex)">
                        <select name="department_id" class="px-2 py-1 border rounded text-sm">
                            <option value="">Aucun</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}">{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="px-4 py-1.5 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">
                        <i class="fas fa-plus mr-1"></i> Ajouter
                    </button>
                </form>
            </div>
        </div>

        {{-- ═══════════════════════════════════════ --}}
        {{-- CATEGORIES                              --}}
        {{-- ═══════════════════════════════════════ --}}
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b flex items-center justify-between">
                <h2 class="font-bold text-lg"><i class="fas fa-tags text-purple-500 mr-2"></i>Categories</h2>
                <span class="text-xs text-gray-400">{{ $categories->count() }} categorie(s)</span>
            </div>

            {{-- Category list --}}
            <div class="divide-y">
                @foreach($categories as $category)
                <div x-data="{ editing: false }" class="px-6 py-3">
                    {{-- Display mode --}}
                    <div x-show="!editing" class="flex items-center justify-between">
                        <div>
                            <span class="font-medium text-sm">{{ $category->name }}</span>
                            <span class="text-xs text-gray-400 ml-1">({{ $category->slug }})</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-400">{{ $category->sort_order }}</span>
                            {{-- Toggle active --}}
                            <form method="POST" action="{{ route('admin.tickets.settings.toggle-category', $category->id) }}">
                                @csrf
                                <button type="submit" class="px-2 py-1 rounded text-xs font-medium {{ $category->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                    {{ $category->is_active ? 'Actif' : 'Inactif' }}
                                </button>
                            </form>
                            <button @click="editing = true" class="text-blue-500 hover:text-blue-700 text-sm" title="Modifier">
                                <i class="fas fa-pen"></i>
                            </button>
                            <form method="POST" action="{{ route('admin.tickets.settings.destroy-category', $category->id) }}" onsubmit="return confirm('Supprimer cette categorie ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-400 hover:text-red-600 text-sm" title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    {{-- Edit mode --}}
                    <div x-show="editing" x-cloak>
                        <form method="POST" action="{{ route('admin.tickets.settings.update-category', $category->id) }}">
                            @csrf
                            @method('PUT')
                            <div class="grid grid-cols-2 gap-2 mb-2">
                                <input type="text" name="name" value="{{ $category->name }}" class="px-2 py-1 border rounded text-sm" placeholder="Nom" required>
                                <input type="number" name="sort_order" value="{{ $category->sort_order }}" class="px-2 py-1 border rounded text-sm" placeholder="Ordre" min="0">
                            </div>
                            <div class="flex gap-2">
                                <button type="submit" class="px-3 py-1 bg-blue-600 text-white rounded text-xs hover:bg-blue-700">Enregistrer</button>
                                <button type="button" @click="editing = false" class="px-3 py-1 bg-gray-200 text-gray-600 rounded text-xs hover:bg-gray-300">Annuler</button>
                            </div>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Add new category --}}
            <div class="px-6 py-4 border-t bg-gray-50">
                <h4 class="text-sm font-medium text-gray-600 mb-2">Ajouter une categorie</h4>
                <form method="POST" action="{{ route('admin.tickets.settings.store-category') }}">
                    @csrf
                    <div class="flex gap-2 mb-2">
                        <input type="text" name="name" class="flex-1 px-2 py-1 border rounded text-sm" placeholder="Nom de la categorie" required>
                    </div>
                    <button type="submit" class="px-4 py-1.5 bg-purple-600 text-white rounded text-sm hover:bg-purple-700">
                        <i class="fas fa-plus mr-1"></i> Ajouter
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>
@endsection
