<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IosBetaRequest;
use Illuminate\Http\Request;

class IosBetaController extends Controller
{
    public function index()
    {
        $requests = IosBetaRequest::orderBy('created_at', 'desc')->get();
        $stats = [
            'total' => $requests->count(),
            'pending' => $requests->where('status', 'pending')->count(),
            'invited' => $requests->where('status', 'invited')->count(),
        ];

        return view('admin.ios-beta.index', compact('requests', 'stats'));
    }

    public function markInvited($id)
    {
        $request = IosBetaRequest::findOrFail($id);
        $request->update([
            'status' => 'invited',
            'invited_at' => now(),
        ]);

        return back()->with('success', "Invitation marquée comme envoyée pour {$request->email}");
    }

    public function destroy($id)
    {
        $request = IosBetaRequest::findOrFail($id);
        $request->delete();

        return back()->with('success', 'Demande supprimée');
    }
}
