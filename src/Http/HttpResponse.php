<?php

declare(strict_types=1);

namespace rafalmasiarek\HttpClient\Http;

/**
 * Eager (already-resolved) HttpResponseInterface implementation.
 *
 * Useful for constructing a synthetic response (CachingHttpClient serving a
 * cached entry, a test double, ...) where there's no real lazy transfer to
 * drive — every accessor just returns the value given at construction time.
 *
 * CurlHttpClient does not return this class for a real network request; it
 * returns CurlResponse, which is genuinely lazy. See HttpResponseInterface.
 *
 * @package rafalmasiarek\HttpClient\Http
 */
final readonly class HttpResponse implements HttpResponseInterface
{
    /**
     * @param int                        $statusCode HTTP status code. 0 when the request never reached the server.
     * @param array<string, list<string>> $headers   Response headers, lowercase names, all values per
     *                                                name preserved (e.g. multiple Set-Cookie headers).
     * @param string                     $body       Response body.
     * @param string|null                $error      curl error message, when the request failed at the transport level.
     * @param HttpTransportDebug|null    $debug      Timing/target info from curl_getinfo(), for diagnosing
     *                                                connectivity failures. Not populated by every
     *                                                HttpClientInterface implementation.
     */
    public function __construct(
        public int $statusCode,
        public array $headers,
        public string $body,
        public ?string $error = null,
        public ?HttpTransportDebug $debug = null,
    ) {
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader(string $name): ?string
    {
        return $this->headers[\strtolower($name)][0] ?? null;
    }

    public function getHeaderLine(string $name): string
    {
        return \implode(', ', $this->headers[\strtolower($name)] ?? []);
    }

    public function getContent(): string
    {
        return $this->body;
    }

    public function json(): mixed
    {
        return \json_decode($this->body, true);
    }

    public function isSuccessful(): bool
    {
        return $this->error === null && $this->statusCode >= 200 && $this->statusCode < 300;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function getInfo(?string $key = null): mixed
    {
        $info = $this->debug?->toArray() ?? [];
        $info['canceled'] = false;

        return $key === null ? $info : ($info[$key] ?? null);
    }

    public function cancel(): void
    {
        // Already resolved — nothing to cancel.
    }
}
