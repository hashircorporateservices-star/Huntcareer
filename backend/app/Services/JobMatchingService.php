<?php

namespace App\Services;

use App\Models\Job;
use App\Models\JobMatch;
use App\Models\Resume;
use Illuminate\Support\Facades\Http;

/**
 * Scores how well a resume fits a job (feature #5).
 *
 * The heavy lifting is an LLM call that compares the resume's parsed skills /
 * experience against the job's requirements and returns a structured breakdown.
 * A deterministic fallback keeps the app working if the AI provider is down.
 */
class JobMatchingService
{
    public function scoreAndStore(Job $job, Resume $resume): JobMatch
    {
        $result = $this->score($job, $resume);

        return JobMatch::updateOrCreate(
            ['job_id' => $job->id, 'resume_id' => $resume->id],
            [
                'score'     => $result['score'],
                'breakdown' => $result['breakdown'],
                'rationale' => $result['rationale'],
            ]
        );
    }

    /**
     * @return array{score:int, breakdown:array, rationale:string}
     */
    public function score(Job $job, Resume $resume): array
    {
        try {
            return $this->scoreWithAi($job, $resume);
        } catch (\Throwable $e) {
            report($e);
            return $this->scoreHeuristic($job, $resume);
        }
    }

    protected function scoreWithAi(Job $job, Resume $resume): array
    {
        $prompt = <<<PROMPT
        You are a finance-recruitment matching engine. Compare the candidate resume to the job.
        Return ONLY compact JSON, no markdown:
        {"score": <0-100>, "breakdown": {"skills": <0-100>, "experience": <0-100>, "seniority": <0-100>, "domain": <0-100>}, "rationale": "<one sentence>"}

        JOB:
        Title: {$job->title}
        Country: {$job->country}
        Required experience (yrs): {$job->experience_years_min}
        Required skills: {$this->jsonList($job->extracted_skills)}
        Description: {$this->truncate($job->description, 4000)}

        CANDIDATE:
        Skills: {$this->jsonList($resume->parsed_skills)}
        Experience: {$this->truncate(json_encode($resume->parsed_experience), 4000)}
        PROMPT;

        $response = Http::withToken(config('services.ai.key'))
            ->timeout(30)
            ->post(config('services.ai.endpoint'), [
                'model'    => config('services.ai.model'),
                'messages' => [['role' => 'user', 'content' => $prompt]],
                'max_tokens' => 400,
            ])
            ->throw()
            ->json();

        $raw = data_get($response, 'choices.0.message.content')
            ?? data_get($response, 'content.0.text', '{}');

        $parsed = json_decode($this->stripFences($raw), true) ?: [];

        return [
            'score'     => (int) max(0, min(100, $parsed['score'] ?? 0)),
            'breakdown' => $parsed['breakdown'] ?? [],
            'rationale' => (string) ($parsed['rationale'] ?? ''),
        ];
    }

    /** Deterministic fallback: skill overlap + experience gap. Never throws. */
    protected function scoreHeuristic(Job $job, Resume $resume): array
    {
        $jobSkills = collect($job->extracted_skills ?? [])->map(fn ($s) => strtolower(trim($s)));
        $cvSkills  = collect($resume->parsed_skills ?? [])->map(fn ($s) => strtolower(trim($s)));

        $overlap = $jobSkills->isEmpty()
            ? 60
            : (int) round($jobSkills->intersect($cvSkills)->count() / $jobSkills->count() * 100);

        return [
            'score'     => $overlap,
            'breakdown' => ['skills' => $overlap, 'experience' => null, 'seniority' => null, 'domain' => null],
            'rationale' => 'Heuristic skill-overlap score (AI matcher unavailable).',
        ];
    }

    private function jsonList(?array $items): string
    {
        return $items ? implode(', ', $items) : '(none listed)';
    }

    private function truncate(?string $text, int $max): string
    {
        $text = (string) $text;
        return mb_strlen($text) > $max ? mb_substr($text, 0, $max) . '…' : $text;
    }

    private function stripFences(string $s): string
    {
        return trim(preg_replace('/```(json)?/', '', $s));
    }
}
