<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\AttendanceController;
use App\Http\Controllers\API\CampusController;
use App\Http\Controllers\API\PresenceCheckController;
use App\Http\Controllers\API\UserController;

// Route de test (accessible via GET dans le navigateur)
Route::get('/', function () {
    return response()->json([
        'app' => 'Attendance System API',
        'version' => '1.0.0',
        'status' => 'running',
        'timestamp' => now(),
        'endpoints' => [
            'POST /api/login' => 'Connexion',
            'POST /api/logout' => 'Déconnexion (auth required)',
            'GET /api/user' => 'User info (auth required)',
            'POST /api/attendance/check-in' => 'Check-in (auth required)',
            'POST /api/attendance/check-out' => 'Check-out (auth required)',
            'GET /api/campuses' => 'Liste des campus (auth required)',
            'GET /api/presence-check/pending' => 'Vérifications en attente (auth required)',
        ],
    ]);
});

// Routes publiques
Route::post('/login', [AuthController::class, 'login']);

// ========== ROUTE DE TEST (À SUPPRIMER EN PRODUCTION) ==========
Route::get('/test-notifications', function () {
    $result = \App\Services\PresenceNotificationService::sendPresenceCheckNotifications();
    return response()->json([
        'message' => 'Test des notifications de présence',
        'result' => $result,
    ]);
});

Route::get('/test-stats', function () {
    $stats = \App\Services\PresenceNotificationService::getTodayStats();
    return response()->json([
        'message' => 'Statistiques des notifications du jour',
        'stats' => $stats,
    ]);
});

// Routes protégées par Sanctum
Route::middleware('auth:sanctum')->group(function () {

    // ========== AUTH ==========
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // ========== ATTENDANCE (Pointages) ==========
    Route::prefix('attendance')->group(function () {
        Route::post('/check-in', [AttendanceController::class, 'checkIn']);
        Route::post('/check-out', [AttendanceController::class, 'checkOut']);
        Route::get('/my-history', [AttendanceController::class, 'myHistory']);
        Route::get('/today', [AttendanceController::class, 'today']);
        Route::get('/stats', [AttendanceController::class, 'stats']);
        Route::get('/current-status', [AttendanceController::class, 'currentStatus']);
    });

    // ========== CAMPUS ==========
    Route::prefix('campuses')->group(function () {
        Route::get('/', [CampusController::class, 'index']);
        Route::get('/my-campuses', [CampusController::class, 'myCampuses']);
        Route::get('/{id}', [CampusController::class, 'show']);
        Route::post('/check-zone', [CampusController::class, 'checkZone']);
        Route::post('/calculate-distance', [CampusController::class, 'calculateDistance']);
        Route::get('/{id}/schedule', [CampusController::class, 'schedule']);
        Route::get('/{id}/stats', [CampusController::class, 'stats']);
    });

    // ========== PRESENCE CHECK (Vérifications de présence) ==========
    Route::prefix('presence-check')->group(function () {
        Route::get('/pending', [PresenceCheckController::class, 'pending']);
        Route::post('/respond', [PresenceCheckController::class, 'respond']);
        Route::get('/history', [PresenceCheckController::class, 'history']);
        Route::get('/stats', [PresenceCheckController::class, 'stats']);
        Route::get('/{id}', [PresenceCheckController::class, 'show']);
    });

    // ========== USER (Profil et paramètres) ==========
    Route::prefix('user')->group(function () {
        Route::get('/profile', [UserController::class, 'profile']);
        Route::put('/profile', [UserController::class, 'updateProfile']);
        Route::post('/change-password', [UserController::class, 'changePassword']);
        Route::post('/update-fcm-token', [UserController::class, 'updateFcmToken']);
        Route::post('/remove-fcm-token', [UserController::class, 'removeFcmToken']);
        Route::get('/dashboard', [UserController::class, 'dashboard']);
        Route::get('/my-campuses', [UserController::class, 'myCampuses']);
        Route::get('/notifications', [UserController::class, 'notifications']);
        Route::post('/notifications/{id}/mark-as-read', [UserController::class, 'markNotificationAsRead']);
        Route::post('/notifications/mark-all-as-read', [UserController::class, 'markAllNotificationsAsRead']);

        // Salary and deductions endpoints
        Route::get('/salary-status', [\App\Http\Controllers\Api\MobileApiController::class, 'getSalaryStatus']);
        Route::get('/manual-deductions', [\App\Http\Controllers\Api\MobileApiController::class, 'getManualDeductions']);
        Route::get('/loans', [\App\Http\Controllers\Api\MobileApiController::class, 'getLoans']);
    });

    // ========== PRESENCE NOTIFICATIONS ==========
    Route::prefix('presence-notifications')->group(function () {
        Route::get('/pending', [\App\Http\Controllers\API\PresenceNotificationController::class, 'getPending']);
        Route::post('/respond', [\App\Http\Controllers\API\PresenceNotificationController::class, 'respond']);
        Route::get('/history', [\App\Http\Controllers\API\PresenceNotificationController::class, 'history']);
        Route::get('/stats', [\App\Http\Controllers\API\PresenceNotificationController::class, 'stats']);
    });

    // ========== UNITÉS D'ENSEIGNEMENT (pour vacataires) ==========
    Route::prefix('unites-enseignement')->group(function () {
        Route::get('/', [\App\Http\Controllers\API\UniteEnseignementController::class, 'index']);
        Route::get('/actives', [\App\Http\Controllers\API\UniteEnseignementController::class, 'actives']);
        Route::get('/statistiques', [\App\Http\Controllers\API\UniteEnseignementController::class, 'statistiques']);
        Route::get('/{id}', [\App\Http\Controllers\API\UniteEnseignementController::class, 'show']);
    });

    // ========== GÉOFENCING (Notifications d'entrée en zone) ==========
    Route::prefix('geofencing')->group(function () {
        Route::post('/entry', [\App\Http\Controllers\API\GeofencingController::class, 'onGeofenceEntry']);
        Route::post('/clicked', [\App\Http\Controllers\API\GeofencingController::class, 'markAsClicked']);
        Route::post('/ignored', [\App\Http\Controllers\API\GeofencingController::class, 'markAsIgnored']);
        Route::get('/status', [\App\Http\Controllers\API\GeofencingController::class, 'getStatus']);
    });

    // ========== LOCATION TRACKING (Suivi en temps réel) ==========
    Route::prefix('location')->group(function () {
        Route::post('/update', [\App\Http\Controllers\API\LocationController::class, 'updateLocation']);
        Route::post('/deactivate', [\App\Http\Controllers\API\LocationController::class, 'deactivateLocation']);
        Route::get('/active-users', [\App\Http\Controllers\API\LocationController::class, 'getActiveUsers']);
        Route::get('/user/{userId}', [\App\Http\Controllers\API\LocationController::class, 'getUserLocation']);
    });

    // Test route
    Route::get('/test', function () {
        return response()->json([
            'message' => 'API fonctionne !',
            'user' => auth()->user()->full_name,
            'timestamp' => now(),
        ]);
    });
});
