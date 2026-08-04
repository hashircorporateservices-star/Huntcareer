<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Services\InterviewService;
use Illuminate\Http\Request;

class InterviewController extends Controller
{
    public function __construct(protected InterviewService $service) {}

    /** Generate technical/HR/behavioural questions for a job. */
    public function generate(Request $request)
    {
        $data = $request->validate(['job_id' => 'required|exists:jobs,id']);
        $job = Job::findOrFail($data['job_id']);

        return response()->json($this->service->generate($request->user(), $job));
    }

    /** One roleplay turn — feedback on the last answer + the next question. */
    public function roleplay(Request $request)
    {
        $data = $request->validate([
            'job_id'          => 'required|exists:jobs,id',
            'history'         => 'array',
            'history.*.role'  => 'required|in:interviewer,candidate',
            'history.*.text'  => 'required|string',
        ]);

        $job = Job::findOrFail($data['job_id']);

        return response()->json($this->service->roleplay($job, $data['history'] ?? []));
    }
}
