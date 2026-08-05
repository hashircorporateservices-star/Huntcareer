<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\Http\Request;

/**
 * Public, unauthenticated job board — the lead magnet. Reads the same jobs table
 * the Scouts populate from Adzuna / official ATS feeds (no scraping).
 */
class PublicJobController extends Controller
{
    public function index(Request $request)
    {
        return Job::query()
            ->with('company:id,name')
            ->when($request->country, fn ($q, $c) => $q->where('country', strtoupper($c)))
            ->when($request->q, fn ($q, $term) => $q->where('title', 'ilike', "%{$term}%"))
            ->when($request->visa, fn ($q) => $q->where('visa_sponsorship', true))
            ->whereNull('closed_at')
            ->orderByDesc('posted_at')
            ->paginate(24)
            ->through(fn (Job $j) => [
                'id'        => $j->id,
                'title'     => $j->title,
                'company'   => $j->company?->name,
                'country'   => $j->country,
                'city'      => $j->city,
                'work_mode' => $j->work_mode,
                'visa'      => $j->visa_sponsorship,
                'apply_url' => $j->apply_url,
                'posted_at' => $j->posted_at,
            ]);
    }
}
