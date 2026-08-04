<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    /** Live funnel + totals for the dashboard/analytics page. */
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $byStatus = Application::where('user_id', $userId)
            ->selectRaw('status, count(*) as n')
            ->groupBy('status')
            ->pluck('n', 'status');

        return response()->json([
            'funnel' => [
                'saved'     => (int) ($byStatus['saved'] ?? 0),
                'applied'   => (int) ($byStatus['applied'] ?? 0),
                'assessment'=> (int) ($byStatus['assessment'] ?? 0),
                'interview' => (int) ($byStatus['interview'] ?? 0),
                'offer'     => (int) ($byStatus['offer'] ?? 0),
                'rejected'  => (int) ($byStatus['rejected'] ?? 0),
                'accepted'  => (int) ($byStatus['accepted'] ?? 0),
            ],
            'total_applications' => Application::where('user_id', $userId)->count(),
        ]);
    }
}
