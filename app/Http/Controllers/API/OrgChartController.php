<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;

class OrgChartController extends Controller
{
    /**
     * Organigramme complet
     */
    public function index()
    {
        $departments = Department::where('is_active', true)
            ->with(['head:id,first_name,last_name,photo,employee_type,job_position_id', 'head.jobPosition:id,name'])
            ->withCount('users')
            ->orderBy('name')
            ->get()
            ->map(function ($dept) {
                return [
                    'id' => $dept->id,
                    'name' => $dept->name,
                    'code' => $dept->code,
                    'head' => $dept->head ? [
                        'id' => $dept->head->id,
                        'full_name' => $dept->head->full_name,
                        'photo' => $dept->head->photo,
                        'position' => $dept->head->jobPosition?->name,
                    ] : null,
                    'employee_count' => $dept->users_count,
                ];
            });

        return response()->json(['success' => true, 'departments' => $departments]);
    }

    /**
     * Membres d'un departement
     */
    public function departmentMembers($departmentId)
    {
        $department = Department::findOrFail($departmentId);

        $members = User::where('department_id', $departmentId)
            ->where('is_active', true)
            ->with(['jobPosition:id,name', 'manager:id,first_name,last_name'])
            ->select('id', 'first_name', 'last_name', 'photo', 'employee_type', 'job_position_id', 'manager_id')
            ->orderBy('first_name')
            ->get()
            ->map(function ($u) {
                return [
                    'id' => $u->id,
                    'full_name' => $u->full_name,
                    'photo' => $u->photo,
                    'employee_type' => $u->employee_type,
                    'position' => $u->jobPosition?->name,
                    'manager' => $u->manager?->full_name,
                ];
            });

        return response()->json([
            'success' => true,
            'department' => ['id' => $department->id, 'name' => $department->name],
            'members' => $members,
        ]);
    }

    /**
     * Ma hierarchie (qui je suis, mon manager, mes subordonnes)
     */
    public function myHierarchy(Request $request)
    {
        $user = $request->user();
        $user->load(['manager:id,first_name,last_name,photo,job_position_id', 'manager.jobPosition:id,name',
                      'subordinates:id,first_name,last_name,photo,employee_type,job_position_id,manager_id',
                      'subordinates.jobPosition:id,name',
                      'department:id,name', 'jobPosition:id,name']);

        return response()->json([
            'success' => true,
            'me' => [
                'full_name' => $user->full_name,
                'photo' => $user->photo,
                'position' => $user->jobPosition?->name,
                'department' => $user->department?->name,
            ],
            'manager' => $user->manager ? [
                'id' => $user->manager->id,
                'full_name' => $user->manager->full_name,
                'photo' => $user->manager->photo,
                'position' => $user->manager->jobPosition?->name,
            ] : null,
            'subordinates' => $user->subordinates->map(function ($s) {
                return [
                    'id' => $s->id,
                    'full_name' => $s->full_name,
                    'photo' => $s->photo,
                    'position' => $s->jobPosition?->name,
                ];
            }),
        ]);
    }
}
