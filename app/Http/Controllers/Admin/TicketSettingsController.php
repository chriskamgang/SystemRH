<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\TicketService;
use App\Models\TicketCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TicketSettingsController extends Controller
{
    /**
     * Show the ticket settings page (services + categories).
     */
    public function index()
    {
        $services = TicketService::orderBy('sort_order')->get();
        $categories = TicketCategory::orderBy('sort_order')->get();
        $departments = Department::orderBy('name')->get();

        return view('admin.tickets.settings', compact('services', 'categories', 'departments'));
    }

    // ─── Services ─────────────────────────────────

    public function storeService(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:ticket_services,name',
            'icon' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:7',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $maxOrder = TicketService::max('sort_order') ?? -1;

        TicketService::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'icon' => $request->icon,
            'color' => $request->color,
            'is_active' => true,
            'sort_order' => $maxOrder + 1,
            'department_id' => $request->department_id,
        ]);

        return back()->with('success', 'Service ajoute avec succes.');
    }

    public function updateService(Request $request, $id)
    {
        $service = TicketService::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:ticket_services,name,' . $id,
            'icon' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:7',
            'sort_order' => 'nullable|integer|min:0',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $service->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'icon' => $request->icon,
            'color' => $request->color,
            'sort_order' => $request->sort_order ?? $service->sort_order,
            'department_id' => $request->department_id,
        ]);

        return back()->with('success', 'Service mis a jour.');
    }

    public function toggleService($id)
    {
        $service = TicketService::findOrFail($id);
        $service->update(['is_active' => !$service->is_active]);

        return back()->with('success', 'Statut du service modifie.');
    }

    public function destroyService($id)
    {
        $service = TicketService::findOrFail($id);

        // Check if any tickets reference this service
        $count = Ticket::where('target_service', $service->slug)
            ->orWhere('assigned_to_service', $service->slug)
            ->count();

        if ($count > 0) {
            return back()->with('error', 'Impossible de supprimer ce service : ' . $count . ' ticket(s) y font reference. Vous pouvez le desactiver a la place.');
        }

        $service->delete();

        return back()->with('success', 'Service supprime.');
    }

    // ─── Categories ───────────────────────────────

    public function storeCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:ticket_categories,name',
        ]);

        $maxOrder = TicketCategory::max('sort_order') ?? -1;

        TicketCategory::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'is_active' => true,
            'sort_order' => $maxOrder + 1,
        ]);

        return back()->with('success', 'Categorie ajoutee avec succes.');
    }

    public function updateCategory(Request $request, $id)
    {
        $category = TicketCategory::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:ticket_categories,name,' . $id,
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $category->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'sort_order' => $request->sort_order ?? $category->sort_order,
        ]);

        return back()->with('success', 'Categorie mise a jour.');
    }

    public function toggleCategory($id)
    {
        $category = TicketCategory::findOrFail($id);
        $category->update(['is_active' => !$category->is_active]);

        return back()->with('success', 'Statut de la categorie modifie.');
    }

    public function destroyCategory($id)
    {
        $category = TicketCategory::findOrFail($id);

        $count = Ticket::where('category', $category->slug)->count();

        if ($count > 0) {
            return back()->with('error', 'Impossible de supprimer cette categorie : ' . $count . ' ticket(s) y font reference. Vous pouvez la desactiver a la place.');
        }

        $category->delete();

        return back()->with('success', 'Categorie supprimee.');
    }
}
