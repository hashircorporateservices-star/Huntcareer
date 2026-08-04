<?php

namespace App\Services;

use App\Models\Job;
use App\Models\Resume;
use Illuminate\Support\Facades\Http;

/**
 * Feature #6 — rewrites the base resume to match a specific job, ATS-optimised.
 * Produces a NEW resume row (never mutates the base) so you keep every variant.
 */
class ResumeTailoringService
{
    public function tailorForJob(Resume $base, Job $job): Resume
    {
        // Reuse an existing tailored variant for this job if one exists.
        $existing = Resume::where('user_id', $base->user_id)
            ->where('label', $this->variantLabel($job))
            ->first();
        if ($existing) {
            return $existing;
        }

        $tailoredText = $this->rewrite($base, $job);

        return Resume::create([
            'user_id'          => $base->user_id,
            'label'            => $this->variantLabel($job),
            'is_base'          => false,
            'storage_path'     => $base->storage_path,   // regenerated PDF is produced on export
            'parsed_skills'    => $base->parsed_skills,
            'parsed_experience'=> $base->parsed_experience,
            'parsed_education' => $base->parsed_education,
            'parsed_text'      => $tailoredText,
            'parsed_at'        => now(),
        ]);
    }

    protected function rewrite(Resume $base, Job $job): string
    {
        try {
            $country = $job->country ?: 'the target country';
            $prompt = <<<PROMPT
            Rewrite this professional's resume to target the job below.

            Hard rules:
            - Keep every fact truthful. Do not invent employers, titles, dates, or metrics.
            - Follow the standard resume/CV format and conventions of {$country}
              (length, sections, whether a photo/DOB is expected, date formats, spelling).
            - Keep it concise and easy to scan — recruiters have seconds. Short, clear,
              high-impact wording; cut filler and generic phrasing.
            - Mirror the job's real keywords ONLY where the candidate genuinely has the skill.
            - Write in natural, fluent, professional human language — the voice of an
              experienced, educated professional. It must NOT read as automated, templated,
              or AI-generated: vary sentence structure, avoid buzzword stuffing and clichés
              ("results-driven", "dynamic team player"), and avoid obviously synthetic phrasing.
            - Use clean ATS-friendly structure (no tables/columns/graphics). Return plain text only.

            JOB TITLE: {$job->title}
            JOB REQUIREMENTS: {$job->description}

            CURRENT RESUME:
            {$base->parsed_text}
            PROMPT;

            $response = Http::withToken(config('services.ai.key'))
                ->timeout(45)
                ->post(config('services.ai.endpoint'), [
                    'model'      => config('services.ai.model'),
                    'messages'   => [['role' => 'user', 'content' => $prompt]],
                    'max_tokens' => 2000,
                ])->throw()->json();

            return data_get($response, 'choices.0.message.content')
                ?? data_get($response, 'content.0.text', $base->parsed_text);
        } catch (\Throwable $e) {
            report($e);
            return $base->parsed_text ?? '';   // fall back to the untailored text
        }
    }

    protected function variantLabel(Job $job): string
    {
        return "Tailored · job#{$job->id} · {$job->title}";
    }

    /**
     * AI Resume Builder — build a resume from scratch out of the user's profile,
     * following the target country's conventions. Returns a new Resume row.
     */
    public function buildFromProfile(\App\Models\User $user, string $targetCountry, string $targetTitle): Resume
    {
        $profile = $user->jobProfile;
        $summary = $profile?->experience_summary ?? '';
        $base    = $user->baseResume;
        $source  = $base?->parsed_text ?? $summary;

        $text = $this->ai(<<<PROMPT
        Write a complete, professional resume for "{$targetTitle}" following the standard
        resume/CV conventions of {$targetCountry}.

        Rules:
        - Use ONLY the facts provided; do not fabricate employers, dates, or achievements.
        - Concise and easy to scan; short, clear, impactful wording; no filler.
        - Natural, fluent, professional human language — the voice of an experienced,
          educated professional. Must NOT sound automated, templated, or AI-generated.
        - ATS-friendly plain text (no tables/graphics). Return the resume text only.

        CANDIDATE NAME: {$user->name}
        SUMMARY: {$summary}
        SOURCE DETAIL: {$source}
        PROMPT);

        return Resume::create([
            'user_id'     => $user->id,
            'label'       => "Built · {$targetTitle} ({$targetCountry})",
            'is_base'     => false,
            'storage_path'=> $base?->storage_path ?? '',
            'parsed_text' => $text,
            'parsed_at'   => now(),
        ]);
    }

    protected function ai(string $prompt): string
    {
        try {
            $res = \Illuminate\Support\Facades\Http::withToken(config('services.ai.key'))
                ->timeout(60)
                ->post(config('services.ai.endpoint'), [
                    'model'      => config('services.ai.model'),
                    'messages'   => [['role' => 'user', 'content' => $prompt]],
                    'max_tokens' => 2000,
                ])->throw()->json();

            return data_get($res, 'choices.0.message.content')
                ?? data_get($res, 'content.0.text', '');
        } catch (\Throwable $e) {
            report($e);
            return '';
        }
    }
}
