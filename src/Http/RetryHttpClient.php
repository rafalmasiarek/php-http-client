<?php

declare(strict_types=1);

namespace rafalmasiarek\HttpClient\Http;

use Psr\Log\LoggerInterface;

/**
 * Decorates an HttpClientInterface with retry-on-transient-failure behavior,
 * so individual callers don't each reimplement their own retry loop.
 *
 * Delegates the actual retry/idempotency policy to a RetryStrategyInterface —
 * this class only owns the attempt loop, the sleep between attempts, which
 * base URI (if more than one was given) each attempt uses, and (when a logger
 * is given) reporting each retry.
 *
 * @package rafalmasiarek\HttpClient\Http
 */
final class RetryHttpClient implements HttpClientInterface
{
    /**
     * @param HttpClientInterface     $client   Underlying transport.
     * @param RetryStrategyInterface  $strategy Retry policy.
     * @param LoggerInterface|null    $logger   Receives an 'http.request.retry' debug entry
     *                                          for every retried attempt. Optional.
     */
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly RetryStrategyInterface $strategy,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * Supports one extra option beyond HttpClientInterface::request(): 'base_uri'
     * (string, or list<string>). When a list is given, $url is treated as a path
     * appended to whichever base the current attempt uses — the first attempt uses
     * index 0, each retry advances to the next base (clamped to the last one once
     * retries outnumber the list), so a failing host doesn't get retried against
     * itself when an alternate is available.
     *
     * @param  string               $method
     * @param  string               $url
     * @param  array<string, mixed> $options
     * @return HttpResponseInterface The first response the strategy stops retrying on —
     *                       may itself be an error/non-2xx response; this method
     *                       never throws for a failure the strategy declines to retry.
     */
    public function request(string $method, string $url, array $options = []): HttpResponseInterface
    {
        $baseUris = $this->normalizeBaseUris($options['base_uri'] ?? null);
        unset($options['base_uri']);

        $attempt = 1;

        while (true) {
            $targetUrl = $baseUris !== null ? $this->buildUrlForAttempt($baseUris, $url, $attempt) : $url;
            $response = $this->client->request($method, $targetUrl, $options);

            if (!$this->strategy->shouldRetry($method, $response, $attempt)) {
                return $response;
            }

            $delayMs = $this->strategy->getDelayMilliseconds($response, $attempt);

            $this->logger?->debug('http.request.retry', [
                'method'   => $method,
                'url'      => $targetUrl,
                'attempt'  => $attempt,
                'status'   => $response->getStatusCode(),
                'error'    => $response->getError(),
                'delay_ms' => $delayMs,
            ]);

            if ($delayMs > 0) {
                \usleep($delayMs * 1000);
            }

            $attempt++;
        }
    }

    /**
     * @param  mixed $baseUri
     * @return list<string>|null Null when absent/empty — request() then uses $url as-is every attempt.
     */
    private function normalizeBaseUris(mixed $baseUri): ?array
    {
        if ($baseUri === null) {
            return null;
        }
        if (\is_string($baseUri) && $baseUri !== '') {
            return [$baseUri];
        }
        if (\is_array($baseUri) && $baseUri !== []) {
            return \array_values(\array_map('strval', $baseUri));
        }
        return null;
    }

    /**
     * @param  list<string> $baseUris
     * @param  string       $path
     * @param  int          $attempt 1-based.
     * @return string
     */
    private function buildUrlForAttempt(array $baseUris, string $path, int $attempt): string
    {
        $index = \min($attempt - 1, \count($baseUris) - 1);

        return \rtrim($baseUris[$index], '/') . '/' . \ltrim($path, '/');
    }
}
