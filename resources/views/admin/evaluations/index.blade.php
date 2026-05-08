@extends('layouts.admin')

@section('title', 'Évaluations du Personnel')
@section('page-title', 'Évaluations du Personnel')

@section('content')
<div class="space-y-6">

    {{-- ── Header ─────────────────────────────────────────────────────────── --}}
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Évaluations du Personnel</h2>
            <p class="text-gray-600 mt-1">Gérez les campagnes d'évaluation et suivez les performances</p>
        </div>
        <a href="{{ route('admin.evaluations.create-campaign') }}"
           class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition font-semibold">
            <i class="fas fa-plus mr-2"></i>Nouvelle Campagne
        </a>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center gap-2">
        <i class="fas fa-check-circle text-green-500"></i>
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex items-center gap-2">
        <i class="fas fa-exclamation-circle text-red-500"></i>
        {{ session('error') }}
    </div>
    @endif

    {{-- ── Global Stats Cards ──────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-blue-100 rounded-lg shrink-0">
                    <i class="fas fa-clipboard-list text-blue-600 text-lg"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Total Campagnes</p>
                    <p class="text-2xl font-bold text-gray-900 leading-tight">{{ $globalStats['total_campaigns'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-green-100 rounded-lg shrink-0">
                    <i class="fas fa-play-circle text-green-600 text-lg"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Actives</p>
                    <p class="text-2xl font-bold text-gray-900 leading-tight">{{ $globalStats['active_campaigns'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-purple-100 rounded-lg shrink-0">
                    <i class="fas fa-file-alt text-purple-600 text-lg"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Total Évaluations</p>
                    <p class="text-2xl font-bold text-gray-900 leading-tight">{{ $globalStats['total_evaluations'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-emerald-100 rounded-lg shrink-0">
                    <i class="fas fa-check-double text-emerald-600 text-lg"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Terminées</p>
                    <p class="text-2xl font-bold text-gray-900 leading-tight">{{ $globalStats['completed_evaluations'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-yellow-100 rounded-lg shrink-0">
                    <i class="fas fa-star text-yellow-500 text-lg"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Score Moyen</p>
                    <p class="text-2xl font-bold text-gray-900 leading-tight">
                        {{ $globalStats['avg_score'] > 0 ? $globalStats['avg_score'].'/5' : '–' }}
                    </p>
                </div>
            </div>
        </div>

    </div>

    {{-- ── Filters ─────────────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <form method="GET" action="{{ route('admin.evaluations.index') }}"
              class="grid grid-cols-1 md:grid-cols-4 gap-4">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Rechercher</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Titre, description…"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm
                              focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                <select name="status"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm
                               focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Tous les statuts</option>
                    <option value="draft"  {{ request('status') == 'draft'  ? 'selected' : '' }}>Brouillon</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>Clôturée</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Année</label>
                <select name="year"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm
                               focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Toutes les années</option>
                    @foreach($availableYears as $yr)
                        <option value="{{ $yr }}" {{ request('year') == $yr ? 'selected' : '' }}>
                            {{ $yr }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit"
                        class="px-4 py-2 bg-gray-800 hover:bg-gray-900 text-white rounded-lg text-sm transition">
                    <i class="fas fa-search mr-1"></i>Filtrer
                </button>
                <a href="{{ route('admin.evaluations.index') }}"
                   class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg text-sm transition">
                    <i class="fas fa-times"></i>
                </a>
            </div>

        </form>
    </div>

    {{-- ── Campaigns Table ─────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">

        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-800">
                Campagnes d'Évaluation
                <span class="ml-2 text-sm font-normal text-gray-500">
                    ({{ $campaigns->total() }} résultat{{ $campaigns->total() > 1 ? 's' : '' }})
                </span>
            </h3>
        </div>

        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campagne</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Année</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Période</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Évaluations</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Progression</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Score Moy.</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($campaigns as $campaign)
                @php $cs = $campaign->computed_stats; @endphp
                <tr class="hover:bg-gray-50 transition-colors">

                    {{-- Title & description --}}
                    <td class="px-6 py-4">
                        <div class="font-semibold text-gray-900">{{ $campaign->title }}</div>
                        @if($campaign->description)
                            <div class="text-xs text-gray-500 mt-0.5">
                                {{ \Illuminate\Support\Str::limit($campaign->description, 70) }}
                            </div>
                        @endif
                        <div class="text-xs text-gray-400 mt-1">
                            {{ $campaign->criteria->count() }} critère{{ $campaign->criteria->count() > 1 ? 's' : '' }}
                        </div>
                    </td>

                    {{-- Year --}}
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-700">
                        {{ $campaign->year }}
                    </td>

                    {{-- Period --}}
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                        <div>{{ $campaign->start_date?->format('d/m/Y') }}</div>
                        <div class="text-gray-400 text-xs">→ {{ $campaign->end_date?->format('d/m/Y') }}</div>
                    </td>

                    {{-- Count --}}
                    <td class="px-6 py-4 text-center">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                            {{ $cs['totalEvaluations'] }}
                        </span>
                        @if($cs['pendingEvaluations'] > 0)
                            <div class="text-xs text-orange-500 mt-0.5">{{ $cs['pendingEvaluations'] }} en attente</div>
                        @endif
                    </td>

                    {{-- Progress bar --}}
                    <td class="px-6 py-4" style="min-width: 140px">
                        @if($cs['totalEvaluations'] > 0)
                            <div class="flex items-center gap-2">
                                <div class="flex-1 bg-gray-200 rounded-full h-2">
                                    <div class="bg-green-500 h-2 rounded-full transition-all"
                                         style="width: {{ $cs['progressPct'] }}%"></div>
                                </div>
                                <span class="text-xs font-medium text-gray-700 w-8 text-right">{{ $cs['progressPct'] }}%</span>
                            </div>
                            <div class="text-xs text-gray-400 mt-1">
                                {{ $cs['completedEvaluations'] }}/{{ $cs['totalEvaluations'] }} validées
                            </div>
                        @else
                            <span class="text-xs text-gray-400">–</span>
                        @endif
                    </td>

                    {{-- Average score --}}
                    <td class="px-6 py-4 text-center">
                        @if($cs['avgScore'] !== null)
                            <div class="text-sm font-bold text-gray-800">
                                {{ $cs['avgScore'] }}<span class="text-gray-400 font-normal">/5</span>
                            </div>
                            <div class="flex justify-center mt-0.5">
                                @for($i = 1; $i <= 5; $i++)
                                    @if($i <= round($cs['avgScore']))
                                        <i class="fas fa-star text-yellow-400 text-xs"></i>
                                    @else
                                        <i class="far fa-star text-gray-300 text-xs"></i>
                                    @endif
                                @endfor
                            </div>
                        @else
                            <span class="text-gray-400 text-sm">–</span>
                        @endif
                    </td>

                    {{-- Status badge --}}
                    <td class="px-6 py-4 text-center whitespace-nowrap">
                        @if($campaign->status === 'active')
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500 inline-block"></span>
                                Active
                            </span>
                        @elseif($campaign->status === 'draft')
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-400 inline-block"></span>
                                Brouillon
                            </span>
                        @elseif($campaign->status === 'closed')
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-400 inline-block"></span>
                                Clôturée
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                                {{ $campaign->status }}
                            </span>
                        @endif
                    </td>

                    {{-- Actions --}}
                    <td class="px-6 py-4 text-center whitespace-nowrap">
                        <a href="{{ route('admin.evaluations.show', $campaign->id) }}"
                           class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-800 text-sm font-medium transition">
                            <i class="fas fa-eye"></i>
                            <span>Détail</span>
                        </a>
                    </td>

                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-16 text-center">
                        <div class="flex flex-col items-center gap-3">
                            <div class="p-4 bg-gray-100 rounded-full">
                                <i class="fas fa-clipboard-list text-gray-400 text-3xl"></i>
                            </div>
                            <p class="text-gray-500 font-medium">Aucune campagne d'évaluation trouvée</p>
                            <a href="{{ route('admin.evaluations.create-campaign') }}"
                               class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                Créer la première campagne
                            </a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($campaigns->hasPages())
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $campaigns->appends(request()->query())->links() }}
        </div>
        @endif

    </div>

    {{-- ── Status legend ────────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <p class="text-xs font-semibold text-gray-500 uppercase mb-3">Légende des Statuts</p>
        <div class="flex flex-wrap gap-6">
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">
                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400 inline-block"></span>Brouillon
                </span>
                <span class="text-xs text-gray-500">En cours de préparation, non visible par les employés</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                    <span class="w-1.5 h-1.5 rounded-full bg-green-500 inline-block"></span>Active
                </span>
                <span class="text-xs text-gray-500">Évaluations en cours — accessibles aux évaluateurs et employés</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400 inline-block"></span>Clôturée
                </span>
                <span class="text-xs text-gray-500">Campagne terminée — résultats finaux disponibles</span>
            </div>
        </div>
    </div>

</div>
@endsection
