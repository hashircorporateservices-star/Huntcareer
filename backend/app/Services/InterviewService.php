<?php

namespace App\Services;

use App\Models\InterviewQuestion;
use App\Models\Job;
use App\Models\User;
use Illuminate\Support\Facades\Http;

/**
 * Interview prep (feature #9) + interactive roleplay.
 *  - generate(): produce technical / HR / behavioural questions for a job.
 *  - roleplay(): stateless turn — the AI acts as interviewer, scores the last
 *    answer, and asks the next question. The frontend keeps the history.
 */
class InterviewService
{
    /** @return InterviewQuestion[] */
    public function generate(User $user, Job $job, int $perCategory = 4): array
    {
        $created = [];
        foreach (['technical', 'hr', 'behavioral'] as $category) {
            foreach ($this->askForQuestions($job, $category, $perCategory) as $q) {
                $created[] = InterviewQuestion::create([
                    'user_id'          => $user->id,
                    'job_id'           => $job->id,
                    'category'         => $category,
                    'question'         => $q['question'] ?? '',
                    'suggested_answer' => $q['suggested_answer'] ?? null,
                ]);
            }
        }
        return $created;
    }

    protected function askForQuestions(Job $job, string $category, int $n): array
    {
        try {
            $prompt = "Generate {$n} {$category} interview questions for the role \"{$job->title}\". "
                . "Return ONLY a JSON array: [{\"question\":\"\",\"suggested_answer\":\"\"}]. "
                . "Keep suggested answers concise and grounded in the role.\n\nJob: {$job->description}";

            $raw = $this->ai($prompt, 1200);
            return json_decode(trim(preg_replace('/```(json)?/', '', $raw)), true) ?: [];
        } catch (\Throwable $e) {
            report($e);
            return [];
        }
    }

    /**
     * One roleplay turn.
     *
     * @param array $history  [{role:'interviewer'|'candidate', text:string}, ...]
     * @return array{feedback:?string, question:string, done:bool}
     */
    public function roleplay(Job $job, array $history): array
    {
        $transcript = collect($history)
            ->map(fn ($m) => strtoupper($m['role']) . ': ' . $m['text'])
            ->implode("\n");

        $prompt = <<<PROMPT
        You are an experienced interviewer for "{$job->title}". Conduct a realistic interview.
        Given the transcript so far, if the candidate just answered, give brief, specific feedback
        on that answer (1-2 sentences, note one strength and one improvement). Then ask the next
        question. If there is no candidate turn yet, just ask the opening question.
        Return ONLY JSON: {"feedback": <string or null>, "question": <string>, "done": <bool>}.

        Role description: {$job->description}

        Transcript:
        {$transcript}
        PROMPT;

        try {
            $raw = $this->ai($prompt, 500);
            $parsed = json_decode(trim(preg_replace('/```(json)?/', '', $raw)), true) ?: [];
            return [
                'feedback' => $parsed['feedback'] ?? null,
                'question' => $parsed['question'] ?? 'Tell me about yourself.',
                'done'     => (bool) ($parsed['done'] ?? false),
            ];
        } catch (\Throwable $e) {
            report($e);
            return ['feedback' => null, 'question' => 'Tell me about yourself.', 'done' => false];
        }
    }

    protected function ai(string $prompt, int $maxTokens): string
    {
        $res = Http::withToken(config('services.ai.key'))
            ->timeout(45)
            ->post(config('services.ai.endpoint'), [
                'model'      => config('services.ai.model'),
                'messages'   => [['role' => 'user', 'content' => $prompt]],
                'max_tokens' => $maxTokens,
            ])->throw()->json();

        return data_get($res, 'choices.0.message.content')
            ?? data_get($res, 'content.0.text', '');
    }
}
