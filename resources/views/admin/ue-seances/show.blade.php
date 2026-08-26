@extends('layouts.admin')
@section('title', 'Séances TP - ' . $ue->code_ue)
@section('page-title', 'Suivi des séances TP')

@section('content')
<div class="max-w-6xl mx-auto">
    {{-- En-tête UE --}}
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex justify-between items-start">
            <div>
                <h2 class="text-xl font-bold text-gray-800">{{ $ue->code_ue }} — {{ $ue->nom_matiere }}</h2>
                <div class="flex gap-4 mt-2 text-sm text-gray-500">
                    @if($ue->enseignant)
                        <span><i class="fas fa-user-tie mr-1"></i> {{ $ue->enseignant->full_name }}</span>
                    @endif
                    @if($ue->niveau)
                        <span><i class="fas fa-layer-group mr-1"></i> {{ $ue->niveau }}</span>
                    @endif
                    @if($ue->type_ue === 'tronc_commun')
                        <span class="px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded text-xs font-bold">TC — {{ implode(', ', $ue->groupes ?? []) }}</span>
                    @elseif($ue->specialite)
                        <span><i class="fas fa-graduation-cap mr-1"></i> {{ $ue->specialite }}</span>
                    @endif
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.unites-enseignement.seances.edit', $ue) }}"
                    class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg text-sm">
                    <i class="fas fa-edit mr-1"></i> Modifier les séances
                </a>
                <a href="{{ route('admin.tp-competences.dashboard') }}"
                    class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg text-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Retour
                </a>
            </div>
        </div>
    </div>

    @if($ue->seances->count() === 0)
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-8 text-center">
            <i class="fas fa-clipboard-list text-4xl text-yellow-400 mb-4"></i>
            <p class="text-gray-600 mb-4">Aucune séance TP configurée pour cette UE.</p>
            <a href="{{ route('admin.unites-enseignement.seances.edit', $ue) }}"
                class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                <i class="fas fa-plus mr-1"></i> Configurer les séances
            </a>
        </div>
    @else
        {{-- Tableau séances / objectifs / validations étudiants --}}
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 w-8">#</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Séance</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600">Objectif</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-600">TP effectués</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-600">Objectifs atteints</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($ue->seances as $seance)
                            @php
                                $totalStudents = $students->count();
                                $tpCount = $seance->validations->where('tp_effectue', true)->count();
                                $objCount = $seance->validations->where('objectif_atteint', true)->count();
                                $tpPct = $totalStudents > 0 ? round(($tpCount / $totalStudents) * 100) : 0;
                                $objPct = $totalStudents > 0 ? round(($objCount / $totalStudents) * 100) : 0;
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-gray-400 font-mono">{{ $seance->numero }}</td>
                                <td class="px-4 py-3 font-medium text-gray-800">{{ $seance->titre }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $seance->objectif }}</td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <span class="font-bold {{ $tpPct >= 75 ? 'text-green-600' : ($tpPct >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                                            {{ $tpCount }}/{{ $totalStudents }}
                                        </span>
                                        <div class="w-16 bg-gray-200 rounded-full h-2">
                                            <div class="h-2 rounded-full {{ $tpPct >= 75 ? 'bg-green-500' : ($tpPct >= 50 ? 'bg-yellow-500' : 'bg-red-500') }}"
                                                style="width: {{ $tpPct }}%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <span class="font-bold {{ $objPct >= 75 ? 'text-green-600' : ($objPct >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                                            {{ $objCount }}/{{ $totalStudents }}
                                        </span>
                                        <div class="w-16 bg-gray-200 rounded-full h-2">
                                            <div class="h-2 rounded-full {{ $objPct >= 75 ? 'bg-green-500' : ($objPct >= 50 ? 'bg-yellow-500' : 'bg-red-500') }}"
                                                style="width: {{ $objPct }}%"></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Détail par étudiant --}}
        @if($students->count() > 0)
        <div class="bg-white rounded-lg shadow mt-6 overflow-hidden">
            <div class="px-6 py-4 border-b bg-gray-50">
                <h3 class="font-bold text-gray-700"><i class="fas fa-users mr-2"></i> Validations par étudiant ({{ $students->count() }})</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 sticky left-0 bg-gray-50">Étudiant</th>
                            @foreach($ue->seances as $seance)
                                <th class="px-3 py-3 text-center font-semibold text-gray-600 min-w-[120px]" colspan="2">
                                    <div class="text-xs">S{{ $seance->numero }}</div>
                                    <div class="text-[10px] text-gray-400 font-normal truncate max-w-[120px]">{{ $seance->titre }}</div>
                                </th>
                            @endforeach
                        </tr>
                        <tr class="bg-gray-100">
                            <th class="px-4 py-1 sticky left-0 bg-gray-100"></th>
                            @foreach($ue->seances as $seance)
                                <th class="px-1 py-1 text-center text-[10px] text-gray-500">TP</th>
                                <th class="px-1 py-1 text-center text-[10px] text-gray-500">Obj</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($students as $student)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 font-medium text-gray-800 sticky left-0 bg-white whitespace-nowrap">
                                    <a href="{{ route('admin.tp-competences.carnet', $student) }}" class="hover:text-blue-600">
                                        {{ $student->last_name }} {{ $student->first_name }}
                                    </a>
                                </td>
                                @foreach($ue->seances as $seance)
                                    @php
                                        $val = $seance->validations->where('user_id', $student->id)->first();
                                    @endphp
                                    <td class="px-1 py-2 text-center">
                                        @if($val && $val->tp_effectue)
                                            <span class="text-green-500"><i class="fas fa-check-circle"></i></span>
                                        @else
                                            <span class="text-gray-300"><i class="far fa-circle"></i></span>
                                        @endif
                                    </td>
                                    <td class="px-1 py-2 text-center">
                                        @if($val && $val->objectif_atteint)
                                            <span class="text-green-500"><i class="fas fa-check-circle"></i></span>
                                        @else
                                            <span class="text-gray-300"><i class="far fa-circle"></i></span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    @endif
</div>
@endsection
