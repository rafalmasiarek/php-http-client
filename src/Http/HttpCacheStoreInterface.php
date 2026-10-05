<?php

declare(strict_types=1);

namespace rafalmasiarek\HttpClient\Http;

/**
 * Minimal storage contract for CachingHttpClient — not PSR-16/PSR-6, just
 * get/set with a TTL, so a backend doesn't need a PSR cache dependency.
 *
 * @package rafalmasiarek\HttpClient\Http
 */
interface HttpCacheStoreInterface
{
    /**
     * Returns the entry regardless of freshness — CachingHttpClient checks
     * 'expires_at' itself, since a stale entry's validators are still needed.
     *
     * @param  string $key
     * @return array<string, mixed>|null Null only when no entry exists.
     */
    public function get(string $key): ?array;

    /**
     * @param  string               $key
     * @param  array<string, mixed> $entry
     * @param  int                  $ttl   Seconds until this entry should be treated as expired.
     * @return void
     */
    public function set(string $key, array $entry, int $ttl): void;
}
