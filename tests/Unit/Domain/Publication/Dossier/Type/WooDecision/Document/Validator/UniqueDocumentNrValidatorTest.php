<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Document\Validator;

use Doctrine\Common\Collections\ArrayCollection;
use Mockery;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Validator\UniqueDocumentNr;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Validator\UniqueDocumentNrValidator;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class UniqueDocumentNrValidatorTest extends UnitTestCase
{
    public function testThrowsOnWrongConstraintType(): void
    {
        $repository = Mockery::mock(DocumentRepository::class);
        $validator = new UniqueDocumentNrValidator($repository);

        self::expectException(UnexpectedTypeException::class);

        $validator->validate(Mockery::mock(Document::class), Mockery::mock(Constraint::class));
    }

    public function testNoViolationWhenValueIsNotADocument(): void
    {
        $repository = Mockery::mock(DocumentRepository::class);
        $repository->shouldNotReceive('findOneByDocumentNrCaseInsensitive');

        $context = Mockery::mock(ExecutionContextInterface::class);
        $context->shouldNotReceive('buildViolation');

        $validator = new UniqueDocumentNrValidator($repository);
        $validator->initialize($context);

        $validator->validate('not-a-document', new UniqueDocumentNr());
    }

    public function testNoViolationWhenNoConflictingDocumentExists(): void
    {
        $document = Mockery::mock(Document::class);
        $document->expects('getDocumentNr')->andReturn('PREFIX-sint-doc1');

        $repository = Mockery::mock(DocumentRepository::class);
        $repository->expects('findOneByDocumentNrCaseInsensitive')->with('PREFIX-sint-doc1')->andReturnNull();

        $context = Mockery::mock(ExecutionContextInterface::class);
        $context->shouldNotReceive('buildViolation');

        $validator = new UniqueDocumentNrValidator($repository);
        $validator->initialize($context);

        $validator->validate($document, new UniqueDocumentNr());
    }

    public function testNoViolationWhenConflictingDocumentIsSameDocument(): void
    {
        $id = Uuid::v6();

        $document = Mockery::mock(Document::class);
        $document->expects('getDocumentNr')->andReturn('PREFIX-sint-doc1');
        $document->expects('getId')->andReturn($id);

        $conflicting = Mockery::mock(Document::class);
        $conflicting->expects('getId')->andReturn($id);

        $repository = Mockery::mock(DocumentRepository::class);
        $repository->expects('findOneByDocumentNrCaseInsensitive')->with('PREFIX-sint-doc1')->andReturn($conflicting);

        $context = Mockery::mock(ExecutionContextInterface::class);
        $context->shouldNotReceive('buildViolation');

        $validator = new UniqueDocumentNrValidator($repository);
        $validator->initialize($context);

        $validator->validate($document, new UniqueDocumentNr());
    }

    public function testNoViolationWhenConflictingDocumentHasNoDossier(): void
    {
        $document = Mockery::mock(Document::class);
        $document->expects('getDocumentNr')->andReturn('PREFIX-sint-doc1');
        $document->expects('getId')->andReturn(Uuid::v6());

        $conflicting = Mockery::mock(Document::class);
        $conflicting->expects('getId')->andReturn(Uuid::v6());
        $conflicting->expects('getDossiers')->andReturn(new ArrayCollection());

        $repository = Mockery::mock(DocumentRepository::class);
        $repository->expects('findOneByDocumentNrCaseInsensitive')->with('PREFIX-sint-doc1')->andReturn($conflicting);

        $context = Mockery::mock(ExecutionContextInterface::class);
        $context->shouldNotReceive('buildViolation');

        $validator = new UniqueDocumentNrValidator($repository);
        $validator->initialize($context);

        $validator->validate($document, new UniqueDocumentNr());
    }

    public function testAddsViolationWithDecomposedPartsWhenDocumentNrExistsInAnotherDocument(): void
    {
        $document = Mockery::mock(Document::class);
        $document->expects('getDocumentNr')->twice()->andReturn('PREFIX-sint-doc1');
        $document->expects('getId')->andReturn(Uuid::v6());

        $dossier = Mockery::mock(WooDecision::class);
        $dossier->expects('getDocumentPrefix')->andReturn('PREFIX');

        $conflicting = Mockery::mock(Document::class);
        $conflicting->expects('getId')->andReturn(Uuid::v6());
        $conflicting->expects('getDossiers')->andReturn(new ArrayCollection([$dossier]));

        $repository = Mockery::mock(DocumentRepository::class);
        $repository->expects('findOneByDocumentNrCaseInsensitive')->with('PREFIX-sint-doc1')->andReturn($conflicting);

        $builder = Mockery::mock(ConstraintViolationBuilderInterface::class);
        $builder->expects('atPath')->with('documentNr')->andReturn($builder);
        $builder->expects('setParameter')->with('{{ prefix }}', 'PREFIX')->andReturn($builder);
        $builder->expects('setParameter')->with('{{ matter }}', 'sint')->andReturn($builder);
        $builder->expects('setParameter')->with('{{ documentId }}', 'doc1')->andReturn($builder);
        $builder->expects('setCode')->with(UniqueDocumentNr::NOT_UNIQUE_ERROR)->andReturn($builder);
        $builder->expects('addViolation');

        $context = Mockery::mock(ExecutionContextInterface::class);
        $context->expects('buildViolation')->with('document.document_nr_not_unique')->andReturn($builder);

        $validator = new UniqueDocumentNrValidator($repository);
        $validator->initialize($context);

        $validator->validate($document, new UniqueDocumentNr());
    }
}
