<?php

declare(strict_types=1);

namespace Shared\Validator;

use Shared\Service\Security\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use function levenshtein;

/**
 * This validator checks if the password is too similar to the user's email address.
 */
class SimilarityEmailValidator extends ConstraintValidator
{
    public function __construct(protected TokenStorageInterface $tokenStorage)
    {
    }

    /**
     * @param string $value
     */
    public function validate($value, Constraint $constraint): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if ($this->tokenStorage->getToken() === null) {
            return;
        }

        $user = $this->tokenStorage->getToken()->getUser();
        if (! $user) {
            return;
        }

        /** @var User $user */
        if (levenshtein($value, $user->getEmail()) < 5) {
            /** @var SimilarityEmail $constraint */
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}
