<?php

declare(strict_types=1);

namespace App\Validator;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * This validator validates if the new password isn't the same as the current password of the user.
 */
class NotTheSamePasswordValidator extends ConstraintValidator
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly Security $security,
    ) {
    }

    /**
     * @param string $value
     */
    public function validate($value, Constraint $constraint): void
    {
        $user = $this->security->getUser();

        if ($value === null) {
            return;
        }

        /** @var User $user */
        if ($this->passwordHasher->isPasswordValid($user, $value)) {
            /** @var NotTheSamePassword $constraint */
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}
