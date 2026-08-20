@extends('layouts.admin')

@section('title', 'Bornes de pointage')
@section('page-title', 'Bornes de pointage (Kiosk)')

@section('content')
<div class="space-y-6">
    {{-- Ajouter une borne --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Ajouter une borne</h3>
        <form method="POST" action="{{ route('admin.kiosk.devices.store') }}" class="flex flex-wrap gap-4 items-end">
            @csrf
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">Nom de la borne</label>
                <input type="text" name="name" required placeholder="Ex: Borne Entree Hopital"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">Campus</label>
                <select name="campus_id" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach($campuses as $campus)
                        <option value="{{ $campus->id }}">{{ $campus->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                <i class="fas fa-plus mr-2"></i>Ajouter
            </button>
        </form>
    </div>

    {{-- Liste des bornes --}}
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b">
            <h3 class="text-lg font-semibold text-gray-800">Bornes enregistrees ({{ $kiosks->count() }})</h3>
        </div>

        @if($kiosks->isEmpty())
            <div class="p-6 text-center text-gray-500">
                Aucune borne enregistree. Ajoutez-en une ci-dessus.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nom</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Campus</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Token</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Derniere activite</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($kiosks as $kiosk)
                        <tr>
                            <td class="px-6 py-4 font-medium text-gray-900">{{ $kiosk->name }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ $kiosk->campus->name }}</td>
                            <td class="px-6 py-4">
                                <code class="text-xs bg-gray-100 px-2 py-1 rounded select-all">{{ Str::limit($kiosk->device_token, 20) }}</code>
                                <button onclick="navigator.clipboard.writeText('{{ $kiosk->device_token }}')" class="ml-1 text-blue-500 hover:text-blue-700" title="Copier">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </td>
                            <td class="px-6 py-4">
                                @if($kiosk->is_active)
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-700">Active</span>
                                @else
                                    <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-700">Desactivee</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ $kiosk->last_seen_at ? $kiosk->last_seen_at->diffForHumans() : 'Jamais' }}
                            </td>
                            <td class="px-6 py-4 flex gap-2">
                                <form method="POST" action="{{ route('admin.kiosk.devices.toggle', $kiosk->id) }}">
                                    @csrf
                                    <button class="text-sm px-3 py-1 rounded {{ $kiosk->is_active ? 'bg-yellow-100 text-yellow-700 hover:bg-yellow-200' : 'bg-green-100 text-green-700 hover:bg-green-200' }}">
                                        {{ $kiosk->is_active ? 'Desactiver' : 'Activer' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.kiosk.devices.regenerate', $kiosk->id) }}">
                                    @csrf
                                    <button class="text-sm px-3 py-1 rounded bg-blue-100 text-blue-700 hover:bg-blue-200" onclick="return confirm('Regenerer le token ? La borne devra etre reconfiguree.')">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.kiosk.devices.destroy', $kiosk->id) }}">
                                    @csrf @method('DELETE')
                                    <button class="text-sm px-3 py-1 rounded bg-red-100 text-red-700 hover:bg-red-200" onclick="return confirm('Supprimer cette borne ?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Lien vers badges --}}
    <div class="text-center">
        <a href="{{ route('admin.kiosk.badges') }}" class="inline-flex items-center px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition text-lg">
            <i class="fas fa-id-badge mr-3"></i>Gerer les badges QR des employes
        </a>
    </div>
</div>
@endsection
