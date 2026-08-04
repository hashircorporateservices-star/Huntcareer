<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Daily rollups so Analytics loads instantly instead of aggregating live.
        Schema::create('analytics_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('jobs_found')->default(0);
            $table->unsignedInteger('applications_submitted')->default(0);
            $table->unsignedInteger('interviews')->default(0);
            $table->unsignedInteger('offers')->default(0);
            $table->unsignedInteger('rejections')->default(0);
            $table->json('by_country')->nullable();
            $table->json('by_role_family')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'date']);
        });

        // Key/value per user. Encryption handled at the app layer for sensitive keys
        // (e.g. any provider tokens) via Laravel's encrypted casts.
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->longText('value')->nullable();
            $table->boolean('is_encrypted')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'key']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');                         // auto_apply.prepared, application.submitted, ...
            $table->string('subject_type')->nullable();       // model class
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('meta')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['subject_type', 'subject_id']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('analytics_snapshots');
    }
};
