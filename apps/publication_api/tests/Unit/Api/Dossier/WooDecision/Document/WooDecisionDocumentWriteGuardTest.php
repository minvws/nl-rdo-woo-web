<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\WooDecision\Document;

use ApiPlatform\Validator\Exception\ValidationException;
use Doctrine\Common\Collections\ArrayCollection;
use Mockery;
use Mockery\MockInterface;
use PublicationApi\Api\Dossier\WooDecision\Document\WooDecisionDocumentWriteGuard;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DocumentNumber;
use Symfony\Component\Validator\ConstraintViolationListInterface;

final class WooDecisionDocumentWriteGuardTest extends UnitTestCase
{
    private DocumentRepository&MockInterface $documentRepository;

    private WooDecisionDocumentWriteGuard $guard;

    protected function setUp(): void
    {
        parent::setUp();

        $this->documentRepository = Mockery::mock(DocumentRepository::class);
        $this->guard = new WooDecisionDocumentWriteGuard($this->documentRepository);
    }

    public function testAllowsSingleLinkedDocumentInCurrentDossier(): void
    {
        $dossier = Mockery::mock(WooDecision::class);
        $document = $this->createDocument([$dossier], expectDocumentNumber: true);
        $documentNumber = DocumentNumber::fromString('test-context-1-test-document-1');

        $this->expectDocumentLookup([$documentNumber], [$document]);

        $this->guard->assertCanWrite($dossier, [$documentNumber]);

        $this->addToAssertionCount(1);
    }

    public function testRejectsDocumentLinkedToMultipleDossiers(): void
    {
        $dossier = Mockery::mock(WooDecision::class);
        $document = $this->createDocument([$dossier, Mockery::mock(WooDecision::class)], expectDocumentNumber: true);
        $documentNumber = DocumentNumber::fromString('test-context-1-test-document-1');

        $this->expectDocumentLookup([$documentNumber], [$document]);

        $violations = $this->assertValidationFails($dossier, [$documentNumber]);

        self::assertSame('documents[0]', $violations->get(0)->getPropertyPath());
        self::assertSame(
            'Document "test-context-1-test-document-1" cannot be modified through the Publication API because it is linked to multiple dossiers.',
            $violations->get(0)->getMessage(),
        );
    }

    public function testRejectsDocumentLinkedOutsideCurrentDossier(): void
    {
        $document = $this->createDocument([Mockery::mock(WooDecision::class)], expectDocumentNumber: true);
        $documentNumber = DocumentNumber::fromString('test-context-1-test-document-1');

        $this->expectDocumentLookup([$documentNumber], [$document]);

        $violations = $this->assertValidationFails(Mockery::mock(WooDecision::class), [$documentNumber]);

        self::assertSame('documents[0]', $violations->get(0)->getPropertyPath());
        self::assertSame(
            'Document "test-context-1-test-document-1" cannot be linked to another dossier through the Publication API yet.',
            $violations->get(0)->getMessage(),
        );
    }

    public function testRejectsExistingDocumentForNewDossier(): void
    {
        $document = $this->createDocument([Mockery::mock(WooDecision::class)], expectDocumentNumber: true);
        $documentNumber = DocumentNumber::fromString('test-context-1-test-document-1');

        $this->expectDocumentLookup([$documentNumber], [$document]);

        $violations = $this->assertValidationFails(null, [$documentNumber]);

        self::assertSame('documents[0]', $violations->get(0)->getPropertyPath());
    }

    public function testReportsEveryBlockedDocumentInOneValidationException(): void
    {
        $dossier = Mockery::mock(WooDecision::class);
        $firstDocumentNumber = DocumentNumber::fromString('test-context-1-test-document-1');
        $secondDocumentNumber = DocumentNumber::fromString('test-context-2-test-document-2');
        $firstDocument = $this->createDocument(
            [Mockery::mock(WooDecision::class)],
            'test-context-1-test-document-1',
            expectDocumentNumber: true,
        );
        $secondDocument = $this->createDocument(
            [$dossier, Mockery::mock(WooDecision::class)],
            'test-context-2-test-document-2',
            expectDocumentNumber: true,
        );
        $this->expectDocumentLookup(
            [$firstDocumentNumber, $secondDocumentNumber],
            [$firstDocument, $secondDocument],
        );

        $violations = $this->assertValidationFails($dossier, [$firstDocumentNumber, $secondDocumentNumber]);

        self::assertCount(2, $violations);
        self::assertSame('documents[0]', $violations->get(0)->getPropertyPath());
        self::assertSame('documents[1]', $violations->get(1)->getPropertyPath());
        self::assertStringContainsString('test-context-1-test-document-1', (string) $violations->get(0)->getMessage());
        self::assertStringContainsString('test-context-2-test-document-2', (string) $violations->get(1)->getMessage());
    }

    /**
     * @param list<DocumentNumber> $documentNumbers
     * @param list<Document> $documents
     */
    private function expectDocumentLookup(array $documentNumbers, array $documents): void
    {
        $expectedDocumentNumbers = [];
        foreach ($documentNumbers as $documentNumber) {
            $expectedDocumentNumbers[] = $documentNumber->toString();
        }

        $this->documentRepository
            ->expects('findByDocumentNumbersCaseInsensitive')
            ->with(Mockery::on(static function (array $documentNumbers) use ($expectedDocumentNumbers): bool {
                $actualDocumentNumbers = [];
                foreach ($documentNumbers as $documentNumber) {
                    if (! $documentNumber instanceof DocumentNumber) {
                        return false;
                    }

                    $actualDocumentNumbers[] = $documentNumber->toString();
                }

                return $actualDocumentNumbers === $expectedDocumentNumbers;
            }))
            ->andReturn($documents);
    }

    /**
     * @param list<WooDecision> $dossiers
     */
    private function createDocument(
        array $dossiers,
        string $documentNumber = 'test-context-1-test-document-1',
        bool $expectDocumentNumber = false,
    ): Document&MockInterface {
        $document = Mockery::mock(Document::class);
        $document->expects('getDossiers')->andReturn(new ArrayCollection($dossiers));
        if ($expectDocumentNumber) {
            $document->expects('getDocumentNumber')->andReturn(DocumentNumber::fromString($documentNumber));
        } else {
            $document->expects('getDocumentNumber')->never();
        }

        return $document;
    }

    /**
     * @param list<DocumentNumber> $documentNumbers
     */
    private function assertValidationFails(?WooDecision $dossier, array $documentNumbers): ConstraintViolationListInterface
    {
        try {
            $this->guard->assertCanWrite($dossier, $documentNumbers);
        } catch (ValidationException $exception) {
            return $exception->getConstraintViolationList();
        }

        self::fail('Expected a validation exception');
    }
}
