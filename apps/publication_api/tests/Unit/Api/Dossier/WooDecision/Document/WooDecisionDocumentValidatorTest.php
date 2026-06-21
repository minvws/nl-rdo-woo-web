<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\WooDecision\Document;

use ApiPlatform\Validator\Exception\ValidationException;
use Mockery;
use Mockery\MockInterface;
use PublicationApi\Api\Dossier\WooDecision\Document\WooDecisionDocumentRequestDto;
use PublicationApi\Api\Dossier\WooDecision\Document\WooDecisionDocumentValidator;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Domain\Publication\SourceType;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DocumentId;
use Shared\ValueObject\DocumentMatter;
use Shared\ValueObject\ExternalId;
use Shared\ValueObject\FileName;
use Shared\ValueObject\PlainDate;
use Symfony\Component\Validator\ConstraintViolationListInterface;

use function array_map;
use function range;

final class WooDecisionDocumentValidatorTest extends UnitTestCase
{
    private DocumentRepository&MockInterface $documentRepository;
    private WooDecisionDocumentValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->documentRepository = Mockery::mock(DocumentRepository::class);
        $this->validator = new WooDecisionDocumentValidator(
            $this->documentRepository,
        );
    }

    public function testValidatePassesForEmptyBatch(): void
    {
        $this->assertValidationPasses([]);
    }

    public function testValidatePassesWhenNoDocumentHasRefersTo(): void
    {
        $dto = $this->createDto(externalId: 'ext-1', refersTo: []);

        $this->assertValidationPasses([$dto]);
    }

    public function testValidatePassesWhenRefersToPointsToAnotherDocInSameBatch(): void
    {
        $dtoA = $this->createDto(externalId: 'ext-a', refersTo: [ExternalId::create('ext-b')]);
        $dtoB = $this->createDto(externalId: 'ext-b', refersTo: []);

        $this->assertValidationPasses([$dtoA, $dtoB]);
    }

    public function testValidatePassesWhenRefersToPointsToExistingDocInDatabase(): void
    {
        $this->documentRepository->expects('findExistingExternalIds')->andReturn(['ext-db']);

        $dto = $this->createDto(externalId: 'ext-a', refersTo: [ExternalId::create('ext-db')]);

        $this->assertValidationPasses([$dto]);
    }

    public function testValidatePassesWhenRefersToPointsToBothBatchDocAndDatabaseDoc(): void
    {
        $this->documentRepository->expects('findExistingExternalIds')->andReturn(['ext-db']);

        $dtoA = $this->createDto(externalId: 'ext-a', refersTo: [
            ExternalId::create('ext-b'),
            ExternalId::create('ext-db'),
        ]);
        $dtoB = $this->createDto(externalId: 'ext-b', refersTo: []);

        $this->assertValidationPasses([$dtoA, $dtoB]);
    }

    public function testValidatePassesForMultipleDocsWithValidCrossReferences(): void
    {
        $dtoA = $this->createDto(externalId: 'ext-a', refersTo: [ExternalId::create('ext-b')]);
        $dtoB = $this->createDto(externalId: 'ext-b', refersTo: [ExternalId::create('ext-c')]);
        $dtoC = $this->createDto(externalId: 'ext-c', refersTo: []);

        $this->assertValidationPasses([$dtoA, $dtoB, $dtoC]);
    }

    public function testValidateThrowsWhenRefersToPointsToNonExistingDocument(): void
    {
        $this->documentRepository->expects('findExistingExternalIds')->andReturn([]);

        $dto = $this->createDto(externalId: 'ext-a', refersTo: [ExternalId::create('ext-missing')]);

        $this->expectException(ValidationException::class);

        $this->validator->validate([$dto]);
    }

    public function testViolationContainsCorrectPropertyPathAndMessageForSingleMissingReference(): void
    {
        $this->documentRepository->expects('findExistingExternalIds')->andReturn([]);

        $dto = $this->createDto(externalId: 'ext-a', refersTo: [ExternalId::create('ext-missing')]);

        $violations = $this->assertValidationFails([$dto]);

        $this->assertCount(1, $violations);
        $this->assertSame('documents[0].refersTo[0]', $violations->get(0)->getPropertyPath());
        $this->assertSame('The referenced document could not be found', $violations->get(0)->getMessage());
    }

    public function testViolationIsReportedForEachMissingReferenceWithinOneDtot(): void
    {
        $this->documentRepository->expects('findExistingExternalIds')->andReturn([]);

        $dto = $this->createDto(
            externalId: 'ext-a',
            refersTo: [
                ExternalId::create('ext-missing-1'),
                ExternalId::create('ext-missing-2'),
            ],
        );

        $violations = $this->assertValidationFails([$dto]);

        $this->assertCount(2, $violations);
        $this->assertSame('documents[0].refersTo[0]', $violations->get(0)->getPropertyPath());
        $this->assertSame('documents[0].refersTo[1]', $violations->get(1)->getPropertyPath());
    }

    public function testViolationsAreReportedAcrossMultipleDtosWithMissingReferences(): void
    {
        $this->documentRepository->expects('findExistingExternalIds')->andReturn([]);

        $dtoA = $this->createDto(externalId: 'ext-a', refersTo: [ExternalId::create('ext-missing-1')]);
        $dtoB = $this->createDto(externalId: 'ext-b', refersTo: [ExternalId::create('ext-missing-2')]);

        $violations = $this->assertValidationFails([$dtoA, $dtoB]);

        $this->assertCount(2, $violations);
        $this->assertSame('documents[0].refersTo[0]', $violations->get(0)->getPropertyPath());
        $this->assertSame('documents[1].refersTo[0]', $violations->get(1)->getPropertyPath());
    }

    public function testValidateThrowsWhenOnlyOneOfMultipleRefersToIsMissing(): void
    {
        $this->documentRepository->expects('findExistingExternalIds')->andReturn(['ext-db']);

        $dto = $this->createDto(
            externalId: 'ext-a',
            refersTo: [
                ExternalId::create('ext-db'),
                ExternalId::create('ext-missing'),
            ],
        );

        $violations = $this->assertValidationFails([$dto]);

        $this->assertCount(1, $violations);
        $this->assertSame('documents[0].refersTo[1]', $violations->get(0)->getPropertyPath());
    }

    public function testValidatePassesWhenDatabaseReturnsMoreIdsThanRequested(): void
    {
        $this->documentRepository->expects('findExistingExternalIds')->andReturn(['ext-db', 'ext-other', 'ext-yet-another']);

        $dto = $this->createDto(externalId: 'ext-a', refersTo: [ExternalId::create('ext-db')]);

        $this->assertValidationPasses([$dto]);
    }

    public function testValidatePassesForDocumentWithManyValidReferences(): void
    {
        $existingIds = array_map(static fn (int $i) => "ext-db-$i", range(1, 10));
        $refersTo = array_map(ExternalId::create(...), $existingIds);

        $this->documentRepository->expects('findExistingExternalIds')->andReturn($existingIds);

        $dto = $this->createDto(externalId: 'ext-a', refersTo: $refersTo);

        $this->assertValidationPasses([$dto]);
    }

    public function testViolationIndexReflectsActualPositionInBatch(): void
    {
        $this->documentRepository->expects('findExistingExternalIds')->andReturn([]);

        $dtoA = $this->createDto(externalId: 'ext-a', refersTo: []);
        $dtoB = $this->createDto(externalId: 'ext-b', refersTo: []);
        $dtoC = $this->createDto(externalId: 'ext-c', refersTo: [ExternalId::create('ext-missing')]);

        $violations = $this->assertValidationFails([$dtoA, $dtoB, $dtoC]);

        $this->assertCount(1, $violations);
        $this->assertSame('documents[2].refersTo[0]', $violations->get(0)->getPropertyPath());
    }

    public function testValidateThrowsWhenDocumentRefersToItself(): void
    {
        $dto = $this->createDto(externalId: 'ext-a', refersTo: [ExternalId::create('ext-a')]);

        $violations = $this->assertValidationFails([$dto]);

        $this->assertCount(1, $violations);
        $this->assertSame('documents[0].refersTo[0]', $violations->get(0)->getPropertyPath());
        $this->assertSame('A document cannot refer to itself', $violations->get(0)->getMessage());
    }

    public function testSelfReferenceViolationWithMixedValidReferences(): void
    {
        $this->documentRepository->expects('findExistingExternalIds')->andReturn(['ext-db']);

        $dtoA = $this->createDto(
            externalId: 'ext-a',
            refersTo: [
                ExternalId::create('ext-a'),
                ExternalId::create('ext-b'),
                ExternalId::create('ext-db'),
            ],
        );
        $dtoB = $this->createDto(externalId: 'ext-b', refersTo: []);

        $violations = $this->assertValidationFails([$dtoA, $dtoB]);

        $this->assertCount(1, $violations);
        $this->assertSame('documents[0].refersTo[0]', $violations->get(0)->getPropertyPath());
        $this->assertSame('A document cannot refer to itself', $violations->get(0)->getMessage());
    }

    public function testSelfReferenceViolationCombinedWithMissingReference(): void
    {
        $this->documentRepository->expects('findExistingExternalIds')->andReturn([]);

        $dto = $this->createDto(
            externalId: 'ext-a',
            refersTo: [
                ExternalId::create('ext-a'),
                ExternalId::create('ext-missing'),
            ],
        );

        $violations = $this->assertValidationFails([$dto]);

        $this->assertCount(2, $violations);
        $this->assertSame('documents[0].refersTo[0]', $violations->get(0)->getPropertyPath());
        $this->assertSame('A document cannot refer to itself', $violations->get(0)->getMessage());
        $this->assertSame('documents[0].refersTo[1]', $violations->get(1)->getPropertyPath());
        $this->assertSame('The referenced document could not be found', $violations->get(1)->getMessage());
    }

    /**
     * @param list<WooDecisionDocumentRequestDto> $dtos
     */
    private function assertValidationFails(array $dtos): ConstraintViolationListInterface
    {
        try {
            $this->validator->validate($dtos);
        } catch (ValidationException $e) {
            return $e->getConstraintViolationList();
        }

        $this->fail('Expected ValidationException to be thrown');
    }

    /**
     * @param list<WooDecisionDocumentRequestDto> $dtos
     */
    private function assertValidationPasses(array $dtos): void
    {
        try {
            $this->validator->validate($dtos);
            $this->addToAssertionCount(1);
        } catch (ValidationException $e) {
            $this->fail('Expected no ValidationException, but got: ' . $e->getMessage());
        }
    }

    /**
     * @param list<ExternalId> $refersTo
     */
    private function createDto(string $externalId, array $refersTo = []): WooDecisionDocumentRequestDto
    {
        return new WooDecisionDocumentRequestDto(
            inquiryNumbers: [],
            documentDate: PlainDate::create('2025-01-01'),
            documentId: DocumentId::create('doc-' . $externalId),
            externalId: ExternalId::create($externalId),
            familyId: null,
            fileName: FileName::create('test.pdf'),
            grounds: [],
            isSuspended: false,
            judgement: Judgement::PUBLIC,
            links: [],
            refersTo: $refersTo,
            remark: null,
            sourceType: SourceType::PDF,
            threadId: null,
            matter: DocumentMatter::create('test'),
        );
    }
}
