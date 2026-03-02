<?php

declare(strict_types=1);

namespace Crovly\Exceptions;

class ValidationException extends CrovlyException
{
    public function __construct(string $message = 'Validation error')
    {
        parent::__construct($message, 400, 'validation_error');
    }
}
