<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use Illuminate\Http\Request;

class ComplaintController extends Controller
{
    public function index(Request $request)
    {
        $complaints = $request->user()->complaints()
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'complaints' => $complaints,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $complaint = $request->user()->complaints()->create([
            'subject' => $request->subject,
            'content' => $request->content,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Plainte soumise avec succès.',
            'complaint' => $complaint,
        ], 201);
    }
}
