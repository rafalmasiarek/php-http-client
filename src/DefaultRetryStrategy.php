<?php

declare(strict_types=1);

namespace rafalmasiarek\HttpClient\Http;

/**
 * Retries transport-level failures and a conservative set of transient HTTP
 * statuses, only for methods considered safe to replay automatically.
 *
 * Never retries a non-idempotent method (POST, PATCH, ...) — a caller that
 * knows its request body is safe to resend should retry it explicitly rather
 * than rely on this default.
 *
 * @package rafalmasiarek\HttpClient\Http
 */
final class DefaultRetryStrategy implements RetryStrategyInterface
{
    /** @var list<int> HTTP statuses treated as transient for an idempotent method. */
    private const RETRYABLE_STATUS_CODES = [408, 425, 429, 500, 502, 503, 504];

    /** @var list<string> Methods retried automatically; POST/PATCH are not. */
    private const IDEMPOTENT_METHODS = ['GET', 'HEAD', 'OPTIONS', 'PUT', 'DELETE'];

    /**
     * @param int $maxRetries  Attempts after the first, e.g. 3 means up to 4 total tries.
     * @param int $baseDelayMs Base for exponential backoff (doubles each attempt).
     * @param int $maxDelayMs  Upper bound on any single computed delay, including Retry-After.
     * @param int $jitterMs    Random extra delay added on top, to avoid synchronized retry storms.
     */
    public function __construct(
        private readonly int $maxRetries = 3,
        private readonly int $baseDelayMs = 1000,
        private readonly int $maxDelayMs = 30_000,
        private readonly int $jitterMs = 250,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function shouldRetry(string $method, HttpResponseInterface $response, int $attempt): bool
    {
        if ($attempt > $this->maxRetries) {
            return false;
        }

        if ($response->getError() !== null) {
            return true;
        }

        if (!\in_array(\strtoupper($method), self::IDEMPOTENT_METHODS, true)) {
            return false;
        }

        return \in_array($response->getStatusCode(), self::RETRYABLE_STATUS_CODES, true);
    }

    /**
     * @inheritDoc
     */
    public function getDelayMilliseconds(HttpResponseInterface $response, int $attempt): int
    {
        $retryAfterSec = $this->parseRetryAfter($response->getHeaderLine('Retry-After'));
        if ($retryAfterSec !== null) {
            return \min($this->maxDelayMs, $retryAfterSec * 1000);
        }

        $delay = \min($this->maxDelayMs, (2 ** \max(0, $attempt - 1)) * $this->baseDelayMs);

        return $delay + \random_int(0, $this->jitterMs);
    }

    /**
     * Parses a Retry-After header value (delay-seconds or an HTTP-date) into seconds from now.
     *
     * @param  string $value
     * @return int|null Null when absent or unparseable.
     */
    private function parseRetryAfter(string $value): ?int
    {
        if ($value === '') {
            return null;
        }

        if (\ctype_digit($value)) {
            return (int) $value;
        }

        $ts = \strtotime($value);
        if ($ts === false) {
            return null;
        }

        return \max(0, $ts - \time());
    }
}
