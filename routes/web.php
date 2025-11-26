<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\CampusController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\ReportController;

// Authentication routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);

// Redirect root to dashboard
Route::redirect('/', '/admin/dashboard');

// Admin routes (protected by auth middleware)
Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Employees
    Route::resource('employees', EmployeeController::class);
    Route::post('employees/{id}/reset-device', [EmployeeController::class, 'resetDevice'])->name('employees.reset-device');

    // Import/Export Employees (ancien système global)
    Route::get('employees-import', [EmployeeController::class, 'showImportForm'])->name('employees.import-form');
    Route::post('employees-import', [EmployeeController::class, 'import'])->name('employees.import');
    Route::get('employees-template', [EmployeeController::class, 'downloadTemplate'])->name('employees.download-template');

    // Import/Export Employees par type (nouveau système)
    // PERMANENTS
    Route::get('employees-permanent-template', [EmployeeController::class, 'downloadPermanentTemplate'])->name('employees.download-permanent-template');
    Route::post('employees-import-permanent', [EmployeeController::class, 'importPermanent'])->name('employees.import-permanent');

    // SEMI-PERMANENTS
    Route::get('employees-semi-permanent-template', [EmployeeController::class, 'downloadSemiPermanentTemplate'])->name('employees.download-semi-permanent-template');
    Route::post('employees-import-semi-permanent', [EmployeeController::class, 'importSemiPermanent'])->name('employees.import-semi-permanent');

    // VACATAIRES
    Route::get('employees-vacataire-template', [EmployeeController::class, 'downloadVacataireTemplate'])->name('employees.download-vacataire-template');
    Route::post('employees-import-vacataire', [EmployeeController::class, 'importVacataire'])->name('employees.import-vacataire');

    // Campus
    Route::resource('campuses', CampusController::class);

    // Attendances
    Route::get('/attendances', [AttendanceController::class, 'index'])->name('attendances.index');
    Route::get('/attendances/{id}', [AttendanceController::class, 'show'])->name('attendances.show');

    // Real-time map
    Route::get('/realtime', [DashboardController::class, 'realtime'])->name('realtime');

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');

    // Vacataires
    Route::prefix('vacataires')->name('vacataires.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\VacataireController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Admin\VacataireController::class, 'create'])->name('create');

        // Gestion des paiements (AVANT les routes dynamiques)
        Route::get('/payments', [App\Http\Controllers\Admin\VacataireController::class, 'payments'])->name('payments');
        Route::post('/payments/generate', [App\Http\Controllers\Admin\VacataireController::class, 'generateMonthlyPayments'])->name('payments.generate');
        Route::post('/payments/{id}/validate', [App\Http\Controllers\Admin\VacataireController::class, 'validatePayment'])->name('payments.validate');
        Route::post('/payments/{id}/mark-paid', [App\Http\Controllers\Admin\VacataireController::class, 'markAsPaid'])->name('payments.mark-paid');

        // Rapport vacataires (AVANT les routes dynamiques)
        Route::get('/report', [App\Http\Controllers\Admin\VacataireController::class, 'report'])->name('report');
        Route::get('/report/export', [App\Http\Controllers\Admin\VacataireController::class, 'exportReport'])->name('report.export');

        // Gestion des UE d'un vacataire (AVANT les routes dynamiques)
        Route::get('/{id}/unites', [App\Http\Controllers\Admin\UniteEnseignementController::class, 'vacataireUnites'])->name('unites');

        // Routes CRUD dynamiques (APRÈS les routes spécifiques)
        Route::post('/', [App\Http\Controllers\Admin\VacataireController::class, 'store'])->name('store');
        Route::get('/{id}', [App\Http\Controllers\Admin\VacataireController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [App\Http\Controllers\Admin\VacataireController::class, 'edit'])->name('edit');
        Route::put('/{id}', [App\Http\Controllers\Admin\VacataireController::class, 'update'])->name('update');
        Route::delete('/{id}', [App\Http\Controllers\Admin\VacataireController::class, 'destroy'])->name('destroy');
    });

    // Semi-permanents
    Route::prefix('semi-permanents')->name('semi-permanents.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\SemiPermanentController::class, 'index'])->name('index');

        // Gestion des paiements (AVANT les routes dynamiques)
        Route::get('/payments', [App\Http\Controllers\Admin\SemiPermanentController::class, 'payments'])->name('payments');

        // Rapport semi-permanents (AVANT les routes dynamiques)
        Route::get('/report', [App\Http\Controllers\Admin\SemiPermanentController::class, 'report'])->name('report');
        Route::get('/report/export', [App\Http\Controllers\Admin\SemiPermanentController::class, 'exportReport'])->name('report.export');

        // Gestion des UE d'un semi-permanent (AVANT les routes dynamiques)
        Route::get('/{id}/unites', [App\Http\Controllers\Admin\UniteEnseignementController::class, 'vacataireUnites'])->name('unites');

        // Rapport hebdomadaire détaillé (AVANT les routes dynamiques)
        Route::get('/{id}/weekly-report', [App\Http\Controllers\Admin\SemiPermanentController::class, 'weeklyReport'])->name('weekly-report');

        // Routes CRUD dynamiques (APRÈS les routes spécifiques)
        Route::get('/{id}', [App\Http\Controllers\Admin\SemiPermanentController::class, 'show'])->name('show');
    });

    // Unités d'Enseignement (UE)
    Route::prefix('unites-enseignement')->name('unites-enseignement.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\UniteEnseignementController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Admin\UniteEnseignementController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Admin\UniteEnseignementController::class, 'store'])->name('store');
        Route::get('/{id}', [App\Http\Controllers\Admin\UniteEnseignementController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [App\Http\Controllers\Admin\UniteEnseignementController::class, 'edit'])->name('edit');
        Route::put('/{id}', [App\Http\Controllers\Admin\UniteEnseignementController::class, 'update'])->name('update');
        Route::delete('/{id}', [App\Http\Controllers\Admin\UniteEnseignementController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/activer', [App\Http\Controllers\Admin\UniteEnseignementController::class, 'activer'])->name('activer');
        Route::post('/{id}/desactiver', [App\Http\Controllers\Admin\UniteEnseignementController::class, 'desactiver'])->name('desactiver');
    });

    // Rapport sur la paie
    Route::prefix('payroll')->name('payroll.')->group(function () {
        Route::get('/report', [App\Http\Controllers\Admin\PayrollReportController::class, 'index'])->name('report');
        Route::post('/justify', [App\Http\Controllers\Admin\PayrollReportController::class, 'justify'])->name('justify');
        Route::post('/apply-deduction', [App\Http\Controllers\Admin\PayrollReportController::class, 'applyDeduction'])->name('apply-deduction');
        Route::get('/report/export', [App\Http\Controllers\Admin\PayrollReportController::class, 'export'])->name('report.export');
    });

    // Paie Manuelle
    Route::prefix('manual-payroll')->name('manual-payroll.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\ManualPayrollController::class, 'index'])->name('index');
        Route::post('/calculate', [App\Http\Controllers\Admin\ManualPayrollController::class, 'calculate'])->name('calculate');
        Route::post('/calculate-bulk', [App\Http\Controllers\Admin\ManualPayrollController::class, 'calculateBulk'])->name('calculate-bulk');
    });

    // Déductions manuelles
    Route::prefix('manual-deductions')->name('manual-deductions.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\ManualDeductionController::class, 'index'])->name('index');
        Route::post('/', [App\Http\Controllers\Admin\ManualDeductionController::class, 'store'])->name('store');
        Route::put('/{id}', [App\Http\Controllers\Admin\ManualDeductionController::class, 'update'])->name('update');
        Route::post('/{id}/cancel', [App\Http\Controllers\Admin\ManualDeductionController::class, 'cancel'])->name('cancel');
        Route::delete('/{id}', [App\Http\Controllers\Admin\ManualDeductionController::class, 'destroy'])->name('destroy');
    });

    // Prêts (Loans)
    Route::prefix('loans')->name('loans.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\LoanController::class, 'index'])->name('index');
        Route::post('/', [App\Http\Controllers\Admin\LoanController::class, 'store'])->name('store');
        Route::put('/{id}', [App\Http\Controllers\Admin\LoanController::class, 'update'])->name('update');
        Route::post('/{id}/mark-completed', [App\Http\Controllers\Admin\LoanController::class, 'markAsCompleted'])->name('mark-completed');
        Route::post('/{id}/cancel', [App\Http\Controllers\Admin\LoanController::class, 'cancel'])->name('cancel');
        Route::post('/{id}/record-payment', [App\Http\Controllers\Admin\LoanController::class, 'recordPayment'])->name('record-payment');
        Route::delete('/{id}', [App\Http\Controllers\Admin\LoanController::class, 'destroy'])->name('destroy');
    });

    // Settings
    Route::get('/settings', [DashboardController::class, 'settings'])->name('settings');
    Route::put('/settings', [DashboardController::class, 'updateSettings'])->name('settings.update');

    // Roles management in settings
    Route::post('/settings/roles', [DashboardController::class, 'storeRole'])->name('settings.roles.store');
    Route::put('/settings/roles/{id}', [DashboardController::class, 'updateRole'])->name('settings.roles.update');
    Route::delete('/settings/roles/{id}', [DashboardController::class, 'deleteRole'])->name('settings.roles.delete');

    // API endpoint for real-time map
    Route::get('/api/active-checkins', [DashboardController::class, 'activeCheckIns'])->name('api.active-checkins');

    // API endpoint for manual deductions details
    Route::get('/api/manual-deductions/{userId}', [App\Http\Controllers\Admin\ManualDeductionController::class, 'getDeductionsForUser'])->name('api.manual-deductions.user');

    // ========== PRESENCE ALERTS / NOTIFICATIONS ==========
    Route::prefix('presence-alerts')->name('presence-alerts.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\PresenceAlertController::class, 'index'])->name('index');
        Route::get('/settings', [App\Http\Controllers\Admin\PresenceAlertController::class, 'settings'])->name('settings');
        Route::post('/settings', [App\Http\Controllers\Admin\PresenceAlertController::class, 'updateSettings'])->name('settings.update');
        Route::get('/statistics', [App\Http\Controllers\Admin\PresenceAlertController::class, 'statistics'])->name('statistics');
        Route::get('/{id}', [App\Http\Controllers\Admin\PresenceAlertController::class, 'show'])->name('show');
        Route::post('/{id}/validate', [App\Http\Controllers\Admin\PresenceAlertController::class, 'validate'])->name('validate');
        Route::post('/{id}/ignore', [App\Http\Controllers\Admin\PresenceAlertController::class, 'ignore'])->name('ignore');
    });

    // API pour les alertes de présence (AJAX)
    Route::get('/api/presence-alerts/incidents', [App\Http\Controllers\Admin\PresenceAlertController::class, 'apiGetIncidents'])->name('api.presence-alerts.incidents');
    Route::get('/api/presence-alerts/pending-count', [App\Http\Controllers\Admin\PresenceAlertController::class, 'apiGetPendingCount'])->name('api.presence-alerts.pending-count');

    // Logout
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

});
