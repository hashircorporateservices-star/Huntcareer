<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobProfile;
use Illuminate\Http\Request;

/** One reusable screening-answer profile per user (wizard Step 3). */
class JobProfileController extends Controller
{
    public function show(Request $request)
    {
        return $request->user()->jobProfile ?? new JobProfile();
    }

    public function upsert(Request $request)
    {
        $data = $request->validate([
            'mobile'              => 'nullable|string|max:40',
            'based_country'       => 'nullable|string|max:80',
            'based_city'          => 'nullable|string|max:80',
            'based_state'         => 'nullable|string|max:80',
            'postcode'            => 'nullable|string|max:20',
            'current_title'       => 'nullable|string|max:120',
            'availability'        => 'nullable|string|max:20',
            'work_auth_countries' => 'nullable|array',
            'requires_visa'       => 'nullable|boolean',
            'nationalities'       => 'nullable|array',
            'current_salary'      => 'nullable|integer|min:0',
            'expected_salary'     => 'nullable|integer|min:0',
            'salary_currency'     => 'nullable|string|size:3',
            'linkedin_url'        => 'nullable|url|max:255',
            'experience_summary'  => 'nullable|string|max:500',
            'screening_answers'   => 'nullable|array',
        ]);

        return JobProfile::updateOrCreate(['user_id' => $request->user()->id], $data);
    }
}
