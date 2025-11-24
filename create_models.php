<?php

/**
 * Script pour créer tous les Models avec leurs relations
 * Exécute: php create_models.php
 */

$modelsPath = __DIR__ . '/app/Models/';

// Définition de tous les models
$models = [
    'Role.php' => <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
    ];

    /**
     * Relations
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')
            ->withTimestamps();
    }
}
PHP,

    'Permission.php' => <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'category',
    ];

    /**
     * Relations
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions')
            ->withTimestamps();
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_permissions')
            ->withTimestamps();
    }
}
PHP,

    'Campus.php' => <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campus extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'address',
        'description',
        'latitude',
        'longitude',
        'radius',
        'start_time',
        'end_time',
        'late_tolerance',
        'working_days',
        'is_active',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'radius' => 'integer',
        'late_tolerance' => 'integer',
        'working_days' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Relations
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_campus')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function departments()
    {
        return $this->hasMany(Department::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function presenceChecks()
    {
        return $this->hasMany(PresenceCheck::class);
    }

    public function tardiness()
    {
        return $this->hasMany(Tardiness::class);
    }

    public function absences()
    {
        return $this->hasMany(Absence::class);
    }

    /**
     * Helper methods
     */
    public function isUserInZone($latitude, $longitude)
    {
        // Calcul de la distance en mètres entre deux points GPS
        $earthRadius = 6371000; // en mètres

        $latFrom = deg2rad($this->latitude);
        $lonFrom = deg2rad($this->longitude);
        $latTo = deg2rad($latitude);
        $lonTo = deg2rad($longitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        $distance = $angle * $earthRadius;

        return $distance <= $this->radius;
    }
}
PHP,

    'Department.php' => <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'head_user_id',
        'campus_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relations
     */
    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    public function head()
    {
        return $this->belongsTo(User::class, 'head_user_id');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
PHP,

    'Attendance.php' => <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'campus_id',
        'type',
        'timestamp',
        'latitude',
        'longitude',
        'accuracy',
        'is_late',
        'late_minutes',
        'device_info',
        'notes',
        'status',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'accuracy' => 'float',
        'is_late' => 'boolean',
        'late_minutes' => 'integer',
        'device_info' => 'array',
    ];

    /**
     * Relations
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    public function tardiness()
    {
        return $this->hasOne(Tardiness::class);
    }
}
PHP,

    'PresenceCheck.php' => <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PresenceCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'campus_id',
        'check_time',
        'response',
        'response_time',
        'latitude',
        'longitude',
        'is_in_zone',
        'notification_sent',
        'notification_id',
    ];

    protected $casts = [
        'check_time' => 'datetime',
        'response_time' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_in_zone' => 'boolean',
        'notification_sent' => 'boolean',
    ];

    /**
     * Relations
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    public function notification()
    {
        return $this->belongsTo(Notification::class);
    }
}
PHP,

    'Tardiness.php' => <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tardiness extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'campus_id',
        'attendance_id',
        'date',
        'expected_time',
        'actual_time',
        'late_minutes',
        'is_justified',
        'justification',
        'justified_by',
        'justified_at',
    ];

    protected $casts = [
        'date' => 'date',
        'expected_time' => 'datetime:H:i:s',
        'actual_time' => 'datetime:H:i:s',
        'late_minutes' => 'integer',
        'is_justified' => 'boolean',
        'justified_at' => 'datetime',
    ];

    /**
     * Relations
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function justifiedBy()
    {
        return $this->belongsTo(User::class, 'justified_by');
    }
}
PHP,

    'Absence.php' => <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Absence extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'campus_id',
        'date',
        'type',
        'is_justified',
        'justification',
        'justified_by',
        'justified_at',
    ];

    protected $casts = [
        'date' => 'date',
        'is_justified' => 'boolean',
        'justified_at' => 'datetime',
    ];

    /**
     * Relations
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    public function justifiedBy()
    {
        return $this->belongsTo(User::class, 'justified_by');
    }
}
PHP,

    'Notification.php' => <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'body',
        'is_read',
        'read_at',
        'sent_at',
        'delivery_status',
        'data',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'sent_at' => 'datetime',
        'data' => 'array',
    ];

    /**
     * Relations
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function presenceChecks()
    {
        return $this->hasMany(PresenceCheck::class);
    }
}
PHP,

    'Setting.php' => <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key_name',
        'value',
        'type',
        'description',
    ];

    /**
     * Helper methods
     */
    public static function get($key, $default = null)
    {
        $setting = self::where('key_name', $key)->first();

        if (!$setting) {
            return $default;
        }

        // Cast selon le type
        return match ($setting->type) {
            'integer' => (int) $setting->value,
            'boolean' => $setting->value === 'true' || $setting->value === '1',
            'json' => json_decode($setting->value, true),
            default => $setting->value,
        };
    }

    public static function set($key, $value)
    {
        $setting = self::where('key_name', $key)->first();

        if ($setting) {
            $setting->update(['value' => $value]);
        } else {
            self::create([
                'key_name' => $key,
                'value' => $value,
                'type' => 'string',
            ]);
        }

        return true;
    }
}
PHP,
];

foreach ($models as $filename => $content) {
    $file = $modelsPath . $filename;
    file_put_contents($file, $content);
    echo "✅ Model {$filename} créé avec succès\n";
}

echo "\n🎉 Tous les Models ont été créés avec leurs relations !\n";
