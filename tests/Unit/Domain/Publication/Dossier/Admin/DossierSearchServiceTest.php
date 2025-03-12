<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Admin;

use App\Domain\Publication\Attachment\Repository\AttachmentRepository;
use App\Domain\Publication\Dossier\Admin\DossierSearchService;
use App\Domain\Publication\Dossier\Admin\SearchParameters;
use App\Domain\Publication\Dossier\DossierRepository;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\MainDocument\MainDocumentRepository;
use App\Entity\Organisation;
use App\Service\Security\Authorization\AuthorizationMatrix;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class DossierSearchServiceTest extends MockeryTestCase
{
    private DossierRepository&MockInterface $dossierRepository;
    private DocumentRepository&MockInterface $documentRepository;
    private MainDocumentRepository&MockInterface $abstractMainDocumentRepository;
    private AttachmentRepository&MockInterface $abstractAttachmentRepository;
    private AuthorizationMatrix&MockInterface $authorizationMatrix;
    private DossierSearchService $searchService;
    private Organisation&MockInterface $organisation;

    public function setUp(): void
    {
        $this->organisation = \Mockery::mock(Organisation::class);

        $this->dossierRepository = \Mockery::mock(DossierRepository::class);
        $this->documentRepository = \Mockery::mock(DocumentRepository::class);
        $this->abstractMainDocumentRepository = \Mockery::mock(MainDocumentRepository::class);
        $this->abstractAttachmentRepository = \Mockery::mock(AttachmentRepository::class);

        $this->authorizationMatrix = \Mockery::mock(AuthorizationMatrix::class);
        $this->authorizationMatrix->shouldReceive('getActiveOrganisation')->andReturn($this->organisation);

        $this->searchService = new DossierSearchService(
            $this->dossierRepository,
            $this->documentRepository,
            $this->abstractMainDocumentRepository,
            $this->abstractAttachmentRepository,
            $this->authorizationMatrix,
        );
    }

    public function testSearchDossiers(): void
    {
        $searchParameters = new SearchParameters(searchTerm: 'foo bar');
        $dossier = \Mockery::mock(WooDecision::class);

        $this->dossierRepository
            ->expects('findBySearchTerm')
            ->with($searchParameters->searchTerm, DossierSearchService::SEARCH_RESULT_LIMIT, $this->organisation, null, null)
            ->andReturn([$dossier]);

        self::assertEquals(
            [$dossier],
            $this->searchService->searchDossiers($searchParameters),
        );
    }

    public function testSearchDocuments(): void
    {
        $searchParameters = new SearchParameters(searchTerm: 'foo bar');
        $document = \Mockery::mock(Document::class);

        $this->documentRepository
            ->expects('findBySearchTerm')
            ->with($searchParameters->searchTerm, DossierSearchService::SEARCH_RESULT_LIMIT, $this->organisation, null)
            ->andReturn([$document]);

        self::assertEquals(
            [$document],
            $this->searchService->searchDocuments($searchParameters),
        );
    }

    public function testSearchMainDocuments(): void
    {
        $searchParameters = new SearchParameters(searchTerm: 'foo bar');
        $document = \Mockery::mock(Document::class);

        $this->abstractMainDocumentRepository
            ->expects('findBySearchTerm')
            ->with($searchParameters->searchTerm, DossierSearchService::SEARCH_RESULT_LIMIT, $this->organisation, null, null)
            ->andReturn([$document]);

        self::assertEquals(
            [$document],
            $this->searchService->searchMainDocuments($searchParameters),
        );
    }

    public function testSearchAttachments(): void
    {
        $searchParameters = new SearchParameters(searchTerm: 'foo bar');
        $document = \Mockery::mock(Document::class);

        $this->abstractAttachmentRepository
            ->expects('findBySearchTerm')
            ->with($searchParameters->searchTerm, DossierSearchService::SEARCH_RESULT_LIMIT, $this->organisation, null, null)
            ->andReturn([$document]);

        self::assertEquals(
            [$document],
            $this->searchService->searchAttachments($searchParameters),
        );
    }
}
