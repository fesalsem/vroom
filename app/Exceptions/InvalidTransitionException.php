<?php

namespace App\Exceptions;

use App\Enums\RegistrationStatus;
use RuntimeException;

class InvalidTransitionException extends RuntimeException
{
    public function __construct(
        public readonly RegistrationStatus $from,
        public readonly RegistrationStatus $to,
    ) {
        parent::__construct(
            "Cannot transition from '{$from->value}' to '{$to->value}'."
        );
    }
}
