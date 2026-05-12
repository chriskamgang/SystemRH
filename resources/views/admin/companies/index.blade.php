@extends('layouts.admin')

@section('title', 'Gestion des Entreprises')

@section('content')
<div class="max-w-7xl mx-auto">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Entreprises</h1>
            <p class="text-sm text-gray-500 mt-1">{{ $companies->count() }} entreprise(s) enregistree(s)</p>
        </div>
        <a href="{{ route('admin.companies.create') }}"
           class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center gap-2">
            <i class="fas fa-plus"></i> Nouvelle entreprise
        </a>
    </div>

    {{-- Banner mode switch --}}
    @if(session('switched_company_name'))
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6 flex items-center justify-between rounded">
        <div class="flex items-center gap-3">
            <i class="fas fa-building text-yellow-600"></i>
            <span class="text-yellow-800">
                Vous etes dans l'espace de <strong>{{ session('switched_company_name') }}</strong>
            </span>
        </div>
        <form action="{{ route('admin.companies.switch-back') }}" method="POST">
            @csrf
            <button type="submit" class="bg-yellow-600 text-white px-3 py-1 rounded text-sm hover:bg-yellow-700">
                <i class="fas fa-arrow-left mr-1"></i> Retour vue globale
            </button>
        </form>
    </div>
    @endif

    {{-- Liste des entreprises --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($companies as $company)
        <div class="bg-white rounded-xl shadow-sm border hover:shadow-md transition-shadow">
            <div class="p-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-3">
                        @if($company->logo)
                            <img src="{{ asset('storage/' . $company->logo) }}" alt="{{ $company->name }}"
                                 class="w-12 h-12 rounded-lg object-cover">
                        @else
                            <div class="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center">
                                <i class="fas fa-building text-blue-600 text-lg"></i>
                            </div>
                        @endif
                        <div>
                            <h3 class="font-semibold text-gray-800">{{ $company->name }}</h3>
                            <p class="text-xs text-gray-500">{{ $company->city ?? 'Non defini' }}</p>
                        </div>
                    </div>
                    <span class="px-2 py-1 text-xs rounded-full {{ $company->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                        {{ $company->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>

                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Employes</span>
                        <span class="font-medium">{{ $company->users_count }} / {{ $company->max_employees }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Plan</span>
                        <span class="font-medium capitalize">{{ $company->subscription_plan }}</span>
                    </div>
                    @if($company->sector)
                    <div class="flex justify-between">
                        <span class="text-gray-500">Secteur</span>
                        <span class="font-medium">{{ $company->sector }}</span>
                    </div>
                    @endif
                </div>

                {{-- Progress bar employes --}}
                @php $pct = $company->max_employees > 0 ? min(100, round($company->users_count / $company->max_employees * 100)) : 0; @endphp
                <div class="mt-3">
                    <div class="w-full bg-gray-200 rounded-full h-1.5">
                        <div class="h-1.5 rounded-full {{ $pct > 90 ? 'bg-red-500' : ($pct > 70 ? 'bg-yellow-500' : 'bg-blue-500') }}"
                             style="width: {{ $pct }}%"></div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-2 mt-4 pt-4 border-t">
                    <form action="{{ route('admin.companies.switch', $company->id) }}" method="POST" class="flex-1">
                        @csrf
                        <button type="submit" class="w-full bg-blue-50 text-blue-700 px-3 py-2 rounded-lg text-sm hover:bg-blue-100 transition">
                            <i class="fas fa-sign-in-alt mr-1"></i> Acceder
                        </button>
                    </form>
                    <a href="{{ route('admin.companies.show', $company->id) }}"
                       class="bg-gray-50 text-gray-700 px-3 py-2 rounded-lg text-sm hover:bg-gray-100 transition">
                        <i class="fas fa-cog"></i>
                    </a>
                    <form action="{{ route('admin.companies.toggle', $company->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="px-3 py-2 rounded-lg text-sm transition {{ $company->is_active ? 'bg-red-50 text-red-600 hover:bg-red-100' : 'bg-green-50 text-green-600 hover:bg-green-100' }}">
                            <i class="fas {{ $company->is_active ? 'fa-ban' : 'fa-check' }}"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    @if($companies->isEmpty())
    <div class="text-center py-16 bg-white rounded-xl shadow-sm">
        <i class="fas fa-building text-gray-300 text-5xl mb-4"></i>
        <h3 class="text-lg font-medium text-gray-600">Aucune entreprise</h3>
        <p class="text-gray-400 mt-1">Commencez par creer votre premiere entreprise.</p>
        <a href="{{ route('admin.companies.create') }}"
           class="inline-block mt-4 bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
            <i class="fas fa-plus mr-2"></i> Creer une entreprise
        </a>
    </div>
    @endif
</div>
@endsection
