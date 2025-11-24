<?php

/**
 * Script pour compléter automatiquement toutes les migrations
 * Exécute ce script avec: php complete_migrations.php
 */

$migrationsPath = __DIR__ . '/database/migrations/';

// Définition de toutes les migrations
$migrations = [
    'role_permissions' => <<<'PHP'
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->foreignId('permission_id')->constrained('permissions')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['role_id', 'permission_id']);
        });
    }
PHP,

    'user_permissions' => <<<'PHP'
    public function up(): void
    {
        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('permission_id')->constrained('permissions')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['user_id', 'permission_id']);
            $table->index('user_id');
        });
    }
PHP,

    'user_campus' => <<<'PHP'
    public function up(): void
    {
        Schema::create('user_campus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('campus_id')->constrained('campuses')->onDelete('cascade');
            $table->boolean('is_primary')->default(false)->comment('Campus principal de l\'employé');
            $table->timestamps();

            $table->unique(['user_id', 'campus_id']);
            $table->index('user_id');
            $table->index('campus_id');
        });
    }
PHP,

    'attendances' => <<<'PHP'
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('campus_id')->constrained('campuses')->onDelete('cascade');

            // Type de pointage
            $table->enum('type', ['check_in', 'check_out']);

            // Date et heure du pointage
            $table->timestamp('timestamp')->useCurrent();

            // Géolocalisation au moment du pointage
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->float('accuracy')->nullable()->comment('Précision GPS en mètres');

            // Détection de retard
            $table->boolean('is_late')->default(false);
            $table->integer('late_minutes')->default(0);

            // Informations supplémentaires
            $table->json('device_info')->nullable();
            $table->text('notes')->nullable();

            // Statut
            $table->enum('status', ['valid', 'invalid', 'disputed'])->default('valid');

            $table->timestamps();

            $table->index('user_id');
            $table->index('campus_id');
            $table->index('type');
            $table->index('timestamp');
            $table->index('is_late');
        });
    }
PHP,

    'presence_checks' => <<<'PHP'
    public function up(): void
    {
        Schema::create('presence_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('campus_id')->constrained('campuses')->onDelete('cascade');

            // Moment de la vérification
            $table->timestamp('check_time')->useCurrent();

            // Réponse de l'employé
            $table->enum('response', ['present', 'absent', 'no_response'])->default('no_response');
            $table->timestamp('response_time')->nullable();

            // Géolocalisation au moment de la réponse
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->boolean('is_in_zone')->nullable();

            // Notification
            $table->boolean('notification_sent')->default(false);
            $table->unsignedBigInteger('notification_id')->nullable();

            $table->timestamps();

            $table->index('user_id');
            $table->index('campus_id');
            $table->index('check_time');
            $table->index('response');
        });
    }
PHP,

    'tardiness' => <<<'PHP'
    public function up(): void
    {
        Schema::create('tardiness', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('campus_id')->constrained('campuses')->onDelete('cascade');
            $table->foreignId('attendance_id')->constrained('attendances')->onDelete('cascade');

            // Informations sur le retard
            $table->date('date');
            $table->time('expected_time')->comment('Heure attendue (avec tolérance)');
            $table->time('actual_time')->comment('Heure réelle d\'arrivée');
            $table->integer('late_minutes');

            // Justification
            $table->boolean('is_justified')->default(false);
            $table->text('justification')->nullable();
            $table->foreignId('justified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('justified_at')->nullable();

            $table->timestamps();

            $table->index('user_id');
            $table->index('campus_id');
            $table->index('date');
            $table->index('is_justified');
        });
    }
PHP,

    'absences' => <<<'PHP'
    public function up(): void
    {
        Schema::create('absences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('campus_id')->constrained('campuses')->onDelete('cascade');

            // Date de l'absence
            $table->date('date');

            // Type d'absence
            $table->enum('type', ['no_check_in', 'early_checkout', 'full_day']);

            // Justification
            $table->boolean('is_justified')->default(false);
            $table->text('justification')->nullable();
            $table->foreignId('justified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('justified_at')->nullable();

            $table->timestamps();

            $table->index('user_id');
            $table->index('campus_id');
            $table->index('date');
            $table->index('type');
            $table->index('is_justified');

            $table->unique(['user_id', 'date']);
        });
    }
PHP,

    'notifications' => <<<'PHP'
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Type de notification
            $table->enum('type', [
                'presence_check',
                'check_in_reminder',
                'check_out_reminder',
                'zone_exit',
                'system'
            ]);

            // Contenu
            $table->string('title');
            $table->text('body');

            // Statut
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();

            // Envoi
            $table->timestamp('sent_at')->useCurrent();
            $table->enum('delivery_status', ['pending', 'sent', 'delivered', 'failed'])->default('pending');

            // Données supplémentaires
            $table->json('data')->nullable();

            $table->timestamps();

            $table->index('user_id');
            $table->index('type');
            $table->index('is_read');
            $table->index('sent_at');
        });
    }
PHP,

    'settings' => <<<'PHP'
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key_name', 100)->unique();
            $table->text('value')->nullable();
            $table->enum('type', ['string', 'integer', 'boolean', 'json'])->default('string');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('key_name');
        });
    }
PHP,
];

echo "🚀 Début de la complétion automatique des migrations...\n\n";

$completed = 0;
$errors = 0;

foreach ($migrations as $tableName => $upMethod) {
    // Trouver le fichier de migration correspondant
    $files = glob($migrationsPath . "*_create_{$tableName}_table.php");

    if (empty($files)) {
        echo "❌ Migration pour '{$tableName}' non trouvée\n";
        $errors++;
        continue;
    }

    $file = $files[0];
    $content = file_get_contents($file);

    // Remplacer la méthode up() vide par la méthode complète
    $pattern = '/public function up\(\): void\s*\{[^}]*\}/s';
    $replacement = $upMethod;

    $newContent = preg_replace($pattern, $replacement, $content);

    if ($newContent === $content) {
        echo "⚠️  Aucun changement pour '{$tableName}' (déjà complétée ou format différent)\n";
        continue;
    }

    file_put_contents($file, $newContent);
    echo "✅ Migration '{$tableName}' complétée\n";
    $completed++;
}

echo "\n";
echo "═══════════════════════════════════════════════════\n";
echo "📊 RÉSUMÉ\n";
echo "═══════════════════════════════════════════════════\n";
echo "✅ Migrations complétées: {$completed}\n";
if ($errors > 0) {
    echo "❌ Erreurs: {$errors}\n";
}
echo "═══════════════════════════════════════════════════\n";
echo "\n";
echo "🎉 Script terminé !\n";
echo "👉 Prochaine étape: php artisan migrate\n";
