<?php

declare(strict_types=1);

namespace rafalmasiarek\HttpClient\Http;

/**
 * Decides whether and how long to wait before retrying a request, given the
 * response RetryHttpClient just received (or the transport error it carries).
 *
 * @package rafalmasiarek\HttpClient\Http
 */
interface RetryStrategyInterface
{
    /**
     * @param  string                $method   HTTP method of the request that was just attempted.
     * @param  HttpResponseInterface $response The response received (getStatusCode() 0 and a
     *                                          non-null getError() mean a transport-level failure,
     *                                          not an HTTP response).
     * @param  int                   $attempt  1-based count of attempts made so far, including this one.
     * @return bool True to retry.
     */
    public function shouldRetry(string $method, HttpResponseInterface $response, int $attempt): bool;

    /**
     * @param  HttpResponseInterface $response The response that triggered the retry.
     * @param  int                   $attempt  1-based count of attempts made so far, including this one.
     * @return int Milliseconds to wait before the next attempt.
     */
    public function getDelayMilliseconds(HttpResponseInterface $response, int $attempt): int;
}
