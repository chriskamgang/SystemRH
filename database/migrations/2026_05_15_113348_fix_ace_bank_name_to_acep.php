<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->whereRaw("UPPER(TRIM(banque)) = 'ACE'")
            ->update(['banque' => 'ACEP']);
    }

    public function down(): void
    {
        // Non réversible - les données originales étaient incorrectes
    }
};
