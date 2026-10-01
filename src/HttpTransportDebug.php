<?php

declare(strict_types=1);

namespace rafalmasiarek\HttpClient\Http;

/**
 * Low-level transport timing/target info for a single request, sourced from
 * curl_getinfo(). Meant for diagnosing connectivity failures (DNS, connect,
 * TLS, timeout) — not for routine success-path use.
 *
 * @package rafalmasiarek\HttpClient\Http
 */
final readonly class HttpTransportDebug
{
    /**
     * @param float       $namelookupTime Seconds from start until name resolution completed.
     * @param float       $connectTime    Seconds from start until the TCP connection was established.
     * @param float       $appconnectTime Seconds from start until the TLS/SSL handshake completed (0 for plain HTTP).
     * @param float       $totalTime      Total seconds for the whole request.
     * @param string|null $primaryIp      IP address actually connected to, or null when the connection never got that far.
     * @param int|null    $primaryPort    Port actually connected to, or null when the connection never got that far.
     */
    public function __construct(
        public float $namelookupTime,
        public float $connectTime,
        public float $appconnectTime,
        public float $totalTime,
        public ?string $primaryIp,
        public ?int $primaryPort,
    ) {
    }

    /**
     * Builds an instance from a raw curl_getinfo() array.
     *
     * @param array<string, mixed> $info Result of curl_getinfo($ch).
     *
     * @return self
     */
    public static function fromCurlInfo(array $info): self
    {
        return new self(
            namelookupTime: (float) ($info['namelookup_time'] ?? 0.0),
            connectTime: (float) ($info['connect_time'] ?? 0.0),
            appconnectTime: (float) ($info['appconnect_time'] ?? 0.0),
            totalTime: (float) ($info['total_time'] ?? 0.0),
            primaryIp: isset($info['primary_ip']) && $info['primary_ip'] !== '' ? (string) $info['primary_ip'] : null,
            primaryPort: isset($info['primary_port']) && (int) $info['primary_port'] > 0 ? (int) $info['primary_port'] : null,
        );
    }

    /**
     * @return array<string, float|string|int|null>
     */
    public function toArray(): array
    {
        return [
            'namelookupTime' => $this->namelookupTime,
            'connectTime'    => $this->connectTime,
            'appconnectTime' => $this->appconnectTime,
            'totalTime'      => $this->totalTime,
            'primaryIp'      => $this->primaryIp,
            'primaryPort'    => $this->primaryPort,
        ];
    }
}
