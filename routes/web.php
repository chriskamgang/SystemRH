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

    // Reports (ancien système - à garder pour compatibilité)
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');

    // Nouveau module Rapports
    Route::prefix('rapports')->name('rapports.')->group(function () {
        // 1. Etat du personnel payé sur une période
        Route::get('/personnel-paye', [App\Http\Controllers\Admin\RapportController::class, 'personnelPaye'])->name('personnel-paye');
        Route::get('/personnel-paye/export', [App\Http\Controllers\Admin\RapportController::class, 'personnelPayeExport'])->name('personnel-paye.export');

        // 2. Etat des cours payés
        Route::get('/cours-payes', [App\Http\Controllers\Admin\RapportController::class, 'coursPayes'])->name('cours-payes');
        Route::get('/cours-payes/export', [App\Http\Controllers\Admin\RapportController::class, 'coursPayesExport'])->name('cours-payes.export');

        // 3. Etat des cours non payés
        Route::get('/cours-non-payes', [App\Http\Controllers\Admin\RapportController::class, 'coursNonPayes'])->name('cours-non-payes');
        Route::get('/cours-non-payes/export', [App\Http\Controllers\Admin\RapportController::class, 'coursNonPayesExport'])->name('cours-non-payes.export');

        // 4. Masse salariale des enseignements déjà payés par spécialité
        Route::get('/masse-payes-specialite', [App\Http\Controllers\Admin\RapportController::class, 'massePayesSpecialite'])->name('masse-payes-specialite');
        Route::get('/masse-payes-specialite/export', [App\Http\Controllers\Admin\RapportController::class, 'massePayesSpecialiteExport'])->name('masse-payes-specialite.export');

        // 5. Masse salariale des enseignements non payés par spécialité
        Route::get('/masse-non-payes-specialite', [App\Http\Controllers\Admin\RapportController::class, 'masseNonPayesSpecialite'])->name('masse-non-payes-specialite');
        Route::get('/masse-non-payes-specialite/export', [App\Http\Controllers\Admin\RapportController::class, 'masseNonPayesSpecialiteExport'])->name('masse-non-payes-specialite.export');

        // 6. Masse salariale des enseignements déjà payés par cycle
        Route::get('/masse-payes-cycle', [App\Http\Controllers\Admin\RapportController::class, 'massePayesCycle'])->name('masse-payes-cycle');
        Route::get('/masse-payes-cycle/export', [App\Http\Controllers\Admin\RapportController::class, 'massePayesCycleExport'])->name('masse-payes-cycle.export');

        // 7. Masse salariale des enseignements non payés par cycle
        Route::get('/masse-non-payes-cycle', [App\Http\Controllers\Admin\RapportController::class, 'masseNonPayesCycle'])->name('masse-non-payes-cycle');
        Route::get('/masse-non-payes-cycle/export', [App\Http\Controllers\Admin\RapportController::class, 'masseNonPayesCycleExport'])->name('masse-non-payes-cycle.export');
    });

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

        // Paiements Manuels Vacataires (AVANT les routes dynamiques)
        Route::prefix('paiements-manuels')->name('manual-payments.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\VacataireManualPaymentController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\Admin\VacataireManualPaymentController::class, 'create'])->name('create');
            Route::post('/select-ue', [App\Http\Controllers\Admin\VacataireManualPaymentController::class, 'selectUE'])->name('select-ue');
            Route::post('/store', [App\Http\Controllers\Admin\VacataireManualPaymentController::class, 'store'])->name('store');
            Route::get('/statistics', [App\Http\Controllers\Admin\VacataireManualPaymentController::class, 'statistics'])->name('statistics');
            Route::post('/check-existing', [App\Http\Controllers\Admin\VacataireManualPaymentController::class, 'checkExistingPayment'])->name('check-existing');
            Route::get('/{id}', [App\Http\Controllers\Admin\VacataireManualPaymentController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [App\Http\Controllers\Admin\VacataireManualPaymentController::class, 'edit'])->name('edit');
            Route::put('/{id}', [App\Http\Controllers\Admin\VacataireManualPaymentController::class, 'update'])->name('update');
            Route::delete('/{id}', [App\Http\Controllers\Admin\VacataireManualPaymentController::class, 'destroy'])->name('destroy');
        });

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
        // Gestion centralisée des UE (bibliothèque)
        Route::get('/catalog', [App\Http\Controllers\Admin\UniteEnseignementController::class, 'catalog'])->name('catalog');
        Route::get('/create-standalone', [App\Http\Controllers\Admin\UniteEnseignementController::class, 'createStandalone'])->name('create-standalone');
        Route::post('/store-standalone', [App\Http\Controllers\Admin\UniteEnseignementController::class, 'storeStandalone'])->name('store-standalone');

        // Attribution rapide par code UE
        Route::get('/assign', [App\Http\Controllers\Admin\UniteEnseignementController::class, 'assignForm'])->name('assign');
        Route::post('/assign', [App\Http\Controllers\Admin\UniteEnseignementController::class, 'assignToTeacher'])->name('assign.store');
        Route::get('/search-by-code', [App\Http\Controllers\Admin\UniteEnseignementController::class, 'searchByCode'])->name('search-by-code');
        Route::post('/search-multiple-codes', [App\Http\Controllers\Admin\UniteEnseignementController::class, 'searchMultipleCodes'])->name('search-multiple-codes');

        // Import/Export
        Route::get('/import', [App\Http\Controllers\Admin\UniteEnseignementController::class, 'importForm'])->name('import');
        Route::post('/import', [App\Http\Controllers\Admin\UniteEnseignementController::class, 'import'])->name('import.store');
        Route::get('/download-template', [App\Http\Controllers\Admin\UniteEnseignementController::class, 'downloadTemplate'])->name('download-template');

        // Routes existantes
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

    // Calculateur Générique
    Route::prefix('calculateur-generique')->name('generic-calculator.')->group(function () {
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

    // ========== REAL-TIME TRACKING (Suivi en Temps Réel) ==========
    Route::prefix('real-time-tracking')->name('real-time-tracking.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\RealTimeTrackingController::class, 'index'])->name('index');
        Route::get('/get-locations', [App\Http\Controllers\Admin\RealTimeTrackingController::class, 'getLocations'])->name('get-locations');
        Route::get('/get-stats', [App\Http\Controllers\Admin\RealTimeTrackingController::class, 'getStats'])->name('get-stats');
    });

    // ========== FIREBASE SETTINGS ==========
    Route::prefix('firebase')->name('firebase.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\FirebaseSettingsController::class, 'index'])->name('index');
        Route::match(['get', 'post'], '/upload', [App\Http\Controllers\Admin\FirebaseSettingsController::class, 'upload'])->name('upload');
        Route::post('/test', [App\Http\Controllers\Admin\FirebaseSettingsController::class, 'test'])->name('test');
        Route::get('/download', [App\Http\Controllers\Admin\FirebaseSettingsController::class, 'download'])->name('download');
    });

    // Logout
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

});