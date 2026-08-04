<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CoverLetter;
use Illuminate\Http\Request;

class CoverLetterController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()->id
            ? CoverLetter::where('user_id', $request->user()->id)->latest()->get()
            : [];
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'label'       => 'nullable|string|max:120',
            'body'        => 'required|string',
            'is_template' => 'boolean',
            'job_id'      => 'nullable|exists:jobs,id',
            'resume_id'   => 'nullable|exists:resumes,id',
        ]);
        $data['user_id'] = $request->user()->id;

        return response()->json(CoverLetter::create($data), 201);
    }

    public function show(Request $request, CoverLetter $coverLetter)
    {
        $this->owns($request, $coverLetter);
        return $coverLetter;
    }

    public function update(Request $request, CoverLetter $coverLetter)
    {
        $this->owns($request, $coverLetter);
        $coverLetter->update($request->validate([
            'label' => 'sometimes|string|max:120',
            'body'  => 'sometimes|string',
        ]));
        return $coverLetter;
    }

    public function destroy(Request $request, CoverLetter $coverLetter)
    {
        $this->owns($request, $coverLetter);
        $coverLetter->delete();
        return response()->noContent();
    }

    protected function owns(Request $request, CoverLetter $cl): void
    {
        abort_unless($cl->user_id === $request->user()->id, 403);
    }
}
