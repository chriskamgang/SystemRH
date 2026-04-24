<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Specialty;
use App\Models\Department;
use Illuminate\Http\Request;

class SpecialtyController extends Controller
{
    public function index()
    {
        $specialties = Specialty::with('department')->orderBy('name')->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        return view('admin.specialties.index', compact('specialties', 'departments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:specialties',
            'code' => 'nullable|string|max:50',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        Specialty::create([
            'name' => $request->name,
            'code' => $request->code,
            'department_id' => $request->department_id,
            'is_active' => true,
        ]);

        return redirect()->back()->with('success', 'Spécialité créée avec succès');
    }

    public function update(Request $request, Specialty $specialty)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:specialties,name,' . $specialty->id,
            'code' => 'nullable|string|max:50',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $specialty->update([
            'name' => $request->name,
            'code' => $request->code,
            'department_id' => $request->department_id,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->back()->with('success', 'Spécialité mise à jour');
    }

    public function destroy(Specialty $specialty)
    {
        if ($specialty->students()->count() > 0) {
            return redirect()->back()->with('error', 'Impossible de supprimer cette spécialité car elle contient des étudiants');
        }

        $specialty->delete();
        return redirect()->back()->with('success', 'Spécialité supprimée');
    }
}
