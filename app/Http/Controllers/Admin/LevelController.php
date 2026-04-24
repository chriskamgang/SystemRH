<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Level;
use Illuminate\Http\Request;

class LevelController extends Controller
{
    public function index()
    {
        $levels = Level::orderBy('name')->get();
        return view('admin.levels.index', compact('levels'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:levels',
            'code' => 'nullable|string|max:50',
        ]);

        Level::create([
            'name' => $request->name,
            'code' => $request->code,
            'is_active' => true,
        ]);

        return redirect()->back()->with('success', 'Niveau créé avec succès');
    }

    public function update(Request $request, Level $level)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:levels,name,' . $level->id,
            'code' => 'nullable|string|max:50',
        ]);

        $level->update([
            'name' => $request->name,
            'code' => $request->code,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->back()->with('success', 'Niveau mis à jour');
    }

    public function destroy(Level $level)
    {
        if ($level->students()->count() > 0) {
            return redirect()->back()->with('error', 'Impossible de supprimer ce niveau car il contient des étudiants');
        }

        $level->delete();
        return redirect()->back()->with('success', 'Niveau supprimé');
    }
}
