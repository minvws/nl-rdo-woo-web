<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\WooDecision\Uploads\Document;

use ApiPlatform\Metadata\Put;
use ApiPlatform\Validator\Exception\ValidationException;
use Mockery;
use Mockery\MockInterface;
use PublicationApi\Api\Dossier\WooDecision\Uploads\Document\WooDecisionDocumentWithdrawProcessor;
use PublicationApi\Api\Dossier\WooDecision\Uploads\Document\WooDecisionDocumentWithdrawRequestDto;
use PublicationApi\Api\Organisation\OrganisationResolverInterface;
use PublicationApi\Domain\Exception\EntityNotFoundException;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentDispatcher;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentWithdrawReason;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\ExternalId;
use stdClass;
use Webmozart\Assert\InvalidArgumentException;

final class WooDecisionDocumentWithdrawProcessorTest extends UnitTestCase
{
    private OrganisationResolverInterface&MockInterface $organisationResolver;
    private WooDecisionRepository&MockInterface $wooDecisionRepository;
    private DocumentRepository&MockInterface $documentRepository;
    private DocumentDispatcher&MockInterface $documentDispatcher;
    private Organisation&MockInterface $organisation;
    private WooDecision&MockInterface $wooDecision;
    private Document&MockInterface $document;
    private WooDecisionDocumentWithdrawProcessor $processor;

    /** @var array{organisationId: string, dossierExternalId: string, documentExternalId: string} */
    private array $uriVariables;

    protected function setUp(): void
    {
        $this->organisationResolver = Mockery::mock(OrganisationResolverInterface::class);
        $this->wooDecisionRepository = Mockery::mock(WooDecisionRepository::class);
        $this->documentRepository = Mockery::mock(DocumentRepository::class);
        $this->documentDispatcher = Mockery::mock(DocumentDispatcher::class);
        $this->organisation = Mockery::mock(Organisation::class);
        $this->wooDecision = Mockery::mock(WooDecision::class);
        $this->document = Mockery::mock(Document::class);

        $this->uriVariables = [
            'organisationId' => self::getFaker()->uuid(),
            'dossierExternalId' => self::getFaker()->externalId()->toString(),
            'documentExternalId' => self::getFaker()->externalId()->toString(),
        ];

        $this->processor = new WooDecisionDocumentWithdrawProcessor(
            $this->organisationResolver,
            $this->wooDecisionRepository,
            $this->documentRepository,
            $this->documentDispatcher,
        );

        parent::setUp();
    }

    public function testProcessDispatchesWithdrawCommand(): void
    {
        $this->organisationResolver->expects('resolve')->with($this->uriVariables)->andReturn($this->organisation);
        $this->mockDossierLookup($this->wooDecision);
        $this->mockDocumentLookup($this->document);

        $this->document->expects('shouldBeUploaded')->andReturn(true);
        $this->document->expects('isWithdrawn')->andReturn(false);

        $this->documentDispatcher
            ->expects('dispatchWithdrawDocumentCommand')
            ->with($this->wooDecision, $this->document, DocumentWithdrawReason::DATA_IN_DOCUMENT, 'foo');

        $this->processor->process(
            new WooDecisionDocumentWithdrawRequestDto(DocumentWithdrawReason::DATA_IN_DOCUMENT, 'foo'),
            new Put(),
            $this->uriVariables,
        );
    }

    public function testProcessThrowsOnInvalidData(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->processor->process(new stdClass(), new Put());
    }

    public function testProcessThrowsWhenOrganisationIsUnknown(): void
    {
        $this->organisationResolver
            ->expects('resolve')
            ->with($this->uriVariables)
            ->andThrow(EntityNotFoundException::for('Organisation', $this->uriVariables['organisationId']));

        $this->expectException(EntityNotFoundException::class);

        $this->processor->process(
            new WooDecisionDocumentWithdrawRequestDto(DocumentWithdrawReason::DATA_IN_DOCUMENT, 'foo'),
            new Put(),
            $this->uriVariables,
        );
    }

    public function testProcessThrowsWhenDossierIsUnknown(): void
    {
        $this->organisationResolver->expects('resolve')->with($this->uriVariables)->andReturn($this->organisation);
        $this->mockDossierLookup(null);

        $this->expectException(ValidationException::class);

        $this->processor->process(
            new WooDecisionDocumentWithdrawRequestDto(DocumentWithdrawReason::DATA_IN_DOCUMENT, 'foo'),
            new Put(),
            $this->uriVariables,
        );
    }

    public function testProcessThrowsWhenDocumentIsUnknown(): void
    {
        $this->organisationResolver->expects('resolve')->with($this->uriVariables)->andReturn($this->organisation);
        $this->mockDossierLookup($this->wooDecision);
        $this->mockDocumentLookup(null);

        $this->expectException(ValidationException::class);

        $this->processor->process(
            new WooDecisionDocumentWithdrawRequestDto(DocumentWithdrawReason::DATA_IN_DOCUMENT, 'foo'),
            new Put(),
            $this->uriVariables,
        );
    }

    public function testProcessThrowsWhenDocumentCannotBeWithdrawn(): void
    {
        $this->organisationResolver->expects('resolve')->with($this->uriVariables)->andReturn($this->organisation);
        $this->mockDossierLookup($this->wooDecision);
        $this->mockDocumentLookup($this->document);

        $this->document->expects('shouldBeUploaded')->andReturn(false);

        $this->expectException(ValidationException::class);

        $this->processor->process(
            new WooDecisionDocumentWithdrawRequestDto(DocumentWithdrawReason::DATA_IN_DOCUMENT, 'foo'),
            new Put(),
            $this->uriVariables,
        );
    }

    private function mockDossierLookup(?WooDecision $result): void
    {
        $this->wooDecisionRepository
            ->expects('findByOrganisationAndExternalId')
            ->with(
                $this->organisation,
                Mockery::on(fn ($id): bool => $id instanceof ExternalId && $id->toString() === $this->uriVariables['dossierExternalId']),
            )
            ->andReturn($result);
    }

    private function mockDocumentLookup(?Document $result): void
    {
        $this->documentRepository
            ->expects('findByDossierAndExternalId')
            ->with(
                $this->wooDecision,
                Mockery::on(fn ($id): bool => $id instanceof ExternalId && $id->toString() === $this->uriVariables['documentExternalId']),
            )
            ->andReturn($result);
    }
}
