<?php

namespace App\Service;

use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Applique les contraintes portees par les entites avant persistance.
 */
class EntityValidator
{
    public function __construct(private readonly ValidatorInterface $validator)
    {
    }

    public function assertValide(object $entite): void
    {
        $violations = $this->validator->validate($entite);

        if (0 === count($violations)) {
            return;
        }

        throw new UnprocessableEntityHttpException(
            'La validation a echoue.',
            new ValidationFailedException($entite, $violations)
        );
    }
}
