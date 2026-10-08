<?php

declare(strict_types=1);

namespace rafalmasiarek\HttpClient\Http;

/**
 * Structured classification of a transport-level failure —
 * HttpResponseInterface::getError()'s companion for callers that need to
 * branch on *why* a request failed without string-matching
 * getError()'s free-text message (which is only ever curl_strerror()'s
 * exact English wording, not guaranteed stable, and not comparable across
 * a different HttpClientInterface implementation).
 *
 * @package rafalmasiarek\HttpClient\Http
 */
enum TransportErrorKind
{
    /** The request did not complete within the configured timeout. */
    case Timeout;

    /** The target hostname (or configured proxy) could not be resolved. */
    case DnsFailure;

    /** The remote host refused or did not accept the connection. */
    case ConnectionRefused;

    /** A TLS handshake or certificate validation failure. */
    case Tls;

    /** Refused by this client's own block_private_network check — never a
     *  curl-level failure, since the request was never actually sent. */
    case Blocked;

    /** The caller called HttpResponseInterface::cancel() before completion. */
    case Canceled;

    /** A transport failure occurred, but doesn't map to any case above. */
    case Unknown;

    /**
     * Classifies a curl_errno() value (a CURLE_* constant) into this enum.
     *
     * @param int $errno
     *
     * @return self
     */
    public static function fromCurlErrno(int $errno): self
    {
        return match ($errno) {
            \CURLE_OPERATION_TIMEDOUT => self::Timeout,
            \CURLE_COULDNT_RESOLVE_HOST, \CURLE_COULDNT_RESOLVE_PROXY => self::DnsFailure,
            \CURLE_COULDNT_CONNECT => self::ConnectionRefused,
            \CURLE_SSL_CONNECT_ERROR, \CURLE_PEER_FAILED_VERIFICATION, \CURLE_SSL_CERTPROBLEM, \CURLE_SSL_CACERT => self::Tls,
            default => self::Unknown,
        };
    }
}
