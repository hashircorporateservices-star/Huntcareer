<?php

namespace App\Services;

use App\Models\CoverLetter;
use App\Models\Job;
use App\Models\Resume;
use App\Models\User;
use Illuminate\Support\Facades\Http;

/** Feature #7 — generate a cover letter for a job in one call. */
class CoverLetterService
{
    public function generate(User $user, Job $job, Resume $resume): CoverLetter
    {
        $body = $this->write($user, $job, $resume);

        return CoverLetter::create([
            'user_id'   => $user->id,
            'job_id'    => $job->id,
            'resume_id' => $resume->id,
            'label'     => "Cover letter · {$job->title}",
            'body'      => $body,
        ]);
    }

    protected function write(User $user, Job $job, Resume $resume): string
    {
        try {
            $company = $job->company?->name ?? 'the hiring team';
            // Key skills the description emphasises, so the letter targets them directly.
            $keySkills = $this->extractKeySkills($job);
            $spelling  = in_array($job->country, ['GB', 'IE', 'MT']) ? 'British' : 'US';

            $prompt = <<<PROMPT
            Write a cover letter from {$user->name} for "{$job->title}" at {$company}.

            Requirements:
            - {$spelling} spelling. Follow the standard cover-letter conventions of the
              target country ({$job->country}). Max 300 words.
            - Concise and easy to scan — recruiters have seconds. Short, clear, impactful
              sentences; no filler; don't restate the whole CV.
            - The job's key required skills are: {$keySkills}. Address the ones the candidate
              genuinely has, with concrete evidence from the resume. Never claim unsupported skills.
            - Natural, fluent, professional human language — the voice of an experienced,
              educated professional. It must NOT read as automated, templated, or AI-generated:
              avoid clichés ("I am writing to express my interest", "results-driven",
              "great fit"), vary sentence rhythm, sound like a real person.
            - Format:
                Line 1: today's date
                Line 2: blank
                Line 3: "Dear Hiring Manager," (or the named contact if the JD gives one)
                Body: 3 short paragraphs — (1) role + strongest hook, (2) 2-3 matched
                      requirements with evidence, (3) close + availability.
                Sign-off: "Kind regards," then {$user->name}
            - Return the letter text only, no commentary.

            JOB DESCRIPTION: {$job->description}
            RESUME: {$resume->parsed_text}
            PROMPT;

            $response = Http::withToken(config('services.ai.key'))
                ->timeout(45)
                ->post(config('services.ai.endpoint'), [
                    'model'      => config('services.ai.model'),
                    'messages'   => [['role' => 'user', 'content' => $prompt]],
                    'max_tokens' => 900,
                ])->throw()->json();

            return data_get($response, 'choices.0.message.content')
                ?? data_get($response, 'content.0.text', '');
        } catch (\Throwable $e) {
            report($e);
            return "Dear Hiring Manager,\n\n[Cover letter generation is temporarily unavailable — "
                 . "edit this draft manually before submitting.]\n\nKind regards,\n{$user->name}";
        }
    }

    /**
     * Pull the key required skills out of the JD. Reuses any AI-extracted skills
     * already on the job; otherwise asks the model for a short list. Cached back
     * onto the job so we don't re-extract per cover letter.
     */
    protected function extractKeySkills(Job $job): string
    {
        if (! empty($job->extracted_skills)) {
            return implode(', ', $job->extracted_skills);
        }

        try {
            $response = Http::withToken(config('services.ai.key'))
                ->timeout(30)
                ->post(config('services.ai.endpoint'), [
                    'model'      => config('services.ai.model'),
                    'messages'   => [[
                        'role' => 'user',
                        'content' => "List only the 6-10 most important required skills/competencies "
                            . "from this finance job description as a comma-separated list, no other text:\n\n"
                            . $job->description,
                    ]],
                    'max_tokens' => 200,
                ])->throw()->json();

            $list = data_get($response, 'choices.0.message.content')
                ?? data_get($response, 'content.0.text', '');
            $skills = array_values(array_filter(array_map('trim', explode(',', $list))));

            if ($skills) {
                $job->update(['extracted_skills' => $skills]);   // cache for matching + reuse
            }
            return implode(', ', $skills);
        } catch (\Throwable $e) {
            report($e);
            return '(key skills unavailable)';
        }
    }
}
