<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use Illuminate\Http\Request;

class ComplaintController extends Controller
{
    public function index()
    {
        $complaints = Complaint::with('user')->latest()->paginate(20);
        return view('admin.complaints.index', compact('complaints'));
    }

    public function show($id)
    {
        $complaint = Complaint::with('user')->findOrFail($id);
        return view('admin.complaints.show', compact('complaint'));
    }

    public function respond(Request $request, $id)
    {
        $request->validate([
            'admin_response' => 'required|string',
            'status' => 'required|in:in_review,resolved',
        ]);

        $complaint = Complaint::findOrFail($id);
        $complaint->update([
            'admin_response' => $request->admin_response,
            'status' => $request->status,
        ]);

        return redirect()->route('admin.complaints.show', $id)->with('success', 'Réponse envoyée avec succès.');
    }

    public function destroy($id)
    {
        $complaint = Complaint::findOrFail($id);
        $complaint->delete();

        return redirect()->route('admin.complaints.index')->with('success', 'Plainte supprimée.');
    }
}
