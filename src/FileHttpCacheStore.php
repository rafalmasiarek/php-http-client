<?php

declare(strict_types=1);

namespace rafalmasiarek\HttpClient\Http;

/**
 * Default HttpCacheStoreInterface backend: one JSON file per key. LOCK_EX
 * on write only — not meant for high-concurrency shared caches.
 *
 * @package rafalmasiarek\HttpClient\Http
 */
final class FileHttpCacheStore implements HttpCacheStoreInterface
{
    /**
     * @param string $directory Created on first write if it doesn't exist.
     */
    public function __construct(
        private readonly string $directory,
    ) {
    }

    public function get(string $key): ?array
    {
        $path = $this->path($key);

        if (!\is_file($path)) {
            return null;
        }

        $raw = @\file_get_contents($path);
        if ($raw === false || $raw === '') {
            return null;
        }

        $decoded = \json_decode($raw, true);

        // Returned regardless of whether expires_at is in the past — CachingHttpClient
        // needs the stale entry's validators (etag/last_modified) to revalidate it, not
        // just a yes/no on freshness.
        return \is_array($decoded) && isset($decoded['expires_at']) ? $decoded : null;
    }

    public function set(string $key, array $entry, int $ttl): void
    {
        if (!\is_dir($this->directory) && !@\mkdir($this->directory, 0o700, true) && !\is_dir($this->directory)) {
            return;
        }

        $entry['expires_at'] = \time() + $ttl;

        $json = \json_encode($entry);
        if ($json === false) {
            return;
        }

        @\file_put_contents($this->path($key), $json, \LOCK_EX);
    }

    /**
     * @param  string $key
     * @return string
     */
    private function path(string $key): string
    {
        return $this->directory . '/' . \hash('sha256', $key) . '.json';
    }
}
