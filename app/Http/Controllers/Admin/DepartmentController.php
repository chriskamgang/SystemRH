<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::withCount('users')
            ->with(['campus', 'head'])
            ->orderBy('name')
            ->get();

        return view('admin.departments.index', compact('departments'));
    }

    public function create()
    {
        $campuses = Campus::where('is_active', true)->orderBy('name')->get();
        $users = User::where('is_active', true)->orderBy('first_name')->orderBy('last_name')->get();

        return view('admin.departments.create', compact('campuses', 'users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:150',
            'code'        => 'nullable|string|max:20|unique:departments,code',
            'description' => 'nullable|string|max:500',
            'campus_id'   => 'nullable|exists:campuses,id',
            'head_user_id'=> 'nullable|exists:users,id',
            'is_active'   => 'boolean',
        ]);

        Department::create([
            'name'         => $request->name,
            'code'         => $request->code ?: null,
            'description'  => $request->description,
            'campus_id'    => $request->campus_id,
            'head_user_id' => $request->head_user_id,
            'is_active'    => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.departments.index')
            ->with('success', 'Département créé avec succès.');
    }

    public function edit(string $id)
    {
        $department = Department::findOrFail($id);
        $campuses = Campus::where('is_active', true)->orderBy('name')->get();
        $users = User::where('is_active', true)->orderBy('first_name')->orderBy('last_name')->get();

        return view('admin.departments.edit', compact('department', 'campuses', 'users'));
    }

    public function update(Request $request, string $id)
    {
        $department = Department::findOrFail($id);

        $request->validate([
            'name'         => 'required|string|max:150',
            'code'         => 'nullable|string|max:20|unique:departments,code,' . $id,
            'description'  => 'nullable|string|max:500',
            'campus_id'    => 'nullable|exists:campuses,id',
            'head_user_id' => 'nullable|exists:users,id',
            'is_active'    => 'boolean',
        ]);

        $department->update([
            'name'         => $request->name,
            'code'         => $request->code ?: null,
            'description'  => $request->description,
            'campus_id'    => $request->campus_id,
            'head_user_id' => $request->head_user_id,
            'is_active'    => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.departments.index')
            ->with('success', 'Département mis à jour avec succès.');
    }

    public function destroy(string $id)
    {
        $department = Department::withCount('users')->findOrFail($id);

        if ($department->users_count > 0) {
            return redirect()->route('admin.departments.index')
                ->with('error', 'Impossible de supprimer ce département : ' . $department->users_count . ' employé(s) y sont rattachés.');
        }

        $department->delete();

        return redirect()->route('admin.departments.index')
            ->with('success', 'Département supprimé avec succès.');
    }
}
