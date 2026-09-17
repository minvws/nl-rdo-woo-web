<?php

declare(strict_types=1);

namespace Admin\Validator\Organisation;

use Admin\Form\Organisation\OrganisationFormData;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Organisation\OrganisationRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniqueOrganisationValidator extends ConstraintValidator
{
    public function __construct(
        private readonly OrganisationRepository $organisationRepository,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof UniqueOrganisation) {
            throw new UnexpectedTypeException($constraint, UniqueOrganisation::class);
        }

        if (! $value instanceof OrganisationFormData) {
            return;
        }

        if ($value->name !== '') {
            $existingByName = $this->organisationRepository->findOneBy(['name' => $value->name]);
            if ($existingByName instanceof Organisation && ! $this->isExcluded($existingByName, $value)) {
                $this->context
                    ->buildViolation($constraint->nameMessage)
                    ->atPath('name')
                    ->addViolation();
            }
        }

        if ($value->prefix === null) {
            return;
        }

        $existingByPrefix = $this->organisationRepository->findOneBy(['prefix' => $value->prefix]);
        if ($existingByPrefix instanceof Organisation && ! $this->isExcluded($existingByPrefix, $value)) {
            $this->context
                ->buildViolation($constraint->prefixMessage)
                ->atPath('prefix')
                ->addViolation();
        }
    }

    private function isExcluded(Organisation $organisation, OrganisationFormData $data): bool
    {
        return $data->organisationId !== null && $organisation->getId()->equals($data->organisationId);
    }
}
