<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Validator;

use Mockery;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Domain\Publication\Dossier\Validator\DateFromConstraint;
use Shared\Domain\Publication\Dossier\Validator\NoIncompleteDocuments;
use Shared\Domain\Publication\Dossier\Validator\NoIncompleteDocumentsValidator;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class NoIncompleteDocumentsValidatorTest extends UnitTestCase
{
    public function testValidateAddsViolationWhenIncompleteDocumentsExist(): void
    {
        $dossierId = Uuid::v6();

        $dossier = Mockery::mock(AbstractDossier::class);
        $dossier->expects('getId')->andReturn($dossierId);

        $repository = Mockery::mock(DocumentRepository::class);
        $repository->expects('hasIncompleteDocumentsForDossier')->with($dossierId)->andReturnTrue();

        $context = Mockery::mock(ExecutionContextInterface::class);
        $context->expects('buildViolation->addViolation');

        $validator = new NoIncompleteDocumentsValidator($repository);
        $validator->initialize($context);

        $validator->validate($dossier, new NoIncompleteDocuments());
    }

    public function testValidateAddsNoViolationWhenNoIncompleteDocumentsExist(): void
    {
        $dossierId = Uuid::v6();

        $dossier = Mockery::mock(AbstractDossier::class);
        $dossier->expects('getId')->andReturn($dossierId);

        $repository = Mockery::mock(DocumentRepository::class);
        $repository->expects('hasIncompleteDocumentsForDossier')->with($dossierId)->andReturnFalse();

        $context = Mockery::mock(ExecutionContextInterface::class);
        $context->shouldNotReceive('buildViolation');

        $validator = new NoIncompleteDocumentsValidator($repository);
        $validator->initialize($context);

        $validator->validate($dossier, new NoIncompleteDocuments());
    }

    public function testValidateIfInvalidConstraint(): void
    {
        $repository = Mockery::mock(DocumentRepository::class);
        $noIncompleteDocumentsValidator = new NoIncompleteDocumentsValidator($repository);

        self::expectException(UnexpectedTypeException::class);
        $noIncompleteDocumentsValidator->validate('foo', new DateFromConstraint());
    }

    public function testValidateIfInvalidValue(): void
    {
        $repository = Mockery::mock(DocumentRepository::class);
        $noIncompleteDocumentsValidator = new NoIncompleteDocumentsValidator($repository);

        self::expectException(UnexpectedTypeException::class);
        $noIncompleteDocumentsValidator->validate('foo', new NoIncompleteDocuments());
    }
}
