<?php

declare(strict_types=1);

namespace App\Tests\Unit\Api\Admin\Publication\Search;

use App\Api\Admin\Publication\Search\SearchResultDtoFactory;
use App\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use App\Domain\Publication\Attachment\Enum\AttachmentType;
use App\Domain\Publication\Attachment\ViewModel\Attachment;
use App\Domain\Publication\Dossier\DossierRepository;
use App\Domain\Publication\Dossier\Type\DossierReference;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\Type\WooDecision\Decision\DecisionType;
use App\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use App\Domain\Publication\Dossier\Type\WooDecision\PublicationReason;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\MainDocument\ViewModel\MainDocument;
use App\Domain\Publication\SourceType;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Result\Dossier\DossierSearchResultEntry;
use App\Domain\Search\Result\Dossier\WooDecision\WooDecisionSearchResult;
use App\Domain\Search\Result\SubType\SubTypeSearchResultEntry;
use App\Domain\Search\Result\SubType\WooDecisionDocument\DocumentViewModel;
use App\Service\DossierWizard\DossierWizardStatus;
use App\Service\DossierWizard\WizardStatusFactory;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;

class SearchResultDtoFactoryTest extends UnitTestCase
{
    private UrlGeneratorInterface&MockInterface $urlGenerator;
    private WizardStatusFactory&MockInterface $wizardStatusFactory;
    private DossierRepository&MockInterface $dossierRepository;
    private SearchResultDtoFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->urlGenerator = \Mockery::mock(UrlGeneratorInterface::class);
        $this->wizardStatusFactory = \Mockery::mock(WizardStatusFactory::class);
        $this->dossierRepository = \Mockery::mock(DossierRepository::class);

        $this->factory = new SearchResultDtoFactory(
            $this->urlGenerator,
            $this->wizardStatusFactory,
            $this->dossierRepository,
        );
    }

    public function testMakeThrowsExceptionForUnsupportedType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->factory->make(new \stdClass());
    }

    public function testMakeCollection(): void
    {
        $wooDecisionId = Uuid::fromRfc4122('55ae5de9-55f4-3420-b50b-5cde6e07fc5a');
        $wooDecisionNr = 'woodecision123';
        $wooDecisionPrefix = 'PREFIX';

        $this->assertMatchesSnapshot(
            $this->factory->makeCollection([
                $this->setupWooDecisionEntry($wooDecisionId, $wooDecisionNr, $wooDecisionPrefix),
                $this->setupWooDecisionDocumentEntry($wooDecisionNr, $wooDecisionPrefix),
                $this->setupWooDecisionAttachmentEntry($wooDecisionNr, $wooDecisionPrefix),
                $this->setupWooDecisionMainDocumentEntry($wooDecisionNr, $wooDecisionPrefix),
            ]),
        );
    }

    private function setupWooDecisionEntry(
        Uuid $wooDecisionId,
        string $wooDecisionNr,
        string $wooDecisionPrefix,
    ): DossierSearchResultEntry {
        $wooDecisionEntry = new DossierSearchResultEntry(
            ElasticDocumentType::WOO_DECISION,
            new WooDecisionSearchResult(
                $wooDecisionId,
                $wooDecisionNr,
                $wooDecisionPrefix,
                'foo bar',
                DecisionType::PUBLIC,
                'summary',
                new \DateTimeImmutable('2024-03-04 12:10:45'),
                new \DateTimeImmutable('2023-03-04 12:10:45'),
                10,
                PublicationReason::WOO_REQUEST,
            ),
            [],
        );

        $this->urlGenerator->shouldReceive('generate')->with(
            'app_admin_dossier',
            [
                'prefix' => $wooDecisionPrefix,
                'dossierId' => $wooDecisionNr,
            ]
        )->andReturn('/link/to/woo-decision');

        return $wooDecisionEntry;
    }

    private function setupWooDecisionDocumentEntry(
        string $wooDecisionNr,
        string $wooDecisionPrefix,
    ): SubTypeSearchResultEntry {
        $entry = new SubTypeSearchResultEntry(
            new DocumentViewModel(
                'document123',
                $documentNr = '123',
                '123.pdf',
                SourceType::PDF,
                true,
                456,
                6,
                Judgement::PUBLIC,
                new \DateTimeImmutable('2024-03-07 12:10:45'),
            ),
            [
                new DossierReference(
                    $wooDecisionNr,
                    $wooDecisionPrefix,
                    'foo bar',
                    DossierType::WOO_DECISION,
                ),
            ],
            [],
            ElasticDocumentType::WOO_DECISION_DOCUMENT,
        );

        $this->urlGenerator->shouldReceive('generate')->with(
            'app_admin_dossier_woodecision_document',
            [
                'prefix' => $wooDecisionPrefix,
                'dossierId' => $wooDecisionNr,
                'documentId' => $documentNr,
            ]
        )->andReturn('/link/to/woo-decision-document');

        return $entry;
    }

    private function setupWooDecisionAttachmentEntry(
        string $wooDecisionNr,
        string $wooDecisionPrefix,
    ): SubTypeSearchResultEntry {
        $entry = new SubTypeSearchResultEntry(
            new Attachment(
                'attachment456',
                'attachment title',
                '01-01-2024',
                AttachmentType::CONCESSION,
                null,
                null,
                1,
                '',
                AttachmentLanguage::DUTCH,
                [],
                '',
                '',
                1,
                false,
                null,
                null,
            ),
            [
                0 => new DossierReference(
                    $wooDecisionNr,
                    $wooDecisionPrefix,
                    'foo bar',
                    DossierType::WOO_DECISION,
                ),
            ],
            [],
            ElasticDocumentType::ATTACHMENT,
        );

        $wooDecisionEntity = \Mockery::mock(WooDecision::class);
        $wizardStatus = \Mockery::mock(DossierWizardStatus::class);
        $wizardStatus
            ->expects('getAttachmentStep->getRouteName')
            ->andReturn($attachmentRouteName = 'attachment_route');

        $this->dossierRepository
            ->expects('findOneByPrefixAndDossierNr')
            ->with($wooDecisionPrefix, $wooDecisionNr)
            ->andReturn($wooDecisionEntity);

        $this->wizardStatusFactory
            ->expects('getWizardStatus')
            ->with($wooDecisionEntity)
            ->andReturn($wizardStatus);

        $this->urlGenerator->shouldReceive('generate')->with(
            $attachmentRouteName,
            [
                'prefix' => $wooDecisionPrefix,
                'dossierId' => $wooDecisionNr,
            ]
        )->andReturn('/link/to/woo-decision-attachment');

        return $entry;
    }

    private function setupWooDecisionMainDocumentEntry(
        string $wooDecisionNr,
        string $wooDecisionPrefix,
    ): SubTypeSearchResultEntry {
        $entry = new SubTypeSearchResultEntry(
            new MainDocument(
                'maindoc456',
                'maindocument title',
                '01-01-2024',
                AttachmentType::CONCESSION,
                null,
                null,
                1,
                '',
                AttachmentLanguage::DUTCH,
                [],
                '',
                '',
                1,
                false,
            ),
            [
                0 => new DossierReference(
                    $wooDecisionNr,
                    $wooDecisionPrefix,
                    'foo bar',
                    DossierType::WOO_DECISION,
                ),
            ],
            [],
            ElasticDocumentType::WOO_DECISION_MAIN_DOCUMENT,
        );

        $wooDecisionEntity = \Mockery::mock(WooDecision::class);
        $wizardStatus = \Mockery::mock(DossierWizardStatus::class);
        $wizardStatus
            ->expects('getAttachmentStep->getRouteName')
            ->andReturn($mainDocumentRouteName = 'attachment_route');

        $this->dossierRepository
            ->expects('findOneByPrefixAndDossierNr')
            ->with($wooDecisionPrefix, $wooDecisionNr)
            ->andReturn($wooDecisionEntity);

        $this->wizardStatusFactory
            ->expects('getWizardStatus')
            ->with($wooDecisionEntity)
            ->andReturn($wizardStatus);

        $this->urlGenerator->shouldReceive('generate')->with(
            $mainDocumentRouteName,
            [
                'prefix' => $wooDecisionPrefix,
                'dossierId' => $wooDecisionNr,
            ]
        )->andReturn('/link/to/woo-decision-main-document');

        return $entry;
    }
}
