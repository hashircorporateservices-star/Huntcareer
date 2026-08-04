<?php

namespace App\Services;

use App\Models\Job;
use App\Models\User;
use Google\Client as GoogleClient;
use Google\Service\Gmail;
use Illuminate\Support\Facades\Http;

/**
 * Turns the job-alert emails already in YOUR inbox into scored, tailorable jobs.
 *
 * This is the ToS-safe reading of "1000 jobs from my email": it reads your own
 * Gmail (read-only scope) and never sends anything. Alert formats vary wildly,
 * so each email is passed through the AI to extract structured listings.
 *
 * Requires: composer require google/apiclient
 * OAuth scope: https://www.googleapis.com/auth/gmail.readonly
 * The user's Google refresh token is stored (encrypted) in the settings table.
 */
class GmailJobAlertService
{
    /** Senders whose emails are treated as job alerts. */
    protected array $alertSenders = [
        'jobalerts-noreply@linkedin.com',
        'noreply@indeed.com',
        'noreply@seek.com.au',
        'no-reply@stepstone.de',
        'alerts@irishjobs.ie',
    ];

    public function __construct(protected JobMatchingService $matcher) {}

    /**
     * Fetch recent alert emails and ingest any new jobs.
     *
     * @return int number of new jobs created
     */
    public function ingestForUser(User $user, int $lookbackDays = 3): int
    {
        $gmail = new Gmail($this->clientFor($user));

        $query = sprintf(
            '(%s) newer_than:%dd',
            collect($this->alertSenders)->map(fn ($s) => "from:$s")->implode(' OR '),
            $lookbackDays
        );

        $messages = $gmail->users_messages->listUsersMessages('me', ['q' => $query, 'maxResults' => 100]);
        $created = 0;

        foreach ($messages->getMessages() ?? [] as $ref) {
            $message = $gmail->users_messages->get('me', $ref->getId(), ['format' => 'full']);
            $body    = $this->plainBody($message);

            foreach ($this->extractListings($body) as $listing) {
                if ($this->createJob($listing)) {
                    $created++;
                }
            }
        }

        return $created;
    }

    /** Ask the model to pull structured listings out of one alert email. */
    protected function extractListings(string $emailText): array
    {
        try {
            $prompt = "Extract every job listing from this job-alert email. Return ONLY a JSON array; "
                . "each item: {\"title\":\"\",\"company\":\"\",\"country\":\"<ISO alpha-2>\",\"city\":\"\","
                . "\"apply_url\":\"\"}. If none, return []. Email:\n\n" . mb_substr($emailText, 0, 8000);

            $response = Http::withToken(config('services.ai.key'))
                ->timeout(40)
                ->post(config('services.ai.endpoint'), [
                    'model'      => config('services.ai.model'),
                    'messages'   => [['role' => 'user', 'content' => $prompt]],
                    'max_tokens' => 1500,
                ])->throw()->json();

            $raw = data_get($response, 'choices.0.message.content')
                ?? data_get($response, 'content.0.text', '[]');

            return json_decode(trim(preg_replace('/```(json)?/', '', $raw)), true) ?: [];
        } catch (\Throwable $e) {
            report($e);
            return [];
        }
    }

    protected function createJob(array $listing): bool
    {
        if (empty($listing['apply_url']) || empty($listing['title'])) {
            return false;
        }

        // Skip anything not in a target country.
        if (! array_key_exists($listing['country'] ?? '', config('copilot.countries'))) {
            return false;
        }

        // Dedupe on the apply URL.
        if (Job::where('apply_url', $listing['apply_url'])->exists()) {
            return false;
        }

        Job::create([
            'source'      => 'email_alert',
            'apply_url'   => $listing['apply_url'],
            'title'       => $listing['title'],
            'role_family' => $this->normaliseRole($listing['title']),
            'country'     => $listing['country'],
            'city'        => $listing['city'] ?? null,
            'fetched_at'  => now(),
        ]);

        return true;
    }

    /** Map a raw title onto a role_family using the aliases in config (optional). */
    protected function normaliseRole(string $title): ?string
    {
        $t = strtolower($title);
        // role_families is optional now (matching is title-based); default to [] so
        // ingestion never depends on a config key that may be absent.
        foreach (config('copilot.role_families', []) as $family => $meta) {
            foreach ($meta['aliases'] ?? [] as $alias) {
                if (str_contains($t, $alias)) {
                    return $family;
                }
            }
        }
        return null;
    }

    protected function plainBody(Gmail\Message $message): string
    {
        $parts = $message->getPayload()->getParts() ?: [$message->getPayload()];
        $text = '';
        foreach ($parts as $part) {
            $data = $part->getBody()?->getData();
            if ($data) {
                $text .= base64_decode(strtr($data, '-_', '+/'));
            }
        }
        return strip_tags($text);
    }

    protected function clientFor(User $user): GoogleClient
    {
        $client = new GoogleClient();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->addScope(Gmail::GMAIL_READONLY);
        $client->refreshToken(
            $user->settings()->where('key', 'google_refresh_token')->first()?->value
        );
        return $client;
    }
}
