<?php

use App\Http\Controllers\Api\AutoApplyRuleController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\ReviewQueueController;
use Illuminate\Support\Facades\Route;

// Public: Lemon Squeezy webhook (verified by HMAC signature, not auth).
Route::post('/billing/webhook', [BillingController::class, 'webhook']);

/*
 * All routes are auth-protected (Sanctum). Single-operator app, but auth still
 * gates the API. Rate limiting applied via the 'throttle' middleware group.
 */
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {

    // Auth
    Route::get('/me', [\App\Http\Controllers\Auth\SocialAuthController::class, 'me']);
    Route::post('/logout', [\App\Http\Controllers\Auth\SocialAuthController::class, 'logout']);

    // Billing
    Route::post('/billing/checkout', [BillingController::class, 'checkout']);

    // Jobs & matching
    Route::get('/jobs', [\App\Http\Controllers\Api\JobController::class, 'index']);
    Route::get('/jobs/{job}', [\App\Http\Controllers\Api\JobController::class, 'show']);
    Route::post('/jobs/{job}/save', [\App\Http\Controllers\Api\JobController::class, 'save']);

    // Documents
    Route::apiResource('/resumes', \App\Http\Controllers\Api\ResumeController::class);
    Route::post('/resumes/build', [\App\Http\Controllers\Api\ResumeController::class, 'build']);
    Route::post('/resumes/{resume}/tailor/{job}', [\App\Http\Controllers\Api\ResumeController::class, 'tailor']);
    Route::apiResource('/cover-letters', \App\Http\Controllers\Api\CoverLetterController::class);

    // Applications pipeline
    Route::apiResource('/applications', \App\Http\Controllers\Api\ApplicationController::class);
    Route::patch('/applications/{application}/status', [\App\Http\Controllers\Api\ApplicationController::class, 'updateStatus']);

    // Auto-apply rules (Scouts)
    Route::apiResource('/auto-apply/rules', AutoApplyRuleController::class);
    Route::post('/auto-apply/rules/{rule}/run', [AutoApplyRuleController::class, 'runNow']);

    // Reusable screening-answer profile (wizard Step 3)
    Route::get('/job-profile', [\App\Http\Controllers\Api\JobProfileController::class, 'show']);
    Route::post('/job-profile', [\App\Http\Controllers\Api\JobProfileController::class, 'upsert']);

    // Review queue (approve / submit / skip)
    Route::get('/auto-apply/queue', [ReviewQueueController::class, 'index']);
    Route::post('/auto-apply/queue/bulk-approve', [ReviewQueueController::class, 'bulkApprove']);
    Route::post('/auto-apply/queue/{item}/approve', [ReviewQueueController::class, 'approve']);
    Route::post('/auto-apply/queue/{item}/submit', [ReviewQueueController::class, 'submit']);
    Route::post('/auto-apply/queue/{item}/skip', [ReviewQueueController::class, 'skip']);

    // Recruiters, hiring-manager contacts, interview prep, analytics
    Route::apiResource('/recruiters', \App\Http\Controllers\Api\RecruiterController::class);
    Route::get('/contacts', [\App\Http\Controllers\Api\ContactController::class, 'index']);
    Route::post('/contacts/{contact}/reveal', [\App\Http\Controllers\Api\ContactController::class, 'reveal']);
    // Interview prep + roleplay
    Route::post('/interview-questions/generate', [\App\Http\Controllers\Api\InterviewController::class, 'generate']);
    Route::post('/interview/roleplay', [\App\Http\Controllers\Api\InterviewController::class, 'roleplay']);

    // Career tools
    Route::post('/career/salary-estimate', [\App\Http\Controllers\Api\CareerToolsController::class, 'salaryEstimate']);
    Route::post('/career/follow-up', [\App\Http\Controllers\Api\CareerToolsController::class, 'followUp']);
    Route::get('/analytics', [\App\Http\Controllers\Api\AnalyticsController::class, 'index']);
});
