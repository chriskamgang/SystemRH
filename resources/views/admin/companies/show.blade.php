@extends('layouts.admin')

@section('title', $company->name)

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.companies.index') }}" class="text-gray-400 hover:text-gray-600">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div class="flex-1">
            <h1 class="text-2xl font-bold text-gray-800">{{ $company->name }}</h1>
            <p class="text-sm text-gray-500">Creee le {{ $company->created_at->format('d/m/Y') }}</p>
        </div>
        <span class="px-3 py-1 rounded-full text-sm {{ $company->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
            {{ $company->is_active ? 'Active' : 'Inactive' }}
        </span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Stats --}}
        <div class="lg:col-span-3 grid grid-cols-3 gap-4">
            <div class="bg-white rounded-xl shadow-sm p-4 text-center">
                <div class="text-3xl font-bold text-blue-600">{{ $stats['total_employees'] }}</div>
                <div class="text-sm text-gray-500 mt-1">Employes actifs</div>
                <div class="text-xs text-gray-400">/ {{ $company->max_employees }} max</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4 text-center">
                <div class="text-3xl font-bold text-green-600">{{ $stats['total_departments'] }}</div>
                <div class="text-sm text-gray-500 mt-1">Departements</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4 text-center">
                <div class="text-3xl font-bold text-purple-600">{{ $stats['total_campuses'] }}</div>
                <div class="text-sm text-gray-500 mt-1">Campus</div>
            </div>
        </div>

        {{-- Infos --}}
        <div class="lg:col-span-2">
            <form action="{{ route('admin.companies.update', $company->id) }}" method="POST" enctype="multipart/form-data"
                  class="bg-white rounded-xl shadow-sm p-6">
                @csrf
                @method('PUT')
                <h2 class="text-lg font-semibold text-gray-700 mb-4">Informations</h2>

                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nom</label>
                        <input type="text" name="name" value="{{ $company->name }}" required
                               class="w-full border rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" value="{{ $company->email }}"
                               class="w-full border rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Telephone</label>
                        <input type="text" name="phone" value="{{ $company->phone }}"
                               class="w-full border rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ville</label>
                        <input type="text" name="city" value="{{ $company->city }}"
                               class="w-full border rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Secteur</label>
                        <select name="sector" class="w-full border rounded-lg px-3 py-2">
                            <option value="">--</option>
                            @foreach(['Education','Sante','Finance','Technologie','Commerce','Industrie','Services','Autre'] as $s)
                                <option value="{{ $s }}" {{ $company->sector == $s ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Adresse</label>
                        <input type="text" name="address" value="{{ $company->address }}"
                               class="w-full border rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Plan</label>
                        <select name="subscription_plan" class="w-full border rounded-lg px-3 py-2">
                            <option value="basic" {{ $company->subscription_plan == 'basic' ? 'selected' : '' }}>Basic</option>
                            <option value="pro" {{ $company->subscription_plan == 'pro' ? 'selected' : '' }}>Pro</option>
                            <option value="enterprise" {{ $company->subscription_plan == 'enterprise' ? 'selected' : '' }}>Enterprise</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Max employes</label>
                        <input type="number" name="max_employees" value="{{ $company->max_employees }}" min="1"
                               class="w-full border rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Logo</label>
                        <input type="file" name="logo" accept="image/*" class="w-full border rounded-lg px-3 py-2 text-sm">
                    </div>
                </div>

                <div class="mt-4 flex justify-end">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>

        {{-- Admins --}}
        <div>
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-700 mb-4">Administrateurs</h2>
                <div class="space-y-3">
                    @foreach($admins as $admin)
                    <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                        <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center">
                            <span class="text-purple-600 font-medium text-sm">{{ strtoupper(substr($admin->first_name, 0, 1) . substr($admin->last_name, 0, 1)) }}</span>
                        </div>
                        <div>
                            <div class="font-medium text-sm">{{ $admin->full_name }}</div>
                            <div class="text-xs text-gray-500">{{ $admin->email }}</div>
                        </div>
                    </div>
                    @endforeach

                    @if($admins->isEmpty())
                    <p class="text-gray-400 text-sm text-center py-4">Aucun administrateur</p>
                    @endif
                </div>
            </div>

            {{-- Acces rapide --}}
            <div class="bg-white rounded-xl shadow-sm p-6 mt-6">
                <h2 class="text-lg font-semibold text-gray-700 mb-4">Actions</h2>
                <div class="space-y-2">
                    <form action="{{ route('admin.companies.switch', $company->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full bg-blue-50 text-blue-700 px-4 py-2 rounded-lg text-sm hover:bg-blue-100 text-left">
                            <i class="fas fa-sign-in-alt mr-2"></i> Acceder a l'espace
                        </button>
                    </form>
                    <form action="{{ route('admin.companies.toggle', $company->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full {{ $company->is_active ? 'bg-red-50 text-red-600 hover:bg-red-100' : 'bg-green-50 text-green-600 hover:bg-green-100' }} px-4 py-2 rounded-lg text-sm text-left">
                            <i class="fas {{ $company->is_active ? 'fa-ban' : 'fa-check' }} mr-2"></i>
                            {{ $company->is_active ? 'Desactiver' : 'Activer' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
