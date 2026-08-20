<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Validator;

use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniqueDocumentNumberValidator extends ConstraintValidator
{
    public function __construct(
        private readonly DocumentRepository $documentRepository,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof UniqueDocumentNumber) {
            throw new UnexpectedTypeException($constraint, UniqueDocumentNumber::class);
        }

        if (! $value instanceof Document) {
            return;
        }

        $conflicting = $this->documentRepository->findOneByDocumentNumberCaseInsensitive($value->getDocumentNumber());
        if ($conflicting === null) {
            return;
        }

        if ($conflicting->getId()->equals($value->getId())) {
            return;
        }

        $this->context
            ->buildViolation($constraint->message)
            ->atPath('documentNumber')
            ->setParameter('{{ documentNumber }}', $value->getDocumentNumber())
            ->setCode(UniqueDocumentNumber::NOT_UNIQUE_ERROR)
            ->addViolation();
    }
}
