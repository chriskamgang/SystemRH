<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UeSchedule;
use App\Models\UniteEnseignement;
use App\Models\Campus;
use App\Models\User;
use Illuminate\Http\Request;

class UeScheduleController extends Controller
{
    public function index(Request $request)
    {
        $query = UeSchedule::with(['uniteEnseignement.enseignant', 'campus', 'creator']);

        if ($request->filled('enseignant_id')) {
            $query->whereHas('uniteEnseignement', function ($q) use ($request) {
                $q->where('enseignant_id', $request->enseignant_id);
            });
        }

        if ($request->filled('campus_id')) {
            $query->where('campus_id', $request->campus_id);
        }

        if ($request->filled('jour_semaine')) {
            $query->where('jour_semaine', $request->jour_semaine);
        }

        $schedules = $query->orderByRaw("FIELD(jour_semaine, 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche')")
            ->orderBy('heure_debut')
            ->paginate(25);

        $enseignants = User::whereIn('employee_type', ['enseignant_vacataire', 'semi_permanent', 'enseignant_titulaire'])
            ->where('is_active', true)
            ->orderBy('last_name')
            ->get();

        $campuses = Campus::where('is_active', true)->orderBy('name')->get();

        return view('admin.emploi-du-temps.index', compact('schedules', 'enseignants', 'campuses'));
    }

    public function create()
    {
        $ues = UniteEnseignement::where('statut', 'activee')
            ->with('enseignant')
            ->orderBy('code_ue')
            ->get();

        $campuses = Campus::where('is_active', true)->orderBy('name')->get();

        return view('admin.emploi-du-temps.create', compact('ues', 'campuses'));
    }

    public function store(Request $request)
    {
        $validJours = ['lundi','mardi','mercredi','jeudi','vendredi','samedi','dimanche'];

        $request->validate([
            'unite_enseignement_id' => 'required|exists:unites_enseignement,id',
            'jours' => 'required|array|min:1',
            'date_debut_validite' => 'nullable|date',
            'date_fin_validite' => 'nullable|date|after_or_equal:date_debut_validite',
        ]);

        $created = 0;
        $skipped = [];

        foreach ($request->jours as $jour => $slots) {
            if (!in_array($jour, $validJours)) {
                continue;
            }

            // Supporter l'ancien format (un seul créneau) et le nouveau (tableau de créneaux)
            if (isset($slots['heure_debut'])) {
                $slots = [$slots];
            }

            foreach ($slots as $data) {
                if (empty($data['heure_debut']) || empty($data['heure_fin'])) {
                    continue;
                }

                if ($data['heure_fin'] <= $data['heure_debut']) {
                    $skipped[] = ucfirst($jour) . ' (heure fin avant début)';
                    continue;
                }

                $campusId = $data['campus_id'] ?? $request->campus_id;
                if (empty($campusId)) {
                    $skipped[] = ucfirst($jour) . ' (campus non sélectionné)';
                    continue;
                }

                $exists = UeSchedule::where('unite_enseignement_id', $request->unite_enseignement_id)
                    ->where('jour_semaine', $jour)
                    ->where('heure_debut', $data['heure_debut'])
                    ->exists();

                if ($exists) {
                    $skipped[] = ucfirst($jour) . ' ' . $data['heure_debut'] . ' (déjà existant)';
                    continue;
                }

                UeSchedule::create([
                    'unite_enseignement_id' => $request->unite_enseignement_id,
                    'campus_id' => $campusId,
                    'jour_semaine' => $jour,
                    'heure_debut' => $data['heure_debut'],
                    'heure_fin' => $data['heure_fin'],
                    'salle' => $data['salle'] ?? null,
                    'date_debut_validite' => $request->date_debut_validite,
                    'date_fin_validite' => $request->date_fin_validite,
                    'created_by' => auth()->id(),
                ]);
                $created++;
            }
        }

        $message = "{$created} créneau(x) créé(s) avec succès.";
        if (!empty($skipped)) {
            $message .= ' Ignorés : ' . implode(', ', $skipped) . '.';
            return redirect()->route('admin.emploi-du-temps.index')->with('warning', $message);
        }

        return redirect()->route('admin.emploi-du-temps.index')
            ->with('success', $message);
    }

    public function edit($id)
    {
        $schedule = UeSchedule::with(['uniteEnseignement.enseignant', 'campus'])->findOrFail($id);

        $ues = UniteEnseignement::where('statut', 'activee')
            ->with('enseignant')
            ->orderBy('code_ue')
            ->get();

        $campuses = Campus::where('is_active', true)->orderBy('name')->get();

        return view('admin.emploi-du-temps.edit', compact('schedule', 'ues', 'campuses'));
    }

    public function update(Request $request, $id)
    {
        $schedule = UeSchedule::findOrFail($id);

        $request->validate([
            'unite_enseignement_id' => 'required|exists:unites_enseignement,id',
            'campus_id' => 'required|exists:campuses,id',
            'jour_semaine' => 'required|in:lundi,mardi,mercredi,jeudi,vendredi,samedi,dimanche',
            'heure_debut' => 'required|date_format:H:i',
            'heure_fin' => 'required|date_format:H:i|after:heure_debut',
            'salle' => 'nullable|string|max:50',
            'date_debut_validite' => 'nullable|date',
            'date_fin_validite' => 'nullable|date|after_or_equal:date_debut_validite',
            'is_active' => 'boolean',
        ]);

        // Vérifier l'unicité (sauf pour le créneau actuel)
        $exists = UeSchedule::where('unite_enseignement_id', $request->unite_enseignement_id)
            ->where('jour_semaine', $request->jour_semaine)
            ->where('heure_debut', $request->heure_debut)
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['heure_debut' => 'Un créneau existe déjà pour cette UE à ce jour et cette heure.'])->withInput();
        }

        $schedule->update([
            'unite_enseignement_id' => $request->unite_enseignement_id,
            'campus_id' => $request->campus_id,
            'jour_semaine' => $request->jour_semaine,
            'heure_debut' => $request->heure_debut,
            'heure_fin' => $request->heure_fin,
            'salle' => $request->salle,
            'date_debut_validite' => $request->date_debut_validite,
            'date_fin_validite' => $request->date_fin_validite,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.emploi-du-temps.index')
            ->with('success', 'Créneau modifié avec succès.');
    }

    public function destroy($id)
    {
        $schedule = UeSchedule::findOrFail($id);
        $schedule->delete();

        return redirect()->route('admin.emploi-du-temps.index')
            ->with('success', 'Créneau supprimé avec succès.');
    }

    public function byEnseignant($id)
    {
        $enseignant = User::findOrFail($id);

        $ueIds = UniteEnseignement::where('enseignant_id', $id)
            ->where('statut', 'activee')
            ->pluck('id');

        $schedules = UeSchedule::whereIn('unite_enseignement_id', $ueIds)
            ->where('is_active', true)
            ->with(['uniteEnseignement', 'campus'])
            ->orderByRaw("FIELD(jour_semaine, 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche')")
            ->orderBy('heure_debut')
            ->get()
            ->groupBy('jour_semaine');

        $jours = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];

        return view('admin.emploi-du-temps.by-enseignant', compact('enseignant', 'schedules', 'jours'));
    }

    public function bulkCreate()
    {
        $ues = UniteEnseignement::where('statut', 'activee')
            ->with('enseignant')
            ->orderBy('code_ue')
            ->get();

        $campuses = Campus::where('is_active', true)->orderBy('name')->get();

        return view('admin.emploi-du-temps.bulk-create', compact('ues', 'campuses'));
    }

    public function bulkStore(Request $request)
    {
        $request->validate([
            'slots' => 'required|array|min:1',
            'slots.*.unite_enseignement_id' => 'required|exists:unites_enseignement,id',
            'slots.*.campus_id' => 'required|exists:campuses,id',
            'slots.*.jour_semaine' => 'required|in:lundi,mardi,mercredi,jeudi,vendredi,samedi,dimanche',
            'slots.*.heure_debut' => 'required|date_format:H:i',
            'slots.*.heure_fin' => 'required|date_format:H:i',
            'slots.*.salle' => 'nullable|string|max:50',
            'date_debut_validite' => 'nullable|date',
            'date_fin_validite' => 'nullable|date|after_or_equal:date_debut_validite',
        ]);

        $created = 0;
        $errors = [];

        foreach ($request->slots as $index => $slot) {
            $exists = UeSchedule::where('unite_enseignement_id', $slot['unite_enseignement_id'])
                ->where('jour_semaine', $slot['jour_semaine'])
                ->where('heure_debut', $slot['heure_debut'])
                ->exists();

            if ($exists) {
                $errors[] = "Ligne " . ($index + 1) . " : créneau déjà existant.";
                continue;
            }

            UeSchedule::create([
                'unite_enseignement_id' => $slot['unite_enseignement_id'],
                'campus_id' => $slot['campus_id'],
                'jour_semaine' => $slot['jour_semaine'],
                'heure_debut' => $slot['heure_debut'],
                'heure_fin' => $slot['heure_fin'],
                'salle' => $slot['salle'] ?? null,
                'date_debut_validite' => $request->date_debut_validite,
                'date_fin_validite' => $request->date_fin_validite,
                'created_by' => auth()->id(),
            ]);
            $created++;
        }

        $message = "{$created} créneau(x) créé(s) avec succès.";
        if (!empty($errors)) {
            $message .= ' Erreurs : ' . implode(' ', $errors);
        }

        return redirect()->route('admin.emploi-du-temps.index')
            ->with($errors ? 'warning' : 'success', $message);
    }
}
