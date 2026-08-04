<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Commercial layer added for the paid product: subscription, a credit ledger,
 * and hiring-manager contacts (revealed for credits).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('plan');                            // premium | elite
            $table->string('billing_cycle');                   // weekly | monthly | quarterly
            $table->enum('status', ['trialing', 'active', 'past_due', 'cancelled'])->default('trialing');
            $table->string('provider')->default('lemonsqueezy');
            $table->string('provider_subscription_id')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('renews_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        // Append-only ledger. Current balance = sum(delta). credits reveal contacts.
        Schema::create('credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('delta');                          // +grant, -spend
            $table->string('reason');                          // monthly_grant | contact_reveal | refund
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('hiring_manager_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('job_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('title')->nullable();
            $table->string('email')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('source')->nullable();              // where the contact came from

            // Gated: details hidden until revealed (costs 1 credit).
            $table->boolean('revealed')->default(false);
            $table->timestamp('revealed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'revealed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hiring_manager_contacts');
        Schema::dropIfExists('credit_transactions');
        Schema::dropIfExists('subscriptions');
    }
};
