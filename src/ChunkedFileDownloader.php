<?php

declare(strict_types=1);

namespace rafalmasiarek\HttpClient\Http;

/**
 * Downloads a large file in range-requested chunks, retrying failed chunks
 * individually instead of restarting the whole transfer, and resuming from
 * an existing partial download when the destination already has one.
 *
 * Writes to a "{destination}.part" sidecar and only renames it onto the real
 * destination once the transfer completes successfully — a reader can never
 * observe a truncated file at the destination path.
 *
 * A resume is only trusted when a "{destination}.part.meta" sidecar recorded
 * the source's ETag/Last-Modified from the response that started the partial
 * file. Without it (missing, or an older download that predates this file),
 * the partial is discarded and the download restarts from scratch — resuming
 * an unvalidated partial risks silently splicing bytes from two different
 * versions of the resource into one corrupt file. When a validated resume
 * gets a 200 back instead of 206 (server ignored If-Range because the
 * resource changed, or doesn't support Range at all), the partial and its
 * metadata are discarded and the download restarts once, from that response.
 *
 * Falls back to a single, unranged request when the server ignores the
 * Range header on a fresh (offset 0) download — a server that can't resume
 * mid-download can't be resumed at all.
 *
 * @package rafalmasiarek\HttpClient\Http
 */
final class ChunkedFileDownloader
{
    /**
     * @param HttpClientInterface $http               Client used to fetch each chunk.
     * @param int                 $chunkSize          Bytes requested per range, e.g. 8 MiB.
     * @param int                 $maxRetriesPerChunk Attempts per chunk before giving up.
     */
    public function __construct(
        private readonly HttpClientInterface $http,
        private readonly int $chunkSize = 8 * 1024 * 1024,
        private readonly int $maxRetriesPerChunk = 3,
    ) {
    }

    /**
     * Downloads $url to $destinationPath.
     *
     * @param  string               $url             Absolute URL.
     * @param  string               $destinationPath Final file path; a same-directory ".part" sidecar
     *                                                is used during the transfer and atomically renamed
     *                                                onto this path only once it completes successfully.
     * @param  array<string, mixed> $requestOptions  Extra HttpClientInterface::request() options
     *                                                (e.g. headers, timeout, local_cert) merged into
     *                                                every chunk request. 'Range'/'If-Range' headers,
     *                                                if present, are overridden per chunk.
     * @return void
     * @throws DownloadException On a write failure, an unexpected status code, a server that can't
     *                           resume a partial download, or a chunk that exhausts its retries.
     */
    public function download(string $url, string $destinationPath, array $requestOptions = []): void
    {
        $partPath = $destinationPath . '.part';
        $metaPath = $partPath . '.meta';

        $this->attempt($url, $partPath, $metaPath, $requestOptions, false);

        if (!@\rename($partPath, $destinationPath)) {
            throw new DownloadException("Unable to move \"{$partPath}\" to \"{$destinationPath}\".");
        }
        @\unlink($metaPath);
    }

    /**
     * Runs one download pass against $partPath, restarting itself exactly once
     * (via $restarted) when a trusted resume turns out to be invalid.
     *
     * @param  string               $url
     * @param  string               $partPath
     * @param  string               $metaPath
     * @param  array<string, mixed> $requestOptions
     * @param  bool                 $restarted Whether this call is already a post-restart retry.
     * @return void
     * @throws DownloadException
     */
    private function attempt(string $url, string $partPath, string $metaPath, array $requestOptions, bool $restarted): void
    {
        $ifRange = null;
        $offset = 0;

        if (\is_file($partPath)) {
            $ifRange = $this->readIfRange($metaPath);
            if ($ifRange === null) {
                // No trustworthy validator for this partial — can't confirm the remote
                // resource hasn't changed since it was started. Discard rather than
                // risk splicing bytes from two different versions of the resource.
                @\unlink($partPath);
                @\unlink($metaPath);
            } else {
                $offset = (int) \filesize($partPath);
            }
        }

        $handle = \fopen($partPath, $offset > 0 ? 'ab' : 'wb');
        if ($handle === false) {
            throw new DownloadException("Unable to open \"{$partPath}\" for writing.");
        }

        $closed = false;

        try {
            $total = null;

            while ($total === null || $offset < $total) {
                $end = $offset + $this->chunkSize - 1;
                if ($total !== null) {
                    // Some servers reject a range end past the resource size with 416
                    // instead of clamping it themselves, so clamp the final chunk here.
                    $end = \min($end, $total - 1);
                }

                $options = $requestOptions;
                $headers = (array) ($options['headers'] ?? []);
                $headers['Range'] = "bytes={$offset}-{$end}";
                if ($offset > 0 && $ifRange !== null) {
                    $headers['If-Range'] = $ifRange;
                }
                $options['headers'] = $headers;

                $chunk = $this->fetchChunkWithRetry($url, $options);

                if ($chunk->getStatusCode() === 200) {
                    if ($offset > 0) {
                        // If-Range wasn't honored (resource changed, or the server just
                        // doesn't support conditional range requests) — the bytes we
                        // hold no longer safely belong with this response.
                        \fclose($handle);
                        $closed = true;

                        if ($restarted) {
                            throw new DownloadException(
                                "Remote resource for \"{$url}\" kept changing across restart attempts."
                            );
                        }

                        @\unlink($partPath);
                        @\unlink($metaPath);
                        $this->attempt($url, $partPath, $metaPath, $requestOptions, true);
                        return;
                    }

                    $this->writeIfRange($metaPath, $chunk);
                    if (\fwrite($handle, $chunk->getContent()) === false) {
                        throw new DownloadException("Write failure to \"{$partPath}\".");
                    }
                    return;
                }

                if ($chunk->getStatusCode() === 416) {
                    if ($total === null) {
                        throw new DownloadException(
                            "Server rejected the range request for \"{$url}\" (416) before the total size was known."
                        );
                    }
                    break;
                }

                if ($chunk->getStatusCode() !== 206) {
                    throw new DownloadException(
                        "Unexpected status {$chunk->getStatusCode()} fetching \"{$url}\" range \"{$headers['Range']}\"."
                    );
                }

                if ($total === null) {
                    $total = $this->parseTotalSize($chunk->getHeaderLine('Content-Range'));
                }

                if ($ifRange === null) {
                    $ifRange = $this->writeIfRange($metaPath, $chunk);
                }

                if (\fwrite($handle, $chunk->getContent()) === false) {
                    throw new DownloadException("Write failure to \"{$partPath}\".");
                }

                $received = \strlen($chunk->getContent());
                if ($received === 0) {
                    break;
                }

                $offset += $received;
            }
        } finally {
            if (!$closed) {
                \fclose($handle);
            }
        }
    }

    /**
     * Fetches one chunk, retrying on transport failure.
     *
     * @param  string               $url
     * @param  array<string, mixed> $options Already carries the Range/If-Range headers for this chunk.
     * @return HttpResponseInterface
     * @throws DownloadException When every attempt fails.
     */
    private function fetchChunkWithRetry(string $url, array $options): HttpResponseInterface
    {
        $lastError = null;

        for ($attempt = 1; $attempt <= $this->maxRetriesPerChunk; $attempt++) {
            $response = $this->http->request('GET', $url, $options);
            if ($response->getError() === null) {
                return $response;
            }

            $lastError = $response->getError();
        }

        $range = (string) (((array) ($options['headers'] ?? []))['Range'] ?? '');
        throw new DownloadException(
            "Failed to fetch range \"{$range}\" from \"{$url}\" after {$this->maxRetriesPerChunk} attempt(s): {$lastError}"
        );
    }

    /**
     * Extracts the total resource size from a Content-Range header value ("bytes start-end/total").
     *
     * @param  string $contentRange
     * @return int|null
     */
    private function parseTotalSize(string $contentRange): ?int
    {
        return \preg_match('#/(\d+)$#', $contentRange, $m) === 1 ? (int) $m[1] : null;
    }

    /**
     * Reads the If-Range validator persisted for an existing partial download.
     *
     * @param  string $metaPath
     * @return string|null The ETag or Last-Modified value to send as If-Range, or null when
     *                      no (trustworthy) validator was recorded.
     */
    private function readIfRange(string $metaPath): ?string
    {
        if (!\is_file($metaPath)) {
            return null;
        }

        $raw = @\file_get_contents($metaPath);
        if ($raw === false || $raw === '') {
            return null;
        }

        $decoded = \json_decode($raw, true);
        if (!\is_array($decoded) || !isset($decoded['if_range']) || !\is_string($decoded['if_range']) || $decoded['if_range'] === '') {
            return null;
        }

        return $decoded['if_range'];
    }

    /**
     * Persists the ETag/Last-Modified from a chunk response as the resume validator,
     * preferring ETag. Returns null (and writes nothing) when neither is present —
     * a resume after a crash for such a resource is never trusted, only completing
     * the current, uninterrupted process is possible.
     *
     * @param  string                $metaPath
     * @param  HttpResponseInterface $response
     * @return string|null
     */
    private function writeIfRange(string $metaPath, HttpResponseInterface $response): ?string
    {
        $ifRange = $response->getHeaderLine('ETag');
        if ($ifRange === '') {
            $ifRange = $response->getHeaderLine('Last-Modified');
        }
        if ($ifRange === '') {
            return null;
        }

        @\file_put_contents($metaPath, \json_encode(['if_range' => $ifRange]), \LOCK_EX);

        return $ifRange;
    }
}
