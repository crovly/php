<?php

declare(strict_types=1);

namespace Crovly;

use Crovly\Exceptions\ApiException;
use Crovly\Exceptions\CrovlyException;
use Crovly\Exceptions\ValidationException;

class HttpClient
{
    private const SDK_VERSION = '1.0.0';

    private string $secretKey;
    private string $baseUrl;
    private int $timeout;

    public function __construct(string $secretKey, string $baseUrl, int $timeout)
    {
        $this->secretKey = $secretKey;
        $this->baseUrl = $baseUrl;
        $this->timeout = $timeout;
    }

    /**
     * Send a POST request with JSON body.
     *
     * @param string $path API endpoint path
     * @param array<string, mixed> $body Request body
     * @return array<string, mixed> Decoded JSON response
     * @throws CrovlyException
     */
    public function post(string $path, array $body): array
    {
        $url = $this->baseUrl . $path;

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->secretKey,
            'User-Agent: crovly-php/' . self::SDK_VERSION,
        ];

        $ch = curl_init();

        try {
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($body, JSON_THROW_ON_ERROR),
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);

            $responseBody = curl_exec($ch);

            if ($responseBody === false) {
                throw new CrovlyException(
                    'Network error: ' . curl_error($ch),
                    0,
                    'network_error',
                );
            }

            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $decoded = json_decode((string) $responseBody, true);

            if ($statusCode >= 200 && $statusCode < 300) {
                if (!is_array($decoded)) {
                    throw new CrovlyException('Invalid JSON response', $statusCode, 'parse_error');
                }
                return $decoded;
            }

            // Build error from response
            throw $this->buildApiError($statusCode, $decoded);
        } finally {
            curl_close($ch);
        }
    }

    /**
     * @param array<string, mixed>|null $body Decoded response body
     */
    private function buildApiError(int $statusCode, ?array $body): CrovlyException
    {
        $message = $body['error'] ?? $body['message'] ?? 'Unknown error';

        return match ($statusCode) {
            400 => new ValidationException($message),
            401 => new ApiException($message, 401, 'authentication_error'),
            403 => new ApiException($message, 403, 'forbidden'),
            429 => new ApiException($message, 429, 'rate_limit'),
            default => new ApiException($message, $statusCode, 'api_error'),
        };
    }
}
