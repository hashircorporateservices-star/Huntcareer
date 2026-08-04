<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Core tables: the single account (you), the companies you're targeting,
 * the jobs pulled from sources, and the AI match score per job.
 *
 * Personal-use note: this is single-tenant. There is a `users` table because
 * auth (Google / Microsoft / email) still needs an identity row, but the whole
 * app assumes one operator.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();          // null for pure OAuth accounts
            $table->string('oauth_provider')->nullable();     // google | microsoft | null
            $table->string('oauth_provider_id')->nullable();
            $table->string('timezone')->default('Asia/Dubai');
            $table->boolean('is_admin')->default(false);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('website')->nullable();
            $table->string('domain')->nullable()->index();    // used to dedupe + join recruiters
            $table->string('industry')->nullable();
            $table->string('hq_country', 2)->nullable();      // ISO-3166 alpha-2
            $table->string('size')->nullable();               // e.g. "51-200"
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOndelete();
            $table->string('source');                         // linkedin | indeed | seek | stepstone | greenhouse | ...
            $table->string('source_job_id')->nullable();      // external id, for dedupe
            $table->string('apply_url', 1024);
            $table->boolean('is_direct_ats')->default(false); // true => official ATS we may auto-submit to
            $table->string('ats_provider')->nullable();       // greenhouse | lever | workday | null

            $table->string('title');
            $table->string('role_family')->nullable();        // normalised: finance_manager | financial_controller | ...
            $table->string('country', 2)->index();            // ISO alpha-2
            $table->string('city')->nullable();
            $table->enum('work_mode', ['remote', 'hybrid', 'onsite'])->nullable();

            $table->unsignedInteger('salary_min')->nullable();
            $table->unsignedInteger('salary_max')->nullable();
            $table->string('salary_currency', 3)->nullable();
            $table->boolean('visa_sponsorship')->nullable();  // null = unknown

            $table->unsignedSmallInteger('experience_years_min')->nullable();
            $table->longText('description')->nullable();
            $table->json('extracted_skills')->nullable();     // AI-parsed required skills

            $table->timestamp('posted_at')->nullable();
            $table->timestamp('fetched_at')->useCurrent();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->unique(['source', 'source_job_id']);
            $table->index(['country', 'role_family']);
        });

        // AI match score is per (job, resume) because tailoring is resume-specific.
        Schema::create('job_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resume_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('score');             // 0-100
            $table->json('breakdown')->nullable();            // {skills: 92, experience: 88, ...}
            $table->text('rationale')->nullable();            // short human-readable why
            $table->timestamps();

            $table->unique(['job_id', 'resume_id']);
            $table->index('score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_matches');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('users');
    }
};
