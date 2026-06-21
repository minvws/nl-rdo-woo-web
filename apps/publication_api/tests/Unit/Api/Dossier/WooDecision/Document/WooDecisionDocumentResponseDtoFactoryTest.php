<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\WooDecision\Document;

use Mockery;
use Mockery\MockInterface;
use PublicationApi\Api\Dossier\WooDecision\Document\WooDecisionDocumentResponseDtoFactory;
use PublicationApi\Api\Dossier\WooDecision\Document\WooDecisionRelatedDocumentResponseDtoFactory;
use PublicationApi\Domain\OpenApi\Links\LinkCollection;
use PublicationApi\Domain\Upload\DocumentUploadStatusService;
use PublicationApi\Domain\Upload\UploadStatus;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use Shared\Domain\Publication\PublicUrlGenerator;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\ExternalId;
use Shared\ValueObject\Url;

final class WooDecisionDocumentResponseDtoFactoryTest extends UnitTestCase
{
    private DossierPathHelper&MockInterface $dossierPathHelper;
    private DocumentUploadStatusService&MockInterface $documentUploadStatusService;
    private PublicUrlGenerator&MockInterface $publicUrlGenerator;
    private WooDecisionRelatedDocumentResponseDtoFactory&MockInterface $relatedDocumentResponseDtoFactory;
    private WooDecisionDocumentResponseDtoFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dossierPathHelper = Mockery::mock(DossierPathHelper::class);
        $this->documentUploadStatusService = Mockery::mock(DocumentUploadStatusService::class);
        $this->publicUrlGenerator = Mockery::mock(PublicUrlGenerator::class);
        $this->relatedDocumentResponseDtoFactory = Mockery::mock(WooDecisionRelatedDocumentResponseDtoFactory::class);

        $this->factory = new WooDecisionDocumentResponseDtoFactory(
            $this->dossierPathHelper,
            $this->documentUploadStatusService,
            $this->publicUrlGenerator,
            $this->relatedDocumentResponseDtoFactory,
        );
    }

    public function testPreviewStateOnlyHasUploadLink(): void
    {
        $wooDecision = $this->createWooDecision(DossierStatus::PREVIEW);
        $document = new Document();
        $document->setJudgement(Judgement::PUBLIC);
        $document->setDocumentNr('PREFIX-1-1');
        $wooDecision->addDocument($document);

        $this->publicUrlGenerator
            ->expects('buildUrlFromRoute')
            ->andReturn(Url::create('https://example.com/upload'));

        $this->dossierPathHelper
            ->expects('getAbsoluteDetailsPath')
            ->never();

        $this->documentUploadStatusService
            ->expects('getUploadStatus')
            ->once()
            ->andReturn(UploadStatus::UPLOAD_REQUIRED);

        $this->relatedDocumentResponseDtoFactory
            ->expects('fromEntities')
            ->once()
            ->andReturn([]);

        $result = $this->factory->fromWooDecision($wooDecision);

        $this->assertCount(1, $result);

        /** @var LinkCollection $halLinks */
        $halLinks = $result[0]->halLinks;
        $links = $halLinks->jsonSerialize();

        $this->assertTrue($links->offsetExists(LinkCollection::UPLOAD));
        $this->assertFalse($links->offsetExists(LinkCollection::PUBLIC));
        $this->assertFalse($links->offsetExists(LinkCollection::FILE));
    }

    public function testPublishedStateHasAllLinks(): void
    {
        $wooDecision = $this->createWooDecision(DossierStatus::PUBLISHED);
        $document = new Document();
        $document->setJudgement(Judgement::PUBLIC);
        $document->setDocumentNr('PREFIX-1-1');
        $wooDecision->addDocument($document);

        $this->publicUrlGenerator
            ->expects('buildUrlFromRoute')
            ->twice()
            ->andReturn(
                Url::create('https://example.com/upload'),
                Url::create('https://example.com/file'),
            );

        $this->dossierPathHelper
            ->expects('getAbsoluteDetailsPath')
            ->once()
            ->andReturn('https://example.com/dossier');

        $this->documentUploadStatusService
            ->expects('getUploadStatus')
            ->once()
            ->andReturn(UploadStatus::PROCESSED);

        $this->relatedDocumentResponseDtoFactory
            ->expects('fromEntities')
            ->once()
            ->andReturn([]);

        $result = $this->factory->fromWooDecision($wooDecision);

        $this->assertCount(1, $result);

        /** @var LinkCollection $halLinks */
        $halLinks = $result[0]->halLinks;
        $links = $halLinks->jsonSerialize();

        $this->assertTrue($links->offsetExists(LinkCollection::UPLOAD));
        $this->assertTrue($links->offsetExists(LinkCollection::PUBLIC));
        $this->assertTrue($links->offsetExists(LinkCollection::FILE));
    }

    private function createWooDecision(DossierStatus $status): WooDecision
    {
        $organisation = new Organisation();
        $wooDecision = new WooDecision();
        $wooDecision->setOrganisation($organisation);
        $wooDecision->setExternalId(ExternalId::create('ext-123'));
        $wooDecision->setStatus($status);
        $wooDecision->setDocumentPrefix('PREFIX');
        $wooDecision->setDossierNr('DOSSIER-123');

        return $wooDecision;
    }
}
