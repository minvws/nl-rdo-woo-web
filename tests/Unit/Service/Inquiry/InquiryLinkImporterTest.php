<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Inquiry;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DocumentPrefix;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Exception\InquiryLinkImportException;
use Shared\Service\Inquiry\DocumentInquiryNumbers;
use Shared\Service\Inquiry\InquiryChangeset;
use Shared\Service\Inquiry\InquiryLinkImporter;
use Shared\Service\Inquiry\InquiryLinkImportParser;
use Shared\Service\Inquiry\InquiryNumbers;
use Shared\Service\Inquiry\InquiryService;
use Shared\Tests\Unit\Domain\Upload\IterableToGenerator;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Uuid;

class InquiryLinkImporterTest extends UnitTestCase
{
    use IterableToGenerator;

    private InquiryLinkImporter $importer;
    private InquiryService&MockInterface $inquiryService;
    private DocumentRepository&MockInterface $documentRepository;
    private InquiryLinkImportParser&MockInterface $parser;

    protected function setUp(): void
    {
        $this->inquiryService = Mockery::mock(InquiryService::class);
        $this->documentRepository = Mockery::mock(DocumentRepository::class);
        $this->parser = Mockery::mock(InquiryLinkImportParser::class);

        $this->importer = new InquiryLinkImporter(
            $this->inquiryService,
            $this->documentRepository,
            $this->parser,
        );

        parent::setUp();
    }

    public function testParseFailsWithAGenericExceptionIfPrefixDoesNotMatchTheActiveOrganisation(): void
    {
        $upload = Mockery::mock(UploadedFile::class);

        $organisationA = Mockery::mock(Organisation::class);
        $organisationB = Mockery::mock(Organisation::class);

        $prefix = Mockery::mock(DocumentPrefix::class);
        $prefix->expects('getOrganisation')->andReturn($organisationB);

        $result = $this->importer->import($organisationA, $upload, $prefix);

        self::assertFalse($result->isSuccessful());
        self::assertTrue($result->hasGenericExceptions());
    }

    public function testParseSuccessful(): void
    {
        $upload = Mockery::mock(UploadedFile::class);
        $organisation = Mockery::mock(Organisation::class);

        $prefix = Mockery::mock(DocumentPrefix::class);
        $prefix->expects('getOrganisation')->andReturn($organisation);

        $documentNrA = 'foo-xx-123';
        $documentNrB = 'foo-xx-456';

        $this->parser
            ->expects('parse')
            ->with($upload, $prefix)
            ->andReturn($this->iterableToGenerator([
                $documentNrA => ['case1', 'case2'],
                $documentNrB => ['case1'],
            ]));

        $this->documentRepository
            ->expects('getDocumentInquiryNumbers')
            ->with($documentNrA)
            ->andReturn(
                new DocumentInquiryNumbers(Uuid::fromRfc4122('1ef3ea0e-678d-6cee-9604-c962be9d60b2'), InquiryNumbers::empty()),
            );

        $this->documentRepository
            ->expects('getDocumentInquiryNumbers')
            ->with($documentNrB)
            ->andReturn(
                new DocumentInquiryNumbers(Uuid::fromRfc4122('1ef3ea0e-678d-6cee-9604-c962be9d60b1'), InquiryNumbers::empty()),
            );

        $this->inquiryService->expects('applyChangesetAsync')->with(Mockery::on(
            function (InquiryChangeset $changeset): bool {
                $this->assertMatchesJsonSnapshot($changeset->getChanges());

                return true;
            },
        ));

        $result = $this->importer->import($organisation, $upload, $prefix);

        self::assertTrue($result->isSuccessful());
        self::assertEquals(3, $result->getAddedRelationsCount());
    }

    public function testParseReportsInvalidInquiryNumber(): void
    {
        $upload = Mockery::mock(UploadedFile::class);

        $organisation = Mockery::mock(Organisation::class);

        $prefix = Mockery::mock(DocumentPrefix::class);
        $prefix->expects('getOrganisation')->andReturn($organisation);

        $documentNrA = 'foo-xx-123';
        $documentNrB = 'foo-xx-456';

        $this->parser
            ->expects('parse')
            ->with($upload, $prefix)
            ->andReturn($this->iterableToGenerator([
                $documentNrA => ['case1', 'case2'],
                $documentNrB => ['case1', '<script>foo</script>'],
            ]));

        $this->documentRepository
            ->expects('getDocumentInquiryNumbers')
            ->with($documentNrA)
            ->andReturn(
                new DocumentInquiryNumbers(Uuid::fromRfc4122('1ef3ea0e-678d-6cee-9604-c962be9d60b2'), InquiryNumbers::empty()),
            );

        $this->documentRepository
            ->expects('getDocumentInquiryNumbers')
            ->with($documentNrB)
            ->andReturn(
                new DocumentInquiryNumbers(Uuid::fromRfc4122('1ef3ea0e-678d-6cee-9604-c962be9d60b1'), InquiryNumbers::empty()),
            );

        $this->inquiryService->expects('applyChangesetAsync')->with(Mockery::on(
            function (InquiryChangeset $changeset): bool {
                $this->assertMatchesJsonSnapshot($changeset->getChanges());

                return true;
            },
        ));

        $result = $this->importer->import($organisation, $upload, $prefix);

        self::assertFalse($result->isSuccessful());
        self::assertEquals(
            [
                2 => [
                    InquiryLinkImportException::forInvalidInquiryNumber(2, ['case1', '<script>foo</script>']),
                ],
            ],
            $result->rowExceptions,
        );
    }
}
