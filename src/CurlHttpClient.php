<?php

declare(strict_types=1);

namespace rafalmasiarek\HttpClient\Http;

use rafalmasiarek\HttpClient\Dns\DnsQueryException;
use rafalmasiarek\HttpClient\Dns\DnsResolverInterface;

/**
 * curl-based HttpClientInterface implementation. Resolves hostnames via an
 * injected DnsResolverInterface and pins the result with CURLOPT_RESOLVE
 * instead of curl's own DNS. A failed resolve falls back to curl's own
 * resolution, except under 'block_private_network' (see buildHandle()),
 * where an unconfirmed target is treated as unsafe.
 *
 * request() returns a lazy CurlResponse (see HttpResponseInterface). Every
 * response from one client instance shares one curl_multi handle, created
 * lazily; CurlResponse drives it on first access.
 *
 * Redirects are followed by CurlResponse itself, one hop at a time, so
 * credential headers get stripped and the private-network check re-run on
 * every hop, not just the first.
 *
 * @package rafalmasiarek\HttpClient\Http
 */
final class CurlHttpClient implements HttpClientInterface
{
    /** @var float Default total request timeout, in seconds, when 'timeout' is not in $options. */
    private const DEFAULT_TIMEOUT = 10.0;

    /** @var int Maximum number of redirect hops followed before giving up and returning the last redirect response as-is. */
    public const MAX_REDIRECTS = 20;

    /** @var list<string> Header names (lowercase) never forwarded to a different origin on redirect. */
    private const CREDENTIAL_HEADERS = ['authorization', 'proxy-authorization', 'cookie'];

    /** @var \CurlMultiHandle|null Lazily created; shared by every CurlResponse this client produces. */
    private ?\CurlMultiHandle $multi = null;

    /** @var array<int, CurlResponse> In-flight responses, keyed by spl_object_id() of their current curl handle. */
    private array $pending = [];

    /**
     * @param DnsResolverInterface $dns Resolver used to pin each request's target IP.
     */
    public function __construct(
        private readonly DnsResolverInterface $dns,
    ) {
    }

    /**
     * Extra $options beyond HttpClientInterface::request(): 'follow_redirects' (bool,
     * default true); 'block_private_network' (bool, default false), re-checked on every
     * redirect hop; 'max_connect_duration' (float seconds) — connect phase only, separate
     * from 'timeout'; 'on_progress' (callable(int $dlNow, int $dlSize, array $info): void),
     * called periodically — a thrown exception aborts the transfer (see getError()).
     *
     * @param  string               $method
     * @param  string               $url
     * @param  array<string, mixed> $options
     * @return HttpResponseInterface
     */
    public function request(string $method, string $url, array $options = []): HttpResponseInterface
    {
        $url = $this->applyQuery($url, (array) ($options['query'] ?? []));
        unset($options['query']);

        $followRedirects = (bool) ($options['follow_redirects'] ?? true);
        unset($options['follow_redirects']);

        return new CurlResponse($this, \strtoupper($method), $url, $options, $followRedirects);
    }

    /**
     * Builds one hop's curl handle, wiring its header/body callbacks to write
     * directly into $forResponse. Returns null (after marking $forResponse done
     * via _completeBlocked()) when block_private_network refuses the target —
     * there's no handle to register in that case.
     *
     * @internal Called only by CurlResponse (initial request and each redirect hop).
     *
     * @param  string               $method
     * @param  string               $url
     * @param  array<string, mixed> $options
     * @param  CurlResponse         $forResponse
     * @return \CurlHandle|null
     */
    public function buildHandle(string $method, string $url, array $options, CurlResponse $forResponse): ?\CurlHandle
    {
        [$requestBody, $headers] = $this->resolveBody($options);

        $ch = \curl_init();

        \curl_setopt_array($ch, [
            \CURLOPT_URL            => $url,
            \CURLOPT_CUSTOMREQUEST  => $method,
            \CURLOPT_TIMEOUT        => (float) ($options['timeout'] ?? self::DEFAULT_TIMEOUT),
            \CURLOPT_SSL_VERIFYPEER => (bool) ($options['verify_peer'] ?? true),
            \CURLOPT_HTTPHEADER     => $this->formatHeaders($headers),
        ]);

        if (isset($options['max_connect_duration'])) {
            \curl_setopt($ch, \CURLOPT_CONNECTTIMEOUT_MS, (int) ((float) $options['max_connect_duration'] * 1000));
        }

        if ($method === 'HEAD') {
            // Without this, curl still expects a body sized per Content-Length a GET
            // to the same URL would return, and reports a transport error when the
            // server correctly sends none — even though the headers we actually want
            // (e.g. Last-Modified) arrive fine.
            \curl_setopt($ch, \CURLOPT_NOBODY, true);
        }

        if ($requestBody !== null) {
            \curl_setopt($ch, \CURLOPT_POSTFIELDS, $requestBody);
        }

        if (isset($options['auth_basic'])) {
            $auth = $options['auth_basic'];
            \curl_setopt($ch, \CURLOPT_USERPWD, \is_array($auth) ? \implode(':', $auth) : (string) $auth);
        }

        $this->applyTlsOptions($ch, $options);

        $host = \parse_url($url)['host'] ?? null;
        $resolvedIp = $this->resolveHostIp($host);

        if ((bool) ($options['block_private_network'] ?? false) && $host !== null) {
            $ipToCheck = $resolvedIp ?? $host;
            if ($this->isPrivateOrReservedIp($ipToCheck)) {
                \curl_close($ch);
                $forResponse->_completeBlocked($host);
                return null;
            }
        }

        if ($resolvedIp !== null && $host !== null && \filter_var($host, \FILTER_VALIDATE_IP) === false) {
            $resolve = $this->buildResolveOption($url, $host, $resolvedIp);
            if ($resolve !== null) {
                \curl_setopt($ch, \CURLOPT_RESOLVE, [$resolve]);
            }
        }

        \curl_setopt($ch, \CURLOPT_HEADERFUNCTION, static function ($ch, string $line) use ($forResponse): int {
            $forResponse->_onHeaderLine($line);
            return \strlen($line);
        });

        \curl_setopt($ch, \CURLOPT_WRITEFUNCTION, static function ($ch, string $chunk) use ($forResponse): int {
            $forResponse->_onBodyChunk($chunk);
            return \strlen($chunk);
        });

        if (isset($options['on_progress']) && \is_callable($options['on_progress'])) {
            $onProgress = $options['on_progress'];
            \curl_setopt($ch, \CURLOPT_NOPROGRESS, false);
            \curl_setopt($ch, \CURLOPT_XFERINFOFUNCTION, static function ($ch, int $dlTotal, int $dlNow, int $ulTotal, int $ulNow) use ($onProgress): int {
                try {
                    $onProgress($dlNow, $dlTotal > 0 ? $dlTotal : -1, \curl_getinfo($ch));
                } catch (\Throwable) {
                    return 1; // non-zero aborts the transfer; surfaced via getError() as a CURLE_ABORTED_BY_CALLBACK message.
                }
                return 0;
            });
        }

        return $ch;
    }

    /**
     * @internal Called only by CurlResponse, to add a freshly built handle to this
     * client's shared curl_multi handle.
     */
    public function registerHandle(\CurlHandle $handle, CurlResponse $response): void
    {
        $this->pending[\spl_object_id($handle)] = $response;
        \curl_multi_add_handle($this->multi(), $handle);
    }

    /**
     * @internal Called only by CurlResponse::cancel(), to remove a handle before
     * it has finished.
     */
    public function forget(\CurlHandle $handle): void
    {
        $id = \spl_object_id($handle);
        if (isset($this->pending[$id])) {
            \curl_multi_remove_handle($this->multi(), $handle);
            unset($this->pending[$id]);
        }
    }

    /**
     * Drives the shared curl_multi handle until $target's current hop finishes.
     * Every other pending handle advances in the same loop — that's the concurrency.
     *
     * @internal Called only by CurlResponse::ensureComplete().
     */
    public function pumpUntilDone(CurlResponse $target): void
    {
        $targetHandle = $target->_handle();
        if ($targetHandle === null) {
            return;
        }

        $targetId = \spl_object_id($targetHandle);
        if (!isset($this->pending[$targetId])) {
            return;
        }

        $multi = $this->multi();

        do {
            $status = \curl_multi_exec($multi, $running);

            while (($info = \curl_multi_info_read($multi)) !== false) {
                $this->finishOne($multi, $info);
            }

            if (!isset($this->pending[$targetId])) {
                return;
            }

            if ($running > 0) {
                \curl_multi_select($multi, 1.0);
            }
        } while ($running > 0 && $status === \CURLM_OK);
    }

    /**
     * @param \CurlMultiHandle           $multi
     * @param array{msg: int, result: int, handle: \CurlHandle} $info One entry from curl_multi_info_read().
     */
    private function finishOne(\CurlMultiHandle $multi, array $info): void
    {
        $ch = $info['handle'];
        $id = \spl_object_id($ch);
        $response = $this->pending[$id] ?? null;
        unset($this->pending[$id]);
        \curl_multi_remove_handle($multi, $ch);

        if ($response === null) {
            \curl_close($ch);
            return;
        }

        $errno = (int) $info['result'];
        $error = $errno !== 0 ? \curl_strerror($errno) : null;
        $curlInfo = \curl_getinfo($ch);
        $statusCode = $errno === 0 ? (int) ($curlInfo['http_code'] ?? 0) : 0;

        \curl_close($ch);

        $response->_hopFinished($statusCode, $error, $curlInfo);
    }

    /**
     * @return \CurlMultiHandle
     */
    private function multi(): \CurlMultiHandle
    {
        return $this->multi ??= \curl_multi_init();
    }

    /**
     * Resolves the request body and any headers implied by it (e.g. Content-Type for JSON).
     *
     * A 'body' array without any \CURLFile/\CURLStringFile value is form-urlencoded.
     * One containing such a value is passed to curl as-is, which then sends it as
     * multipart/form-data with its own boundary and Content-Type — no header is
     * added here in that case, since ours would conflict with curl's boundary.
     *
     * @param  array<string, mixed> $options
     * @return array{0: string|array<string,mixed>|null, 1: array<string, string|list<string>>}
     */
    private function resolveBody(array $options): array
    {
        $headers = (array) ($options['headers'] ?? []);

        if (isset($options['body'])) {
            $body = $options['body'];

            if (\is_array($body)) {
                if ($this->hasFileParts($body)) {
                    return [$body, $headers];
                }
                $headers['Content-Type'] ??= 'application/x-www-form-urlencoded';
                return [\http_build_query($body), $headers];
            }

            return [(string) $body, $headers];
        }

        if (\array_key_exists('json', $options)) {
            $encoded = \json_encode($options['json']);
            $headers['Content-Type'] ??= 'application/json';
            return [$encoded !== false ? $encoded : null, $headers];
        }

        return [null, $headers];
    }

    /**
     * @param  array<string, mixed> $body
     * @return bool True when any value is a file part (multipart/form-data upload).
     */
    private function hasFileParts(array $body): bool
    {
        foreach ($body as $value) {
            if ($value instanceof \CURLFile || $value instanceof \CURLStringFile) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param  array<string, string|list<string>> $headers
     * @return list<string>
     */
    private function formatHeaders(array $headers): array
    {
        $lines = [];
        foreach ($headers as $name => $value) {
            foreach ((array) $value as $singleValue) {
                $lines[] = "{$name}: {$singleValue}";
            }
        }
        return $lines;
    }

    /**
     * Removes Authorization/Proxy-Authorization/Cookie headers (case-insensitive
     * name match) — called when a redirect crosses origins, so credentials meant
     * for the original host are never sent to wherever it redirected to.
     *
     * @internal Called only by CurlResponse on a cross-origin redirect.
     *
     * @param  array<string, string|list<string>> $headers
     * @return array<string, string|list<string>>
     */
    public function stripCredentialHeaders(array $headers): array
    {
        foreach ($headers as $name => $value) {
            if (\in_array(\strtolower((string) $name), self::CREDENTIAL_HEADERS, true)) {
                unset($headers[$name]);
            }
        }
        return $headers;
    }

    /**
     * Resolves a redirect's Location header against the URL it came from.
     * Handles absolute URLs, protocol-relative ("//host/path"), absolute-path
     * ("/path"), and relative paths (resolved against the base URL's directory,
     * with "." and ".." segments collapsed).
     *
     * @internal Called only by CurlResponse on a redirect.
     *
     * @param  string $baseUrl
     * @param  string $location
     * @return string
     */
    public function resolveRedirectUrl(string $baseUrl, string $location): string
    {
        if (\preg_match('#^[a-z][a-z0-9+.-]*://#i', $location) === 1) {
            return $location;
        }

        $base = \parse_url($baseUrl);
        if ($base === false) {
            return $location;
        }

        $scheme = $base['scheme'] ?? 'http';
        $host = $base['host'] ?? '';
        $port = isset($base['port']) ? ':' . $base['port'] : '';
        $authority = $scheme . '://' . $host . $port;

        if (\str_starts_with($location, '//')) {
            return $scheme . ':' . $location;
        }

        if (\str_starts_with($location, '/')) {
            return $authority . $this->normalizePath($location);
        }

        $basePath = $base['path'] ?? '/';
        $slashPos = \strrpos($basePath, '/');
        $baseDir = $slashPos !== false ? \substr($basePath, 0, $slashPos + 1) : '/';

        return $authority . $this->normalizePath($baseDir . $location);
    }

    /**
     * Collapses "." and ".." path segments.
     *
     * @param  string $path
     * @return string
     */
    private function normalizePath(string $path): string
    {
        $segments = \explode('/', $path);
        $out = [];
        foreach ($segments as $segment) {
            if ($segment === '.' || $segment === '') {
                continue;
            }
            if ($segment === '..') {
                \array_pop($out);
                continue;
            }
            $out[] = $segment;
        }

        $normalized = '/' . \implode('/', $out);
        if (\str_ends_with($path, '/') && $normalized !== '/') {
            $normalized .= '/';
        }

        return $normalized;
    }

    /**
     * @internal Called only by CurlResponse on a redirect.
     *
     * @param  string $urlA
     * @param  string $urlB
     * @return bool True when scheme, host, or port differ between the two URLs.
     *              Unparseable input is treated as cross-origin (fail safe).
     */
    public function isCrossOrigin(string $urlA, string $urlB): bool
    {
        $a = \parse_url($urlA);
        $b = \parse_url($urlB);
        if ($a === false || $b === false) {
            return true;
        }

        $schemeA = \strtolower($a['scheme'] ?? '');
        $schemeB = \strtolower($b['scheme'] ?? '');
        $hostA = \strtolower($a['host'] ?? '');
        $hostB = \strtolower($b['host'] ?? '');
        $portA = $a['port'] ?? ($schemeA === 'https' ? 443 : 80);
        $portB = $b['port'] ?? ($schemeB === 'https' ? 443 : 80);

        return $schemeA !== $schemeB || $hostA !== $hostB || $portA !== $portB;
    }

    /**
     * Applies client-certificate (mTLS) options, when provided.
     *
     * Supported $options keys: local_cert (client cert path), local_pk (private
     * key path, when separate from the cert), passphrase (private key
     * passphrase), cafile (custom CA bundle path).
     *
     * @param  \CurlHandle          $ch
     * @param  array<string, mixed> $options
     * @return void
     */
    private function applyTlsOptions(\CurlHandle $ch, array $options): void
    {
        if (isset($options['local_cert'])) {
            \curl_setopt($ch, \CURLOPT_SSLCERT, (string) $options['local_cert']);
        }
        if (isset($options['local_pk'])) {
            \curl_setopt($ch, \CURLOPT_SSLKEY, (string) $options['local_pk']);
        }
        if (isset($options['passphrase'])) {
            \curl_setopt($ch, \CURLOPT_SSLKEYPASSWD, (string) $options['passphrase']);
        }
        if (isset($options['cafile'])) {
            \curl_setopt($ch, \CURLOPT_CAINFO, (string) $options['cafile']);
        }
    }

    /**
     * Appends query parameters to a URL.
     *
     * @param  string              $url
     * @param  array<string,mixed> $query
     * @return string
     */
    private function applyQuery(string $url, array $query): string
    {
        if ($query === []) {
            return $url;
        }

        $separator = \str_contains($url, '?') ? '&' : '?';
        return $url . $separator . \http_build_query($query);
    }

    /**
     * Resolves a host to the IP that will actually be connected to: the host
     * itself when it's already a literal IP, otherwise the first address the
     * configured DNS resolver returns for it. Returns null when the host is
     * absent or resolution fails — callers fall back to curl's own resolution
     * in that case, except where 'block_private_network' is set (see
     * buildHandle()), where a null result is treated as unsafe rather than
     * silently allowed.
     *
     * @param  string|null $host
     * @return string|null
     */
    private function resolveHostIp(?string $host): ?string
    {
        if ($host === null) {
            return null;
        }

        if (\filter_var($host, \FILTER_VALIDATE_IP) !== false) {
            return $host;
        }

        try {
            $answer = $this->dns->resolve($host);
        } catch (DnsQueryException) {
            return null;
        }

        return $answer->records[0] ?? null;
    }

    /**
     * @param  string $ip Candidate IPv4/IPv6 address (or a hostname that failed to resolve).
     * @return bool True when $ip is not a valid public, routable address — this
     *              includes private ranges (RFC 1918, ULA, ...), loopback, link-local,
     *              reserved ranges, and anything that isn't a valid IP at all (e.g. an
     *              unresolved hostname passed through), so unresolvable input fails closed.
     */
    private function isPrivateOrReservedIp(string $ip): bool
    {
        return \filter_var($ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_NO_PRIV_RANGE | \FILTER_FLAG_NO_RES_RANGE) === false;
    }

    /**
     * Builds a CURLOPT_RESOLVE entry pinning $host to an already-resolved $ip.
     *
     * @param  string $url
     * @param  string $host
     * @param  string $ip
     * @return string|null "host:port:ip" (IPv6 addresses bracketed).
     */
    private function buildResolveOption(string $url, string $host, string $ip): ?string
    {
        $scheme = \parse_url($url)['scheme'] ?? 'http';
        $port = \parse_url($url)['port'] ?? ($scheme === 'https' ? 443 : 80);

        if (\filter_var($ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV6) !== false) {
            $ip = "[{$ip}]";
        }

        return "{$host}:{$port}:{$ip}";
    }
}
