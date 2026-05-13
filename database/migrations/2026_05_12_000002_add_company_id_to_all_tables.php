<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables principales qui necessitent un company_id.
     * Les tables pivot et enfants heritent via leur parent.
     */
    private array $tables = [
        'users',
        'departments',
        'campuses',
        'roles',
        'permissions',
        'attendances',
        'tickets',
        'ticket_services',
        'ticket_categories',
        'leave_requests',
        'justification_requests',
        'work_certificates',
        'salary_advance_requests',
        'payroll_records',
        'vacataire_payments',
        'unites_enseignement',
        'presence_incidents',
        'tardinesses',
        'absences',
        'notifications',
        'settings',
        'loans',
        'manual_deductions',
        'manual_payroll_adjustments',
        'manual_attendances',
        'security_violations',
        'wallets',
        'tasks',
        'complaints',
        'employee_documents',
        'job_positions',
        'moratorium_requests',
        'evaluation_campaigns',
        'job_postings',
        'training_programs',
        'conversations',
        'levels',
        'specialties',
        'onboarding_templates',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'company_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->foreignId('company_id')->nullable()->after('id')->constrained('companies')->nullOnDelete();
                    $t->index('company_id');
                });
            }
        }

        // Ajouter is_super_admin aux users pour le super admin global
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'is_super_admin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_super_admin')->default(false)->after('can_access_admin');
            });
        }
    }

    public function down(): void
    {
        // Retirer is_super_admin
        if (Schema::hasColumn('users', 'is_super_admin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_super_admin');
            });
        }

        foreach (array_reverse($this->tables) as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'company_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropConstrainedForeignId('company_id');
                });
            }
        }
    }
};
