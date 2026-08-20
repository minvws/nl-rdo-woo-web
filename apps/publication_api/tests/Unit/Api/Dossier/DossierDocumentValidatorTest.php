<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier;

use ApiPlatform\Validator\Exception\ValidationException;
use Doctrine\Common\Collections\ArrayCollection;
use Mockery;
use PublicationApi\Api\Dossier\DossierDocumentValidator;
use PublicationApi\Api\Dossier\WooDecision\Document\WooDecisionDocumentRequestDto;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\DossierValidationGroup;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\SourceType;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DocumentId;
use Shared\ValueObject\ExternalId;
use Shared\ValueObject\FileName;
use Shared\ValueObject\PlainDate;
use Shared\ValueObject\PublicationContext;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use function sprintf;

class DossierDocumentValidatorTest extends UnitTestCase
{
    public function testValidateSucceeds(): void
    {
        $validator = Mockery::mock(ValidatorInterface::class);
        $dossierDocumentValidator = new DossierDocumentValidator($validator);

        $documents = [Mockery::mock(Document::class)];

        $validator->expects('validate')
            ->with(
                $documents,
                null,
                [
                    DossierValidationGroup::DETAILS->value,
                    DossierValidationGroup::DECISION->value,
                    DossierValidationGroup::DOCUMENTS->value,
                    DossierValidationGroup::PUBLICATION->value,
                    DossierValidationGroup::CONTENT->value,
                    Constraint::DEFAULT_GROUP,
                ],
            )
            ->andReturn(new ConstraintViolationList());

        $dossierDocumentValidator->validate($documents, DossierStatus::CONCEPT);
    }

    public function testValidateRethrowsOnValidationFailure(): void
    {
        $validator = Mockery::mock(ValidatorInterface::class);
        $dossierDocumentValidator = new DossierDocumentValidator($validator);

        $message = self::getFaker()->sentence();
        $documents = [Mockery::mock(Document::class)];
        $violations = new ConstraintViolationList([
            new ConstraintViolation(
                $message,
                null,
                [],
                null,
                '[0].documentNumber',
                null,
                null,
                null,
            ),
        ]);

        $validator->expects('validate')
            ->andReturn($violations);

        self::expectException(ValidationException::class);
        self::expectExceptionMessageIs(sprintf('documents.[0].documentNumber: %s', $message));

        $dossierDocumentValidator->validate($documents, DossierStatus::CONCEPT);
    }

    public function testAssertDocumentSetUnchangedInNonConceptPassesWhenDocumentsAreUnchanged(): void
    {
        $validator = Mockery::mock(ValidatorInterface::class);
        $dossierDocumentValidator = new DossierDocumentValidator($validator);

        $externalId = self::getFaker()->externalId();

        $document = Mockery::mock(Document::class);
        $document->expects('getExternalId')
            ->andReturn($externalId);

        $wooDecision = Mockery::mock(WooDecision::class);
        $wooDecision->expects('getStatus')
            ->andReturn(DossierStatus::PUBLISHED);
        $wooDecision->expects('getDocuments')
            ->andReturn(new ArrayCollection([$document]));

        $dossierDocumentValidator->assertDocumentSetUnchangedInNonConcept($wooDecision, [
            $this->createDocumentRequestDto($externalId),
        ]);
    }

    public function testAssertDocumentSetUnchangedInNonConceptIsSkippedForConceptDossier(): void
    {
        $validator = Mockery::mock(ValidatorInterface::class);
        $dossierDocumentValidator = new DossierDocumentValidator($validator);

        $wooDecision = Mockery::mock(WooDecision::class);
        $wooDecision->expects('getStatus')
            ->andReturn(DossierStatus::CONCEPT);

        $dossierDocumentValidator->assertDocumentSetUnchangedInNonConcept($wooDecision, []);
    }

    public function testAssertDocumentSetUnchangedInNonConceptThrowsWhenDocumentIsRemoved(): void
    {
        $validator = Mockery::mock(ValidatorInterface::class);
        $dossierDocumentValidator = new DossierDocumentValidator($validator);

        $document = Mockery::mock(Document::class);
        $document->expects('getExternalId')
            ->andReturn(self::getFaker()->externalId());

        $wooDecision = Mockery::mock(WooDecision::class);
        $wooDecision->expects('getStatus')
            ->andReturn(DossierStatus::PUBLISHED);
        $wooDecision->expects('getDocuments')
            ->andReturn(new ArrayCollection([$document]));

        self::expectException(ValidationException::class);

        $dossierDocumentValidator->assertDocumentSetUnchangedInNonConcept($wooDecision, []);
    }

    public function testAssertDocumentSetUnchangedInNonConceptPassesWhenDocumentIsAdded(): void
    {
        $validator = Mockery::mock(ValidatorInterface::class);
        $dossierDocumentValidator = new DossierDocumentValidator($validator);

        $externalId = self::getFaker()->externalId();

        $document = Mockery::mock(Document::class);
        $document->expects('getExternalId')
            ->andReturn($externalId);

        $wooDecision = Mockery::mock(WooDecision::class);
        $wooDecision->expects('getStatus')
            ->andReturn(DossierStatus::PUBLISHED);
        $wooDecision->expects('getDocuments')
            ->andReturn(new ArrayCollection([$document]));

        $dossierDocumentValidator->assertDocumentSetUnchangedInNonConcept($wooDecision, [
            $this->createDocumentRequestDto($externalId),
            $this->createDocumentRequestDto(self::getFaker()->externalId()),
        ]);

        $this->addToAssertionCount(1);
    }

    private function createDocumentRequestDto(ExternalId $externalId): WooDecisionDocumentRequestDto
    {
        return new WooDecisionDocumentRequestDto(
            inquiryNumbers: [],
            documentDate: PlainDate::create('2025-01-01'),
            documentId: DocumentId::create('doc.123'),
            externalId: $externalId,
            familyId: null,
            fileName: FileName::create('test.pdf'),
            grounds: [],
            isSuspended: false,
            judgement: Judgement::PUBLIC,
            links: [],
            refersTo: [],
            remark: null,
            sourceType: SourceType::PDF,
            threadId: null,
            publicationContext: PublicationContext::fromString('test'),
        );
    }
}
