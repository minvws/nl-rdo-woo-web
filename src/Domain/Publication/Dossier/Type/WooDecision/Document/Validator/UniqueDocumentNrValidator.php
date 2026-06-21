<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Validator;

use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Service\Inventory\DocumentNumber;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniqueDocumentNrValidator extends ConstraintValidator
{
    public function __construct(
        private readonly DocumentRepository $documentRepository,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof UniqueDocumentNr) {
            throw new UnexpectedTypeException($constraint, UniqueDocumentNr::class);
        }

        if (! $value instanceof Document) {
            return;
        }

        $conflicting = $this->documentRepository->findOneByDocumentNrCaseInsensitive($value->getDocumentNr());
        if ($conflicting === null) {
            return;
        }

        if ($conflicting->getId()->equals($value->getId())) {
            return;
        }

        $dossier = $conflicting->getDossiers()->first();
        if (! $dossier instanceof WooDecision) {
            return;
        }

        $documentNumber = DocumentNumber::fromString(
            $dossier->getDocumentPrefix(),
            null,
            $value->getDocumentNr(),
        );

        $this->context
            ->buildViolation($constraint->message)
            ->atPath('documentNr')
            ->setParameter('{{ prefix }}', $documentNumber->prefix)
            ->setParameter('{{ matter }}', $documentNumber->matter !== null ? $documentNumber->matter->toString() : '')
            ->setParameter('{{ documentId }}', $documentNumber->id->toString())
            ->setCode(UniqueDocumentNr::NOT_UNIQUE_ERROR)
            ->addViolation();
    }
}
