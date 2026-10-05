<?php

declare(strict_types=1);

namespace rafalmasiarek\HttpClient\Http;

/**
 * Minimal HTTP client contract, option shape inspired by Symfony HttpClient / Guzzle.
 *
 * @package rafalmasiarek\HttpClient\Http
 */
interface HttpClientInterface
{
    /**
     * Performs an HTTP request.
     *
     * Supported $options keys:
     *  - headers     (array<string,string|list<string>>) : request headers; an array value sends
     *                                                        the same header name multiple times
     *  - query       (array<string,mixed>) : appended to the URL as a query string
     *  - body        (string|array<string,mixed>) : request body. A string is sent as-is. An array is
     *                                         sent application/x-www-form-urlencoded, unless it contains
     *                                         a \CURLFile/\CURLStringFile value, in which case it's sent
     *                                         multipart/form-data (curl sets its own boundary + Content-Type).
     *  - json        (mixed)               : JSON-encoded request body; sets Content-Type: application/json
     *                                         (ignored if 'body' is also set)
     *  - timeout     (float)               : total request timeout in seconds
     *  - auth_basic  (string|array{0:string,1:string}) : HTTP Basic Auth, as "user:pass" or [user, pass]
     *  - verify_peer (bool)                : verify the server's TLS certificate (default: true)
     *  - follow_redirects (bool)           : follow HTTP redirects (default: true). Set false to inspect
     *                                         a redirect response itself (status + Location header)
     *                                         instead of the final destination. Implementations that
     *                                         follow redirects themselves (rather than delegating to the
     *                                         underlying transport) must strip Authorization/Cookie/
     *                                         Proxy-Authorization headers and auth_basic when a redirect
     *                                         hop crosses origins (scheme, host, or port differs).
     *  - block_private_network (bool)      : refuse to connect when the target host resolves to a
     *                                         private or reserved-range address (default: false),
     *                                         re-checked on every redirect hop. Intended for requests
     *                                         to externally-supplied URLs (e.g. a user-submitted link a
     *                                         background job fetches) as an SSRF guard — leave unset for
     *                                         requests to hardcoded, trusted endpoints.
     *  - local_cert  (string)               : client certificate path, for mTLS
     *  - local_pk    (string)               : client private key path, when separate from local_cert
     *  - passphrase  (string)               : client private key passphrase
     *  - cafile      (string)               : custom CA bundle path
     *  - max_connect_duration (float)       : limits only the connect phase (DNS+TCP+TLS), in seconds,
     *                                         separately from 'timeout' which bounds the whole request.
     *                                         Not every implementation supports this distinction.
     *  - on_progress (callable(int $dlNow, int $dlSize, array $info): void) : called periodically as
     *                                         the transfer progresses (at least once per second while
     *                                         data is flowing; $dlSize is -1 when unknown). A thrown
     *                                         exception aborts the transfer and is reported via
     *                                         HttpResponseInterface::getError(). Not every implementation
     *                                         calls this.
     *
     * Returns a lazy response: no network I/O has necessarily happened yet when this
     * method returns. The first call to any accessor on the returned object (getStatusCode(),
     * getHeaders(), getContent(), ...) drives the request to completion. Calling request()
     * several times before reading any of the responses lets an implementation that shares
     * a single underlying transport (e.g. one curl_multi handle) run them concurrently;
     * reading a response immediately makes that one call behave synchronously.
     *
     * @param  string               $method  HTTP method (GET, POST, ...).
     * @param  string               $url     Absolute URL.
     * @param  array<string, mixed> $options See above.
     * @return HttpResponseInterface
     */
    public function request(string $method, string $url, array $options = []): HttpResponseInterface;
}
