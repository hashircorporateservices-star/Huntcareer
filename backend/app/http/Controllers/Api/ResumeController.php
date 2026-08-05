<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\Resume;
use App\Services\ResumeTailoringService;
use Illuminate\Http\Request;

class ResumeController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()->resumes()->latest()->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'label'        => 'required|string|max:120',
            'is_base'      => 'boolean',
            'storage_path' => 'nullable|string',
            'parsed_text'  => 'nullable|string',
        ]);
        $data['user_id'] = $request->user()->id;

        // Only one base resume: demote any existing base if this one is base.
        if ($data['is_base'] ?? false) {
            $request->user()->resumes()->where('is_base', true)->update(['is_base' => false]);
        }

        return response()->json(Resume::create($data), 201);
    }

    public function show(Request $request, Resume $resume)
    {
        $this->owns($request, $resume);
        return $resume;
    }

    public function update(Request $request, Resume $resume)
    {
        $this->owns($request, $resume);
        $resume->update($request->validate([
            'label'       => 'sometimes|string|max:120',
            'parsed_text' => 'nullable|string',
        ]));
        return $resume;
    }

    public function destroy(Request $request, Resume $resume)
    {
        $this->owns($request, $resume);
        $resume->delete();
        return response()->noContent();
    }

    /** Produce a tailored variant of a resume for a specific job. */
    public function tailor(Request $request, Resume $resume, Job $job, ResumeTailoringService $tailor)
    {
        $this->owns($request, $resume);
        return response()->json($tailor->tailorForJob($resume, $job), 201);
    }

    /** AI Resume Builder — build a fresh resume from your profile for a target market. */
    public function build(Request $request, ResumeTailoringService $tailor)
    {
        $data = $request->validate([
            'target_title'   => 'required|string|max:120',
            'target_country' => 'required|string|max:60',
        ]);

        return response()->json(
            $tailor->buildFromProfile($request->user(), $data['target_country'], $data['target_title']),
            201
        );
    }

    /** Upload a resume file (pdf/docx/txt), store it, extract text. */
    public function upload(Request $request, \App\Services\ResumeParsingService $parser)
    {
        $request->validate([
            'file'    => 'required|file|mimes:pdf,doc,docx,txt,md|max:5120', // 5 MB
            'label'   => 'nullable|string|max:120',
            'is_base' => 'boolean',
        ]);

        $file = $request->file('file');
        $ext  = $file->getClientOriginalExtension();

        // Private storage — resumes are never web-public.
        $path = $file->store("resumes/{$request->user()->id}");
        $text = $parser->extract(storage_path("app/{$path}"), $ext);

        if ($request->boolean('is_base')) {
            $request->user()->resumes()->where('is_base', true)->update(['is_base' => false]);
        }

        return response()->json(Resume::create([
            'user_id'      => $request->user()->id,
            'label'        => $request->input('label') ?: $file->getClientOriginalName(),
            'is_base'      => $request->boolean('is_base'),
            'storage_path' => $path,
            'parsed_text'  => $text,
            'parsed_at'    => now(),
        ]), 201);
    }

    protected function owns(Request $request, Resume $resume): void
    {
        abort_unless($resume->user_id === $request->user()->id, 403);
    }
}
