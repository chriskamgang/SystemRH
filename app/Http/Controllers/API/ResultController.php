<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AcademicResult;
use Illuminate\Http\Request;

class ResultController extends Controller
{
    public function index(Request $request)
    {
        $results = $request->user()->academicResults()
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'results' => $results,
        ]);
    }
}
