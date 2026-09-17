<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\WooDecision\Document;

use Mockery;
use Mockery\MockInterface;
use PublicationApi\Api\Dossier\WooDecision\Document\WooDecisionDocumentResponseDtoFactory;
use PublicationApi\Api\Dossier\WooDecision\Document\WooDecisionRelatedDocumentResponseDtoFactory;
use PublicationApi\Api\Dossier\WooDecision\Inquiry\InquiryLinkFactory;
use PublicationApi\Domain\OpenApi\Links\ApiUrlGenerator;
use PublicationApi\Domain\OpenApi\Links\Link;
use PublicationApi\Domain\OpenApi\Links\LinkCollection;
use PublicationApi\Domain\Upload\DocumentUploadStatusService;
use PublicationApi\Domain\Upload\UploadStatus;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use Shared\Domain\Publication\PublicUrlGenerator;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DocumentId;
use Shared\ValueObject\DocumentNumber;
use Shared\ValueObject\ExternalId;
use Shared\ValueObject\Url;

final class WooDecisionDocumentResponseDtoFactoryTest extends UnitTestCase
{
    private ApiUrlGenerator&MockInterface $apiUrlGenerator;
    private DossierPathHelper&MockInterface $dossierPathHelper;
    private DocumentUploadStatusService&MockInterface $documentUploadStatusService;
    private InquiryLinkFactory&MockInterface $inquiryLinkFactory;
    private PublicUrlGenerator&MockInterface $publicUrlGenerator;
    private WooDecisionRelatedDocumentResponseDtoFactory&MockInterface $relatedDocumentResponseDtoFactory;
    private WooDecisionDocumentResponseDtoFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->apiUrlGenerator = Mockery::mock(ApiUrlGenerator::class);
        $this->dossierPathHelper = Mockery::mock(DossierPathHelper::class);
        $this->documentUploadStatusService = Mockery::mock(DocumentUploadStatusService::class);
        $this->inquiryLinkFactory = Mockery::mock(InquiryLinkFactory::class);
        $this->publicUrlGenerator = Mockery::mock(PublicUrlGenerator::class);
        $this->relatedDocumentResponseDtoFactory = Mockery::mock(WooDecisionRelatedDocumentResponseDtoFactory::class);

        $this->factory = new WooDecisionDocumentResponseDtoFactory(
            $this->apiUrlGenerator,
            $this->dossierPathHelper,
            $this->documentUploadStatusService,
            $this->inquiryLinkFactory,
            $this->publicUrlGenerator,
            $this->relatedDocumentResponseDtoFactory,
        );
    }

    public function testPreviewStateOnlyHasUploadLink(): void
    {
        $wooDecision = $this->createWooDecision(DossierStatus::PREVIEW);
        $document = new Document();
        $document->setJudgement(Judgement::PUBLIC);
        $documentNumber = DocumentNumber::fromString('PREFIX-1-1');
        $document->setDocumentNumber($documentNumber);
        $document->setDocumentId(DocumentId::create('1'));
        $wooDecision->addDocument($document);

        $this->apiUrlGenerator
            ->expects('buildUrlFromRoute')
            ->andReturn(Url::create('https://example.com/upload'));

        $this->publicUrlGenerator
            ->expects('buildUrlFromRoute')
            ->never();

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
        self::assertSame($documentNumber, $result[0]->documentNumber);

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
        $documentNumber = DocumentNumber::fromString('PREFIX-1-1');
        $document->setDocumentNumber($documentNumber);
        $document->setDocumentId(DocumentId::create('1'));
        $wooDecision->addDocument($document);

        $this->apiUrlGenerator
            ->expects('buildUrlFromRoute')
            ->once()
            ->andReturn(Url::create('https://example.com/upload'));

        $this->publicUrlGenerator
            ->expects('buildUrlFromRoute')
            ->once()
            ->andReturn(Url::create('https://example.com/file'));

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
        self::assertSame($documentNumber, $result[0]->documentNumber);

        $halLinks = $result[0]->halLinks;
        $links = $halLinks->jsonSerialize();

        $this->assertTrue($links->offsetExists(LinkCollection::UPLOAD));
        $this->assertTrue($links->offsetExists(LinkCollection::PUBLIC));
        $this->assertTrue($links->offsetExists(LinkCollection::FILE));
    }

    public function testInquiryLinksAreAddedWhenTheDocumentHasInquiries(): void
    {
        $inquiry = new Inquiry()->setInquiryNumber('C-1');

        $document = new Document();
        $document->setJudgement(Judgement::PUBLIC);
        $document->setDocumentNumber(DocumentNumber::fromString('PREFIX-1-1'));
        $document->setDocumentId(DocumentId::create('1'));
        $document->addInquiry($inquiry);

        $wooDecision = $this->createWooDecision(DossierStatus::PREVIEW);
        $wooDecision->addDocument($document);

        $this->apiUrlGenerator
            ->expects('buildUrlFromRoute')
            ->andReturn(Url::create('https://example.com/upload'));

        $this->documentUploadStatusService
            ->expects('getUploadStatus')
            ->once()
            ->andReturn(UploadStatus::UPLOAD_REQUIRED);

        $this->relatedDocumentResponseDtoFactory
            ->expects('fromEntities')
            ->once()
            ->andReturn([]);

        $inquiryLink = new Link(Url::create('https://example.com/zaak/token-1'), 'C-1');
        $this->inquiryLinkFactory
            ->expects('fromInquiry')
            ->once()
            ->with($inquiry)
            ->andReturn($inquiryLink);

        $result = $this->factory->fromWooDecision($wooDecision);

        $this->assertCount(1, $result);

        $links = $result[0]->halLinks->jsonSerialize();

        $this->assertTrue($links->offsetExists(LinkCollection::INQUIRIES));
        $this->assertSame([$inquiryLink], $links->offsetGet(LinkCollection::INQUIRIES));
    }

    public function testNoInquiryLinksAreAddedWhenTheDocumentHasNoInquiries(): void
    {
        $wooDecision = $this->createWooDecision(DossierStatus::PREVIEW);
        $document = new Document();
        $document->setJudgement(Judgement::PUBLIC);
        $document->setDocumentNumber(DocumentNumber::fromString('PREFIX-1-1'));
        $document->setDocumentId(DocumentId::create('1'));
        $wooDecision->addDocument($document);

        $this->apiUrlGenerator
            ->expects('buildUrlFromRoute')
            ->andReturn(Url::create('https://example.com/upload'));

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

        $this->assertFalse($result[0]->halLinks->jsonSerialize()->offsetExists(LinkCollection::INQUIRIES));
    }

    private function createWooDecision(DossierStatus $status): WooDecision
    {
        $organisation = new Organisation();
        $wooDecision = new WooDecision();
        $wooDecision->setOrganisation($organisation);
        $wooDecision->setExternalId(ExternalId::create('ext-123'));
        $wooDecision->setStatus($status);
        $wooDecision->setDocumentPrefix('PREFIX');
        $wooDecision->setDossierNumber('DOSSIER-123');

        return $wooDecision;
    }
}
