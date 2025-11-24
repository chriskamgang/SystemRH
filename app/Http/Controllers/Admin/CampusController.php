<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CampusController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $campuses = Campus::withCount(['users', 'attendances'])
            ->orderBy('name')
            ->get();

        return view('admin.campuses.index', compact('campuses'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.campuses.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'required|integer|min:10|max:10000',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        Campus::create([
            'name' => $request->name,
            'address' => $request->address,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'radius' => $request->radius,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()
            ->route('admin.campuses.index')
            ->with('success', 'Campus créé avec succès');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $campus = Campus::withCount(['users', 'attendances'])
            ->findOrFail($id);

        // Get recent attendances for this campus
        $recent_attendances = $campus->attendances()
            ->with('user')
            ->orderBy('timestamp', 'desc')
            ->limit(10)
            ->get();

        return view('admin.campuses.show', compact('campus', 'recent_attendances'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $campus = Campus::findOrFail($id);
        return view('admin.campuses.edit', compact('campus'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $campus = Campus::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'required|integer|min:10|max:10000',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }

        $campus->update([
            'name' => $request->name,
            'address' => $request->address,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'radius' => $request->radius,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()
            ->route('admin.campuses.index')
            ->with('success', 'Campus mis à jour avec succès');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $campus = Campus::findOrFail($id);

        // Check if campus has associated users
        if ($campus->users()->count() > 0) {
            return redirect()
                ->back()
                ->with('error', 'Impossible de supprimer ce campus car des employés y sont affectés');
        }

        $campus->delete();

        return redirect()
            ->route('admin.campuses.index')
            ->with('success', 'Campus supprimé avec succès');
    }
}
