<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Carries a field-keyed error bag from the service layer to any caller
 * (CLI command, controller, future admin form) without duplicating rules.
 */
final class ValidationException extends RuntimeException
{
    /**
     * @param array<string, string> $errors
     */
    public function __construct(private readonly array $errors)
    {
        parent::__construct('Validation failed.');
    }

    /**
     * @return array<string, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
