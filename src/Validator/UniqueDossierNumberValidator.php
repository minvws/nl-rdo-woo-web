<?php

declare(strict_types=1);

namespace Shared\Validator;

use Shared\Domain\Publication\Dossier\DossierRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Webmozart\Assert\Assert;

use function is_string;

class UniqueDossierNumberValidator extends ConstraintValidator
{
    public function __construct(
        private readonly DossierRepository $dossierRepository,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        Assert::isInstanceOf($constraint, UniqueDossierNumber::class);

        if (! is_string($value) || $value === '') {
            return;
        }

        $existing = $this->dossierRepository->findOneBy([
            'documentPrefix' => $constraint->documentPrefix,
            'dossierNumber' => $value,
        ]);

        if ($existing === null) {
            return;
        }

        if ($constraint->excludeId !== null && $existing->getId()->equals($constraint->excludeId)) {
            return;
        }

        $this->context->buildViolation($constraint->message)->addViolation();
    }
}
