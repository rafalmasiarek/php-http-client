# http-client
Lazy, concurrent curl-based HTTP client with DNS pinning, retry, SSE, caching, and rate
limiting — extracted from [dashboard-kit](https://github.com/rafalmasiarek/php-dashboard-kit).




## Index

### Classes

- [`CachingHttpClient`](#cachinghttpclient) — `rafalmasiarek\HttpClient\Http`
- [`ChunkedFileDownloader`](#chunkedfiledownloader) — `rafalmasiarek\HttpClient\Http`
- [`CurlHttpClient`](#curlhttpclient) — `rafalmasiarek\HttpClient\Http`
- [`CurlResponse`](#curlresponse) — `rafalmasiarek\HttpClient\Http`
- [`DefaultRetryStrategy`](#defaultretrystrategy) — `rafalmasiarek\HttpClient\Http`
- [`DownloadException`](#downloadexception) — `rafalmasiarek\HttpClient\Http`
- [`EventSourceClient`](#eventsourceclient) — `rafalmasiarek\HttpClient\Http`
- [`FileHttpCacheStore`](#filehttpcachestore) — `rafalmasiarek\HttpClient\Http`
- [`HttpResponse`](#httpresponse) — `rafalmasiarek\HttpClient\Http`
- [`HttpTransportDebug`](#httptransportdebug) — `rafalmasiarek\HttpClient\Http`
- [`RetryHttpClient`](#retryhttpclient) — `rafalmasiarek\HttpClient\Http`
- [`ServerSentEvent`](#serversentevent) — `rafalmasiarek\HttpClient\Http`
- [`ThrottlingHttpClient`](#throttlinghttpclient) — `rafalmasiarek\HttpClient\Http`

### Interfaces

- [`HttpCacheStoreInterface`](#httpcachestoreinterface) — `rafalmasiarek\HttpClient\Http`
- [`HttpClientInterface`](#httpclientinterface) — `rafalmasiarek\HttpClient\Http`
- [`HttpResponseInterface`](#httpresponseinterface) — `rafalmasiarek\HttpClient\Http`
- [`RetryStrategyInterface`](#retrystrategyinterface) — `rafalmasiarek\HttpClient\Http`

### Enums

- [`TransportErrorKind`](#transporterrorkind) — `rafalmasiarek\HttpClient\Http`

## `CachingHttpClient`

*final class* — `rafalmasiarek\HttpClient\Http\CachingHttpClient` · [source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/CachingHttpClient.php#L18)

Decorates an HttpClientInterface with a practical subset of RFC 9111: freshness via Cache-Control max-age/Expires, revalidation via ETag/Last-Modified once stale. Only GET/HEAD are cached. Out of scope: Vary support, stale-while-revalidate/stale-if-error, request collapsing. No usable freshness lifetime (no max-age, no Expires) means not cacheable — no guessed default TTL.

implements [`HttpClientInterface`](#httpclientinterface)

**Depends on:** [`HttpCacheStoreInterface`](#httpcachestoreinterface)

| Method | Visibility | Returns | Summary |
|---|---|---|---|
| [`__construct()`](#cachinghttpclient-__construct) | public | — | — |
| [`request()`](#cachinghttpclient-request) | public | [`HttpResponseInterface`](#httpresponseinterface) | — |
| [`fromCachedEntry()`](#cachinghttpclient-fromcachedentry) | private | [`HttpResponseInterface`](#httpresponseinterface) | — |
| [`fetchAndMaybeCache()`](#cachinghttpclient-fetchandmaybecache) | private | [`HttpResponseInterface`](#httpresponseinterface) | — |
| [`computeTtl()`](#cachinghttpclient-computettl) | private | `int\|null` | — |
| [`cacheKey()`](#cachinghttpclient-cachekey) | private | `string` | — |

<details>
<summary><code>__construct()</code></summary>

#### `CachingHttpClient __construct()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/CachingHttpClient.php#L28)

```php
public function __construct(HttpClientInterface $client, HttpCacheStoreInterface $store, int $maxTtl)
```

| Param | Type | Description |
|---|---|---|
| `$client` | [`HttpClientInterface`](#httpclientinterface) | Underlying transport. |
| `$store` | [`HttpCacheStoreInterface`](#httpcachestoreinterface) | Where cached entries are persisted. |
| `$maxTtl` | `int` | Upper bound on any computed freshness lifetime, in seconds. |

</details>

<details>
<summary><code>request()</code></summary>

#### `CachingHttpClient request()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/CachingHttpClient.php#L41)

```php
public function request(string $method, string $url, array $options): HttpResponseInterface
```

| Param | Type | Description |
|---|---|---|
| `$method` | `string` | — |
| `$url` | `string` | — |
| `$options` | `array<string, mixed>` | — |

**Returns:** [`HttpResponseInterface`](#httpresponseinterface)

</details>

<details>
<summary><code>fromCachedEntry()</code></summary>

#### `CachingHttpClient fromCachedEntry()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/CachingHttpClient.php#L64)

```php
private function fromCachedEntry(array $entry): HttpResponseInterface
```

| Param | Type | Description |
|---|---|---|
| `$entry` | `array<string, mixed>` | — |

**Returns:** [`HttpResponseInterface`](#httpresponseinterface)

</details>

<details>
<summary><code>fetchAndMaybeCache()</code></summary>

#### `CachingHttpClient fetchAndMaybeCache()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/CachingHttpClient.php#L81)

```php
private function fetchAndMaybeCache(string $method, string $url, array $options, string $key, ?array $stale): HttpResponseInterface
```

| Param | Type | Description |
|---|---|---|
| `$method` | `string` | — |
| `$url` | `string` | — |
| `$options` | `array<string, mixed>` | — |
| `$key` | `string` | — |
| `$stale` | `array<string, mixed>\|null` | Expired entry whose validators can be sent for revalidation. |

**Returns:** [`HttpResponseInterface`](#httpresponseinterface)

</details>

<details>
<summary><code>computeTtl()</code></summary>

#### `CachingHttpClient computeTtl()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/CachingHttpClient.php#L126)

```php
private function computeTtl(HttpResponseInterface $response): ?int
```

| Param | Type | Description |
|---|---|---|
| `$response` | [`HttpResponseInterface`](#httpresponseinterface) | — |

**Returns:** `int\|null` — Null when the response gives no usable freshness lifetime (no max-age, no Expires) or explicitly forbids storage (Cache-Control: no-store/private).

</details>

<details>
<summary><code>cacheKey()</code></summary>

#### `CachingHttpClient cacheKey()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/CachingHttpClient.php#L154)

```php
private function cacheKey(string $method, string $url): string
```

| Param | Type | Description |
|---|---|---|
| `$method` | `string` | — |
| `$url` | `string` | — |

**Returns:** `string`

</details>

## `ChunkedFileDownloader`

*final class* — `rafalmasiarek\HttpClient\Http\ChunkedFileDownloader` · [source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/ChunkedFileDownloader.php#L32)

Downloads a large file in range-requested chunks, retrying failed chunks individually instead of restarting the whole transfer, and resuming from an existing partial download when the destination already has one. Writes to a "{destination}.part" sidecar and only renames it onto the real destination once the transfer completes successfully — a reader can never observe a truncated file at the destination path. A resume is only trusted when a "{destination}.part.meta" sidecar recorded the source's ETag/Last-Modified from the response that started the partial file. Without it (missing, or an older download that predates this file), the partial is discarded and the download restarts from scratch — resuming an unvalidated partial risks silently splicing bytes from two different versions of the resource into one corrupt file. When a validated resume gets a 200 back instead of 206 (server ignored If-Range because the resource changed, or doesn't support Range at all), the partial and its metadata are discarded and the download restarts once, from that response. Falls back to a single, unranged request when the server ignores the Range header on a fresh (offset 0) download — a server that can't resume mid-download can't be resumed at all.

**Depends on:** [`HttpClientInterface`](#httpclientinterface)

| Method | Visibility | Returns | Summary |
|---|---|---|---|
| [`__construct()`](#chunkedfiledownloader-__construct) | public | — | — |
| [`download()`](#chunkedfiledownloader-download) | public | `void` | Downloads $url to $destinationPath. |
| [`attempt()`](#chunkedfiledownloader-attempt) | private | `void` | Runs one download pass against $partPath, restarting itself exactly once (via $restarted) when a trusted resume turns out to be invalid. |
| [`fetchChunkWithRetry()`](#chunkedfiledownloader-fetchchunkwithretry) | private | [`HttpResponseInterface`](#httpresponseinterface) | Fetches one chunk, retrying on transport failure. |
| [`parseTotalSize()`](#chunkedfiledownloader-parsetotalsize) | private | `int\|null` | Extracts the total resource size from a Content-Range header value ("bytes start-end/total"). |
| [`readIfRange()`](#chunkedfiledownloader-readifrange) | private | `string\|null` | Reads the If-Range validator persisted for an existing partial download. |
| [`writeIfRange()`](#chunkedfiledownloader-writeifrange) | private | `string\|null` | Persists the ETag/Last-Modified from a chunk response as the resume validator, preferring ETag. Returns null (and writes nothing) when neither is present — a resume after a crash for such a resource is never trusted, only completing the current, uninterrupted process is possible. |

<details>
<summary><code>__construct()</code></summary>

#### `ChunkedFileDownloader __construct()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/ChunkedFileDownloader.php#L39)

```php
public function __construct(HttpClientInterface $http, int $chunkSize, int $maxRetriesPerChunk)
```

| Param | Type | Description |
|---|---|---|
| `$http` | [`HttpClientInterface`](#httpclientinterface) | Client used to fetch each chunk. |
| `$chunkSize` | `int` | Bytes requested per range, e.g. 8 MiB. |
| `$maxRetriesPerChunk` | `int` | Attempts per chunk before giving up. |

</details>

<details>
<summary><code>download()</code></summary>

#### `ChunkedFileDownloader download()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/ChunkedFileDownloader.php#L61)

```php
public function download(string $url, string $destinationPath, array $requestOptions): void
```

Downloads $url to $destinationPath.

| Param | Type | Description |
|---|---|---|
| `$url` | `string` | Absolute URL. |
| `$destinationPath` | `string` | Final file path; a same-directory ".part" sidecar is used during the transfer and atomically renamed onto this path only once it completes successfully. |
| `$requestOptions` | `array<string, mixed>` | Extra HttpClientInterface::request() options (e.g. headers, timeout, local_cert) merged into every chunk request. 'Range'/'If-Range' headers, if present, are overridden per chunk. |

| Throws | When |
|---|---|
| [`DownloadException`](#downloadexception) | On a write failure, an unexpected status code, a server that can't resume a partial download, or a chunk that exhausts its retries. |

</details>

<details>
<summary><code>attempt()</code></summary>

#### `ChunkedFileDownloader attempt()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/ChunkedFileDownloader.php#L86)

```php
private function attempt(string $url, string $partPath, string $metaPath, array $requestOptions, bool $restarted): void
```

Runs one download pass against $partPath, restarting itself exactly once (via $restarted) when a trusted resume turns out to be invalid.

| Param | Type | Description |
|---|---|---|
| `$url` | `string` | — |
| `$partPath` | `string` | — |
| `$metaPath` | `string` | — |
| `$requestOptions` | `array<string, mixed>` | — |
| `$restarted` | `bool` | Whether this call is already a post-restart retry. |

| Throws | When |
|---|---|
| [`DownloadException`](#downloadexception) | — |

</details>

<details>
<summary><code>fetchChunkWithRetry()</code></summary>

#### `ChunkedFileDownloader fetchChunkWithRetry()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/ChunkedFileDownloader.php#L208)

```php
private function fetchChunkWithRetry(string $url, array $options): HttpResponseInterface
```

Fetches one chunk, retrying on transport failure.

| Param | Type | Description |
|---|---|---|
| `$url` | `string` | — |
| `$options` | `array<string, mixed>` | Already carries the Range/If-Range headers for this chunk. |

**Returns:** [`HttpResponseInterface`](#httpresponseinterface)

| Throws | When |
|---|---|
| [`DownloadException`](#downloadexception) | When every attempt fails. |

</details>

<details>
<summary><code>parseTotalSize()</code></summary>

#### `ChunkedFileDownloader parseTotalSize()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/ChunkedFileDownloader.php#L233)

```php
private function parseTotalSize(string $contentRange): ?int
```

Extracts the total resource size from a Content-Range header value ("bytes start-end/total").

| Param | Type | Description |
|---|---|---|
| `$contentRange` | `string` | — |

**Returns:** `int\|null`

</details>

<details>
<summary><code>readIfRange()</code></summary>

#### `ChunkedFileDownloader readIfRange()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/ChunkedFileDownloader.php#L245)

```php
private function readIfRange(string $metaPath): ?string
```

Reads the If-Range validator persisted for an existing partial download.

| Param | Type | Description |
|---|---|---|
| `$metaPath` | `string` | — |

**Returns:** `string\|null` — The ETag or Last-Modified value to send as If-Range, or null when no (trustworthy) validator was recorded.

</details>

<details>
<summary><code>writeIfRange()</code></summary>

#### `ChunkedFileDownloader writeIfRange()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/ChunkedFileDownloader.php#L274)

```php
private function writeIfRange(string $metaPath, HttpResponseInterface $response): ?string
```

Persists the ETag/Last-Modified from a chunk response as the resume validator, preferring ETag. Returns null (and writes nothing) when neither is present — a resume after a crash for such a resource is never trusted, only completing the current, uninterrupted process is possible.

| Param | Type | Description |
|---|---|---|
| `$metaPath` | `string` | — |
| `$response` | [`HttpResponseInterface`](#httpresponseinterface) | — |

**Returns:** `string\|null`

</details>

## `CurlHttpClient`

*final class* — `rafalmasiarek\HttpClient\Http\CurlHttpClient` · [source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/CurlHttpClient.php#L27)

curl-based HttpClientInterface implementation. Resolves hostnames via an injected DnsResolverInterface and pins the result with CURLOPT_RESOLVE instead of curl's own DNS. A failed resolve falls back to curl's own resolution, except under 'block_private_network' (see buildHandle()), where an unconfirmed target is treated as unsafe. request() returns a lazy CurlResponse (see HttpResponseInterface). Every response from one client instance shares one curl_multi handle, created lazily; CurlResponse drives it on first access. Redirects are followed by CurlResponse itself, one hop at a time, so credential headers get stripped and the private-network check re-run on every hop, not just the first.

implements [`HttpClientInterface`](#httpclientinterface)

**Depends on:** `DnsResolverInterface`, `\CurlMultiHandle`

| Property | Type | Visibility | Description |
|---|---|---|---|
| `$multi` | `\CurlMultiHandle\|null` | private | Lazily created; shared by every CurlResponse this client produces. |
| `$pending` | `array<int, CurlResponse>` | private | In-flight responses, keyed by spl_object_id() of their current curl handle. |

| Method | Visibility | Returns | Summary |
|---|---|---|---|
| [`__construct()`](#curlhttpclient-__construct) | public | — | — |
| [`request()`](#curlhttpclient-request) | public | [`HttpResponseInterface`](#httpresponseinterface) | Extra $options beyond HttpClientInterface::request(): 'follow_redirects' (bool, default true); 'block_private_network' (bool, default false), re-checked on every redirect hop; 'max_connect_duration' (float seconds) — connect phase only, separate from 'timeout'; 'on_progress' (callable(int $dlNow, int $dlSize, array $info): void), called periodically — a thrown exception aborts the transfer (see getError()). |
| [`buildHandle()`](#curlhttpclient-buildhandle) | public | `\CurlHandle\|null` | Builds one hop's curl handle, wiring its header/body callbacks to write directly into $forResponse. Returns null (after marking $forResponse done via _completeBlocked()) when block_private_network refuses the target — there's no handle to register in that case. |
| [`registerHandle()`](#curlhttpclient-registerhandle) | public | `void` | — |
| [`forget()`](#curlhttpclient-forget) | public | `void` | — |
| [`pumpUntilDone()`](#curlhttpclient-pumpuntildone) | public | `void` | Drives the shared curl_multi handle until $target's current hop finishes. Every other pending handle advances in the same loop — that's the concurrency. |
| [`finishOne()`](#curlhttpclient-finishone) | private | `void` | — |
| [`multi()`](#curlhttpclient-multi) | private | `\CurlMultiHandle` | — |
| [`resolveBody()`](#curlhttpclient-resolvebody) | private | `array{string\|array<string, mixed>\|null, array<string, string\|list<string>>}` | Resolves the request body and any headers implied by it (e.g. Content-Type for JSON). A 'body' array without any \CURLFile/\CURLStringFile value is form-urlencoded. One containing such a value is passed to curl as-is, which then sends it as multipart/form-data with its own boundary and Content-Type — no header is added here in that case, since ours would conflict with curl's boundary. |
| [`hasFileParts()`](#curlhttpclient-hasfileparts) | private | `bool` | — |
| [`formatHeaders()`](#curlhttpclient-formatheaders) | private | `list<string>` | — |
| [`stripCredentialHeaders()`](#curlhttpclient-stripcredentialheaders) | public | `array<string, string\|list<string>>` | Removes Authorization/Proxy-Authorization/Cookie headers (case-insensitive name match) — called when a redirect crosses origins, so credentials meant for the original host are never sent to wherever it redirected to. |
| [`resolveRedirectUrl()`](#curlhttpclient-resolveredirecturl) | public | `string` | Resolves a redirect's Location header against the URL it came from. Handles absolute URLs, protocol-relative ("//host/path"), absolute-path ("/path"), and relative paths (resolved against the base URL's directory, with "." and ".." segments collapsed). |
| [`normalizePath()`](#curlhttpclient-normalizepath) | private | `string` | Collapses "." and ".." path segments. |
| [`isCrossOrigin()`](#curlhttpclient-iscrossorigin) | public | `bool` | — |
| [`applyTlsOptions()`](#curlhttpclient-applytlsoptions) | private | `void` | Applies client-certificate (mTLS) options, when provided. Supported $options keys: local_cert (client cert path), local_pk (private key path, when separate from the cert), passphrase (private key passphrase), cafile (custom CA bundle path). |
| [`applyQuery()`](#curlhttpclient-applyquery) | private | `string` | Appends query parameters to a URL. |
| [`resolveHostIp()`](#curlhttpclient-resolvehostip) | private | `string\|null` | Resolves a host to the IP that will actually be connected to: the host itself when it's already a literal IP, otherwise the first address the configured DNS resolver returns for it. Returns null when the host is absent or resolution fails — callers fall back to curl's own resolution in that case, except where 'block_private_network' is set (see buildHandle()), where a null result is treated as unsafe rather than silently allowed. |
| [`isPrivateOrReservedIp()`](#curlhttpclient-isprivateorreservedip) | private | `bool` | — |
| [`buildResolveOption()`](#curlhttpclient-buildresolveoption) | private | `string\|null` | Builds a CURLOPT_RESOLVE entry pinning $host to an already-resolved $ip. |

<details>
<summary><code>__construct()</code></summary>

#### `CurlHttpClient __construct()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/CurlHttpClient.php#L47)

```php
public function __construct(DnsResolverInterface $dns)
```

| Param | Type | Description |
|---|---|---|
| `$dns` | `DnsResolverInterface` | Resolver used to pin each request's target IP. |

</details>

<details>
<summary><code>request()</code></summary>

#### `CurlHttpClient request()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/CurlHttpClient.php#L64)

```php
public function request(string $method, string $url, array $options): HttpResponseInterface
```

Extra $options beyond HttpClientInterface::request(): 'follow_redirects' (bool, default true); 'block_private_network' (bool, default false), re-checked on every redirect hop; 'max_connect_duration' (float seconds) — connect phase only, separate from 'timeout'; 'on_progress' (callable(int $dlNow, int $dlSize, array $info): void), called periodically — a thrown exception aborts the transfer (see getError()).

| Param | Type | Description |
|---|---|---|
| `$method` | `string` | — |
| `$url` | `string` | — |
| `$options` | `array<string, mixed>` | — |

**Returns:** [`HttpResponseInterface`](#httpresponseinterface)

</details>

<details>
<summary><code>buildHandle()</code></summary>

#### `CurlHttpClient buildHandle()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/CurlHttpClient.php#L89)

*`@internal`* — Called only by CurlResponse (initial request and each redirect hop).

```php
public function buildHandle(string $method, string $url, array $options, CurlResponse $forResponse): ?\CurlHandle
```

Builds one hop's curl handle, wiring its header/body callbacks to write directly into $forResponse. Returns null (after marking $forResponse done via _completeBlocked()) when block_private_network refuses the target — there's no handle to register in that case.

| Param | Type | Description |
|---|---|---|
| `$method` | `string` | — |
| `$url` | `string` | — |
| `$options` | `array<string, mixed>` | — |
| `$forResponse` | [`CurlResponse`](#curlresponse) | — |

**Returns:** `\CurlHandle\|null`

</details>

<details>
<summary><code>registerHandle()</code></summary>

#### `CurlHttpClient registerHandle()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/CurlHttpClient.php#L175)

*`@internal`* — Called only by CurlResponse, to add a freshly built handle to this client's shared curl_multi handle.

```php
public function registerHandle(\CurlHandle $handle, CurlResponse $response): void
```

| Param | Type | Description |
|---|---|---|
| `$handle` | `\CurlHandle` | — |
| `$response` | [`CurlResponse`](#curlresponse) | — |

</details>

<details>
<summary><code>forget()</code></summary>

#### `CurlHttpClient forget()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/CurlHttpClient.php#L185)

*`@internal`* — Called only by CurlResponse::cancel(), to remove a handle before it has finished.

```php
public function forget(\CurlHandle $handle): void
```

| Param | Type | Description |
|---|---|---|
| `$handle` | `\CurlHandle` | — |

</details>

<details>
<summary><code>pumpUntilDone()</code></summary>

#### `CurlHttpClient pumpUntilDone()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/CurlHttpClient.php#L200)

*`@internal`* — Called only by CurlResponse::ensureComplete().

```php
public function pumpUntilDone(CurlResponse $target): void
```

Drives the shared curl_multi handle until $target's current hop finishes. Every other pending handle advances in the same loop — that's the concurrency.

| Param | Type | Description |
|---|---|---|
| `$target` | [`CurlResponse`](#curlresponse) | — |

</details>

<details>
<summary><code>finishOne()</code></summary>

#### `CurlHttpClient finishOne()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/CurlHttpClient.php#L235)

```php
private function finishOne(\CurlMultiHandle $multi, array $info): void
```

| Param | Type | Description |
|---|---|---|
| `$multi` | `\CurlMultiHandle` | — |
| `$info` | `array{msg: int, result: int, handle: \CurlHandle}` | One entry from curl_multi_info_read(). |

</details>

<details>
<summary><code>multi()</code></summary>

#### `CurlHttpClient multi()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/CurlHttpClient.php#L262)

```php
private function multi(): \CurlMultiHandle
```

**Returns:** `\CurlMultiHandle`

</details>

<details>
<summary><code>resolveBody()</code></summary>

#### `CurlHttpClient resolveBody()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/CurlHttpClient.php#L278)

```php
private function resolveBody(array $options): array
```

Resolves the request body and any headers implied by it (e.g. Content-Type for JSON). A 'body' array without any \CURLFile/\CURLStringFile value is form-urlencoded. One containing such a value is passed to curl as-is, which then sends it as multipart/form-data with its own boundary and Content-Type — no header is added here in that case, since ours would conflict with curl's boundary.

| Param | Type | Description |
|---|---|---|
| `$options` | `array<string, mixed>` | — |

**Returns:** `array{string\|array<string, mixed>\|null, array<string, string\|list<string>>}`

</details>

<details>
<summary><code>hasFileParts()</code></summary>

#### `CurlHttpClient hasFileParts()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/CurlHttpClient.php#L309)

```php
private function hasFileParts(array $body): bool
```

| Param | Type | Description |
|---|---|---|
| `$body` | `array<string, mixed>` | — |

**Returns:** `bool` — True when any value is a file part (multipart/form-data upload).

</details>

<details>
<summary><code>formatHeaders()</code></summary>

#### `CurlHttpClient formatHeaders()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/CurlHttpClient.php#L323)

```php
private function formatHeaders(array $headers): array
```

| Param | Type | Description |
|---|---|---|
| `$headers` | `array<string, string\|list<string>>` | — |

**Returns:** `list<string>`

</details>

<details>
<summary><code>stripCredentialHeaders()</code></summary>

#### `CurlHttpClient stripCredentialHeaders()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/CurlHttpClient.php#L344)

*`@internal`* — Called only by CurlResponse on a cross-origin redirect.

```php
public function stripCredentialHeaders(array $headers): array
```

Removes Authorization/Proxy-Authorization/Cookie headers (case-insensitive name match) — called when a redirect crosses origins, so credentials meant for the original host are never sent to wherever it redirected to.

| Param | Type | Description |
|---|---|---|
| `$headers` | `array<string, string\|list<string>>` | — |

**Returns:** `array<string, string\|list<string>>`

</details>

<details>
<summary><code>resolveRedirectUrl()</code></summary>

#### `CurlHttpClient resolveRedirectUrl()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/CurlHttpClient.php#L366)

*`@internal`* — Called only by CurlResponse on a redirect.

```php
public function resolveRedirectUrl(string $baseUrl, string $location): string
```

Resolves a redirect's Location header against the URL it came from. Handles absolute URLs, protocol-relative ("//host/path"), absolute-path ("/path"), and relative paths (resolved against the base URL's directory, with "." and ".." segments collapsed).

| Param | Type | Description |
|---|---|---|
| `$baseUrl` | `string` | — |
| `$location` | `string` | — |

**Returns:** `string`

</details>

<details>
<summary><code>normalizePath()</code></summary>

#### `CurlHttpClient normalizePath()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/CurlHttpClient.php#L403)

```php
private function normalizePath(string $path): string
```

Collapses "." and ".." path segments.

| Param | Type | Description |
|---|---|---|
| `$path` | `string` | — |

**Returns:** `string`

</details>

<details>
<summary><code>isCrossOrigin()</code></summary>

#### `CurlHttpClient isCrossOrigin()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/CurlHttpClient.php#L434)

*`@internal`* — Called only by CurlResponse on a redirect.

```php
public function isCrossOrigin(string $urlA, string $urlB): bool
```

| Param | Type | Description |
|---|---|---|
| `$urlA` | `string` | — |
| `$urlB` | `string` | — |

**Returns:** `bool` — True when scheme, host, or port differ between the two URLs. Unparseable input is treated as cross-origin (fail safe).

</details>

<details>
<summary><code>applyTlsOptions()</code></summary>

#### `CurlHttpClient applyTlsOptions()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/CurlHttpClient.php#L463)

```php
private function applyTlsOptions(\CurlHandle $ch, array $options): void
```

Applies client-certificate (mTLS) options, when provided. Supported $options keys: local_cert (client cert path), local_pk (private key path, when separate from the cert), passphrase (private key passphrase), cafile (custom CA bundle path).

| Param | Type | Description |
|---|---|---|
| `$ch` | `\CurlHandle` | — |
| `$options` | `array<string, mixed>` | — |

</details>

<details>
<summary><code>applyQuery()</code></summary>

#### `CurlHttpClient applyQuery()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/CurlHttpClient.php#L486)

```php
private function applyQuery(string $url, array $query): string
```

Appends query parameters to a URL.

| Param | Type | Description |
|---|---|---|
| `$url` | `string` | — |
| `$query` | `array<string, mixed>` | — |

**Returns:** `string`

</details>

<details>
<summary><code>resolveHostIp()</code></summary>

#### `CurlHttpClient resolveHostIp()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/CurlHttpClient.php#L508)

```php
private function resolveHostIp(?string $host): ?string
```

Resolves a host to the IP that will actually be connected to: the host itself when it's already a literal IP, otherwise the first address the configured DNS resolver returns for it. Returns null when the host is absent or resolution fails — callers fall back to curl's own resolution in that case, except where 'block_private_network' is set (see buildHandle()), where a null result is treated as unsafe rather than silently allowed.

| Param | Type | Description |
|---|---|---|
| `$host` | `string\|null` | — |

**Returns:** `string\|null`

</details>

<details>
<summary><code>isPrivateOrReservedIp()</code></summary>

#### `CurlHttpClient isPrivateOrReservedIp()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/CurlHttpClient.php#L534)

```php
private function isPrivateOrReservedIp(string $ip): bool
```

| Param | Type | Description |
|---|---|---|
| `$ip` | `string` | Candidate IPv4/IPv6 address (or a hostname that failed to resolve). |

**Returns:** `bool` — True when $ip is not a valid public, routable address — this includes private ranges (RFC 1918, ULA, ...), loopback, link-local, reserved ranges, and anything that isn't a valid IP at all (e.g. an unresolved hostname passed through), so unresolvable input fails closed.

</details>

<details>
<summary><code>buildResolveOption()</code></summary>

#### `CurlHttpClient buildResolveOption()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/CurlHttpClient.php#L547)

```php
private function buildResolveOption(string $url, string $host, string $ip): ?string
```

Builds a CURLOPT_RESOLVE entry pinning $host to an already-resolved $ip.

| Param | Type | Description |
|---|---|---|
| `$url` | `string` | — |
| `$host` | `string` | — |
| `$ip` | `string` | — |

**Returns:** `string\|null` — "host:port:ip" (IPv6 addresses bracketed).

</details>

## `CurlResponse`

*final class* — `rafalmasiarek\HttpClient\Http\CurlResponse` · [source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/CurlResponse.php#L15)

Lazy HttpResponseInterface returned by CurlHttpClient. Registers its curl handle on the client's shared curl_multi and returns immediately; the first accessor call pumps that shared handle until this response's hop is done, finishing any other pending responses along the way too.

implements [`HttpResponseInterface`](#httpresponseinterface)

**Depends on:** [`CurlHttpClient`](#curlhttpclient), `\CurlHandle`, [`TransportErrorKind`](#transporterrorkind)

| Property | Type | Visibility | Description |
|---|---|---|---|
| `$handle` | `\CurlHandle\|null` | private | Current hop's handle. Null once this hop has finished (closed) or was blocked before starting. |
| `$hopDone` | `bool` | private | Whether the current hop (not necessarily the final one, if redirects remain) has finished. |
| `$done` | `bool` | private | Whether this response is fully resolved (no more redirects to follow). |
| `$canceled` | `bool` | private | — |
| `$statusCode` | `int` | private | — |
| `$headers` | `array<string, list<string>>` | private | — |
| `$body` | `string` | private | — |
| `$error` | `?string` | private | — |
| `$errorKind` | ?[`TransportErrorKind`](#transporterrorkind) | private | — |
| `$lastInfo` | `array<string, mixed>` | private | Snapshot of curl_getinfo() from the most recently finished hop. |
| `$redirectCount` | `int` | private | — |
| `$finalUrl` | `string` | private | — |

| Method | Visibility | Returns | Summary |
|---|---|---|---|
| [`__construct()`](#curlresponse-__construct) | public | — | — |
| [`getStatusCode()`](#curlresponse-getstatuscode) | public | `int` | — |
| [`getHeaders()`](#curlresponse-getheaders) | public | `array` | — |
| [`getHeader()`](#curlresponse-getheader) | public | `?string` | — |
| [`getHeaderLine()`](#curlresponse-getheaderline) | public | `string` | — |
| [`getContent()`](#curlresponse-getcontent) | public | `string` | — |
| [`json()`](#curlresponse-json) | public | `mixed` | — |
| [`isSuccessful()`](#curlresponse-issuccessful) | public | `bool` | — |
| [`getError()`](#curlresponse-geterror) | public | `?string` | — |
| [`getErrorKind()`](#curlresponse-geterrorkind) | public | ?[`TransportErrorKind`](#transporterrorkind) | — |
| [`getInfo()`](#curlresponse-getinfo) | public | `mixed` | — |
| [`cancel()`](#curlresponse-cancel) | public | `void` | — |
| [`_onHeaderLine()`](#curlresponse-_onheaderline) | public | `void` | — |
| [`_onBodyChunk()`](#curlresponse-_onbodychunk) | public | `void` | — |
| [`_completeBlocked()`](#curlresponse-_completeblocked) | public | `void` | — |
| [`_hopFinished()`](#curlresponse-_hopfinished) | public | `void` | — |
| [`_handle()`](#curlresponse-_handle) | public | `?\CurlHandle` | — |
| [`ensureComplete()`](#curlresponse-ensurecomplete) | private | `void` | Drives the shared curl_multi handle (via the owning client) until this response's current hop finishes, then follows any redirect by rebuilding and re-registering a new handle, repeating until a final response is reached. |
| [`maybeFollowRedirect()`](#curlresponse-maybefollowredirect) | private | `bool` | — |

<details>
<summary><code>__construct()</code></summary>

#### `CurlResponse __construct()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/CurlResponse.php#L50)

```php
public function __construct(CurlHttpClient $client, string $method, string $url, array $options, bool $followRedirects)
```

| Param | Type | Description |
|---|---|---|
| `$client` | [`CurlHttpClient`](#curlhttpclient) | Owner — drives the shared curl_multi handle and builds each hop's curl handle. |
| `$method` | `string` | HTTP method. May be downgraded to GET on a 301/302/303 redirect of a non-GET/HEAD request. |
| `$url` | `string` | Request URL (reassigned to each redirect's target). |
| `$options` | `array<string, mixed>` | Request options, as passed to CurlHttpClient::request(). |
| `$followRedirects` | `bool` | Whether to transparently follow 3xx responses. |

</details>

<details>
<summary><code>getStatusCode()</code></summary>

#### `CurlResponse getStatusCode()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/CurlResponse.php#L68)

```php
public function getStatusCode(): int
```

**Returns:** `int`

</details>

<details>
<summary><code>getHeaders()</code></summary>

#### `CurlResponse getHeaders()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/CurlResponse.php#L74)

```php
public function getHeaders(): array
```

**Returns:** `array`

</details>

<details>
<summary><code>getHeader()</code></summary>

#### `CurlResponse getHeader()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/CurlResponse.php#L80)

```php
public function getHeader(string $name): ?string
```

| Param | Type | Description |
|---|---|---|
| `$name` | `string` | — |

**Returns:** `?string`

</details>

<details>
<summary><code>getHeaderLine()</code></summary>

#### `CurlResponse getHeaderLine()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/CurlResponse.php#L86)

```php
public function getHeaderLine(string $name): string
```

| Param | Type | Description |
|---|---|---|
| `$name` | `string` | — |

**Returns:** `string`

</details>

<details>
<summary><code>getContent()</code></summary>

#### `CurlResponse getContent()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/CurlResponse.php#L92)

```php
public function getContent(): string
```

**Returns:** `string`

</details>

<details>
<summary><code>json()</code></summary>

#### `CurlResponse json()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/CurlResponse.php#L98)

```php
public function json(): mixed
```

**Returns:** `mixed`

</details>

<details>
<summary><code>isSuccessful()</code></summary>

#### `CurlResponse isSuccessful()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/CurlResponse.php#L103)

```php
public function isSuccessful(): bool
```

**Returns:** `bool`

</details>

<details>
<summary><code>getError()</code></summary>

#### `CurlResponse getError()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/CurlResponse.php#L108)

```php
public function getError(): ?string
```

**Returns:** `?string`

</details>

<details>
<summary><code>getErrorKind()</code></summary>

#### `CurlResponse getErrorKind()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/CurlResponse.php#L114)

```php
public function getErrorKind(): ?TransportErrorKind
```

**Returns:** ?[`TransportErrorKind`](#transporterrorkind)

</details>

<details>
<summary><code>getInfo()</code></summary>

#### `CurlResponse getInfo()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/CurlResponse.php#L120)

```php
public function getInfo(?string $key): mixed
```

| Param | Type | Description |
|---|---|---|
| `$key` | `?string` | — |

**Returns:** `mixed`

</details>

<details>
<summary><code>cancel()</code></summary>

#### `CurlResponse cancel()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/CurlResponse.php#L133)

```php
public function cancel(): void
```

</details>

<details>
<summary><code>_onHeaderLine()</code></summary>

#### `CurlResponse _onHeaderLine()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/CurlResponse.php#L156)

*`@internal`* — Called only by CurlHttpClient's CURLOPT_HEADERFUNCTION closure.

```php
public function _onHeaderLine(string $line): void
```

| Param | Type | Description |
|---|---|---|
| `$line` | `string` | — |

</details>

<details>
<summary><code>_onBodyChunk()</code></summary>

#### `CurlResponse _onBodyChunk()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/CurlResponse.php#L172)

*`@internal`* — Called only by CurlHttpClient's CURLOPT_WRITEFUNCTION closure.

```php
public function _onBodyChunk(string $chunk): void
```

| Param | Type | Description |
|---|---|---|
| `$chunk` | `string` | — |

</details>

<details>
<summary><code>_completeBlocked()</code></summary>

#### `CurlResponse _completeBlocked()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/CurlResponse.php#L181)

*`@internal`* — Called only by CurlHttpClient::buildHandle() when a block_private_network check refuses the target before a handle is even created.

```php
public function _completeBlocked(string $host): void
```

| Param | Type | Description |
|---|---|---|
| `$host` | `string` | — |

</details>

<details>
<summary><code>_hopFinished()</code></summary>

#### `CurlResponse _hopFinished()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/CurlResponse.php#L196)

*`@internal`* — Called only by CurlHttpClient::pumpUntilDone(). Handle is already closed by then.

```php
public function _hopFinished(int $statusCode, ?string $error, array $info, ?TransportErrorKind $errorKind): void
```

| Param | Type | Description |
|---|---|---|
| `$statusCode` | `int` | — |
| `$error` | `?string` | — |
| `$info` | `array<string, mixed>` | curl_getinfo() snapshot taken before close. |
| `$errorKind` | ?[`TransportErrorKind`](#transporterrorkind) | — |

</details>

<details>
<summary><code>_handle()</code></summary>

#### `CurlResponse _handle()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/CurlResponse.php#L209)

*`@internal`* — Exposes the current hop's handle to CurlHttpClient::pumpUntilDone().

```php
public function _handle(): ?\CurlHandle
```

**Returns:** `?\CurlHandle`

</details>

<details>
<summary><code>ensureComplete()</code></summary>

#### `CurlResponse ensureComplete()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/CurlResponse.php#L219)

```php
private function ensureComplete(): void
```

Drives the shared curl_multi handle (via the owning client) until this response's current hop finishes, then follows any redirect by rebuilding and re-registering a new handle, repeating until a final response is reached.

</details>

<details>
<summary><code>maybeFollowRedirect()</code></summary>

#### `CurlResponse maybeFollowRedirect()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/CurlResponse.php#L240)

```php
private function maybeFollowRedirect(): bool
```

**Returns:** `bool` — True when a redirect was followed (caller should keep looping); false when this response is final.

</details>

## `DefaultRetryStrategy`

*final class* — `rafalmasiarek\HttpClient\Http\DefaultRetryStrategy` · [source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/DefaultRetryStrategy.php#L17)

Retries transport-level failures and a conservative set of transient HTTP statuses, only for methods considered safe to replay automatically. Never retries a non-idempotent method (POST, PATCH, ...) — a caller that knows its request body is safe to resend should retry it explicitly rather than rely on this default.

implements [`RetryStrategyInterface`](#retrystrategyinterface)

| Method | Visibility | Returns | Summary |
|---|---|---|---|
| [`__construct()`](#defaultretrystrategy-__construct) | public | — | — |
| [`shouldRetry()`](#defaultretrystrategy-shouldretry) | public | `bool` | — |
| [`getDelayMilliseconds()`](#defaultretrystrategy-getdelaymilliseconds) | public | `int` | — |
| [`parseRetryAfter()`](#defaultretrystrategy-parseretryafter) | private | `int\|null` | Parses a Retry-After header value (delay-seconds or an HTTP-date) into seconds from now. |

<details>
<summary><code>__construct()</code></summary>

#### `DefaultRetryStrategy __construct()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/DefaultRetryStrategy.php#L31)

```php
public function __construct(int $maxRetries, int $baseDelayMs, int $maxDelayMs, int $jitterMs)
```

| Param | Type | Description |
|---|---|---|
| `$maxRetries` | `int` | Attempts after the first, e.g. 3 means up to 4 total tries. |
| `$baseDelayMs` | `int` | Base for exponential backoff (doubles each attempt). |
| `$maxDelayMs` | `int` | Upper bound on any single computed delay, including Retry-After. |
| `$jitterMs` | `int` | Random extra delay added on top, to avoid synchronized retry storms. |

</details>

<details>
<summary><code>shouldRetry()</code></summary>

#### `DefaultRetryStrategy shouldRetry()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/DefaultRetryStrategy.php#L42)

```php
public function shouldRetry(string $method, HttpResponseInterface $response, int $attempt): bool
```

| Param | Type | Description |
|---|---|---|
| `$method` | `string` | — |
| `$response` | [`HttpResponseInterface`](#httpresponseinterface) | — |
| `$attempt` | `int` | — |

**Returns:** `bool`

</details>

<details>
<summary><code>getDelayMilliseconds()</code></summary>

#### `DefaultRetryStrategy getDelayMilliseconds()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/DefaultRetryStrategy.php#L62)

```php
public function getDelayMilliseconds(HttpResponseInterface $response, int $attempt): int
```

| Param | Type | Description |
|---|---|---|
| `$response` | [`HttpResponseInterface`](#httpresponseinterface) | — |
| `$attempt` | `int` | — |

**Returns:** `int`

</details>

<details>
<summary><code>parseRetryAfter()</code></summary>

#### `DefaultRetryStrategy parseRetryAfter()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/DefaultRetryStrategy.php#L80)

```php
private function parseRetryAfter(string $value): ?int
```

Parses a Retry-After header value (delay-seconds or an HTTP-date) into seconds from now.

| Param | Type | Description |
|---|---|---|
| `$value` | `string` | — |

**Returns:** `int\|null` — Null when absent or unparseable.

</details>

## `DownloadException`

*class* — `rafalmasiarek\HttpClient\Http\DownloadException` · [source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/DownloadException.php#L12)

Thrown when ChunkedFileDownloader cannot complete a download.

extends `\RuntimeException`

## `EventSourceClient`

*final class* — `rafalmasiarek\HttpClient\Http\EventSourceClient` · [source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/EventSourceClient.php#L19)

Consumes a "text/event-stream" (Server-Sent Events) endpoint, invoking a callback per parsed frame. Separate, callback-based API — not built on HttpClientInterface, since a stream delivers incrementally and CurlResponse only resolves whole responses. connect() blocks until the server closes, a transport error occurs, or $onEvent returns false.

**Depends on:** `DnsResolverInterface`

| Method | Visibility | Returns | Summary |
|---|---|---|---|
| [`__construct()`](#eventsourceclient-__construct) | public | — | — |
| [`connect()`](#eventsourceclient-connect) | public | `string\|null` | $options supports 'headers'/'verify_peer' (as in HttpClientInterface::request()) plus 'last_event_id' (string), sent as the Last-Event-ID header for a reconnect. |
| [`extractFrame()`](#eventsourceclient-extractframe) | private | `array{string, string}\|null` | Extracts the next complete, blank-line-terminated frame from $buffer, if any. |
| [`parseFrame()`](#eventsourceclient-parseframe) | private | `ServerSentEvent\|null` | — |
| [`formatHeaders()`](#eventsourceclient-formatheaders) | private | `list<string>` | — |
| [`resolveHostIp()`](#eventsourceclient-resolvehostip) | private | `string\|null` | — |
| [`buildResolveOption()`](#eventsourceclient-buildresolveoption) | private | `string\|null` | — |

<details>
<summary><code>__construct()</code></summary>

#### `EventSourceClient __construct()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/EventSourceClient.php#L25)

```php
public function __construct(DnsResolverInterface $dns)
```

| Param | Type | Description |
|---|---|---|
| `$dns` | `DnsResolverInterface` | Resolver used to pin the connection's target IP, same as CurlHttpClient. |

</details>

<details>
<summary><code>connect()</code></summary>

#### `EventSourceClient connect()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/EventSourceClient.php#L41)

```php
public function connect(string $url, callable $onEvent, array $options): ?string
```

$options supports 'headers'/'verify_peer' (as in HttpClientInterface::request()) plus 'last_event_id' (string), sent as the Last-Event-ID header for a reconnect.

| Param | Type | Description |
|---|---|---|
| `$url` | `string` | — |
| `$onEvent` | `callable` | — |
| `$options` | `array<string, mixed>` | — |

**Returns:** `string\|null` — Last "id:" seen on the stream (feed back as 'last_event_id' on a reconnect), or null if none arrived.

| Throws | When |
|---|---|
| `\RuntimeException` | On a connection failure before any data arrived. |

</details>

<details>
<summary><code>extractFrame()</code></summary>

#### `EventSourceClient extractFrame()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/EventSourceClient.php#L117)

```php
private function extractFrame(string $buffer): ?array
```

Extracts the next complete, blank-line-terminated frame from $buffer, if any.

| Param | Type | Description |
|---|---|---|
| `$buffer` | `string` | — |

**Returns:** `array{string, string}\|null` — [frame, remaining buffer], or null when no complete frame is available yet.

</details>

<details>
<summary><code>parseFrame()</code></summary>

#### `EventSourceClient parseFrame()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/EventSourceClient.php#L132)

```php
private function parseFrame(string $raw): ?ServerSentEvent
```

| Param | Type | Description |
|---|---|---|
| `$raw` | `string` | One frame's worth of lines (no trailing blank line). |

**Returns:** `ServerSentEvent\|null` — Null for a frame with no data line (e.g. a bare comment/retry frame).

</details>

<details>
<summary><code>formatHeaders()</code></summary>

#### `EventSourceClient formatHeaders()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/EventSourceClient.php#L175)

```php
private function formatHeaders(array $headers): array
```

| Param | Type | Description |
|---|---|---|
| `$headers` | `array<string, string\|list<string>>` | — |

**Returns:** `list<string>`

</details>

<details>
<summary><code>resolveHostIp()</code></summary>

#### `EventSourceClient resolveHostIp()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/EventSourceClient.php#L190)

```php
private function resolveHostIp(?string $host): ?string
```

| Param | Type | Description |
|---|---|---|
| `$host` | `string\|null` | — |

**Returns:** `string\|null`

</details>

<details>
<summary><code>buildResolveOption()</code></summary>

#### `EventSourceClient buildResolveOption()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/EventSourceClient.php#L212)

```php
private function buildResolveOption(string $url, string $host, string $ip): ?string
```

| Param | Type | Description |
|---|---|---|
| `$url` | `string` | — |
| `$host` | `string` | — |
| `$ip` | `string` | — |

**Returns:** `string\|null`

</details>

## `FileHttpCacheStore`

*final class* — `rafalmasiarek\HttpClient\Http\FileHttpCacheStore` · [source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/FileHttpCacheStore.php#L13)

Default HttpCacheStoreInterface backend: one JSON file per key. LOCK_EX on write only — not meant for high-concurrency shared caches.

implements [`HttpCacheStoreInterface`](#httpcachestoreinterface)

| Method | Visibility | Returns | Summary |
|---|---|---|---|
| [`__construct()`](#filehttpcachestore-__construct) | public | — | — |
| [`get()`](#filehttpcachestore-get) | public | `?array` | — |
| [`set()`](#filehttpcachestore-set) | public | `void` | — |
| [`path()`](#filehttpcachestore-path) | private | `string` | — |

<details>
<summary><code>__construct()</code></summary>

#### `FileHttpCacheStore __construct()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/FileHttpCacheStore.php#L18)

```php
public function __construct(string $directory)
```

| Param | Type | Description |
|---|---|---|
| `$directory` | `string` | Created on first write if it doesn't exist. |

</details>

<details>
<summary><code>get()</code></summary>

#### `FileHttpCacheStore get()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/FileHttpCacheStore.php#L23)

```php
public function get(string $key): ?array
```

| Param | Type | Description |
|---|---|---|
| `$key` | `string` | — |

**Returns:** `?array`

</details>

<details>
<summary><code>set()</code></summary>

#### `FileHttpCacheStore set()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/FileHttpCacheStore.php#L44)

```php
public function set(string $key, array $entry, int $ttl): void
```

| Param | Type | Description |
|---|---|---|
| `$key` | `string` | — |
| `$entry` | `array` | — |
| `$ttl` | `int` | — |

</details>

<details>
<summary><code>path()</code></summary>

#### `FileHttpCacheStore path()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/FileHttpCacheStore.php#L64)

```php
private function path(string $key): string
```

| Param | Type | Description |
|---|---|---|
| `$key` | `string` | — |

**Returns:** `string`

</details>

## `HttpCacheStoreInterface`

*interface* — `rafalmasiarek\HttpClient\Http\HttpCacheStoreInterface` · [source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/HttpCacheStoreInterface.php#L13)

Minimal storage contract for CachingHttpClient — not PSR-16/PSR-6, just get/set with a TTL, so a backend doesn't need a PSR cache dependency.

| Method | Visibility | Returns | Summary |
|---|---|---|---|
| [`get()`](#httpcachestoreinterface-get) | public | `array<string, mixed>\|null` | Returns the entry regardless of freshness — CachingHttpClient checks 'expires_at' itself, since a stale entry's validators are still needed. |
| [`set()`](#httpcachestoreinterface-set) | public | `void` | — |

<details>
<summary><code>get()</code></summary>

#### `HttpCacheStoreInterface get()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/HttpCacheStoreInterface.php#L22)

```php
public function get(string $key): ?array
```

Returns the entry regardless of freshness — CachingHttpClient checks 'expires_at' itself, since a stale entry's validators are still needed.

| Param | Type | Description |
|---|---|---|
| `$key` | `string` | — |

**Returns:** `array<string, mixed>\|null` — Null only when no entry exists.

</details>

<details>
<summary><code>set()</code></summary>

#### `HttpCacheStoreInterface set()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/HttpCacheStoreInterface.php#L30)

```php
public function set(string $key, array $entry, int $ttl): void
```

| Param | Type | Description |
|---|---|---|
| `$key` | `string` | — |
| `$entry` | `array<string, mixed>` | — |
| `$ttl` | `int` | Seconds until this entry should be treated as expired. |

</details>

## `HttpClientInterface`

*interface* — `rafalmasiarek\HttpClient\Http\HttpClientInterface` · [source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/HttpClientInterface.php#L12)

Minimal HTTP client contract, option shape inspired by Symfony HttpClient / Guzzle.

| Method | Visibility | Returns | Summary |
|---|---|---|---|
| [`request()`](#httpclientinterface-request) | public | [`HttpResponseInterface`](#httpresponseinterface) | Performs an HTTP request. Supported $options keys: - headers (array<string,string\|list<string>>) : request headers; an array value sends the same header name multiple times - query (array<string,mixed>) : appended to the URL as a query string - body (string\|array<string,mixed>) : request body. A string is sent as-is. An array is sent application/x-www-form-urlencoded, unless it contains a \CURLFile/\CURLStringFile value, in which case it's sent multipart/form-data (curl sets its own boundary + Content-Type). - json (mixed) : JSON-encoded request body; sets Content-Type: application/json (ignored if 'body' is also set) - timeout (float) : total request timeout in seconds - auth_basic (string\|array{0:string,1:string}) : HTTP Basic Auth, as "user:pass" or [user, pass] - verify_peer (bool) : verify the server's TLS certificate (default: true) - follow_redirects (bool) : follow HTTP redirects (default: true). Set false to inspect a redirect response itself (status + Location header) instead of the final destination. Implementations that follow redirects themselves (rather than delegating to the underlying transport) must strip Authorization/Cookie/ Proxy-Authorization headers and auth_basic when a redirect hop crosses origins (scheme, host, or port differs). - block_private_network (bool) : refuse to connect when the target host resolves to a private or reserved-range address (default: false), re-checked on every redirect hop. Intended for requests to externally-supplied URLs (e.g. a user-submitted link a background job fetches) as an SSRF guard — leave unset for requests to hardcoded, trusted endpoints. - local_cert (string) : client certificate path, for mTLS - local_pk (string) : client private key path, when separate from local_cert - passphrase (string) : client private key passphrase - cafile (string) : custom CA bundle path - max_connect_duration (float) : limits only the connect phase (DNS+TCP+TLS), in seconds, separately from 'timeout' which bounds the whole request. Not every implementation supports this distinction. - on_progress (callable(int $dlNow, int $dlSize, array $info): void) : called periodically as the transfer progresses (at least once per second while data is flowing; $dlSize is -1 when unknown). A thrown exception aborts the transfer and is reported via HttpResponseInterface::getError(). Not every implementation calls this. Returns a lazy response: no network I/O has necessarily happened yet when this method returns. The first call to any accessor on the returned object (getStatusCode(), getHeaders(), getContent(), ...) drives the request to completion. Calling request() several times before reading any of the responses lets an implementation that shares a single underlying transport (e.g. one curl_multi handle) run them concurrently; reading a response immediately makes that one call behave synchronously. |

<details>
<summary><code>request()</code></summary>

#### `HttpClientInterface request()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/HttpClientInterface.php#L69)

```php
public function request(string $method, string $url, array $options): HttpResponseInterface
```

Performs an HTTP request. Supported $options keys: - headers     (array<string,string|list<string>>) : request headers; an array value sends the same header name multiple times - query       (array<string,mixed>) : appended to the URL as a query string - body        (string|array<string,mixed>) : request body. A string is sent as-is. An array is sent application/x-www-form-urlencoded, unless it contains a \CURLFile/\CURLStringFile value, in which case it's sent multipart/form-data (curl sets its own boundary + Content-Type). - json        (mixed)               : JSON-encoded request body; sets Content-Type: application/json (ignored if 'body' is also set) - timeout     (float)               : total request timeout in seconds - auth_basic  (string|array{0:string,1:string}) : HTTP Basic Auth, as "user:pass" or [user, pass] - verify_peer (bool)                : verify the server's TLS certificate (default: true) - follow_redirects (bool)           : follow HTTP redirects (default: true). Set false to inspect a redirect response itself (status + Location header) instead of the final destination. Implementations that follow redirects themselves (rather than delegating to the underlying transport) must strip Authorization/Cookie/ Proxy-Authorization headers and auth_basic when a redirect hop crosses origins (scheme, host, or port differs). - block_private_network (bool)      : refuse to connect when the target host resolves to a private or reserved-range address (default: false), re-checked on every redirect hop. Intended for requests to externally-supplied URLs (e.g. a user-submitted link a background job fetches) as an SSRF guard — leave unset for requests to hardcoded, trusted endpoints. - local_cert  (string)               : client certificate path, for mTLS - local_pk    (string)               : client private key path, when separate from local_cert - passphrase  (string)               : client private key passphrase - cafile      (string)               : custom CA bundle path - max_connect_duration (float)       : limits only the connect phase (DNS+TCP+TLS), in seconds, separately from 'timeout' which bounds the whole request. Not every implementation supports this distinction. - on_progress (callable(int $dlNow, int $dlSize, array $info): void) : called periodically as the transfer progresses (at least once per second while data is flowing; $dlSize is -1 when unknown). A thrown exception aborts the transfer and is reported via HttpResponseInterface::getError(). Not every implementation calls this. Returns a lazy response: no network I/O has necessarily happened yet when this method returns. The first call to any accessor on the returned object (getStatusCode(), getHeaders(), getContent(), ...) drives the request to completion. Calling request() several times before reading any of the responses lets an implementation that shares a single underlying transport (e.g. one curl_multi handle) run them concurrently; reading a response immediately makes that one call behave synchronously.

| Param | Type | Description |
|---|---|---|
| `$method` | `string` | HTTP method (GET, POST, ...). |
| `$url` | `string` | Absolute URL. |
| `$options` | `array<string, mixed>` | See above. |

**Returns:** [`HttpResponseInterface`](#httpresponseinterface)

</details>

## `HttpResponse`

*final readonly class* — `rafalmasiarek\HttpClient\Http\HttpResponse` · [source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/HttpResponse.php#L19)

Eager (already-resolved) HttpResponseInterface implementation. Useful for constructing a synthetic response (CachingHttpClient serving a cached entry, a test double, ...) where there's no real lazy transfer to drive — every accessor just returns the value given at construction time. CurlHttpClient does not return this class for a real network request; it returns CurlResponse, which is genuinely lazy. See HttpResponseInterface.

implements [`HttpResponseInterface`](#httpresponseinterface)

**Depends on:** [`HttpTransportDebug`](#httptransportdebug), [`TransportErrorKind`](#transporterrorkind)

| Method | Visibility | Returns | Summary |
|---|---|---|---|
| [`__construct()`](#httpresponse-__construct) | public | — | — |
| [`getStatusCode()`](#httpresponse-getstatuscode) | public | `int` | — |
| [`getHeaders()`](#httpresponse-getheaders) | public | `array` | — |
| [`getHeader()`](#httpresponse-getheader) | public | `?string` | — |
| [`getHeaderLine()`](#httpresponse-getheaderline) | public | `string` | — |
| [`getContent()`](#httpresponse-getcontent) | public | `string` | — |
| [`json()`](#httpresponse-json) | public | `mixed` | — |
| [`isSuccessful()`](#httpresponse-issuccessful) | public | `bool` | — |
| [`getError()`](#httpresponse-geterror) | public | `?string` | — |
| [`getErrorKind()`](#httpresponse-geterrorkind) | public | ?[`TransportErrorKind`](#transporterrorkind) | — |
| [`getInfo()`](#httpresponse-getinfo) | public | `mixed` | — |
| [`cancel()`](#httpresponse-cancel) | public | `void` | — |

<details>
<summary><code>__construct()</code></summary>

#### `HttpResponse __construct()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/HttpResponse.php#L32)

```php
public function __construct(int $statusCode, array $headers, string $body, ?string $error, ?HttpTransportDebug $debug, ?TransportErrorKind $errorKind)
```

| Param | Type | Description |
|---|---|---|
| `$statusCode` | `int` | HTTP status code. 0 when the request never reached the server. |
| `$headers` | `array<string, list<string>>` | Response headers, lowercase names, all values per name preserved (e.g. multiple Set-Cookie headers). |
| `$body` | `string` | Response body. |
| `$error` | `string\|null` | curl error message, when the request failed at the transport level. |
| `$debug` | `HttpTransportDebug\|null` | Timing/target info from curl_getinfo(), for diagnosing connectivity failures. Not populated by every HttpClientInterface implementation. |
| `$errorKind` | `TransportErrorKind\|null` | Structured classification of $error, when known. |

</details>

<details>
<summary><code>getStatusCode()</code></summary>

#### `HttpResponse getStatusCode()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/HttpResponse.php#L42)

```php
public function getStatusCode(): int
```

**Returns:** `int`

</details>

<details>
<summary><code>getHeaders()</code></summary>

#### `HttpResponse getHeaders()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/HttpResponse.php#L47)

```php
public function getHeaders(): array
```

**Returns:** `array`

</details>

<details>
<summary><code>getHeader()</code></summary>

#### `HttpResponse getHeader()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/HttpResponse.php#L52)

```php
public function getHeader(string $name): ?string
```

| Param | Type | Description |
|---|---|---|
| `$name` | `string` | — |

**Returns:** `?string`

</details>

<details>
<summary><code>getHeaderLine()</code></summary>

#### `HttpResponse getHeaderLine()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/HttpResponse.php#L57)

```php
public function getHeaderLine(string $name): string
```

| Param | Type | Description |
|---|---|---|
| `$name` | `string` | — |

**Returns:** `string`

</details>

<details>
<summary><code>getContent()</code></summary>

#### `HttpResponse getContent()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/HttpResponse.php#L62)

```php
public function getContent(): string
```

**Returns:** `string`

</details>

<details>
<summary><code>json()</code></summary>

#### `HttpResponse json()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/HttpResponse.php#L67)

```php
public function json(): mixed
```

**Returns:** `mixed`

</details>

<details>
<summary><code>isSuccessful()</code></summary>

#### `HttpResponse isSuccessful()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/HttpResponse.php#L72)

```php
public function isSuccessful(): bool
```

**Returns:** `bool`

</details>

<details>
<summary><code>getError()</code></summary>

#### `HttpResponse getError()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/HttpResponse.php#L77)

```php
public function getError(): ?string
```

**Returns:** `?string`

</details>

<details>
<summary><code>getErrorKind()</code></summary>

#### `HttpResponse getErrorKind()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/HttpResponse.php#L82)

```php
public function getErrorKind(): ?TransportErrorKind
```

**Returns:** ?[`TransportErrorKind`](#transporterrorkind)

</details>

<details>
<summary><code>getInfo()</code></summary>

#### `HttpResponse getInfo()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/HttpResponse.php#L87)

```php
public function getInfo(?string $key): mixed
```

| Param | Type | Description |
|---|---|---|
| `$key` | `?string` | — |

**Returns:** `mixed`

</details>

<details>
<summary><code>cancel()</code></summary>

#### `HttpResponse cancel()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/HttpResponse.php#L95)

```php
public function cancel(): void
```

</details>

## `HttpResponseInterface`

*interface* — `rafalmasiarek\HttpClient\Http\HttpResponseInterface` · [source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/HttpResponseInterface.php#L17)

Lazy HTTP response. request() returns one immediately; the first accessor call below drives the transfer to completion. Firing several request() calls before reading any response runs them concurrently. No header-only wait, no chunked streaming — one accessor call resolves everything. Transport failures are reported via getError(), not exceptions.

| Method | Visibility | Returns | Summary |
|---|---|---|---|
| [`getStatusCode()`](#httpresponseinterface-getstatuscode) | public | `int` | — |
| [`getHeaders()`](#httpresponseinterface-getheaders) | public | `array<string, list<string>>` | — |
| [`getHeader()`](#httpresponseinterface-getheader) | public | `string\|null` | — |
| [`getHeaderLine()`](#httpresponseinterface-getheaderline) | public | `string` | — |
| [`getContent()`](#httpresponseinterface-getcontent) | public | `string` | — |
| [`json()`](#httpresponseinterface-json) | public | `mixed` | — |
| [`isSuccessful()`](#httpresponseinterface-issuccessful) | public | `bool` | — |
| [`getError()`](#httpresponseinterface-geterror) | public | `string\|null` | — |
| [`getErrorKind()`](#httpresponseinterface-geterrorkind) | public | `TransportErrorKind\|null` | Structured classification of getError() — for a caller that needs to branch on *why* a request failed without string-matching getError()'s free-text message. Based on curl's own error code (e.g. CURLE_OPERATION_TIMEDOUT), not the message text, so it's stable across curl/PHP versions. Null whenever getError() is null. |
| [`getInfo()`](#httpresponseinterface-getinfo) | public | `mixed` | Non-blocking transport metadata — never drives the transfer forward, so it's safe to call at any time, including before this response has been read. |
| [`cancel()`](#httpresponseinterface-cancel) | public | `void` | Aborts the request if it hasn't finished yet. A no-op once the response is complete. After this, getStatusCode() is 0 and getError() reports the cancellation. |

<details>
<summary><code>getStatusCode()</code></summary>

#### `HttpResponseInterface getStatusCode()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/HttpResponseInterface.php#L22)

```php
public function getStatusCode(): int
```

**Returns:** `int` — HTTP status code. 0 when the request never reached the server.

</details>

<details>
<summary><code>getHeaders()</code></summary>

#### `HttpResponseInterface getHeaders()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/HttpResponseInterface.php#L28)

```php
public function getHeaders(): array
```

**Returns:** `array<string, list<string>>` — Lowercase header names, all values per name preserved (e.g. multiple Set-Cookie).

</details>

<details>
<summary><code>getHeader()</code></summary>

#### `HttpResponseInterface getHeader()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/HttpResponseInterface.php#L34)

```php
public function getHeader(string $name): ?string
```

| Param | Type | Description |
|---|---|---|
| `$name` | `string` | Header name, case-insensitive. |

**Returns:** `string\|null` — The first value, or null when absent.

</details>

<details>
<summary><code>getHeaderLine()</code></summary>

#### `HttpResponseInterface getHeaderLine()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/HttpResponseInterface.php#L40)

```php
public function getHeaderLine(string $name): string
```

| Param | Type | Description |
|---|---|---|
| `$name` | `string` | Header name, case-insensitive. |

**Returns:** `string` — All values, comma-joined (PSR-7 convention), or empty when absent.

</details>

<details>
<summary><code>getContent()</code></summary>

#### `HttpResponseInterface getContent()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/HttpResponseInterface.php#L45)

```php
public function getContent(): string
```

**Returns:** `string` — Response body.

</details>

<details>
<summary><code>json()</code></summary>

#### `HttpResponseInterface json()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/HttpResponseInterface.php#L50)

```php
public function json(): mixed
```

**Returns:** `mixed` — Body decoded as JSON, or null when it isn't valid JSON.

</details>

<details>
<summary><code>isSuccessful()</code></summary>

#### `HttpResponseInterface isSuccessful()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/HttpResponseInterface.php#L55)

```php
public function isSuccessful(): bool
```

**Returns:** `bool` — True when the request completed with no transport error and a 2xx status.

</details>

<details>
<summary><code>getError()</code></summary>

#### `HttpResponseInterface getError()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/HttpResponseInterface.php#L63)

```php
public function getError(): ?string
```

**Returns:** `string\|null` — Transport-level error (DNS failure, connection refused, timeout, a block_private_network refusal, ...), or null when none occurred. A non-2xx HTTP status that the server actually returned is not an error by itself — check getStatusCode() for that.

</details>

<details>
<summary><code>getErrorKind()</code></summary>

#### `HttpResponseInterface getErrorKind()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/HttpResponseInterface.php#L74)

```php
public function getErrorKind(): ?TransportErrorKind
```

Structured classification of getError() — for a caller that needs to branch on *why* a request failed without string-matching getError()'s free-text message. Based on curl's own error code (e.g. CURLE_OPERATION_TIMEDOUT), not the message text, so it's stable across curl/PHP versions. Null whenever getError() is null.

**Returns:** `TransportErrorKind\|null`

</details>

<details>
<summary><code>getInfo()</code></summary>

#### `HttpResponseInterface getInfo()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/HttpResponseInterface.php#L84)

```php
public function getInfo(?string $key): mixed
```

Non-blocking transport metadata — never drives the transfer forward, so it's safe to call at any time, including before this response has been read.

| Param | Type | Description |
|---|---|---|
| `$key` | `string\|null` | When given, returns just that key's value (or null if absent/not yet known); when null, returns the full info array. |

**Returns:** `mixed`

</details>

<details>
<summary><code>cancel()</code></summary>

#### `HttpResponseInterface cancel()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/HttpResponseInterface.php#L92)

```php
public function cancel(): void
```

Aborts the request if it hasn't finished yet. A no-op once the response is complete. After this, getStatusCode() is 0 and getError() reports the cancellation.

</details>

## `HttpTransportDebug`

*final readonly class* — `rafalmasiarek\HttpClient\Http\HttpTransportDebug` · [source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/HttpTransportDebug.php#L14)

Low-level transport timing/target info for a single request, sourced from curl_getinfo(). Meant for diagnosing connectivity failures (DNS, connect, TLS, timeout) — not for routine success-path use.

| Method | Visibility | Returns | Summary |
|---|---|---|---|
| [`__construct()`](#httptransportdebug-__construct) | public | — | — |
| [`fromCurlInfo()`](#httptransportdebug-fromcurlinfo) | public static | `self` | Builds an instance from a raw curl_getinfo() array. |
| [`toArray()`](#httptransportdebug-toarray) | public | `array<string, float\|string\|int\|null>` | — |

<details>
<summary><code>__construct()</code></summary>

#### `HttpTransportDebug __construct()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/HttpTransportDebug.php#L24)

```php
public function __construct(float $namelookupTime, float $connectTime, float $appconnectTime, float $totalTime, ?string $primaryIp, ?int $primaryPort)
```

| Param | Type | Description |
|---|---|---|
| `$namelookupTime` | `float` | Seconds from start until name resolution completed. |
| `$connectTime` | `float` | Seconds from start until the TCP connection was established. |
| `$appconnectTime` | `float` | Seconds from start until the TLS/SSL handshake completed (0 for plain HTTP). |
| `$totalTime` | `float` | Total seconds for the whole request. |
| `$primaryIp` | `string\|null` | IP address actually connected to, or null when the connection never got that far. |
| `$primaryPort` | `int\|null` | Port actually connected to, or null when the connection never got that far. |

</details>

<details>
<summary><code>fromCurlInfo()</code></summary>

#### `HttpTransportDebug fromCurlInfo()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/HttpTransportDebug.php#L41)

```php
public static function fromCurlInfo(array $info): self
```

Builds an instance from a raw curl_getinfo() array.

| Param | Type | Description |
|---|---|---|
| `$info` | `array<string, mixed>` | Result of curl_getinfo($ch). |

**Returns:** `self`

</details>

<details>
<summary><code>toArray()</code></summary>

#### `HttpTransportDebug toArray()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/HttpTransportDebug.php#L56)

```php
public function toArray(): array
```

**Returns:** `array<string, float\|string\|int\|null>`

</details>

## `RetryHttpClient`

*final class* — `rafalmasiarek\HttpClient\Http\RetryHttpClient` · [source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/RetryHttpClient.php#L20)

Decorates an HttpClientInterface with retry-on-transient-failure behavior, so individual callers don't each reimplement their own retry loop. Delegates the actual retry/idempotency policy to a RetryStrategyInterface — this class only owns the attempt loop, the sleep between attempts, which base URI (if more than one was given) each attempt uses, and (when a logger is given) reporting each retry.

implements [`HttpClientInterface`](#httpclientinterface)

**Depends on:** [`RetryStrategyInterface`](#retrystrategyinterface), `LoggerInterface`

| Method | Visibility | Returns | Summary |
|---|---|---|---|
| [`__construct()`](#retryhttpclient-__construct) | public | — | — |
| [`request()`](#retryhttpclient-request) | public | [`HttpResponseInterface`](#httpresponseinterface) | Supports one extra option beyond HttpClientInterface::request(): 'base_uri' (string, or list<string>). When a list is given, $url is treated as a path appended to whichever base the current attempt uses — the first attempt uses index 0, each retry advances to the next base (clamped to the last one once retries outnumber the list), so a failing host doesn't get retried against itself when an alternate is available. |
| [`normalizeBaseUris()`](#retryhttpclient-normalizebaseuris) | private | `list<string>\|null` | — |
| [`buildUrlForAttempt()`](#retryhttpclient-buildurlforattempt) | private | `string` | — |

<details>
<summary><code>__construct()</code></summary>

#### `RetryHttpClient __construct()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/RetryHttpClient.php#L28)

```php
public function __construct(HttpClientInterface $client, RetryStrategyInterface $strategy, ?LoggerInterface $logger)
```

| Param | Type | Description |
|---|---|---|
| `$client` | [`HttpClientInterface`](#httpclientinterface) | Underlying transport. |
| `$strategy` | [`RetryStrategyInterface`](#retrystrategyinterface) | Retry policy. |
| `$logger` | `LoggerInterface\|null` | Receives an 'http.request.retry' debug entry for every retried attempt. Optional. |

</details>

<details>
<summary><code>request()</code></summary>

#### `RetryHttpClient request()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/RetryHttpClient.php#L50)

```php
public function request(string $method, string $url, array $options): HttpResponseInterface
```

Supports one extra option beyond HttpClientInterface::request(): 'base_uri' (string, or list<string>). When a list is given, $url is treated as a path appended to whichever base the current attempt uses — the first attempt uses index 0, each retry advances to the next base (clamped to the last one once retries outnumber the list), so a failing host doesn't get retried against itself when an alternate is available.

| Param | Type | Description |
|---|---|---|
| `$method` | `string` | — |
| `$url` | `string` | — |
| `$options` | `array<string, mixed>` | — |

**Returns:** [`HttpResponseInterface`](#httpresponseinterface) — The first response the strategy stops retrying on — may itself be an error/non-2xx response; this method never throws for a failure the strategy declines to retry.

</details>

<details>
<summary><code>normalizeBaseUris()</code></summary>

#### `RetryHttpClient normalizeBaseUris()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/RetryHttpClient.php#L88)

```php
private function normalizeBaseUris(mixed $baseUri): ?array
```

| Param | Type | Description |
|---|---|---|
| `$baseUri` | `mixed` | — |

**Returns:** `list<string>\|null` — Null when absent/empty — request() then uses $url as-is every attempt.

</details>

<details>
<summary><code>buildUrlForAttempt()</code></summary>

#### `RetryHttpClient buildUrlForAttempt()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/RetryHttpClient.php#L108)

```php
private function buildUrlForAttempt(array $baseUris, string $path, int $attempt): string
```

| Param | Type | Description |
|---|---|---|
| `$baseUris` | `list<string>` | — |
| `$path` | `string` | — |
| `$attempt` | `int` | 1-based. |

**Returns:** `string`

</details>

## `RetryStrategyInterface`

*interface* — `rafalmasiarek\HttpClient\Http\RetryStrategyInterface` · [source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/RetryStrategyInterface.php#L13)

Decides whether and how long to wait before retrying a request, given the response RetryHttpClient just received (or the transport error it carries).

| Method | Visibility | Returns | Summary |
|---|---|---|---|
| [`shouldRetry()`](#retrystrategyinterface-shouldretry) | public | `bool` | — |
| [`getDelayMilliseconds()`](#retrystrategyinterface-getdelaymilliseconds) | public | `int` | — |

<details>
<summary><code>shouldRetry()</code></summary>

#### `RetryStrategyInterface shouldRetry()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/RetryStrategyInterface.php#L23)

```php
public function shouldRetry(string $method, HttpResponseInterface $response, int $attempt): bool
```

| Param | Type | Description |
|---|---|---|
| `$method` | `string` | HTTP method of the request that was just attempted. |
| `$response` | [`HttpResponseInterface`](#httpresponseinterface) | The response received (getStatusCode() 0 and a non-null getError() mean a transport-level failure, not an HTTP response). |
| `$attempt` | `int` | 1-based count of attempts made so far, including this one. |

**Returns:** `bool` — True to retry.

</details>

<details>
<summary><code>getDelayMilliseconds()</code></summary>

#### `RetryStrategyInterface getDelayMilliseconds()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/RetryStrategyInterface.php#L30)

```php
public function getDelayMilliseconds(HttpResponseInterface $response, int $attempt): int
```

| Param | Type | Description |
|---|---|---|
| `$response` | [`HttpResponseInterface`](#httpresponseinterface) | The response that triggered the retry. |
| `$attempt` | `int` | 1-based count of attempts made so far, including this one. |

**Returns:** `int` — Milliseconds to wait before the next attempt.

</details>

## `ServerSentEvent`

*final readonly class* — `rafalmasiarek\HttpClient\Http\ServerSentEvent` · [source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/ServerSentEvent.php#L13)

One parsed "text/event-stream" frame (a blank-line-terminated block of id:/event:/data:/retry: lines), as delivered by EventSourceClient.

| Method | Visibility | Returns | Summary |
|---|---|---|---|
| [`__construct()`](#serversentevent-__construct) | public | — | — |
| [`getArrayData()`](#serversentevent-getarraydata) | public | `mixed` | — |

<details>
<summary><code>__construct()</code></summary>

#### `ServerSentEvent __construct()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/ServerSentEvent.php#L23)

```php
public function __construct(string $data, string $event, ?string $id, ?int $retry)
```

| Param | Type | Description |
|---|---|---|
| `$data` | `string` | Concatenation of every "data:" line in the frame, joined by "\n" (per the SSE spec) — empty string when the frame had no data line. |
| `$event` | `string` | Event type. "message" when the frame had no explicit "event:" line. |
| `$id` | `string\|null` | Last "id:" value seen (persists across frames per the spec, but EventSourceClient hands you a single frame's view, not the running value). |
| `$retry` | `int\|null` | Reconnection time in milliseconds, when the frame set one. |

</details>

<details>
<summary><code>getArrayData()</code></summary>

#### `ServerSentEvent getArrayData()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/ServerSentEvent.php#L34)

```php
public function getArrayData(): mixed
```

**Returns:** `mixed` — Decoded JSON, or null when $data isn't valid JSON.

</details>

## `ThrottlingHttpClient`

*final class* — `rafalmasiarek\HttpClient\Http\ThrottlingHttpClient` · [source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/ThrottlingHttpClient.php#L16)

Decorates an HttpClientInterface with a token-bucket rate limit. In-process only — bounds one PHP process's rate, not a ceiling shared across workers/servers. request() blocks until a token is free; no "429, try later" path, since this is for self-imposed outbound pacing.

implements [`HttpClientInterface`](#httpclientinterface)

| Property | Type | Visibility | Description |
|---|---|---|---|
| `$tokens` | `float` | private | Current token count, replenished lazily on each request()/wait check. |
| `$lastRefill` | `float` | private | microtime(true) of the last refill computation. |

| Method | Visibility | Returns | Summary |
|---|---|---|---|
| [`__construct()`](#throttlinghttpclient-__construct) | public | — | — |
| [`request()`](#throttlinghttpclient-request) | public | [`HttpResponseInterface`](#httpresponseinterface) | — |
| [`waitForToken()`](#throttlinghttpclient-waitfortoken) | private | `void` | — |
| [`refill()`](#throttlinghttpclient-refill) | private | `void` | — |

<details>
<summary><code>__construct()</code></summary>

#### `ThrottlingHttpClient __construct()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/ThrottlingHttpClient.php#L29)

```php
public function __construct(HttpClientInterface $client, float $maxTokens, float $refillPerSecond)
```

| Param | Type | Description |
|---|---|---|
| `$client` | [`HttpClientInterface`](#httpclientinterface) | Underlying transport. |
| `$maxTokens` | `float` | Bucket capacity — the largest burst allowed. |
| `$refillPerSecond` | `float` | Tokens added per second (the steady-state rate). |

</details>

<details>
<summary><code>request()</code></summary>

#### `ThrottlingHttpClient request()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/ThrottlingHttpClient.php#L44)

```php
public function request(string $method, string $url, array $options): HttpResponseInterface
```

| Param | Type | Description |
|---|---|---|
| `$method` | `string` | — |
| `$url` | `string` | — |
| `$options` | `array<string, mixed>` | — |

**Returns:** [`HttpResponseInterface`](#httpresponseinterface)

</details>

<details>
<summary><code>waitForToken()</code></summary>

#### `ThrottlingHttpClient waitForToken()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/ThrottlingHttpClient.php#L53)

```php
private function waitForToken(): void
```

</details>

<details>
<summary><code>refill()</code></summary>

#### `ThrottlingHttpClient refill()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/ThrottlingHttpClient.php#L68)

```php
private function refill(): void
```

</details>

## `TransportErrorKind`

*enum* — `rafalmasiarek\HttpClient\Http\TransportErrorKind` · [source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/TransportErrorKind.php#L17)

Structured classification of a transport-level failure — HttpResponseInterface::getError()'s companion for callers that need to branch on *why* a request failed without string-matching getError()'s free-text message (which is only ever curl_strerror()'s exact English wording, not guaranteed stable, and not comparable across a different HttpClientInterface implementation).

| Case | Value |
|---|---|
| `Timeout` | `—` |
| `DnsFailure` | `—` |
| `ConnectionRefused` | `—` |
| `Tls` | `—` |
| `Blocked` | `—` |
| `Canceled` | `—` |
| `Unknown` | `—` |

| Method | Visibility | Returns | Summary |
|---|---|---|---|
| [`fromCurlErrno()`](#transporterrorkind-fromcurlerrno) | public static | `self` | Classifies a curl_errno() value (a CURLE_* constant) into this enum. |

<details>
<summary><code>fromCurlErrno()</code></summary>

#### `TransportErrorKind fromCurlErrno()`

[source](https://github.com/rafalmasiarek/php-http-client/blob/main/src/Http/TransportErrorKind.php#L48)

```php
public static function fromCurlErrno(int $errno): self
```

Classifies a curl_errno() value (a CURLE_* constant) into this enum.

| Param | Type | Description |
|---|---|---|
| `$errno` | `int` | — |

**Returns:** `self`

</details>



## License

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

```text
The MIT License (MIT)

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files, to deal in the Software
without restriction.

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND.

Source: https://opensource.org/licenses/MIT
```


## Authors

- Rafał Masiarek | [website](https://masiarek.pl) | [github](https://github.com/rafalmasiarek)


<!-- references -->

[repo_link]: https://github.com/rafalmasiarek/php-http-client
