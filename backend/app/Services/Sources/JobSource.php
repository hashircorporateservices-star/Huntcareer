<?php

namespace App\Services\Sources;

/**
 * A job source is anything that can return listings for a set of criteria.
 * Every source normalises to the same array shape so JobSearchService can treat
 * them uniformly:
 *   ['source','source_job_id','apply_url','is_direct_ats','ats_provider','title',
 *    'company','country','city','work_mode','salary_min','salary_max',
 *    'salary_currency','description','posted_at']
 *
 * Only add sources you can read legitimately (official APIs / aggregators).
 * No scraping of boards whose ToS forbid it.
 */
interface JobSource
{
    public function key(): string;

    /** @param array{titles:array,countries:array,remote:bool,onsite:bool} $criteria */
    public function search(array $criteria): array;
}
