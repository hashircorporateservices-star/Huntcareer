<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Career Tools (feature #10 + #13):
 *  - salaryEstimate(): a range for a title/country/experience. Uses Adzuna's
 *    salary histogram when available, else an AI estimate.
 *  - followUp(): generate a follow-up email (post-application, post-interview, thank-you).
 */
class CareerToolsService
{
    /** @return array{min:?int,max:?int,currency:string,source:string,note:string} */
    public function salaryEstimate(string $title, string $country, ?int $years = null): array
    {
        // Try Adzuna's real histogram data first.
        $adzuna = $this->adzunaSalary($title, $country);
        if ($adzuna) {
            return $adzuna;
        }

        // Fall back to an AI estimate.
        try {
            $prompt = "Estimate a realistic annual salary range for a \"{$title}\" in {$country}"
                . ($years ? " with {$years} years of experience" : "")
                . ". Return ONLY JSON: {\"min\":<int>,\"max\":<int>,\"currency\":\"<ISO>\"}.";
            $raw = $this->ai($prompt, 150);
            $p = json_decode(trim(preg_replace('/```(json)?/', '', $raw)), true) ?: [];
            return [
                'min'      => $p['min'] ?? null,
                'max'      => $p['max'] ?? null,
                'currency' => $p['currency'] ?? 'USD',
                'source'   => 'ai_estimate',
                'note'     => 'AI estimate — treat as a rough guide, not a benchmark.',
            ];
        } catch (\Throwable $e) {
            report($e);
            return ['min' => null, 'max' => null, 'currency' => 'USD', 'source' => 'unavailable', 'note' => 'Estimate unavailable.'];
        }
    }

    protected function adzunaSalary(string $title, string $country): ?array
    {
        $cfg = config('services.adzuna');
        if (empty($cfg['app_id'])) {
            return null;
        }
        $map = ['GB' => 'gb', 'AU' => 'au', 'DE' => 'de', 'US' => 'us', 'NZ' => 'nz'];
        $cc = $map[$country] ?? null;
        if (! $cc) {
            return null;
        }

        try {
            $res = Http::timeout(20)->get("https://api.adzuna.com/v1/api/jobs/{$cc}/history", [
                'app_id'  => $cfg['app_id'],
                'app_key' => $cfg['app_key'],
                'what'    => $title,
            ])->throw()->json();

            $months = $res['month'] ?? [];
            $vals = array_values(array_filter($months));
            if (! $vals) {
                return null;
            }
            $avg = array_sum($vals) / count($vals);

            return [
                'min'      => (int) round($avg * 0.85),
                'max'      => (int) round($avg * 1.15),
                'currency' => strtoupper($cc) === 'US' ? 'USD' : ($cc === 'gb' ? 'GBP' : 'EUR'),
                'source'   => 'adzuna',
                'note'     => 'Based on Adzuna market data for this title.',
            ];
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    /** Generate a follow-up email. $type: post_application | post_interview | thank_you */
    public function followUp(string $type, array $context): string
    {
        $labels = [
            'post_application' => 'a polite follow-up a week after applying, showing continued interest',
            'post_interview'   => 'a follow-up after an interview, referencing what was discussed',
            'thank_you'        => 'a concise thank-you note sent within 24h of the interview',
        ];
        $intent = $labels[$type] ?? $labels['post_application'];

        try {
            $prompt = "Write {$intent}. Keep it under 150 words, professional, no fluff. "
                . "Context: " . json_encode($context) . ". Return the email text only.";
            return $this->ai($prompt, 400);
        } catch (\Throwable $e) {
            report($e);
            return "[Follow-up generation unavailable — please write manually.]";
        }
    }

    protected function ai(string $prompt, int $maxTokens): string
    {
        $res = Http::withToken(config('services.ai.key'))
            ->timeout(40)
            ->post(config('services.ai.endpoint'), [
                'model'      => config('services.ai.model'),
                'messages'   => [['role' => 'user', 'content' => $prompt]],
                'max_tokens' => $maxTokens,
            ])->throw()->json();

        return data_get($res, 'choices.0.message.content')
            ?? data_get($res, 'content.0.text', '');
    }
}
