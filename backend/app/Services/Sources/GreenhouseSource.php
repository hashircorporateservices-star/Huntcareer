<?php

namespace App\Services\Sources;

use Illuminate\Support\Facades\Http;

/**
 * Greenhouse exposes a public job-board API per company board token.
 * These are official-ATS jobs => is_direct_ats true, so they may be auto-submitted.
 * Configure the board tokens you care about in config/services.php.
 */
class GreenhouseSource implements JobSource
{
    public function key(): string { return 'greenhouse'; }

    public function search(array $criteria): array
    {
        $boards = config('services.ats.greenhouse.boards', []);
        $titles = array_map('strtolower', $criteria['titles']);
        $out = [];

        foreach ($boards as $board) {
            try {
                $res = Http::timeout(30)
                    ->get("https://boards-api.greenhouse.io/v1/boards/{$board}/jobs", ['content' => 'true'])
                    ->throw()->json();

                foreach ($res['jobs'] ?? [] as $j) {
                    $title = $j['title'] ?? '';
                    // keep only titles the Scout actually asked for
                    if ($titles && ! collect($titles)->contains(fn ($t) => str_contains(strtolower($title), $t))) {
                        continue;
                    }
                    $out[] = [
                        'source'        => 'greenhouse',
                        'source_job_id' => (string) ($j['id'] ?? ''),
                        'apply_url'     => $j['absolute_url'] ?? '',
                        'is_direct_ats' => true,
                        'ats_provider'  => 'greenhouse',
                        'title'         => $title,
                        'company'       => $board,
                        'country'       => $this->guessCountry($j['location']['name'] ?? ''),
                        'city'          => $j['location']['name'] ?? null,
                        'work_mode'     => null,
                        'salary_min'    => null,
                        'salary_max'    => null,
                        'salary_currency' => null,
                        'description'   => strip_tags($j['content'] ?? ''),
                        'posted_at'     => $j['updated_at'] ?? null,
                    ];
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $out;
    }

    private function guessCountry(string $location): ?string
    {
        $l = strtolower($location);
        return match (true) {
            str_contains($l, 'united kingdom') || str_contains($l, 'london') => 'GB',
            str_contains($l, 'germany') || str_contains($l, 'berlin')        => 'DE',
            str_contains($l, 'australia')                                    => 'AU',
            str_contains($l, 'united states') || str_contains($l, 'usa')     => 'US',
            default => null,
        };
    }
}
