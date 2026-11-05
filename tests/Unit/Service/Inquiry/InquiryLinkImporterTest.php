<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Inquiry;

use Mockery;
use Mockery\MockInterface;
use RuntimeException;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Service\Import\ImportError;
use Shared\Service\Inquiry\DocumentInquiryNumbers;
use Shared\Service\Inquiry\InquiryChangeset;
use Shared\Service\Inquiry\InquiryLinkImporter;
use Shared\Service\Inquiry\InquiryLinkImportParser;
use Shared\Service\Inquiry\InquiryNumbers;
use Shared\Service\Inquiry\InquiryService;
use Shared\Tests\Unit\Domain\Upload\IterableToGenerator;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DocumentNumber;
use Shared\ValueObject\OrganisationPrefix;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Uuid;

use function sprintf;

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

    public function testParseSuccessful(): void
    {
        $upload = Mockery::mock(UploadedFile::class);
        $organisation = Mockery::mock(Organisation::class);
        $organisation->expects('getPrefix')->andReturn(OrganisationPrefix::create('FOO-1'));

        $documentNumberA = 'foo-xx-123';
        $documentNumberB = 'foo-xx-456';

        $this->parser->expects('hasMatterColumn')->with($upload)->andReturnFalse();

        $this->parser
            ->expects('parse')
            ->with($upload, 'FOO-1')
            ->andReturn($this->iterableToGenerator([
                $documentNumberA => ['case1', 'case2'],
                $documentNumberB => ['case1'],
            ]));

        $this->documentRepository
            ->expects('getDocumentInquiryNumbers')
            ->with(Mockery::on(
                static fn (DocumentNumber $documentNumber): bool => $documentNumber->toString() === $documentNumberA,
            ))
            ->andReturn(
                new DocumentInquiryNumbers(Uuid::fromRfc4122('1ef3ea0e-678d-6cee-9604-c962be9d60b2'), InquiryNumbers::empty()),
            );

        $this->documentRepository
            ->expects('getDocumentInquiryNumbers')
            ->with(Mockery::on(
                static fn (DocumentNumber $documentNumber): bool => $documentNumber->toString() === $documentNumberB,
            ))
            ->andReturn(
                new DocumentInquiryNumbers(Uuid::fromRfc4122('1ef3ea0e-678d-6cee-9604-c962be9d60b1'), InquiryNumbers::empty()),
            );

        $this->inquiryService->expects('applyChangesetAsync')->with(Mockery::on(
            function (InquiryChangeset $changeset): bool {
                $this->assertMatchesJsonSnapshot($changeset->getChanges());

                return true;
            },
        ));

        $result = $this->importer->import($organisation, $upload);

        self::assertTrue($result->isSuccessful());
        self::assertEquals(3, $result->getAddedRelationsCount());
    }

    public function testParseReportsInvalidInquiryNumber(): void
    {
        $upload = Mockery::mock(UploadedFile::class);

        $organisation = Mockery::mock(Organisation::class);
        $organisation->expects('getPrefix')->andReturn(OrganisationPrefix::create('FOO-1'));

        $documentNumberA = 'foo-xx-123';
        $documentNumberB = 'foo-xx-456';

        $this->parser->expects('hasMatterColumn')->with($upload)->andReturnFalse();

        $this->parser
            ->expects('parse')
            ->with($upload, 'FOO-1')
            ->andReturn($this->iterableToGenerator([
                $documentNumberA => ['case1', 'case2'],
                $documentNumberB => ['case1', '<script>foo</script>'],
            ]));

        $this->documentRepository
            ->expects('getDocumentInquiryNumbers')
            ->with(Mockery::on(
                static fn (DocumentNumber $documentNumber): bool => $documentNumber->toString() === $documentNumberA,
            ))
            ->andReturn(
                new DocumentInquiryNumbers(Uuid::fromRfc4122('1ef3ea0e-678d-6cee-9604-c962be9d60b2'), InquiryNumbers::empty()),
            );

        $this->documentRepository
            ->expects('getDocumentInquiryNumbers')
            ->with(Mockery::on(
                static fn (DocumentNumber $documentNumber): bool => $documentNumber->toString() === $documentNumberB,
            ))
            ->andReturn(
                new DocumentInquiryNumbers(Uuid::fromRfc4122('1ef3ea0e-678d-6cee-9604-c962be9d60b1'), InquiryNumbers::empty()),
            );

        $this->inquiryService->expects('applyChangesetAsync')->with(Mockery::on(
            function (InquiryChangeset $changeset): bool {
                $this->assertMatchesJsonSnapshot($changeset->getChanges());

                return true;
            },
        ));

        $result = $this->importer->import($organisation, $upload);

        self::assertFalse($result->isSuccessful());
        self::assertEquals(
            [2 => [new ImportError(
                'Invalid inquiry numbers',
                'publication.inquiry.error.inquiry_numbers_invalid',
                [
                    '{rownumber}' => '2',
                    '{inquiryNumbers}' => 'case1, <script>foo</script>',
                ],
            )]],
            $result->rowErrors,
        );
    }

    public function testImportWithMatterColumn(): void
    {
        $organisationPrefix = 'FOO-1';

        $upload = Mockery::mock(UploadedFile::class);
        $organisation = Mockery::mock(Organisation::class);
        $organisation->expects('getPrefix')->andReturn(OrganisationPrefix::create($organisationPrefix));

        $this->parser->expects('hasMatterColumn')->with($upload)->andReturnTrue();

        $this->parser
            ->expects('parse')
            ->with($upload, $organisationPrefix)
            ->andReturn($this->iterableToGenerator([]));

        $this->inquiryService->expects('applyChangesetAsync');

        $result = $this->importer->import($organisation, $upload);

        self::assertTrue($result->hasMatter());
    }

    public function testImportFailsWhenDocumentIsNotFound(): void
    {
        $organisationPrefix = $this->getFaker()->documentPrefix();
        $documentNumber = $this->getFaker()->documentNumber()->toString();

        $upload = Mockery::mock(UploadedFile::class);
        $organisation = Mockery::mock(Organisation::class);
        $organisation->expects('getPrefix')->andReturn(OrganisationPrefix::create($organisationPrefix));

        $inquiryLinkImportParser = Mockery::mock(InquiryLinkImportParser::class);
        $inquiryLinkImportParser->expects('hasMatterColumn')->with($upload)->andReturnFalse();
        $inquiryLinkImportParser
            ->expects('parse')
            ->with($upload, $organisationPrefix)
            ->andReturn($this->iterableToGenerator([
                $documentNumber => [$this->getFaker()->word()],
            ]));

        $documentRepository = Mockery::mock(DocumentRepository::class);
        $documentRepository
            ->expects('getDocumentInquiryNumbers')
            ->andReturn(new DocumentInquiryNumbers(null, InquiryNumbers::empty()));

        $inquiryService = Mockery::mock(InquiryService::class);
        $inquiryService->expects('applyChangesetAsync');

        $inquiryLinkImporter = new InquiryLinkImporter($inquiryService, $documentRepository, $inquiryLinkImportParser);

        $result = $inquiryLinkImporter->import($organisation, $upload);

        self::assertEquals(
            [1 => [new ImportError(
                sprintf('Document %s does not exist', $documentNumber),
                'public.global.no_document_number',
                ['{documentNumber}' => $documentNumber],
            )]],
            $result->rowErrors,
        );
    }

    public function testImportFailsWhenSpreadsheetCannotBeOpened(): void
    {
        $organisationPrefix = $this->getFaker()->documentPrefix();

        $upload = Mockery::mock(UploadedFile::class);
        $organisation = Mockery::mock(Organisation::class);
        $organisation->expects('getPrefix')->andReturn(OrganisationPrefix::create($organisationPrefix));

        $inquiryLinkImportParser = Mockery::mock(InquiryLinkImportParser::class);
        $inquiryLinkImportParser->expects('hasMatterColumn')->with($upload)->andReturnFalse();
        $inquiryLinkImportParser
            ->expects('parse')
            ->with($upload, $organisationPrefix)
            ->andThrow(new RuntimeException());

        $inquiryLinkImporter = new InquiryLinkImporter(
            Mockery::mock(InquiryService::class),
            Mockery::mock(DocumentRepository::class),
            $inquiryLinkImportParser,
        );

        $result = $inquiryLinkImporter->import($organisation, $upload);

        self::assertEquals(
            [new ImportError('Error while opening the spreadsheet', 'publication.dossier.error.opening_inventory_file')],
            $result->genericErrors,
        );
    }
}
