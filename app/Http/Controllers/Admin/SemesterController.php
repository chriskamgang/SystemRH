<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Semester;
use Illuminate\Http\Request;

class SemesterController extends Controller
{
    /**
     * Display a listing of the semesters.
     */
    public function index()
    {
        $semesters = Semester::orderBy('annee_academique', 'desc')
            ->orderBy('numero_semestre', 'desc')
            ->paginate(15);

        return view('admin.semesters.index', compact('semesters'));
    }

    /**
     * Show the form for creating a new semester.
     */
    public function create()
    {
        return view('admin.semesters.create');
    }

    /**
     * Store a newly created semester in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:semesters,code',
            'annee_academique' => 'required|string|max:20',
            'numero_semestre' => 'required|integer|in:1,2',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after:date_debut',
            'description' => 'nullable|string',
        ], [
            'code.unique' => 'Ce code de semestre existe déjà',
            'date_fin.after' => 'La date de fin doit être après la date de début',
            'numero_semestre.in' => 'Le numéro de semestre doit être 1 ou 2',
        ]);

        $semester = Semester::create($validated);

        return redirect()->route('admin.semesters.index')
            ->with('success', 'Semestre créé avec succès');
    }

    /**
     * Display the specified semester.
     */
    public function show(Semester $semester)
    {
        $semester->load('unitesEnseignement.vacataire');

        return view('admin.semesters.show', compact('semester'));
    }

    /**
     * Show the form for editing the specified semester.
     */
    public function edit(Semester $semester)
    {
        return view('admin.semesters.edit', compact('semester'));
    }

    /**
     * Update the specified semester in storage.
     */
    public function update(Request $request, Semester $semester)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:semesters,code,' . $semester->id,
            'annee_academique' => 'required|string|max:20',
            'numero_semestre' => 'required|integer|in:1,2',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after:date_debut',
            'description' => 'nullable|string',
        ], [
            'code.unique' => 'Ce code de semestre existe déjà',
            'date_fin.after' => 'La date de fin doit être après la date de début',
            'numero_semestre.in' => 'Le numéro de semestre doit être 1 ou 2',
        ]);

        $semester->update($validated);

        return redirect()->route('admin.semesters.index')
            ->with('success', 'Semestre mis à jour avec succès');
    }

    /**
     * Remove the specified semester from storage.
     */
    public function destroy(Semester $semester)
    {
        // Vérifier si le semestre a des UE liées
        if ($semester->unitesEnseignement()->count() > 0) {
            return redirect()->route('admin.semesters.index')
                ->with('error', 'Impossible de supprimer ce semestre car il contient des unités d\'enseignement');
        }

        $semester->delete();

        return redirect()->route('admin.semesters.index')
            ->with('success', 'Semestre supprimé avec succès');
    }

    /**
     * Activate the specified semester and deactivate all others.
     */
    public function activate(Semester $semester)
    {
        $semester->activate();

        return redirect()->route('admin.semesters.index')
            ->with('success', "Le semestre {$semester->name} est maintenant actif");
    }

    /**
     * Deactivate the specified semester.
     */
    public function deactivate(Semester $semester)
    {
        $semester->update(['is_active' => false]);

        return redirect()->route('admin.semesters.index')
            ->with('success', "Le semestre {$semester->name} a été désactivé");
    }
}
