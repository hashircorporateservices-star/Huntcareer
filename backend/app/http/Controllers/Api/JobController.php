<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Job;
use Illuminate\Http\Request;

class JobController extends Controller
{
    /** Browsable, filterable list of stored jobs. */
    public function index(Request $request)
    {
        return Job::query()
            ->with('company:id,name')
            ->when($request->country, fn ($q, $c) => $q->where('country', $c))
            ->when($request->work_mode, fn ($q, $m) => $q->where('work_mode', $m))
            ->when($request->q, fn ($q, $term) => $q->where('title', 'ilike', "%{$term}%"))
            ->whereNull('closed_at')
            ->orderByDesc('posted_at')
            ->paginate(20);
    }

    public function show(Job $job)
    {
        return $job->load('company');
    }

    /** Save a job (creates a 'saved' application). */
    public function save(Request $request, Job $job)
    {
        $application = Application::firstOrCreate(
            ['user_id' => $request->user()->id, 'job_id' => $job->id],
            ['status' => 'saved']
        );

        return response()->json($application, 201);
    }
}
