<?php

namespace App\Exception;

use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Exception levée quand la validation d'un profil utilisateur échoue.
 */
class ValidationException extends \RuntimeException
{
    public function __construct(
        private ConstraintViolationListInterface $violations,
        string $message = 'Invalid data supplied',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getViolations(): ConstraintViolationListInterface
    {
        return $this->violations;
    }
}
