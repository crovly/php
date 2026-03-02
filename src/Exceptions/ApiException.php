<?php

declare(strict_types=1);

namespace Crovly\Exceptions;

class ApiException extends CrovlyException
{
    public function __construct(string $message, int $statusCode, string $errorCode = 'api_error')
    {
        parent::__construct($message, $statusCode, $errorCode);
    }
}
