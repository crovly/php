<?php

declare(strict_types=1);

namespace Crovly;

use InvalidArgumentException;
use Crovly\Exceptions\ValidationException;

class Crovly
{
    private const DEFAULT_API_URL = 'https://api.crovly.com';
    private const DEFAULT_TIMEOUT = 10;

    private HttpClient $client;

    /**
     * Create a new Crovly SDK instance.
     *
     * @param string $secretKey Your site's secret key (crvl_secret_xxx)
     * @param array{apiUrl?: string, timeout?: int} $options
     */
    public function __construct(string $secretKey, array $options = [])
    {
        if ($secretKey === '') {
            throw new InvalidArgumentException(
                'Crovly secret key is required. Pass your crvl_secret_xxx key.'
            );
        }

        $apiUrl = rtrim($options['apiUrl'] ?? self::DEFAULT_API_URL, '/');
        $timeout = $options['timeout'] ?? self::DEFAULT_TIMEOUT;

        $this->client = new HttpClient($secretKey, $apiUrl, $timeout);
    }

    /**
     * Verify a captcha token returned by the Crovly widget.
     *
     * @param string $token The token from the widget (crovly-token field)
     * @param string|null $expectedIp Optional client IP to enforce IP binding
     * @return VerifyResponse
     * @throws ValidationException If token is empty
     * @throws \Crovly\Exceptions\CrovlyException On network or API errors
     */
    public function verify(string $token, ?string $expectedIp = null): VerifyResponse
    {
        if ($token === '') {
            throw new ValidationException('Token is required');
        }

        $body = ['token' => $token];

        if ($expectedIp !== null && $expectedIp !== '') {
            $body['expectedIp'] = $expectedIp;
        }

        $data = $this->client->post('/verify-token', $body);

        return new VerifyResponse($data);
    }
}
