<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The scheduled auto-apply engine.
 *
 *  auto_apply_rules  = "at THIS time, for THIS country + roles, prepare applications"
 *  auto_apply_queue  = "here is a fully-prepared application waiting for your one click"
 *
 * The queue is the safety boundary. The scheduler fills it; it never submits to a
 * third-party board on its own. Submission happens when you approve an item, and only
 * via browser-assisted open OR an official ATS API (is_direct_ats jobs).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auto_apply_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label');                          // "UK Controllers, weekday mornings"
            $table->boolean('active')->default(true);

            // Targeting
            $table->string('country', 2)->index();            // one country per rule (the "given country")
            $table->json('role_families');                    // ["finance_manager","financial_controller"]
            $table->json('sources')->nullable();              // limit to sources; null = all
            $table->unsignedTinyInteger('min_match_score')->default(80);

            // Filters mirrored from the search UI
            $table->unsignedInteger('salary_min')->nullable();
            $table->json('work_modes')->nullable();           // ["remote","hybrid"]
            $table->boolean('require_visa_sponsorship')->default(false);

            // Documents to use when preparing
            $table->foreignId('resume_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('tailor_resume')->default(true);
            $table->boolean('generate_cover_letter')->default(true);

            // Scheduling — "at mentioned time". Stored as time + days + timezone,
            // compiled to a cron expression the Laravel scheduler reads.
            $table->time('run_at')->default('08:00:00');
            $table->json('run_days')->default(json_encode(['mon','tue','wed','thu','fri']));
            $table->string('timezone')->default('Asia/Dubai');
            $table->unsignedSmallInteger('max_per_run')->default(10);

            // Safety switch. Default true = every application waits for your review.
            // false is ONLY honoured for is_direct_ats jobs with an authorised integration.
            $table->boolean('require_review')->default(true);

            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();
        });

        Schema::create('auto_apply_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('auto_apply_rule_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('job_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resume_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cover_letter_id')->nullable()->constrained()->nullOnDelete();

            $table->unsignedTinyInteger('match_score')->nullable();

            $table->enum('status', [
                'pending_review',   // prepared, waiting for you
                'approved',         // you approved; ready to submit
                'submitted',        // opened in browser / sent via ATS API
                'skipped',          // you dismissed it
                'failed',           // submission attempt errored
            ])->default('pending_review')->index();

            $table->enum('submit_method', ['browser_assisted', 'ats_api', 'email_draft'])->nullable();
            $table->text('prepared_summary')->nullable();     // "why this matched" + doc summary for the review card
            $table->text('failure_reason')->nullable();
            $table->timestamp('prepared_at')->useCurrent();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'job_id']);            // never queue the same job twice
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auto_apply_queue');
        Schema::dropIfExists('auto_apply_rules');
    }
};
