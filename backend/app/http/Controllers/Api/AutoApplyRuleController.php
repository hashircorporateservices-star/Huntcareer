<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AutoApplyRule;
use App\Services\AutoApplyService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AutoApplyRuleController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()->autoApplyRules()->latest()->get();
    }

    public function show(Request $request, AutoApplyRule $rule)
    {
        $this->authorizeOwner($request, $rule);
        return $rule;
    }

    public function store(Request $request, \App\Services\PlanService $plans)
    {
        abort_unless($plans->canAddScout($request->user()), 403,
            'Your plan\'s Scout limit is reached. Upgrade to add more.');

        $data = $this->validated($request);
        $data['user_id'] = $request->user()->id;
        $this->applyThreshold($data);

        return response()->json($request->user()->autoApplyRules()->create($data), 201);
    }

    public function update(Request $request, AutoApplyRule $rule)
    {
        $this->authorizeOwner($request, $rule);
        $data = $this->validated($request, updating: true);
        $this->applyThreshold($data);
        $rule->update($data);

        return $rule;
    }

    /**
     * The wizard exposes a High/Higher/Highest slider; the matcher filters on a
     * numeric min_match_score. Translate one to the other so the slider actually bites.
     */
    protected function applyThreshold(array &$data): void
    {
        if (! empty($data['match_threshold'])) {
            $data['min_match_score'] = match ($data['match_threshold']) {
                'high'    => 70,
                'highest' => 90,
                default   => 80,   // higher
            };
        }
    }

    public function destroy(Request $request, AutoApplyRule $rule)
    {
        $this->authorizeOwner($request, $rule);
        $rule->delete();

        return response()->noContent();
    }

    /** Run a rule immediately, regardless of its schedule. Prepares into the queue only. */
    public function runNow(Request $request, AutoApplyRule $rule, AutoApplyService $service)
    {
        $this->authorizeOwner($request, $rule);
        $count = $service->runRule($rule);

        return response()->json(['prepared' => $count]);
    }

    protected function validated(Request $request, bool $updating = false): array
    {
        $required = $updating ? 'sometimes' : 'required';

        return $request->validate([
            'label'                 => "$required|string|max:120",
            'active'                => 'boolean',

            // Step 1 — any job, any title (free text)
            'job_titles'            => "$required|array|min:1|max:5",
            'job_titles.*'          => 'string|max:120',
            'remote'                => 'boolean',
            'remote_locations'      => 'nullable|array',
            'onsite'                => 'boolean',
            'onsite_locations'      => 'nullable|array',
            'job_types'             => 'nullable|array',
            'job_types.*'           => Rule::in(array_keys(config('copilot.job_types'))),

            // Step 2 — match tier (50 / 75 / 100) + optional filters
            'match_threshold'       => ['nullable', Rule::in(array_keys(config('copilot.match_thresholds')))],
            'min_match_score'       => ['integer', Rule::in([50, 75, 100])],
            'include_below_threshold' => 'boolean',
            'require_visa_sponsorship' => 'boolean',
            'seniority_levels'      => 'nullable|array',
            'seniority_levels.*'    => Rule::in(array_keys(config('copilot.seniority_levels'))),
            'time_zones'            => 'nullable|array',
            'salary_min'            => 'nullable|integer|min:0',

            // documents + schedule
            'resume_id'             => 'nullable|exists:resumes,id',
            'tailor_resume'         => 'boolean',
            'generate_cover_letter' => 'boolean',
            'run_at'                => 'date_format:H:i',
            'run_days'              => 'array',
            'run_days.*'            => Rule::in(['mon','tue','wed','thu','fri','sat','sun']),
            'country_schedules'              => 'nullable|array',
            'country_schedules.*.country'    => ['required_with:country_schedules', Rule::in(array_keys(config('copilot.countries')))],
            'country_schedules.*.run_at'     => 'required_with:country_schedules|date_format:H:i',
            'country_schedules.*.timezone'   => 'nullable|timezone',
            'country_schedules.*.days'       => 'nullable|array',
            'timezone'              => 'string|timezone',
            'max_per_run'           => 'integer|min:1|max:50',

            // Step 4 — behaviour. auto_ats only submits official-ATS jobs; boards stay review-only.
            'mode'                  => ['nullable', Rule::in(['manual_review', 'auto_ats'])],
            'auto_save_jobs'        => 'boolean',
            'writing_style'         => 'nullable|string|max:2000',
        ]);
    }

    protected function authorizeOwner(Request $request, AutoApplyRule $rule): void
    {
        abort_unless($rule->user_id === $request->user()->id, 403);
    }
}
