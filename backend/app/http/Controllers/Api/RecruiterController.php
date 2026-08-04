<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recruiter;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RecruiterController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()->id
            ? Recruiter::with('company:id,name')->where('user_id', $request->user()->id)->latest()->get()
            : [];
    }

    public function store(Request $request)
    {
        $data = $this->rules($request);
        $data['user_id'] = $request->user()->id;
        return response()->json(Recruiter::create($data), 201);
    }

    public function show(Request $request, Recruiter $recruiter)
    {
        $this->owns($request, $recruiter);
        return $recruiter->load('company');
    }

    public function update(Request $request, Recruiter $recruiter)
    {
        $this->owns($request, $recruiter);
        $recruiter->update($this->rules($request, true));
        return $recruiter;
    }

    public function destroy(Request $request, Recruiter $recruiter)
    {
        $this->owns($request, $recruiter);
        $recruiter->delete();
        return response()->noContent();
    }

    protected function rules(Request $request, bool $updating = false): array
    {
        $req = $updating ? 'sometimes' : 'required';
        return $request->validate([
            'name'         => "$req|string|max:120",
            'email'        => 'nullable|email',
            'phone'        => 'nullable|string|max:40',
            'linkedin_url' => 'nullable|url',
            'company_id'   => 'nullable|exists:companies,id',
            'relationship' => ['nullable', Rule::in(['new','contacted','engaged','placed','cold'])],
            'notes'        => 'nullable|string',
        ]);
    }

    protected function owns(Request $request, Recruiter $r): void
    {
        abort_unless($r->user_id === $request->user()->id, 403);
    }
}
