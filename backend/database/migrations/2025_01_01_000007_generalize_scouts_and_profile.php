<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Broadens a Scout (auto_apply_rule) from finance-only to any job, and captures
 * the screening-question profile the wizard collects (JobCopilot steps 1-4).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auto_apply_rules', function (Blueprint $table) {
            // Step 1 — any job titles (free text, replaces the fixed finance list)
            $table->json('job_titles')->nullable()->after('country');
            $table->boolean('remote')->default(true)->after('job_titles');
            $table->json('remote_locations')->nullable()->after('remote');     // ["Worldwide"] or countries
            $table->boolean('onsite')->default(false)->after('remote_locations');
            $table->json('onsite_locations')->nullable()->after('onsite');     // ["Anywhere in Malta"]
            $table->json('job_types')->nullable()->after('onsite_locations');  // ["fulltime","part_time"]

            // Step 2 — optional filters
            $table->string('match_threshold')->default('higher')->after('min_match_score'); // high|higher|highest
            $table->json('seniority_levels')->nullable()->after('match_threshold');
            $table->json('time_zones')->nullable()->after('seniority_levels');

            // Step 4 — behaviour
            $table->string('mode')->default('manual_review')->after('require_review'); // manual_review | auto_ats
            $table->boolean('auto_save_jobs')->default(true)->after('mode');
            $table->string('writing_style')->nullable()->after('auto_save_jobs');
        });

        // One reusable answer profile per user (Step 3). Scouts reference it.
        Schema::create('job_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('mobile')->nullable();
            $table->string('based_country')->nullable();
            $table->string('based_city')->nullable();
            $table->string('based_state')->nullable();
            $table->string('postcode')->nullable();
            $table->string('current_title')->nullable();
            $table->string('availability')->nullable();          // immediately | 1_week | 2_weeks | 1_month | 2_months

            $table->json('work_auth_countries')->nullable();      // where legally authorised to work
            $table->boolean('requires_visa')->nullable();
            $table->json('nationalities')->nullable();

            $table->unsignedInteger('current_salary')->nullable();
            $table->unsignedInteger('expected_salary')->nullable();
            $table->string('salary_currency', 3)->nullable();

            $table->string('linkedin_url')->nullable();
            $table->text('experience_summary')->nullable();       // the 500-char summary
            $table->json('screening_answers')->nullable();        // extra Q&A the AI reuses

            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_profiles');
        Schema::table('auto_apply_rules', function (Blueprint $table) {
            $table->dropColumn([
                'job_titles', 'remote', 'remote_locations', 'onsite', 'onsite_locations',
                'job_types', 'match_threshold', 'seniority_levels', 'time_zones',
                'mode', 'auto_save_jobs', 'writing_style',
            ]);
        });
    }
};
