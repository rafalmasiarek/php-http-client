<?php

declare(strict_types=1);

namespace rafalmasiarek\HttpClient\Http;

/**
 * Decorates an HttpClientInterface with a token-bucket rate limit.
 *
 * In-process only — bounds one PHP process's rate, not a ceiling shared
 * across workers/servers. request() blocks until a token is free; no
 * "429, try later" path, since this is for self-imposed outbound pacing.
 *
 * @package rafalmasiarek\HttpClient\Http
 */
final class ThrottlingHttpClient implements HttpClientInterface
{
    /** @var float Current token count, replenished lazily on each request()/wait check. */
    private float $tokens;

    /** @var float microtime(true) of the last refill computation. */
    private float $lastRefill;

    /**
     * @param HttpClientInterface $client         Underlying transport.
     * @param float               $maxTokens      Bucket capacity — the largest burst allowed.
     * @param float               $refillPerSecond Tokens added per second (the steady-state rate).
     */
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly float $maxTokens,
        private readonly float $refillPerSecond,
    ) {
        $this->tokens = $maxTokens;
        $this->lastRefill = \microtime(true);
    }

    /**
     * @param  string               $method
     * @param  string               $url
     * @param  array<string, mixed> $options
     * @return HttpResponseInterface
     */
    public function request(string $method, string $url, array $options = []): HttpResponseInterface
    {
        $this->waitForToken();
        return $this->client->request($method, $url, $options);
    }

    /**
     * @return void
     */
    private function waitForToken(): void
    {
        $this->refill();

        while ($this->tokens < 1.0) {
            \usleep(10_000);
            $this->refill();
        }

        $this->tokens -= 1.0;
    }

    /**
     * @return void
     */
    private function refill(): void
    {
        $now = \microtime(true);
        $elapsed = $now - $this->lastRefill;

        $this->tokens = \min($this->maxTokens, $this->tokens + $elapsed * $this->refillPerSecond);
        $this->lastRefill = $now;
    }
}
