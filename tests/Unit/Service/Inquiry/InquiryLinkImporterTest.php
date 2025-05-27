<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Inquiry;

use App\Domain\Publication\Dossier\DocumentPrefix;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use App\Entity\Organisation;
use App\Service\Inquiry\DocumentCaseNumbers;
use App\Service\Inquiry\InquiryChangeset;
use App\Service\Inquiry\InquiryLinkImporter;
use App\Service\Inquiry\InquiryLinkImportParser;
use App\Service\Inquiry\InquiryService;
use App\Tests\Unit\Domain\Upload\IterableToGenerator;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Uuid;

class InquiryLinkImporterTest extends MockeryTestCase
{
    use MatchesSnapshots;
    use IterableToGenerator;

    private InquiryLinkImporter $importer;
    private InquiryService&MockInterface $inquiryService;
    private DocumentRepository&MockInterface $documentRepository;
    private InquiryLinkImportParser&MockInterface $parser;

    public function setUp(): void
    {
        $this->inquiryService = \Mockery::mock(InquiryService::class);
        $this->documentRepository = \Mockery::mock(DocumentRepository::class);
        $this->parser = \Mockery::mock(InquiryLinkImportParser::class);

        $this->importer = new InquiryLinkImporter(
            $this->inquiryService,
            $this->documentRepository,
            $this->parser,
        );

        parent::setUp();
    }

    public function testParseFailsWithAGenericExceptionIfPrefixDoesNotMatchTheActiveOrganisation(): void
    {
        $upload = \Mockery::mock(UploadedFile::class);

        $organisationA = \Mockery::mock(Organisation::class);
        $organisationB = \Mockery::mock(Organisation::class);

        $prefix = \Mockery::mock(DocumentPrefix::class);
        $prefix->shouldReceive('getOrganisation')->andReturn($organisationB);

        $result = $this->importer->import($organisationA, $upload, $prefix);

        self::assertFalse($result->isSuccessful());
        self::assertTrue($result->hasGenericExceptions());
    }

    public function testParseSuccessful(): void
    {
        $upload = \Mockery::mock(UploadedFile::class);
        $organisation = \Mockery::mock(Organisation::class);

        $prefix = \Mockery::mock(DocumentPrefix::class);
        $prefix->shouldReceive('getOrganisation')->andReturn($organisation);

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
            ->expects('getDocumentCaseNrs')
            ->with($documentNrA)
            ->andReturn(
                new DocumentCaseNumbers(Uuid::fromRfc4122('1ef3ea0e-678d-6cee-9604-c962be9d60b2'), [])
            );

        $this->documentRepository
            ->expects('getDocumentCaseNrs')
            ->with($documentNrB)
            ->andReturn(
                new DocumentCaseNumbers(Uuid::fromRfc4122('1ef3ea0e-678d-6cee-9604-c962be9d60b1'), [])
            );

        $this->inquiryService->expects('applyChangesetAsync')->with(\Mockery::on(
            function (InquiryChangeset $changeset): bool {
                $this->assertMatchesJsonSnapshot($changeset->getChanges());

                return true;
            }
        ));

        $result = $this->importer->import($organisation, $upload, $prefix);

        self::assertTrue($result->isSuccessful());
        self::assertEquals(3, $result->getAddedRelationsCount());
    }
}
