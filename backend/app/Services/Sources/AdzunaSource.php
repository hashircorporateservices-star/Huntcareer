<?php

namespace App\Services\Sources;

use Illuminate\Support\Facades\Http;

/**
 * Adzuna aggregates listings from many boards across GB, IE-adjacent, AU, DE, US,
 * and more via one official API. This is the workhorse "all sources" adapter.
 * Docs: https://developer.adzuna.com/
 */
class AdzunaSource implements JobSource
{
    // Our ISO alpha-2 -> Adzuna country path code.
    private array $countryMap = [
        'GB' => 'gb', 'AU' => 'au', 'DE' => 'de', 'US' => 'us',
        'NZ' => 'nz', 'IE' => 'gb', 'MT' => 'gb', // MT/IE fall back to gb index
    ];

    public function key(): string { return 'adzuna'; }

    public function search(array $criteria): array
    {
        $cfg = config('services.adzuna');
        if (empty($cfg['app_id']) || empty($cfg['app_key'])) {
            return [];
        }

        $out = [];
        foreach ($criteria['countries'] ?: ['GB'] as $iso) {
            $cc = $this->countryMap[$iso] ?? 'gb';
            $what = implode(' ', $criteria['titles']);

            try {
                $res = Http::timeout(30)->get(
                    "https://api.adzuna.com/v1/api/jobs/{$cc}/search/1",
                    [
                        'app_id'         => $cfg['app_id'],
                        'app_key'        => $cfg['app_key'],
                        'what_or'        => $what,
                        'results_per_page' => 50,
                        'max_days_old'   => 14,
                        'content-type'   => 'application/json',
                    ]
                )->throw()->json();

                foreach ($res['results'] ?? [] as $j) {
                    $out[] = [
                        'source'          => 'adzuna',
                        'source_job_id'   => (string) ($j['id'] ?? ''),
                        'apply_url'       => $j['redirect_url'] ?? '',
                        'is_direct_ats'   => false,
                        'ats_provider'    => null,
                        'title'           => $j['title'] ?? '',
                        'company'         => $j['company']['display_name'] ?? null,
                        'country'         => $iso,
                        'city'            => $j['location']['display_name'] ?? null,
                        'work_mode'       => null,
                        'salary_min'      => isset($j['salary_min']) ? (int) $j['salary_min'] : null,
                        'salary_max'      => isset($j['salary_max']) ? (int) $j['salary_max'] : null,
                        'salary_currency' => null,
                        'description'     => $j['description'] ?? null,
                        'posted_at'       => $j['created'] ?? null,
                    ];
                }
            } catch (\Throwable $e) {
                report($e);   // one country failing shouldn't kill the whole search
            }
        }

        return $out;
    }
}
