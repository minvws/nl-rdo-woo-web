<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier;

use ApiPlatform\Validator\Exception\ValidationException;
use Shared\Validator\UniqueDossierNr;
use Shared\Validator\Violation\ConstraintViolationBuilder;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use function count;

final readonly class DossierNrValidator
{
    public function __construct(
        private ValidatorInterface $validator,
    ) {
    }

    public function validate(string $dossierNumber, string $documentPrefix, ?Uuid $excludeId = null): void
    {
        $violations = $this->validator->validate(
            $dossierNumber,
            new UniqueDossierNr($documentPrefix, $excludeId),
        );

        if (count($violations) === 0) {
            return;
        }

        $mapped = ConstraintViolationBuilder::createList();
        foreach ($violations as $violation) {
            $mapped->add(ConstraintViolationBuilder::forViolation($violation, 'dossierNumber'));
        }

        throw new ValidationException($mapped);
    }
}
