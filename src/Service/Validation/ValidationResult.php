<?php

namespace App\Service\Validation;

/**
 * Simple DTO to centralize form/business validation in services (not in UI).
 */
final class ValidationResult
{
    /** @var array<string, string> */
    private array $errors;

    /** @param array<string, string> $errors */
    private function __construct(array $errors)
    {
        $this->errors = $errors;
    }

    public static function ok(): self
    {
        return new self([]);
    }

    /** @param array<string, string> $errors */
    public static function fail(array $errors): self
    {
        return new self($errors);
    }

    public function isValid(): bool
    {
        return $this->errors === [];
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }
}

