<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-country apply scheduling. Each entry:
 *   { "country": "GB", "run_at": "09:00", "timezone": "Europe/London", "days": ["mon",...] }
 * The Scout's global run_at/timezone/run_days remain the fallback for any country
 * without its own entry.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auto_apply_rules', function (Blueprint $table) {
            $table->json('country_schedules')->nullable()->after('run_days');
        });
    }

    public function down(): void
    {
        Schema::table('auto_apply_rules', fn (Blueprint $t) => $t->dropColumn('country_schedules'));
    }
};
