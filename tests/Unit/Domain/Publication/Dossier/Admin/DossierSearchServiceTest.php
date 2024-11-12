<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Admin;

use App\Domain\Publication\Attachment\AbstractAttachmentRepository;
use App\Domain\Publication\Dossier\AbstractDossierRepository;
use App\Domain\Publication\Dossier\Admin\DossierSearchService;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\MainDocument\AbstractMainDocumentRepository;
use App\Entity\Document;
use App\Entity\Organisation;
use App\Repository\DocumentRepository;
use App\Service\Security\Authorization\AuthorizationMatrix;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class DossierSearchServiceTest extends MockeryTestCase
{
    private AbstractDossierRepository&MockInterface $dossierRepository;
    private DocumentRepository&MockInterface $documentRepository;
    private AbstractMainDocumentRepository&MockInterface $abstractMainDocumentRepository;
    private AbstractAttachmentRepository&MockInterface $abstractAttachmentRepository;
    private AuthorizationMatrix&MockInterface $authorizationMatrix;
    private DossierSearchService $searchService;
    private Organisation&MockInterface $organisation;

    public function setUp(): void
    {
        $this->organisation = \Mockery::mock(Organisation::class);

        $this->dossierRepository = \Mockery::mock(AbstractDossierRepository::class);
        $this->documentRepository = \Mockery::mock(DocumentRepository::class);
        $this->abstractMainDocumentRepository = \Mockery::mock(AbstractMainDocumentRepository::class);
        $this->abstractAttachmentRepository = \Mockery::mock(AbstractAttachmentRepository::class);

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
        $searchTerm = 'foo bar';
        $dossier = \Mockery::mock(WooDecision::class);

        $this->dossierRepository
            ->expects('findBySearchTerm')
            ->with($searchTerm, DossierSearchService::SEARCH_RESULT_LIMIT, $this->organisation)
            ->andReturn([$dossier]);

        self::assertEquals(
            [$dossier],
            $this->searchService->searchDossiers($searchTerm),
        );
    }

    public function testSearchDocuments(): void
    {
        $searchTerm = 'foo bar';
        $document = \Mockery::mock(Document::class);

        $this->documentRepository
            ->expects('findBySearchTerm')
            ->with($searchTerm, DossierSearchService::SEARCH_RESULT_LIMIT, $this->organisation)
            ->andReturn([$document]);

        self::assertEquals(
            [$document],
            $this->searchService->searchDocuments($searchTerm),
        );
    }

    public function testSearchMainDocuments(): void
    {
        $searchTerm = 'foo bar';
        $document = \Mockery::mock(Document::class);

        $this->abstractMainDocumentRepository
            ->expects('findBySearchTerm')
            ->with($searchTerm, DossierSearchService::SEARCH_RESULT_LIMIT, $this->organisation)
            ->andReturn([$document]);

        self::assertEquals(
            [$document],
            $this->searchService->searchMainDocuments($searchTerm),
        );
    }

    public function testSearchAttachments(): void
    {
        $searchTerm = 'foo bar';
        $document = \Mockery::mock(Document::class);

        $this->abstractAttachmentRepository
            ->expects('findBySearchTerm')
            ->with($searchTerm, DossierSearchService::SEARCH_RESULT_LIMIT, $this->organisation)
            ->andReturn([$document]);

        self::assertEquals(
            [$document],
            $this->searchService->searchAttachments($searchTerm),
        );
    }
}
