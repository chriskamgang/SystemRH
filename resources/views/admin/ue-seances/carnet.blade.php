@extends('layouts.admin')
@section('title', 'Carnet TP - ' . $student->full_name)
@section('page-title', 'Carnet de compétences TP')

@section('content')
<div class="max-w-6xl mx-auto">
    {{-- En-tête étudiant --}}
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex justify-between items-start">
            <div>
                <h2 class="text-xl font-bold text-gray-800">{{ $student->full_name }}</h2>
                <div class="flex gap-4 mt-2 text-sm text-gray-500">
                    @if($student->specialite)
                        <span><i class="fas fa-graduation-cap mr-1"></i> {{ $student->specialite }}</span>
                    @endif
                    @if($student->niveau)
                        <span><i class="fas fa-layer-group mr-1"></i> {{ $student->niveau }}</span>
                    @endif
                    @if($student->matricule)
                        <span><i class="fas fa-id-badge mr-1"></i> {{ $student->matricule }}</span>
                    @endif
                </div>
            </div>
            <a href="{{ route('admin.tp-competences.dashboard') }}"
                class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg text-sm">
                <i class="fas fa-arrow-left mr-1"></i> Retour
            </a>
        </div>
    </div>

    {{-- Résumé global --}}
    @php
        $totalSeances = 0;
        $totalTp = 0;
        $totalObj = 0;
        foreach ($ues as $ue) {
            foreach ($ue->seances as $seance) {
                $totalSeances++;
                $val = $seance->validations->first();
                if ($val && $val->tp_effectue) $totalTp++;
                if ($val && $val->objectif_atteint) $totalObj++;
            }
        }
        $pctTp = $totalSeances > 0 ? round(($totalTp / $totalSeances) * 100) : 0;
        $pctObj = $totalSeances > 0 ? round(($totalObj / $totalSeances) * 100) : 0;
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-5 text-center">
            <div class="text-3xl font-bold text-gray-800">{{ $totalSeances }}</div>
            <div class="text-sm text-gray-500">Séances au total</div>
        </div>
        <div class="bg-white rounded-lg shadow p-5 text-center">
            <div class="text-3xl font-bold text-green-600">{{ $totalTp }} <span class="text-lg text-gray-400">({{ $pctTp }}%)</span></div>
            <div class="text-sm text-gray-500">TP effectués</div>
            <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                <div class="h-2 rounded-full bg-green-500" style="width: {{ $pctTp }}%"></div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-5 text-center">
            <div class="text-3xl font-bold text-purple-600">{{ $totalObj }} <span class="text-lg text-gray-400">({{ $pctObj }}%)</span></div>
            <div class="text-sm text-gray-500">Objectifs atteints</div>
            <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                <div class="h-2 rounded-full bg-purple-500" style="width: {{ $pctObj }}%"></div>
            </div>
        </div>
    </div>

    {{-- Détail par UE --}}
    @foreach($ues as $ue)
        @php
            $ueTp = 0;
            $ueObj = 0;
            $ueTotal = $ue->seances->count();
            foreach ($ue->seances as $s) {
                $v = $s->validations->first();
                if ($v && $v->tp_effectue) $ueTp++;
                if ($v && $v->objectif_atteint) $ueObj++;
            }
            $uePct = $ueTotal > 0 ? round(($ueTp / $ueTotal) * 100) : 0;
        @endphp
        <div class="bg-white rounded-lg shadow mb-4 overflow-hidden">
            <div class="px-6 py-4 border-b bg-gray-50 flex justify-between items-center">
                <div>
                    <span class="font-mono text-blue-600 font-bold">{{ $ue->code_ue }}</span>
                    <span class="font-medium text-gray-800 ml-2">{{ $ue->nom_matiere }}</span>
                    @if($ue->type_ue === 'tronc_commun')
                        <span class="px-2 py-0.5 bg-indigo-100 text-indigo-700 rounded text-xs font-bold ml-2">TC</span>
                    @endif
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-sm {{ $uePct >= 75 ? 'text-green-600' : ($uePct >= 50 ? 'text-yellow-600' : 'text-red-600') }} font-bold">
                        {{ $ueTp }}/{{ $ueTotal }} TP
                    </span>
                    <div class="w-20 bg-gray-200 rounded-full h-2">
                        <div class="h-2 rounded-full {{ $uePct >= 75 ? 'bg-green-500' : ($uePct >= 50 ? 'bg-yellow-500' : 'bg-red-500') }}"
                            style="width: {{ $uePct }}%"></div>
                    </div>
                </div>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500 w-8">#</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Séance</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Objectif</th>
                        <th class="px-4 py-2 text-center text-xs font-semibold text-gray-500">TP effectué</th>
                        <th class="px-4 py-2 text-center text-xs font-semibold text-gray-500">Objectif atteint</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($ue->seances as $seance)
                        @php $val = $seance->validations->first(); @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-gray-400 font-mono">{{ $seance->numero }}</td>
                            <td class="px-4 py-2 font-medium text-gray-800">{{ $seance->titre }}</td>
                            <td class="px-4 py-2 text-gray-600">{{ $seance->objectif }}</td>
                            <td class="px-4 py-2 text-center">
                                @if($val && $val->tp_effectue)
                                    <span class="text-green-500 text-lg"><i class="fas fa-check-circle"></i></span>
                                @else
                                    <span class="text-gray-300 text-lg"><i class="far fa-circle"></i></span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-center">
                                @if($val && $val->objectif_atteint)
                                    <span class="text-green-500 text-lg"><i class="fas fa-check-circle"></i></span>
                                @else
                                    <span class="text-gray-300 text-lg"><i class="far fa-circle"></i></span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach

    @if($ues->count() === 0)
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-8 text-center">
            <p class="text-gray-600">Aucune UE avec séances TP pour cet étudiant.</p>
        </div>
    @endif
</div>
@endsection
