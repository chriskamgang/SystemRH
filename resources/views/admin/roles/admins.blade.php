@extends('layouts.admin')

@section('title', 'Comptes Administrateurs')
@section('page-title', 'Comptes Administrateurs')

@section('content')
<div class="space-y-6" x-data="{ showCreateModal: false }">

    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Comptes Administrateurs</h2>
            <p class="text-gray-600 mt-1">Utilisateurs ayant accès au panneau d'administration</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.roles.index') }}" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition">
                <i class="fas fa-arrow-left mr-2"></i>Retour aux rôles
            </a>
            <button @click="showCreateModal = true" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                <i class="fas fa-user-plus mr-2"></i>Nouveau Compte Admin
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

    <!-- Liste -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Utilisateur</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rôle</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($admins as $admin)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-full {{ $admin->isAdmin() ? 'bg-red-100' : 'bg-blue-100' }} flex items-center justify-center mr-3">
                                <span class="{{ $admin->isAdmin() ? 'text-red-600' : 'text-blue-600' }} font-bold">{{ substr($admin->first_name, 0, 1) }}</span>
                            </div>
                            <div>
                                <div class="font-medium text-gray-900">{{ $admin->full_name }}</div>
                                <div class="text-xs text-gray-500">{{ $admin->employee_type }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $admin->email }}</td>
                    <td class="px-6 py-4">
                        @if($admin->isAdmin())
                            <span class="px-3 py-1 bg-red-100 text-red-800 text-xs font-semibold rounded-full">
                                <i class="fas fa-crown mr-1"></i>{{ $admin->role->display_name }}
                            </span>
                        @else
                            <form action="{{ route('admin.roles.update-admin', $admin->id) }}" method="POST" class="inline">
                                @csrf
                                @method('PUT')
                                <select name="role_id" onchange="this.form.submit()" class="text-sm border rounded-lg px-3 py-1">
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}" {{ $admin->role_id == $role->id ? 'selected' : '' }}>
                                            {{ $role->display_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right">
                        @if(!$admin->isAdmin())
                            <span class="text-xs text-gray-400">
                                {{ $admin->role->permissions->count() }} permissions
                            </span>
                        @else
                            <span class="text-xs text-gray-400">Toutes</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Modal Créer un compte admin -->
    <div x-show="showCreateModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="showCreateModal = false">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg mx-4 p-6" @click.stop>
            <h3 class="text-lg font-bold text-gray-800 mb-4">
                <i class="fas fa-user-plus text-blue-600 mr-2"></i>Nouveau Compte Admin
            </h3>

            <form action="{{ route('admin.roles.store-admin') }}" method="POST">
                @csrf
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Prénom</label>
                            <input type="text" name="first_name" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nom</label>
                            <input type="text" name="last_name" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Rôle</label>
                        <select name="role_id" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->display_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mot de passe</label>
                        <input type="password" name="password" placeholder="Par défaut : password123"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                        <p class="text-xs text-gray-500 mt-1">Laissez vide pour utiliser <strong>password123</strong></p>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" @click="showCreateModal = false" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg">
                        Annuler
                    </button>
                    <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                        <i class="fas fa-check mr-2"></i>Créer le compte
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
