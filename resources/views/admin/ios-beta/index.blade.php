@extends('admin.layouts.app')

@section('title', 'Demandes Beta iOS')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fab fa-apple mr-2"></i>Demandes Beta iOS (TestFlight)
        </h1>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                    <i class="fas fa-users text-blue-600"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Total demandes</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['total'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center mr-3">
                    <i class="fas fa-clock text-orange-600"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500">En attente</p>
                    <p class="text-2xl font-bold text-orange-600">{{ $stats['pending'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                    <i class="fas fa-paper-plane text-green-600"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Invitations envoyées</p>
                    <p class="text-2xl font-bold text-green-600">{{ $stats['invited'] }}</p>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-4">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        </div>
    @endif

    <!-- Info box -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <h3 class="font-semibold text-blue-800 mb-2"><i class="fas fa-info-circle mr-2"></i>Comment ajouter un testeur ?</h3>
        <ol class="text-sm text-blue-700 space-y-1 ml-4 list-decimal">
            <li>Copiez l'email du testeur ci-dessous</li>
            <li>Allez sur <a href="https://appstoreconnect.apple.com" target="_blank" class="underline font-semibold">App Store Connect</a> &rarr; Votre app &rarr; TestFlight</li>
            <li>Cliquez sur <strong>"Testeurs externes"</strong> ou <strong>"Testeurs internes"</strong></li>
            <li>Ajoutez l'email du testeur et envoyez l'invitation</li>
            <li>Revenez ici et cliquez <strong>"Marquer invité"</strong> pour suivre le statut</li>
        </ol>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nom</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email (Apple ID)</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date demande</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($requests as $req)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $req->full_name ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <span class="text-sm text-gray-900 font-mono">{{ $req->email }}</span>
                                <button onclick="navigator.clipboard.writeText('{{ $req->email }}'); this.innerHTML='<i class=\'fas fa-check text-green-500\'></i>'; setTimeout(() => this.innerHTML='<i class=\'fas fa-copy text-gray-400\'></i>', 2000)"
                                    class="ml-2 p-1 hover:bg-gray-100 rounded" title="Copier l'email">
                                    <i class="fas fa-copy text-gray-400"></i>
                                </button>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($req->status === 'pending')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                    <i class="fas fa-clock mr-1"></i>En attente
                                </span>
                            @elseif($req->status === 'invited')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-paper-plane mr-1"></i>Invité
                                </span>
                                @if($req->invited_at)
                                    <span class="text-xs text-gray-500 ml-1">{{ $req->invited_at->format('d/m/Y') }}</span>
                                @endif
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $req->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                            @if($req->status === 'pending')
                                <form action="{{ route('admin.ios-beta.invite', $req->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-green-600 hover:text-green-800 font-medium mr-3" title="Marquer comme invité">
                                        <i class="fas fa-paper-plane mr-1"></i>Marquer invité
                                    </button>
                                </form>
                            @endif
                            <form action="{{ route('admin.ios-beta.destroy', $req->id) }}" method="POST" class="inline" onsubmit="return confirm('Supprimer cette demande ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800" title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-inbox text-4xl mb-3 block"></i>
                            Aucune demande de beta iOS pour le moment.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
