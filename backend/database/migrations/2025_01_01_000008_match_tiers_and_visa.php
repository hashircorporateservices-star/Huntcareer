<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Match-tier support:
 *  - auto_apply_rules.include_below_threshold: surface sub-50% matches as
 *    review-only suggestions (never auto-prepared as full applications).
 *  - auto_apply_queue.is_borderline: marks a queue item that scored below the
 *    Scout's chosen tier — shown for optional review, not tailored/auto-applied.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auto_apply_rules', function (Blueprint $table) {
            $table->boolean('include_below_threshold')->default(false)->after('min_match_score');
        });

        Schema::table('auto_apply_queue', function (Blueprint $table) {
            $table->boolean('is_borderline')->default(false)->after('match_score');
        });
    }

    public function down(): void
    {
        Schema::table('auto_apply_rules', fn (Blueprint $t) => $t->dropColumn('include_below_threshold'));
        Schema::table('auto_apply_queue', fn (Blueprint $t) => $t->dropColumn('is_borderline'));
    }
};
