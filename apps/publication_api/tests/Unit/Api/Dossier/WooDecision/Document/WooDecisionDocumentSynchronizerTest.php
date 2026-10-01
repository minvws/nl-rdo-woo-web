<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\WooDecision\Document;

use Mockery;
use PublicationApi\Api\Dossier\DossierDocumentValidator;
use PublicationApi\Api\Dossier\WooDecision\Document\WooDecisionDocumentRequestDto;
use PublicationApi\Api\Dossier\WooDecision\Document\WooDecisionDocumentSynchronizer;
use PublicationApi\Api\Dossier\WooDecision\Document\WooDecisionDocumentValidator;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\ObsoleteFileRemover;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Ground;
use Shared\Domain\Publication\SourceType;
use Shared\Service\Inventory\DocumentUpdater;
use Shared\Tests\Unit\UnitTestCase;

use function array_map;

final class WooDecisionDocumentSynchronizerTest extends UnitTestCase
{
    public function testValidateRequestDelegatesToTheDocumentValidator(): void
    {
        $wooDecisionDocumentRequestDtos = [
            new WooDecisionDocumentRequestDto(
                [],
                $this->getFaker()->plainDate(),
                $this->getFaker()->documentId(),
                $this->getFaker()->externalId(),
                null,
                $this->getFaker()->fileName(),
                array_map(Ground::from(...), $this->getFaker()->grounds()),
                false,
                Judgement::PUBLIC,
                [],
                [],
                null,
                SourceType::PDF,
                null,
                $this->getFaker()->publicationContext(),
            ),
        ];

        $wooDecisionDocumentValidator = Mockery::mock(WooDecisionDocumentValidator::class);
        $wooDecisionDocumentValidator->expects('validate')->with($wooDecisionDocumentRequestDtos);

        $wooDecisionDocumentSynchronizer = new WooDecisionDocumentSynchronizer(
            Mockery::mock(DocumentRepository::class),
            Mockery::mock(DocumentUpdater::class),
            Mockery::mock(ObsoleteFileRemover::class),
            Mockery::mock(DossierDocumentValidator::class),
            $wooDecisionDocumentValidator,
        );

        $wooDecisionDocumentSynchronizer->validateRequest($wooDecisionDocumentRequestDtos);
    }

    public function testCreateAddsDocuments(): void
    {
        $externalId = $this->getFaker()->externalId();
        $wooDecisionDocumentRequestDto = new WooDecisionDocumentRequestDto(
            [],
            $this->getFaker()->plainDate(),
            $this->getFaker()->documentId(),
            $externalId,
            null,
            $this->getFaker()->fileName(),
            array_map(Ground::from(...), $this->getFaker()->grounds()),
            false,
            Judgement::PUBLIC,
            [],
            [],
            null,
            SourceType::PDF,
            null,
            $this->getFaker()->publicationContext(),
        );
        $wooDecision = new WooDecision();

        $documentRepository = Mockery::mock(DocumentRepository::class);
        $documentRepository->expects('findByDossierAndExternalId')->with($wooDecision, $externalId)->andReturnNull();

        $obsoleteFileRemover = Mockery::mock(ObsoleteFileRemover::class);
        $obsoleteFileRemover->expects('removeIfObsolete')->with(Mockery::type(Document::class));

        $dossierDocumentValidator = Mockery::mock(DossierDocumentValidator::class);
        $dossierDocumentValidator->expects('validate')->with(Mockery::type('array'), $wooDecision->getStatus());

        $wooDecisionDocumentSynchronizer = new WooDecisionDocumentSynchronizer(
            $documentRepository,
            Mockery::mock(DocumentUpdater::class),
            $obsoleteFileRemover,
            $dossierDocumentValidator,
            Mockery::mock(WooDecisionDocumentValidator::class),
        );

        $wooDecisionDocumentSynchronizer->create($wooDecision, [$wooDecisionDocumentRequestDto]);

        self::assertCount(1, $wooDecision->getDocuments());

        $document = $wooDecision->getDocuments()->first();
        self::assertInstanceOf(Document::class, $document);
        self::assertSame($externalId, $document->getExternalId());
    }

    public function testUpdateReplacesDocuments(): void
    {
        $externalId = $this->getFaker()->externalId();
        $wooDecisionDocumentRequestDto = new WooDecisionDocumentRequestDto(
            [],
            $this->getFaker()->plainDate(),
            $this->getFaker()->documentId(),
            $externalId,
            null,
            $this->getFaker()->fileName(),
            array_map(Ground::from(...), $this->getFaker()->grounds()),
            false,
            Judgement::PUBLIC,
            [],
            [],
            null,
            SourceType::PDF,
            null,
            $this->getFaker()->publicationContext(),
        );

        $existingDocument = new Document();
        $existingDocument->setExternalId($externalId);

        $obsoleteDocument = new Document();
        $obsoleteDocument->setExternalId($this->getFaker()->externalId());

        $wooDecision = new WooDecision();
        $wooDecision->addDocument($obsoleteDocument);

        $documentRepository = Mockery::mock(DocumentRepository::class);
        $documentRepository->expects('findByDossierAndExternalId')->with($wooDecision, $externalId)->andReturn($existingDocument);

        $obsoleteFileRemover = Mockery::mock(ObsoleteFileRemover::class);
        $obsoleteFileRemover->expects('removeIfObsolete')->with($existingDocument);

        $dossierDocumentValidator = Mockery::mock(DossierDocumentValidator::class);
        $dossierDocumentValidator->expects('assertDocumentSetUnchangedInNonConcept')->with($wooDecision, [$wooDecisionDocumentRequestDto]);
        $dossierDocumentValidator->expects('validate')->with([$existingDocument], $wooDecision->getStatus());

        $wooDecisionDocumentSynchronizer = new WooDecisionDocumentSynchronizer(
            $documentRepository,
            Mockery::mock(DocumentUpdater::class),
            $obsoleteFileRemover,
            $dossierDocumentValidator,
            Mockery::mock(WooDecisionDocumentValidator::class),
        );

        $wooDecisionDocumentSynchronizer->update($wooDecision, [$wooDecisionDocumentRequestDto]);

        self::assertSame([$existingDocument], $wooDecision->getDocuments()->getValues());
    }

    public function testSynchronizeRefersTo(): void
    {
        $externalId = $this->getFaker()->externalId();
        $refersTo = [$this->getFaker()->externalId()];
        $wooDecisionDocumentRequestDto = new WooDecisionDocumentRequestDto(
            [],
            $this->getFaker()->plainDate(),
            $this->getFaker()->documentId(),
            $externalId,
            null,
            $this->getFaker()->fileName(),
            array_map(Ground::from(...), $this->getFaker()->grounds()),
            false,
            Judgement::PUBLIC,
            [],
            $refersTo,
            null,
            SourceType::PDF,
            null,
            $this->getFaker()->publicationContext(),
        );
        $document = new Document();

        $documentRepository = Mockery::mock(DocumentRepository::class);
        $documentRepository->expects('findByExternalId')->with($externalId)->andReturn($document);

        $documentUpdater = Mockery::mock(DocumentUpdater::class);
        $documentUpdater->expects('updateDocumentReferralsByDocumentExternalId')->with($document, $refersTo);

        $wooDecisionDocumentSynchronizer = new WooDecisionDocumentSynchronizer(
            $documentRepository,
            $documentUpdater,
            Mockery::mock(ObsoleteFileRemover::class),
            Mockery::mock(DossierDocumentValidator::class),
            Mockery::mock(WooDecisionDocumentValidator::class),
        );

        $wooDecisionDocumentSynchronizer->synchronizeRefersTo([$wooDecisionDocumentRequestDto]);
    }

    public function testSynchronizeRefersToSkipsUnknownDocuments(): void
    {
        $externalId = $this->getFaker()->externalId();
        $wooDecisionDocumentRequestDto = new WooDecisionDocumentRequestDto(
            [],
            $this->getFaker()->plainDate(),
            $this->getFaker()->documentId(),
            $externalId,
            null,
            $this->getFaker()->fileName(),
            array_map(Ground::from(...), $this->getFaker()->grounds()),
            false,
            Judgement::PUBLIC,
            [],
            [$this->getFaker()->externalId()],
            null,
            SourceType::PDF,
            null,
            $this->getFaker()->publicationContext(),
        );

        $documentRepository = Mockery::mock(DocumentRepository::class);
        $documentRepository->expects('findByExternalId')->with($externalId)->andReturnNull();

        $wooDecisionDocumentSynchronizer = new WooDecisionDocumentSynchronizer(
            $documentRepository,
            Mockery::mock(DocumentUpdater::class),
            Mockery::mock(ObsoleteFileRemover::class),
            Mockery::mock(DossierDocumentValidator::class),
            Mockery::mock(WooDecisionDocumentValidator::class),
        );

        $wooDecisionDocumentSynchronizer->synchronizeRefersTo([$wooDecisionDocumentRequestDto]);
    }
}
