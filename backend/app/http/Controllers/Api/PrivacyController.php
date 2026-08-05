<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * GDPR / CCPA data-subject rights:
 *  - export():  give the user everything we hold on them (data portability).
 *  - destroy(): hard-delete the account and all related data (right to erasure).
 */
class PrivacyController extends Controller
{
    public function export(Request $request)
    {
        $u = $request->user();

        return response()->json([
            'exported_at'   => now()->toIso8601String(),
            'user'          => $u->only(['id', 'name', 'email', 'created_at']),
            'job_profile'   => $u->jobProfile,
            'resumes'       => $u->resumes()->get(['id', 'label', 'parsed_text', 'created_at']),
            'applications'  => \App\Models\Application::where('user_id', $u->id)->get(),
            'scouts'        => $u->autoApplyRules()->get(),
            'subscriptions' => $u->subscriptions()->get(),
            'referrals'     => \App\Models\Referral::where('referrer_id', $u->id)->get(),
        ])->header('Content-Disposition', 'attachment; filename="foxloopr-my-data.json"');
    }

    public function destroy(Request $request)
    {
        $u = $request->user();

        // Delete stored resume files, then the user (FKs cascade the rest).
        Storage::deleteDirectory("resumes/{$u->id}");

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $u->delete();

        return response()->json(['deleted' => true]);
    }
}
