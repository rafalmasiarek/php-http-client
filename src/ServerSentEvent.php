<?php

declare(strict_types=1);

namespace rafalmasiarek\HttpClient\Http;

/**
 * One parsed "text/event-stream" frame (a blank-line-terminated block of
 * id:/event:/data:/retry: lines), as delivered by EventSourceClient.
 *
 * @package rafalmasiarek\HttpClient\Http
 */
final readonly class ServerSentEvent
{
    /**
     * @param string      $data  Concatenation of every "data:" line in the frame, joined by "\n"
     *                           (per the SSE spec) — empty string when the frame had no data line.
     * @param string      $event Event type. "message" when the frame had no explicit "event:" line.
     * @param string|null $id    Last "id:" value seen (persists across frames per the spec, but
     *                           EventSourceClient hands you a single frame's view, not the running value).
     * @param int|null    $retry Reconnection time in milliseconds, when the frame set one.
     */
    public function __construct(
        public string $data,
        public string $event = 'message',
        public ?string $id = null,
        public ?int $retry = null,
    ) {
    }

    /**
     * @return mixed Decoded JSON, or null when $data isn't valid JSON.
     */
    public function getArrayData(): mixed
    {
        return \json_decode($this->data, true);
    }
}
