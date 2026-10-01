<?php

declare(strict_types=1);

namespace rafalmasiarek\HttpClient\Http;

/**
 * Lazy HTTP response. request() returns one immediately; the first accessor
 * call below drives the transfer to completion. Firing several request()
 * calls before reading any response runs them concurrently.
 *
 * No header-only wait, no chunked streaming — one accessor call resolves
 * everything. Transport failures are reported via getError(), not exceptions.
 *
 * @package rafalmasiarek\HttpClient\Http
 */
interface HttpResponseInterface
{
    /**
     * @return int HTTP status code. 0 when the request never reached the server.
     */
    public function getStatusCode(): int;

    /**
     * @return array<string, list<string>> Lowercase header names, all values per
     *                                      name preserved (e.g. multiple Set-Cookie).
     */
    public function getHeaders(): array;

    /**
     * @param  string $name Header name, case-insensitive.
     * @return string|null The first value, or null when absent.
     */
    public function getHeader(string $name): ?string;

    /**
     * @param  string $name Header name, case-insensitive.
     * @return string All values, comma-joined (PSR-7 convention), or empty when absent.
     */
    public function getHeaderLine(string $name): string;

    /**
     * @return string Response body.
     */
    public function getContent(): string;

    /**
     * @return mixed Body decoded as JSON, or null when it isn't valid JSON.
     */
    public function json(): mixed;

    /**
     * @return bool True when the request completed with no transport error and a 2xx status.
     */
    public function isSuccessful(): bool;

    /**
     * @return string|null Transport-level error (DNS failure, connection refused, timeout,
     *                      a block_private_network refusal, ...), or null when none occurred.
     *                      A non-2xx HTTP status that the server actually returned is not an
     *                      error by itself — check getStatusCode() for that.
     */
    public function getError(): ?string;

    /**
     * Non-blocking transport metadata — never drives the transfer forward, so it's
     * safe to call at any time, including before this response has been read.
     *
     * @param  string|null $key When given, returns just that key's value (or null if absent/not
     *                          yet known); when null, returns the full info array.
     * @return mixed
     */
    public function getInfo(?string $key = null): mixed;

    /**
     * Aborts the request if it hasn't finished yet. A no-op once the response is complete.
     * After this, getStatusCode() is 0 and getError() reports the cancellation.
     *
     * @return void
     */
    public function cancel(): void;
}
