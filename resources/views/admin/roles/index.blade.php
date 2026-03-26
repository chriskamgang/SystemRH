@extends('layouts.admin')

@section('title', 'Gestion des Rôles')
@section('page-title', 'Gestion des Rôles & Permissions')

@section('content')
<div class="space-y-6" x-data="{ showCreateModal: false, editRole: null }">

    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Rôles & Permissions</h2>
            <p class="text-gray-600 mt-1">Gérez les rôles et définissez les droits d'accès par module</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.roles.admins') }}" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
                <i class="fas fa-user-shield mr-2"></i>Comptes Admin
            </a>
            <button @click="showCreateModal = true" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                <i class="fas fa-plus mr-2"></i>Nouveau Rôle
            </button>
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

    <!-- Liste des rôles -->
    @foreach($roles as $role)
    <div class="bg-white rounded-lg shadow overflow-hidden" x-data="{ open: false }">
        <div class="px-6 py-4 flex items-center justify-between cursor-pointer hover:bg-gray-50" @click="open = !open">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 rounded-full flex items-center justify-center {{ $role->name === 'admin' ? 'bg-red-100' : 'bg-blue-100' }}">
                    <i class="fas {{ $role->name === 'admin' ? 'fa-crown text-red-600' : 'fa-user-tag text-blue-600' }}"></i>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">{{ $role->display_name }}</h3>
                    <p class="text-sm text-gray-500">{{ $role->description ?? 'Aucune description' }}</p>
                </div>
                <span class="ml-4 px-3 py-1 bg-gray-100 text-gray-600 text-sm rounded-full">
                    {{ $role->users_count }} utilisateur(s)
                </span>
                <span class="px-3 py-1 bg-blue-100 text-blue-600 text-sm rounded-full">
                    {{ $role->permissions->count() }} permission(s)
                </span>
            </div>
            <div class="flex items-center gap-3">
                @if($role->name !== 'admin')
                    <form action="{{ route('admin.roles.destroy', $role->id) }}" method="POST" onsubmit="return confirm('Supprimer ce rôle ?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-500 hover:text-red-700 p-2" @click.stop title="Supprimer">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                @endif
                <i class="fas fa-chevron-down text-gray-400 transition-transform" :class="{ 'rotate-180': open }"></i>
            </div>
        </div>

        <!-- Permissions du rôle (expandable) -->
        <div x-show="open" x-transition class="border-t bg-gray-50">
            @if($role->name === 'admin')
                <div class="px-6 py-4 text-center text-gray-500">
                    <i class="fas fa-crown text-yellow-500 mr-2"></i>
                    L'administrateur a automatiquement toutes les permissions.
                </div>
            @else
                <form action="{{ route('admin.roles.update', $role->id) }}" method="POST" class="px-6 py-4">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nom affiché</label>
                            <input type="text" name="display_name" value="{{ $role->display_name }}" class="w-full px-3 py-2 border rounded-lg text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <input type="text" name="description" value="{{ $role->description }}" class="w-full px-3 py-2 border rounded-lg text-sm">
                        </div>
                    </div>

                    <!-- Accès Dashboard -->
                    <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="access_dashboard" value="1"
                                {{ $role->permissions->contains('name', 'access_dashboard') ? 'checked' : '' }}
                                class="w-5 h-5 text-yellow-600 rounded">
                            <span class="font-semibold text-yellow-800">
                                <i class="fas fa-key mr-1"></i>Accès au panneau d'administration
                            </span>
                            <span class="text-sm text-yellow-600">(nécessaire pour se connecter au dashboard)</span>
                        </label>
                    </div>

                    <!-- Permissions par module -->
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                        @foreach($modules as $module)
                        <div class="border rounded-lg p-3 bg-white">
                            <div class="flex items-center gap-2 mb-2 pb-2 border-b">
                                <i class="{{ $module['icon'] }} text-gray-500 w-5 text-center"></i>
                                <span class="font-semibold text-sm text-gray-700">{{ $module['label'] }}</span>
                                <!-- Tout cocher -->
                                <button type="button"
                                    class="ml-auto text-xs text-blue-500 hover:text-blue-700"
                                    onclick="this.closest('.border').querySelectorAll('input[type=checkbox]').forEach(c => c.checked = !c.checked)">
                                    Tout
                                </button>
                            </div>
                            <div class="grid grid-cols-2 gap-1">
                                @foreach($module['permissions'] as $perm)
                                    @php
                                        $action = explode('.', $perm->name)[1] ?? '';
                                        $colors = [
                                            'view' => 'text-green-600',
                                            'create' => 'text-blue-600',
                                            'edit' => 'text-orange-600',
                                            'delete' => 'text-red-600',
                                        ];
                                        $icons = [
                                            'view' => 'fa-eye',
                                            'create' => 'fa-plus',
                                            'edit' => 'fa-edit',
                                            'delete' => 'fa-trash',
                                        ];
                                    @endphp
                                    <label class="flex items-center gap-1.5 cursor-pointer text-xs py-0.5">
                                        <input type="checkbox" name="permissions[]" value="{{ $perm->id }}"
                                            {{ $role->permissions->contains('id', $perm->id) ? 'checked' : '' }}
                                            class="w-4 h-4 rounded">
                                        <i class="fas {{ $icons[$action] ?? 'fa-circle' }} {{ $colors[$action] ?? 'text-gray-500' }} text-xs"></i>
                                        <span class="text-gray-700">{{ ucfirst($action) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <div class="mt-4 flex justify-end">
                        <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                            <i class="fas fa-save mr-2"></i>Sauvegarder les permissions
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
    @endforeach

    <!-- Modal Créer un rôle -->
    <div x-show="showCreateModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="showCreateModal = false">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg mx-4 p-6" @click.stop>
            <h3 class="text-lg font-bold text-gray-800 mb-4">
                <i class="fas fa-plus-circle text-blue-600 mr-2"></i>Nouveau Rôle
            </h3>

            <form action="{{ route('admin.roles.store') }}" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Identifiant (slug)</label>
                        <input type="text" name="name" required placeholder="ex: responsable_rh"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                        <p class="text-xs text-gray-500 mt-1">Lettres minuscules, underscores. Ne peut plus être changé.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nom affiché</label>
                        <input type="text" name="display_name" required placeholder="ex: Responsable RH"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <input type="text" name="description" placeholder="ex: Gère les employés et la paie"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="access_dashboard" value="1" class="w-5 h-5 text-yellow-600 rounded">
                            <span class="font-semibold text-yellow-800">Accès au panneau d'administration</span>
                        </label>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" @click="showCreateModal = false" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg">
                        Annuler
                    </button>
                    <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                        <i class="fas fa-check mr-2"></i>Créer le rôle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
