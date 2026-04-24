<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicResult;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AcademicResultController extends Controller
{
    public function index(Request $request)
    {
        $query = AcademicResult::with('user');

        if ($request->search) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('first_name', 'like', "%$search%")
                  ->orWhere('last_name', 'like', "%$search%")
                  ->orWhere('employee_id', 'like', "%$search%");
            })->orWhere('title', 'like', "%$search%");
        }

        $results = $query->latest()->paginate(20);
        return view('admin.results.index', compact('results'));
    }

    public function create()
    {
        $students = User::where('employee_type', 'etudiant')->orderBy('last_name')->get();
        return view('admin.results.create', compact('students'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'type' => 'required|string',
            'file' => 'required|file|mimes:pdf,jpg,png|max:5120',
            'semester' => 'nullable|integer',
            'academic_year' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $path = $request->file('file')->store('results', 'public');

        AcademicResult::create([
            'user_id' => $request->user_id,
            'title' => $request->title,
            'type' => $request->type,
            'file_path' => $path,
            'semester' => $request->semester,
            'academic_year' => $request->academic_year,
            'description' => $request->description,
        ]);

        return redirect()->route('admin.results.index')->with('success', 'Résultat ajouté avec succès.');
    }

    public function destroy($id)
    {
        $result = AcademicResult::findOrFail($id);
        if ($result->file_path) {
            Storage::disk('public')->delete($result->file_path);
        }
        $result->delete();

        return redirect()->route('admin.results.index')->with('success', 'Résultat supprimé.');
    }
}
