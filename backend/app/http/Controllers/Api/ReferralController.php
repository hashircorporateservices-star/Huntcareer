<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $user->ensureReferralCode();

        return response()->json([
            'code'          => $user->referral_code,
            'link'          => rtrim(config('copilot.frontend_url'), '/') . '/?ref=' . $user->referral_code,
            'total'         => Referral::where('referrer_id', $user->id)->count(),
            'rewarded'      => Referral::where('referrer_id', $user->id)->where('status', 'rewarded')->count(),
        ]);
    }
}
