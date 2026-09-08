@extends('layouts.admin')

@section('title', 'Parcours')
@section('page-title', 'Parcours')

@section('content')
<div x-data="{ formulaire: false, etapes: [null, null] }">

    <div class="mb-5 flex items-start gap-2.5 px-4 py-3 bg-blue-50 border border-blue-200 rounded-lg">
        <i class="fas fa-circle-info text-blue-500 mt-0.5"></i>
        <p class="text-sm text-blue-800">
            Un parcours est la suite ordonnée des arrêts d'une ligne, dans un sens donné.
            C'est cet ordre que le chauffeur suit pour pointer son tour ; le dernier arrêt clôt le tour.
        </p>
    </div>

    <div class="flex items-center justify-end mb-5">
        <button @click="formulaire = !formulaire"
                class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-sm font-medium">
            <i class="fas fa-plus mr-1.5"></i> Nouveau parcours
        </button>
    </div>

    <div x-show="formulaire" x-cloak class="mb-5 bg-white rounded-xl border border-amber-200 p-5">
        <h3 class="font-semibold text-gray-800 mb-4">Nouveau parcours</h3>
        <form method="POST" action="{{ route('admin.bus.parcours.store') }}">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-6 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Ligne *</label>
                    <select name="ligne_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        @foreach($lignes as $l)
                            <option value="{{ $l->id }}">{{ $l->code }} — {{ $l->nom }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Libellé *</label>
                    <input name="libelle" required placeholder="Aller du matin" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Sens *</label>
                    <select name="sens" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        @foreach($sens as $s)
                            <option value="{{ $s->value }}">{{ ucfirst($s->value) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Départ</label>
                    <input type="time" name="heure_depart" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">
                        Durée (min) <span class="text-gray-400">(défaut : celle de la ligne)</span>
                    </label>
                    <input type="number" name="duree_reference_minutes" min="1" max="600" placeholder="45"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
            </div>

            {{-- Les étapes, dans l'ordre de passage. --}}
            <label class="block text-xs font-medium text-gray-600 mb-2">Arrêts, dans l'ordre de passage * (2 minimum)</label>
            <template x-for="(etape, index) in etapes" :key="index">
                <div class="flex items-center gap-2 mb-2">
                    <span class="w-7 h-7 rounded-full bg-gray-900 text-white text-xs font-bold flex items-center justify-center flex-shrink-0"
                          x-text="index + 1"></span>
                    <select :name="'etapes[' + index + ']'" required
                            class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <option value="">— choisir un lieu —</option>
                        @foreach($lieux as $lieu)
                            <option value="{{ $lieu->id }}">{{ $lieu->nom }} ({{ $lieu->type->value }})</option>
                        @endforeach
                    </select>
                    <button type="button" @click="etapes.splice(index, 1)" x-show="etapes.length > 2"
                            class="text-gray-400 hover:text-red-600 px-2">
                        <i class="fas fa-xmark"></i>
                    </button>
                </div>
            </template>

            <button type="button" @click="etapes.push(null)"
                    class="mt-1 mb-4 text-sm text-amber-600 hover:text-amber-700 font-medium">
                <i class="fas fa-plus mr-1"></i> Ajouter un arrêt
            </button>

            <div class="flex gap-2">
                <button class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-sm font-medium">Créer le parcours</button>
                <button type="button" @click="formulaire = false" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Annuler</button>
            </div>
        </form>
    </div>

    {{-- Liste des parcours --}}
    <div class="space-y-3">
        @forelse($parcours as $p)
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <div class="flex items-start justify-between gap-4 mb-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="inline-flex px-2 py-0.5 rounded bg-gray-900 text-white text-xs font-bold">
                                {{ $p->ligne->code ?? '—' }}
                            </span>
                            <h3 class="font-semibold text-gray-900">{{ $p->libelle }}</h3>
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                                {{ $p->sens->value === 'aller' ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700' }}">
                                {{ ucfirst($p->sens->value) }}
                            </span>
                            @if($p->heure_depart)
                                <span class="text-xs text-gray-500">
                                    <i class="fas fa-clock mr-0.5"></i>{{ \Illuminate\Support\Str::of($p->heure_depart)->substr(0, 5) }}
                                </span>
                            @endif
                        </div>
                    </div>
                    <form method="POST" action="{{ route('admin.bus.parcours.destroy', $p) }}"
                          onsubmit="return confirm('Supprimer ce parcours ?')">
                        @csrf @method('DELETE')
                        <button class="text-gray-400 hover:text-red-600 px-2" title="Supprimer">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>

                {{-- La suite des arrêts, telle que le chauffeur la parcourt. --}}
                <div class="flex items-center gap-1.5 flex-wrap text-sm">
                    @forelse($p->etapes->sortBy('ordre') as $etape)
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-gray-100 text-gray-700 text-xs">
                            {{ $etape->lieu->nom ?? '—' }}
                            @if($etape->est_terminus)
                                <i class="fas fa-flag-checkered ml-1.5 text-gray-400" title="Terminus"></i>
                            @endif
                        </span>
                        @if(!$loop->last)
                            <i class="fas fa-chevron-right text-gray-300 text-xs"></i>
                        @endif
                    @empty
                        <span class="text-xs text-gray-400">Aucune étape définie.</span>
                    @endforelse
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl border border-gray-200 px-5 py-12 text-center">
                <i class="fas fa-diagram-project text-3xl text-gray-300"></i>
                <p class="mt-3 text-sm text-gray-500">Aucun parcours défini.</p>
            </div>
        @endforelse
    </div>

    @if($parcours->hasPages())
        <div class="mt-4">{{ $parcours->links() }}</div>
    @endif
</div>
@endsection
