<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resumes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label');                          // "Base CV", "UK Controller variant"
            $table->boolean('is_base')->default(false);       // the master you upload once
            $table->string('storage_path');                   // S3 key of the original file
            $table->string('mime')->nullable();

            // AI extraction output (feature #1). Kept structured so tailoring + matching can read it.
            $table->json('parsed_experience')->nullable();
            $table->json('parsed_education')->nullable();
            $table->json('parsed_skills')->nullable();
            $table->json('parsed_certificates')->nullable();
            $table->json('parsed_achievements')->nullable();
            $table->longText('parsed_text')->nullable();      // plain-text fallback for search/matching

            $table->timestamp('parsed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('cover_letters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('resume_id')->nullable()->constrained()->nullOnDelete();
            $table->string('label')->nullable();
            $table->longText('body');
            $table->boolean('is_template')->default(false);   // reusable base template vs. generated one
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cover_letters');
        Schema::dropIfExists('resumes');
    }
};
