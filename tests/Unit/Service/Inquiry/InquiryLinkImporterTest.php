<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Inquiry;

use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Exception\Import\ImportException;
use Shared\Exception\Import\SpreadsheetCannotBeOpenedException;
use Shared\Service\Import\ImportError;
use Shared\Service\Inquiry\DocumentInquiryNumbers;
use Shared\Service\Inquiry\InquiryChangeset;
use Shared\Service\Inquiry\InquiryLinkImporter;
use Shared\Service\Inquiry\InquiryLinkImportParser;
use Shared\Service\Inquiry\InquiryLinkImportRow;
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
    private LoggerInterface&MockInterface $logger;

    protected function setUp(): void
    {
        $this->inquiryService = Mockery::mock(InquiryService::class);
        $this->documentRepository = Mockery::mock(DocumentRepository::class);
        $this->parser = Mockery::mock(InquiryLinkImportParser::class);
        $this->logger = Mockery::mock(LoggerInterface::class);

        $this->importer = new InquiryLinkImporter(
            $this->inquiryService,
            $this->documentRepository,
            $this->parser,
            $this->logger,
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
                2 => new InquiryLinkImportRow(DocumentNumber::fromString($documentNumberA), ['case1', 'case2']),
                3 => new InquiryLinkImportRow(DocumentNumber::fromString($documentNumberB), ['case1']),
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
                2 => new InquiryLinkImportRow(DocumentNumber::fromString($documentNumberA), ['case1', 'case2']),
                3 => new InquiryLinkImportRow(DocumentNumber::fromString($documentNumberB), ['case1', '<script>foo</script>']),
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
            [3 => [new ImportError(
                'Invalid inquiry numbers in row #3',
                'publication.inquiry.error.inquiry_numbers_invalid',
                [
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
        $documentNumber = $this->getFaker()->documentNumber();

        $upload = Mockery::mock(UploadedFile::class);
        $organisation = Mockery::mock(Organisation::class);
        $organisation->expects('getPrefix')->andReturn(OrganisationPrefix::create($organisationPrefix));

        $inquiryLinkImportParser = Mockery::mock(InquiryLinkImportParser::class);
        $inquiryLinkImportParser->expects('hasMatterColumn')->with($upload)->andReturnFalse();
        $inquiryLinkImportParser
            ->expects('parse')
            ->with($upload, $organisationPrefix)
            ->andReturn($this->iterableToGenerator([
                2 => new InquiryLinkImportRow($documentNumber, [$this->getFaker()->word()]),
            ]));

        $documentRepository = Mockery::mock(DocumentRepository::class);
        $documentRepository
            ->expects('getDocumentInquiryNumbers')
            ->andReturn(new DocumentInquiryNumbers(null, InquiryNumbers::empty()));

        $inquiryService = Mockery::mock(InquiryService::class);
        $inquiryService->expects('applyChangesetAsync');

        $inquiryLinkImporter = new InquiryLinkImporter(
            $inquiryService,
            $documentRepository,
            $inquiryLinkImportParser,
            Mockery::mock(LoggerInterface::class),
        );

        $result = $inquiryLinkImporter->import($organisation, $upload);

        self::assertEquals(
            [2 => [new ImportError(
                sprintf('Document %s does not exist', $documentNumber->toString()),
                'public.global.no_document_number',
                ['{documentNumber}' => $documentNumber->toString()],
            )]],
            $result->rowErrors,
        );
    }

    public function testImportFailsWhenSpreadsheetCannotBeOpened(): void
    {
        $upload = Mockery::mock(UploadedFile::class);
        $organisation = Mockery::mock(Organisation::class);
        $organisation->expects('getPrefix')->andReturn(OrganisationPrefix::create('FOO-1'));

        $this->parser->expects('hasMatterColumn')->with($upload)->andReturnFalse();
        $this->parser
            ->expects('parse')
            ->with($upload, 'FOO-1')
            ->andThrow(new SpreadsheetCannotBeOpenedException(new RuntimeException()));

        $result = $this->importer->import($organisation, $upload);

        self::assertEquals(
            [new ImportError('Error while opening the spreadsheet', 'publication.dossier.error.opening_inventory_file')],
            $result->genericErrors,
        );
    }

    public function testImportReportsAndLogsUnexpectedErrorsAsGenericError(): void
    {
        $upload = Mockery::mock(UploadedFile::class);
        $organisation = Mockery::mock(Organisation::class);
        $organisation->expects('getPrefix')->andReturn(OrganisationPrefix::create('FOO-1'));

        $exception = new RuntimeException('foo');

        $this->parser->expects('hasMatterColumn')->with($upload)->andReturnFalse();
        $this->parser
            ->expects('parse')
            ->with($upload, 'FOO-1')
            ->andThrow($exception);

        $this->logger->expects('error')->with(Mockery::type('string'), Mockery::on(
            static fn (array $context): bool => $context['rowNumber'] === null && $context['exception'] instanceof ImportException
                && $context['exception']->getPrevious() === $exception,
        ));

        $result = $this->importer->import($organisation, $upload);

        self::assertEquals(
            [new ImportError('Uncaught exception during inquiry link import: foo', 'publication.inquiry.error.processing_failed')],
            $result->genericErrors,
        );
    }

    public function testImportReportsAndLogsUnexpectedRowErrorsAndContinuesWithTheNextRow(): void
    {
        $upload = Mockery::mock(UploadedFile::class);
        $organisation = Mockery::mock(Organisation::class);
        $organisation->expects('getPrefix')->andReturn(OrganisationPrefix::create('FOO-1'));

        $documentNumberA = DocumentNumber::fromString('foo-xx-123');
        $documentNumberB = DocumentNumber::fromString('foo-xx-456');
        $exception = new RuntimeException('foo');

        $this->parser->expects('hasMatterColumn')->with($upload)->andReturnFalse();
        $this->parser
            ->expects('parse')
            ->with($upload, 'FOO-1')
            ->andReturn($this->iterableToGenerator([
                2 => new InquiryLinkImportRow($documentNumberA, ['case1']),
                3 => new InquiryLinkImportRow($documentNumberB, ['case1']),
            ]));

        $this->documentRepository
            ->expects('getDocumentInquiryNumbers')
            ->with($documentNumberA)
            ->andThrow($exception);

        $this->documentRepository
            ->expects('getDocumentInquiryNumbers')
            ->with($documentNumberB)
            ->andReturn(
                new DocumentInquiryNumbers(Uuid::fromRfc4122('1ef3ea0e-678d-6cee-9604-c962be9d60b1'), InquiryNumbers::empty()),
            );

        $this->logger->expects('error')->with(Mockery::type('string'), Mockery::on(
            static fn (array $context): bool => $context['rowNumber'] === 2 && $context['exception'] instanceof ImportException
                && $context['exception']->getPrevious() === $exception,
        ));

        $this->inquiryService->expects('applyChangesetAsync');

        $result = $this->importer->import($organisation, $upload);

        self::assertEquals(
            [2 => [new ImportError('Uncaught exception during inquiry link import: foo', 'publication.inquiry.error.processing_failed')]],
            $result->rowErrors,
        );
        self::assertEquals(1, $result->getAddedRelationsCount());
    }
}
