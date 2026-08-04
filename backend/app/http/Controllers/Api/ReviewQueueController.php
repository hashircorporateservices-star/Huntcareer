<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AutoApplyQueue;
use App\Services\AutoApplyService;
use Illuminate\Http\Request;

/**
 * The human-in-the-loop surface. Nothing here reaches an employer until you
 * explicitly approve and then submit.
 */
class ReviewQueueController extends Controller
{
    public function index(Request $request)
    {
        return AutoApplyQueue::with(['job.company', 'resume:id,label', 'coverLetter:id,label'])
            ->where('user_id', $request->user()->id)
            ->where('status', 'pending_review')
            ->orderByDesc('match_score')
            ->paginate(20);
    }

    public function approve(Request $request, AutoApplyQueue $item)
    {
        $this->authorizeOwner($request, $item);
        abort_unless($item->status === 'pending_review', 422, 'Item is not awaiting review.');

        $item->update(['status' => 'approved', 'reviewed_at' => now()]);

        return $item;
    }

    /** Approve must have happened first. Returns open_url for browser-assisted items. */
    public function submit(Request $request, AutoApplyQueue $item, AutoApplyService $service)
    {
        $this->authorizeOwner($request, $item);

        return response()->json($service->submitApproved($item));
    }

    /** Approve many at once (review a batch, then submit at a safe pace). */
    public function bulkApprove(Request $request)
    {
        $ids = $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer',
        ])['ids'];

        $updated = AutoApplyQueue::where('user_id', $request->user()->id)
            ->whereIn('id', $ids)
            ->where('status', 'pending_review')
            ->update(['status' => 'approved', 'reviewed_at' => now()]);

        return response()->json(['approved' => $updated]);
    }

    public function skip(Request $request, AutoApplyQueue $item)
    {
        $this->authorizeOwner($request, $item);
        $item->update(['status' => 'skipped', 'reviewed_at' => now()]);

        return response()->noContent();
    }

    protected function authorizeOwner(Request $request, AutoApplyQueue $item): void
    {
        abort_unless($item->user_id === $request->user()->id, 403);
    }
}
