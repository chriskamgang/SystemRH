@extends('layouts.admin')

@section('title', 'Nouvelle Entreprise')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.companies.index') }}" class="text-gray-400 hover:text-gray-600">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="text-2xl font-bold text-gray-800">Nouvelle Entreprise</h1>
    </div>

    <form action="{{ route('admin.companies.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        {{-- Informations entreprise --}}
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">
                <i class="fas fa-building mr-2 text-blue-500"></i> Informations de l'entreprise
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom de l'entreprise *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-300 focus:border-blue-500"
                           placeholder="Ex: Universite de Douala">
                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}"
                           class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-300">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Telephone</label>
                    <input type="text" name="phone" value="{{ old('phone') }}"
                           class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-300">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ville</label>
                    <input type="text" name="city" value="{{ old('city') }}"
                           class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-300"
                           placeholder="Ex: Douala">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Secteur d'activite</label>
                    <select name="sector" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-300">
                        <option value="">-- Choisir --</option>
                        <option value="Education" {{ old('sector') == 'Education' ? 'selected' : '' }}>Education</option>
                        <option value="Sante" {{ old('sector') == 'Sante' ? 'selected' : '' }}>Sante</option>
                        <option value="Finance" {{ old('sector') == 'Finance' ? 'selected' : '' }}>Finance</option>
                        <option value="Technologie" {{ old('sector') == 'Technologie' ? 'selected' : '' }}>Technologie</option>
                        <option value="Commerce" {{ old('sector') == 'Commerce' ? 'selected' : '' }}>Commerce</option>
                        <option value="Industrie" {{ old('sector') == 'Industrie' ? 'selected' : '' }}>Industrie</option>
                        <option value="Services" {{ old('sector') == 'Services' ? 'selected' : '' }}>Services</option>
                        <option value="Autre" {{ old('sector') == 'Autre' ? 'selected' : '' }}>Autre</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Adresse</label>
                    <input type="text" name="address" value="{{ old('address') }}"
                           class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-300">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Logo</label>
                    <input type="file" name="logo" accept="image/*"
                           class="w-full border rounded-lg px-3 py-2 text-sm">
                </div>
            </div>
        </div>

        {{-- Abonnement --}}
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">
                <i class="fas fa-credit-card mr-2 text-green-500"></i> Abonnement
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Plan *</label>
                    <select name="subscription_plan" required
                            class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-300">
                        <option value="basic" {{ old('subscription_plan') == 'basic' ? 'selected' : '' }}>Basic (jusqu'a 50 employes)</option>
                        <option value="pro" {{ old('subscription_plan') == 'pro' ? 'selected' : '' }}>Pro (jusqu'a 200 employes)</option>
                        <option value="enterprise" {{ old('subscription_plan') == 'enterprise' ? 'selected' : '' }}>Enterprise (illimite)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre max d'employes *</label>
                    <input type="number" name="max_employees" value="{{ old('max_employees', 50) }}" required min="1"
                           class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-300">
                </div>
            </div>
        </div>

        {{-- Admin de l'entreprise --}}
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">
                <i class="fas fa-user-shield mr-2 text-purple-500"></i> Administrateur de l'entreprise
            </h2>
            <p class="text-sm text-gray-500 mb-4">Ce compte sera l'administrateur principal de l'entreprise.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Prenom *</label>
                    <input type="text" name="admin_first_name" value="{{ old('admin_first_name') }}" required
                           class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-300">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom *</label>
                    <input type="text" name="admin_last_name" value="{{ old('admin_last_name') }}" required
                           class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-300">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                    <input type="email" name="admin_email" value="{{ old('admin_email') }}" required
                           class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-300">
                    @error('admin_email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mot de passe *</label>
                    <input type="password" name="admin_password" required minlength="6"
                           class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-300">
                    @error('admin_password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.companies.index') }}"
               class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-300">
                Annuler
            </a>
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-check mr-2"></i> Creer l'entreprise
            </button>
        </div>
    </form>
</div>
@endsection
