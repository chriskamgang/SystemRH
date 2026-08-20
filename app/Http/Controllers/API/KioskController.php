<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Campus;
use App\Models\KioskDevice;
use App\Models\LeaveRequest;
use App\Models\Setting;
use App\Models\Tardiness;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class KioskController extends Controller
{
    /**
     * Authentifier la borne et retourner ses infos
     * POST /api/kiosk/auth
     */
    public function auth(Request $request)
    {
        $request->validate([
            'device_token' => 'required|string',
        ]);

        $kiosk = KioskDevice::where('device_token', $request->device_token)
            ->where('is_active', true)
            ->with('campus')
            ->first();

        if (!$kiosk) {
            return response()->json(['message' => 'Borne non reconnue ou desactivee.'], 401);
        }

        $kiosk->update(['last_seen_at' => now()]);

        return response()->json([
            'success' => true,
            'kiosk' => [
                'id' => $kiosk->id,
                'name' => $kiosk->name,
                'campus' => [
                    'id' => $kiosk->campus->id,
                    'name' => $kiosk->campus->name,
                    'attendance_mode' => $kiosk->campus->attendance_mode,
                ],
            ],
        ]);
    }

    /**
     * Scanner un badge QR — check-in ou check-out automatique
     * POST /api/kiosk/scan
     */
    public function scan(Request $request)
    {
        $request->validate([
            'device_token' => 'required|string',
            'qr_token' => 'required|string',
            'photo' => 'nullable|string', // base64
        ]);

        // 1. Verifier la borne
        $kiosk = KioskDevice::where('device_token', $request->device_token)
            ->where('is_active', true)
            ->with('campus')
            ->first();

        if (!$kiosk) {
            return response()->json(['message' => 'Borne non reconnue ou desactivee.'], 401);
        }

        $kiosk->update(['last_seen_at' => now()]);
        $campus = $kiosk->campus;

        // 2. Identifier l'employe par son QR token
        $user = User::where('qr_token', $request->qr_token)
            ->where('is_active', true)
            ->first();

        if (!$user) {
            return response()->json(['message' => 'Badge non reconnu ou revoque.'], 404);
        }

        // 3. Verifier que l'employe est assigne a ce campus
        if (!$user->campuses->contains($campus->id)) {
            return response()->json([
                'message' => "Vous n'etes pas assigne a ce campus.",
                'employee' => $user->first_name . ' ' . $user->last_name,
            ], 403);
        }

        // 4. Verifier conge
        $activeLeave = LeaveRequest::getActiveLeave($user->id);
        if ($activeLeave) {
            return response()->json([
                'message' => "En conge ({$activeLeave->getTypeLabel()}) jusqu'au {$activeLeave->end_date->format('d/m/Y')}.",
                'employee' => $user->first_name . ' ' . $user->last_name,
            ], 400);
        }

        // 5. Sauvegarder la photo si fournie
        $photoPath = null;
        if ($request->photo) {
            $photoPath = $this->savePhoto($request->photo, $user->id);
        }

        // 6. Determiner si check-in ou check-out (automatique)
        $activeCheckIn = $this->findActiveCheckIn($user);

        if ($activeCheckIn) {
            // Check-out si check-in actif sur CE campus
            if ($activeCheckIn->campus_id !== $campus->id) {
                $otherCampus = $activeCheckIn->campus->name ?? 'un autre campus';
                return response()->json([
                    'message' => "Vous avez un check-in actif sur {$otherCampus}. Faites d'abord le check-out la-bas.",
                    'employee' => $user->first_name . ' ' . $user->last_name,
                ], 400);
            }
            return $this->performCheckOut($user, $campus, $kiosk, $activeCheckIn, $photoPath);
        }

        // Check-in
        return $this->performCheckIn($user, $campus, $kiosk, $photoPath);
    }

    private function performCheckIn($user, $campus, $kiosk, $photoPath)
    {
        $now = now();

        // Detecter le shift
        $shift = $this->detectShift($now, $campus);

        // Calculer le retard
        $isLate = false;
        $lateMinutes = 0;
        $isHalfDay = false;
        $shiftTimes = $this->getShiftTimes($shift, $campus);

        $isFirstCheckIn = !Attendance::where('user_id', $user->id)
            ->where('type', 'check-in')
            ->where('shift', $shift)
            ->whereDate('timestamp', today())
            ->exists();

        $isVacataire = $user->employee_type === 'enseignant_vacataire';

        if (!$isVacataire && $isFirstCheckIn) {
            $currentTime = Carbon::parse($now->format('H:i:s'));
            $shiftStartTime = Carbon::parse($shiftTimes['start']);

            if ($shift === 'night' && $campus->isHospitalMode()) {
                $lateTolerance = $campus->night_late_tolerance ?? 15;
            } else {
                $lateTolerance = $user->hasCustomWorkHours()
                    ? $user->getLateTolerance($campus)
                    : 0;

                if ($user->hasCustomWorkHours()) {
                    $shiftStartTime = Carbon::parse($user->custom_start_time);
                }
            }

            $toleranceTime = $shiftStartTime->copy()->addMinutes($lateTolerance);
            $isLate = $currentTime->gt($toleranceTime);
            $lateMinutes = $isLate ? $shiftStartTime->diffInMinutes($currentTime) : 0;

            if ($shift !== 'night') {
                $halfDayThreshold = (int) Setting::get('half_day_threshold_minutes', 120);
                if ($isLate && $lateMinutes >= $halfDayThreshold) {
                    $isHalfDay = true;
                }
            }
        }

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'campus_id' => $campus->id,
            'type' => 'check-in',
            'shift' => $shift,
            'timestamp' => $now,
            'latitude' => $campus->latitude,
            'longitude' => $campus->longitude,
            'is_late' => $isLate,
            'late_minutes' => $lateMinutes,
            'is_half_day' => $isHalfDay,
            'photo_path' => $photoPath,
            'source' => 'kiosk',
            'kiosk_device_id' => $kiosk->id,
            'status' => 'valid',
        ]);

        if ($isLate) {
            Tardiness::create([
                'user_id' => $user->id,
                'campus_id' => $campus->id,
                'attendance_id' => $attendance->id,
                'date' => $now->toDateString(),
                'scheduled_time' => $shiftTimes['start'],
                'actual_time' => $now->format('H:i:s'),
                'late_minutes' => $lateMinutes,
                'status' => 'pending',
            ]);
        }

        $shiftLabels = ['morning' => 'matin', 'evening' => 'soir', 'night' => 'garde de nuit'];
        $shiftLabel = $shiftLabels[$shift] ?? $shift;

        $message = $isLate
            ? "Check-in ({$shiftLabel}) avec retard de {$lateMinutes} min."
            : "Check-in ({$shiftLabel}) enregistre.";

        return response()->json([
            'success' => true,
            'type' => 'check-in',
            'message' => $message,
            'employee' => $user->first_name . ' ' . $user->last_name,
            'photo_url' => $photoPath ? Storage::url($photoPath) : null,
            'shift' => $shiftLabel,
            'is_late' => $isLate,
            'late_minutes' => $lateMinutes,
            'timestamp' => $now->format('H:i'),
        ], 201);
    }

    private function performCheckOut($user, $campus, $kiosk, $checkIn, $photoPath)
    {
        $now = now();
        $shift = $checkIn->shift ?? 'morning';

        $sessionMinutes = $checkIn->timestamp->diffInMinutes($now);
        $sessionHours = round($sessionMinutes / 60, 2);

        $checkout = Attendance::create([
            'user_id' => $user->id,
            'campus_id' => $campus->id,
            'type' => 'check-out',
            'shift' => $shift,
            'timestamp' => $now,
            'latitude' => $campus->latitude,
            'longitude' => $campus->longitude,
            'photo_path' => $photoPath,
            'source' => 'kiosk',
            'kiosk_device_id' => $kiosk->id,
            'status' => 'valid',
        ]);

        $shiftLabels = ['morning' => 'matin', 'evening' => 'soir', 'night' => 'garde de nuit'];
        $shiftLabel = $shiftLabels[$shift] ?? $shift;

        return response()->json([
            'success' => true,
            'type' => 'check-out',
            'message' => "Check-out ({$shiftLabel}) enregistre. Duree: {$sessionHours}h.",
            'employee' => $user->first_name . ' ' . $user->last_name,
            'photo_url' => $photoPath ? Storage::url($photoPath) : null,
            'shift' => $shiftLabel,
            'duration_hours' => $sessionHours,
            'timestamp' => $now->format('H:i'),
        ], 201);
    }

    private function findActiveCheckIn($user)
    {
        $attendances = Attendance::where('user_id', $user->id)
            ->where('timestamp', '>=', today()->subDay())
            ->with('campus')
            ->orderBy('timestamp', 'desc')
            ->get();

        $checkOuts = $attendances->where('type', 'check-out');

        foreach ($attendances->where('type', 'check-in') as $ci) {
            if ($checkOuts->where('timestamp', '>', $ci->timestamp)->isEmpty()) {
                return $ci;
            }
        }

        return null;
    }

    private function detectShift($currentTime, $campus)
    {
        $current = Carbon::parse($currentTime->format('H:i:s'));

        if ($campus->isHospitalMode() && $campus->night_start_time) {
            $nightStart = Carbon::parse($campus->night_start_time);
            $morningStart = Carbon::parse($campus->start_time ?? '08:00');
            if ($current->gte($nightStart) || $current->lt($morningStart)) {
                return 'night';
            }
        }

        $separatorTime = Setting::get('shift_separator_time', '17:30');
        $separator = Carbon::parse($separatorTime);
        return $current->lt($separator) ? 'morning' : 'evening';
    }

    private function getShiftTimes($shift, $campus)
    {
        if ($shift === 'night' && $campus->isHospitalMode()) {
            return [
                'start' => $campus->night_start_time ?? '19:00',
                'end' => $campus->start_time ?? '08:00',
            ];
        } elseif ($shift === 'morning') {
            return [
                'start' => Setting::get('morning_start_time', '08:00'),
                'end' => Setting::get('morning_end_time', '17:00'),
            ];
        }
        return [
            'start' => Setting::get('evening_start_time', '17:30'),
            'end' => Setting::get('evening_end_time', '21:30'),
        ];
    }

    private function savePhoto($base64, $userId): ?string
    {
        try {
            $imageData = base64_decode($base64);
            if (!$imageData) return null;

            $date = now()->format('Y-m-d');
            $filename = "kiosk-photos/{$date}/{$userId}_" . now()->format('His') . '.jpg';
            Storage::disk('public')->put($filename, $imageData);
            return $filename;
        } catch (\Exception $e) {
            return null;
        }
    }
}
