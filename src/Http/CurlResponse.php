<?php

declare(strict_types=1);

namespace rafalmasiarek\HttpClient\Http;

/**
 * Lazy HttpResponseInterface returned by CurlHttpClient. Registers its curl
 * handle on the client's shared curl_multi and returns immediately; the
 * first accessor call pumps that shared handle until this response's hop is
 * done, finishing any other pending responses along the way too.
 *
 * @package rafalmasiarek\HttpClient\Http
 */
final class CurlResponse implements HttpResponseInterface
{
    /** @var \CurlHandle|null Current hop's handle. Null once this hop has finished (closed) or was blocked before starting. */
    private ?\CurlHandle $handle;

    /** @var bool Whether the current hop (not necessarily the final one, if redirects remain) has finished. */
    private bool $hopDone = false;

    /** @var bool Whether this response is fully resolved (no more redirects to follow). */
    private bool $done = false;

    private bool $canceled = false;
    private int $statusCode = 0;

    /** @var array<string, list<string>> */
    private array $headers = [];
    private string $body = '';
    private ?string $error = null;
    private ?TransportErrorKind $errorKind = null;

    /** @var array<string, mixed> Snapshot of curl_getinfo() from the most recently finished hop. */
    private array $lastInfo = [];

    private int $redirectCount = 0;
    private string $finalUrl;

    /**
     * @param CurlHttpClient      $client          Owner — drives the shared curl_multi handle
     *                                              and builds each hop's curl handle.
     * @param string              $method          HTTP method. May be downgraded to GET on a
     *                                              301/302/303 redirect of a non-GET/HEAD request.
     * @param string              $url             Request URL (reassigned to each redirect's target).
     * @param array<string,mixed> $options         Request options, as passed to CurlHttpClient::request().
     * @param bool                $followRedirects Whether to transparently follow 3xx responses.
     */
    public function __construct(
        private readonly CurlHttpClient $client,
        private string $method,
        private string $url,
        private array $options,
        private readonly bool $followRedirects,
    ) {
        $this->finalUrl = $url;
        $this->handle = $this->client->buildHandle($this->method, $this->url, $this->options, $this);

        if ($this->handle === null) {
            // buildHandle() already called _completeBlocked() when this happens.
            return;
        }

        $this->client->registerHandle($this->handle, $this);
    }

    public function getStatusCode(): int
    {
        $this->ensureComplete();
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        $this->ensureComplete();
        return $this->headers;
    }

    public function getHeader(string $name): ?string
    {
        $this->ensureComplete();
        return $this->headers[\strtolower($name)][0] ?? null;
    }

    public function getHeaderLine(string $name): string
    {
        $this->ensureComplete();
        return \implode(', ', $this->headers[\strtolower($name)] ?? []);
    }

    public function getContent(): string
    {
        $this->ensureComplete();
        return $this->body;
    }

    public function json(): mixed
    {
        return \json_decode($this->getContent(), true);
    }

    public function isSuccessful(): bool
    {
        return $this->getError() === null && $this->getStatusCode() >= 200 && $this->getStatusCode() < 300;
    }

    public function getError(): ?string
    {
        $this->ensureComplete();
        return $this->error;
    }

    public function getErrorKind(): ?TransportErrorKind
    {
        $this->ensureComplete();
        return $this->errorKind;
    }

    public function getInfo(?string $key = null): mixed
    {
        $info = $this->hopDone || $this->done
            ? $this->lastInfo
            : ($this->handle !== null ? \curl_getinfo($this->handle) : []);

        $info['redirect_count'] = $this->redirectCount;
        $info['url'] = $this->done ? $this->finalUrl : ($info['url'] ?? $this->url);
        $info['canceled'] = $this->canceled;

        return $key === null ? $info : ($info[$key] ?? null);
    }

    public function cancel(): void
    {
        if ($this->done) {
            return;
        }

        if ($this->handle !== null) {
            $this->client->forget($this->handle);
            \curl_close($this->handle);
            $this->handle = null;
        }

        $this->canceled = true;
        $this->done = true;
        $this->hopDone = true;
        $this->statusCode = 0;
        $this->error = 'Request canceled.';
        $this->errorKind = TransportErrorKind::Canceled;
    }

    /**
     * @internal Called only by CurlHttpClient's CURLOPT_HEADERFUNCTION closure.
     */
    public function _onHeaderLine(string $line): void
    {
        if (\str_starts_with($line, 'HTTP/')) {
            $this->headers = [];
            return;
        }

        $parts = \explode(':', $line, 2);
        if (\count($parts) === 2) {
            $this->headers[\strtolower(\trim($parts[0]))][] = \trim($parts[1]);
        }
    }

    /**
     * @internal Called only by CurlHttpClient's CURLOPT_WRITEFUNCTION closure.
     */
    public function _onBodyChunk(string $chunk): void
    {
        $this->body .= $chunk;
    }

    /**
     * @internal Called only by CurlHttpClient::buildHandle() when a block_private_network
     * check refuses the target before a handle is even created.
     */
    public function _completeBlocked(string $host): void
    {
        $this->handle = null;
        $this->hopDone = true;
        $this->done = true;
        $this->statusCode = 0;
        $this->error = "Blocked request to \"{$host}\": target resolves to a private or reserved network address.";
        $this->errorKind = TransportErrorKind::Blocked;
    }

    /**
     * @internal Called only by CurlHttpClient::pumpUntilDone(). Handle is already closed by then.
     *
     * @param array<string, mixed> $info curl_getinfo() snapshot taken before close.
     */
    public function _hopFinished(int $statusCode, ?string $error, array $info, ?TransportErrorKind $errorKind): void
    {
        $this->statusCode = $statusCode;
        $this->error = $error;
        $this->errorKind = $errorKind;
        $this->lastInfo = $info;
        $this->hopDone = true;
        $this->handle = null;
    }

    /**
     * @internal Exposes the current hop's handle to CurlHttpClient::pumpUntilDone().
     */
    public function _handle(): ?\CurlHandle
    {
        return $this->handle;
    }

    /**
     * Drives the shared curl_multi handle (via the owning client) until this
     * response's current hop finishes, then follows any redirect by rebuilding
     * and re-registering a new handle, repeating until a final response is reached.
     */
    private function ensureComplete(): void
    {
        while (!$this->done) {
            if (!$this->hopDone) {
                $this->client->pumpUntilDone($this);
            }

            if ($this->canceled) {
                return;
            }

            if (!$this->maybeFollowRedirect()) {
                $this->done = true;
            }
        }
    }

    /**
     * @return bool True when a redirect was followed (caller should keep looping);
     *              false when this response is final.
     */
    private function maybeFollowRedirect(): bool
    {
        if (!$this->followRedirects || $this->redirectCount >= CurlHttpClient::MAX_REDIRECTS) {
            return false;
        }

        if (!\in_array($this->statusCode, [301, 302, 303, 307, 308], true)) {
            return false;
        }

        $location = $this->headers['location'][0] ?? null;
        if ($location === null || $location === '') {
            return false;
        }

        $nextUrl = $this->client->resolveRedirectUrl($this->url, $location);

        if ($this->client->isCrossOrigin($this->url, $nextUrl)) {
            $this->options['headers'] = $this->client->stripCredentialHeaders((array) ($this->options['headers'] ?? []));
            unset($this->options['auth_basic']);
        }

        // 301/302/303 historically downgrade a non-GET/HEAD request to GET and drop its
        // body (matches curl's own default FOLLOWLOCATION behavior, and browsers).
        // 307/308 preserve method and body as-is.
        if (
            \in_array($this->statusCode, [301, 302, 303], true)
            && !\in_array($this->method, ['GET', 'HEAD'], true)
        ) {
            $this->method = 'GET';
            unset($this->options['body'], $this->options['json']);
        }

        $this->url = $nextUrl;
        $this->finalUrl = $nextUrl;
        $this->redirectCount++;
        $this->headers = [];
        $this->body = '';
        $this->hopDone = false;

        $this->handle = $this->client->buildHandle($this->method, $this->url, $this->options, $this);

        if ($this->handle === null) {
            // _completeBlocked() already set $this->done = true.
            return false;
        }

        $this->client->registerHandle($this->handle, $this);

        return true;
    }
}
