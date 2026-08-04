<?php

namespace App\Services;

/**
 * Best-effort detection of whether a job offers visa sponsorship / work permit.
 * Fast keyword pass on the description + title. Returns:
 *   true  = clear sponsorship signal
 *   false = clear NO-sponsorship signal
 *   null  = unknown (most jobs) — treated as "not confirmed" by filters
 *
 * This is a signal, NOT a guarantee. Boards rarely state sponsorship explicitly,
 * so a null/false result doesn't prove the employer won't sponsor — it just isn't
 * stated. Filters that require sponsorship match only the confirmed-true set.
 */
class VisaSponsorshipDetector
{
    private array $positive = [
        'visa sponsorship', 'sponsorship available', 'we sponsor', 'will sponsor',
        'sponsorship provided', 'work permit provided', 'work permit sponsorship',
        'skilled worker visa', 'tier 2', 'certificate of sponsorship', 'relocation and visa',
        'visa support', 'eligible for sponsorship', 'sponsor work visa', 'employment pass',
        'blue card', 'relocation package', 'work authorization sponsorship',
    ];

    private array $negative = [
        'no visa sponsorship', 'not able to sponsor', 'cannot sponsor', 'unable to sponsor',
        'no sponsorship', 'sponsorship is not available', 'must have the right to work',
        'must already have the right to work', 'no relocation', 'sponsorship not provided',
    ];

    public function detect(?string $text): ?bool
    {
        if (! $text) {
            return null;
        }
        $t = strtolower($text);

        foreach ($this->negative as $n) {
            if (str_contains($t, $n)) {
                return false;
            }
        }
        foreach ($this->positive as $p) {
            if (str_contains($t, $p)) {
                return true;
            }
        }
        return null; // unknown
    }
}
