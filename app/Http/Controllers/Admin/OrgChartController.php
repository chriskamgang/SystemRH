<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use App\Models\Campus;

class OrgChartController extends Controller
{
    /**
     * Display the organizational chart.
     */
    public function index()
    {
        // Load all active departments with their head and campus
        $departments = Department::with([
            'head',
            'campus',
            'users' => function ($query) {
                $query->with(['jobPosition', 'role'])
                      ->where('is_active', true)
                      ->orderBy('first_name');
            },
        ])
        ->where('is_active', true)
        ->orderBy('name')
        ->get();

        // Employees with no department
        $unassigned = User::with(['jobPosition', 'role'])
            ->where('is_active', true)
            ->whereNull('department_id')
            ->orderBy('first_name')
            ->get();

        // Summary stats
        $totalDepartments = $departments->count();
        $totalEmployees   = User::where('is_active', true)->count();
        $totalCampuses    = Campus::where('is_active', true)->count();

        // Per-type totals
        $typeStats = User::where('is_active', true)
            ->selectRaw('employee_type, COUNT(*) as total')
            ->groupBy('employee_type')
            ->pluck('total', 'employee_type');

        return view('admin.orgchart.index', compact(
            'departments',
            'unassigned',
            'totalDepartments',
            'totalEmployees',
            'totalCampuses',
            'typeStats'
        ));
    }
}
