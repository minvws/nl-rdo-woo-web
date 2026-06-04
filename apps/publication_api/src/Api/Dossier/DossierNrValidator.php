<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier;

use ApiPlatform\Validator\Exception\ValidationException;
use Shared\Validator\UniqueDossierNr;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
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

        $mapped = new ConstraintViolationList();
        foreach ($violations as $violation) {
            $mapped->add(new ConstraintViolation(
                $violation->getMessage(),
                $violation->getMessageTemplate(),
                $violation->getParameters(),
                $violation->getRoot(),
                'dossierNumber',
                $violation->getInvalidValue(),
            ));
        }

        throw new ValidationException($mapped);
    }
}
