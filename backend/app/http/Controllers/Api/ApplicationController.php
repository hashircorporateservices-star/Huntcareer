<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ApplicationController extends Controller
{
    public function index(Request $request)
    {
        return Application::with('job:id,title,country,company_id')
            ->where('user_id', $request->user()->id)
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(20);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'job_id'    => 'required|exists:jobs,id',
            'status'    => ['nullable', Rule::in(['saved','applied','assessment','interview','offer','rejected','accepted'])],
            'notes'     => 'nullable|string',
        ]);
        $data['user_id'] = $request->user()->id;

        $application = Application::firstOrCreate(
            ['user_id' => $data['user_id'], 'job_id' => $data['job_id']],
            ['status' => $data['status'] ?? 'saved', 'notes' => $data['notes'] ?? null]
        );

        return response()->json($application, 201);
    }

    public function show(Request $request, Application $application)
    {
        $this->owns($request, $application);
        return $application->load('job', 'events');
    }

    public function update(Request $request, Application $application)
    {
        $this->owns($request, $application);
        $application->update($request->validate(['notes' => 'nullable|string']));
        return $application;
    }

    /** Move an application through the pipeline (records the transition). */
    public function updateStatus(Request $request, Application $application)
    {
        $this->owns($request, $application);
        $data = $request->validate([
            'status' => ['required', Rule::in(['saved','applied','assessment','interview','offer','rejected','accepted'])],
            'note'   => 'nullable|string',
        ]);

        $application->transitionTo($data['status'], $data['note'] ?? null);

        return $application->fresh('events');
    }

    public function destroy(Request $request, Application $application)
    {
        $this->owns($request, $application);
        $application->delete();
        return response()->noContent();
    }

    protected function owns(Request $request, Application $a): void
    {
        abort_unless($a->user_id === $request->user()->id, 403);
    }
}
