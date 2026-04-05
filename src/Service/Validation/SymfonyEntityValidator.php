<?php

namespace App\Service\Validation;

use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Centralizes validation using Symfony Validator (constraints on entities),
 * returning the same error format used by the UI.
 */
final class SymfonyEntityValidator
{
    public function __construct(private readonly ValidatorInterface $validator)
    {
    }

    /**
     * @return array<string, string> field => message
     */
    public function validate(object $entity): array
    {
        $violations = $this->validator->validate($entity);

        $errors = [];
        /** @var ConstraintViolationInterface $violation */
        foreach ($violations as $violation) {
            $path = (string) $violation->getPropertyPath();
            $field = $path !== '' ? $path : '_global';
            // keep first message per field (simple UX)
            $errors[$field] ??= (string) $violation->getMessage();
        }

        // Keep compatibility with legacy field naming
        if (isset($errors['id_parcelle']) && !isset($errors['parcelle'])) {
            $errors['parcelle'] = $errors['id_parcelle'];
        }

        return $errors;
    }
}

