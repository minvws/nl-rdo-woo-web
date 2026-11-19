<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\DraftDecision;

use InvalidArgumentException;
use Mockery;
use PublicationApi\Api\Dossier\DossierMainDocumentSynchronizer;
use PublicationApi\Api\Dossier\DossierPersister;
use PublicationApi\Api\Dossier\DraftDecision\DraftDecisionAttachmentSynchronizer;
use PublicationApi\Api\Dossier\DraftDecision\DraftDecisionMainDocumentRequestDto;
use PublicationApi\Api\Dossier\DraftDecision\DraftDecisionMainDocumentRequestMapper;
use PublicationApi\Api\Dossier\DraftDecision\DraftDecisionRequestDto;
use PublicationApi\Api\Dossier\DraftDecision\DraftDecisionRequestMapper;
use PublicationApi\Api\Dossier\DraftDecision\DraftDecisionStrategy;
use PublicationApi\Api\Dossier\MainDocumentSnapshot;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecision;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecisionMainDocument;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;
use Symfony\Component\Uid\Uuid;

final class DraftDecisionStrategyTest extends UnitTestCase
{
    public function testDossierType(): void
    {
        $draftDecisionStrategy = new DraftDecisionStrategy(
            Mockery::mock(DossierPersister::class),
            Mockery::mock(DraftDecisionRequestMapper::class),
            Mockery::mock(DraftDecisionAttachmentSynchronizer::class),
            Mockery::mock(DraftDecisionMainDocumentRequestMapper::class),
            Mockery::mock(DossierMainDocumentSynchronizer::class),
        );

        self::assertSame(DraftDecision::class, $draftDecisionStrategy->dossierType());
    }

    public function testCreate(): void
    {
        $dossierExternalId = $this->getFaker()->externalId();
        $documentPrefix = $this->getFaker()->documentPrefix();
        $organisation = Mockery::mock(Organisation::class);
        $department = Mockery::mock(Department::class);
        $subject = Mockery::mock(Subject::class);

        $mainDocumentRequestDto = new DraftDecisionMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->attachmentType(DraftDecisionMainDocument::getAllowedTypes()),
        );
        $draftDecisionRequestDto = new DraftDecisionRequestDto(
            Uuid::v6(),
            null,
            $this->getFaker()->sentence(),
            DossierTitle::create($this->getFaker()->sentence()),
            [],
            $this->getFaker()->plainDate(),
            $this->getFaker()->dossierNumber(),
            $this->getFaker()->plainDate(),
            $mainDocumentRequestDto,
        );

        $draftDecision = new DraftDecision();
        $attachmentEvents = [];

        $draftDecisionRequestMapper = Mockery::mock(DraftDecisionRequestMapper::class);
        $draftDecisionRequestMapper->expects('create')->with(
            $draftDecisionRequestDto,
            $organisation,
            $department,
            $subject,
            $dossierExternalId,
            $documentPrefix,
        )->andReturn($draftDecision);

        $draftDecisionMainDocumentRequestMapper = Mockery::mock(DraftDecisionMainDocumentRequestMapper::class);

        $dossierMainDocumentSynchronizer = Mockery::mock(DossierMainDocumentSynchronizer::class);
        $dossierMainDocumentSynchronizer->expects('create')
            ->with($draftDecision, $mainDocumentRequestDto, $draftDecisionMainDocumentRequestMapper);

        $draftDecisionAttachmentSynchronizer = Mockery::mock(DraftDecisionAttachmentSynchronizer::class);
        $draftDecisionAttachmentSynchronizer->expects('create')
            ->with($draftDecision, $draftDecisionRequestDto->attachments)
            ->andReturn($attachmentEvents);

        $dossierPersister = Mockery::mock(DossierPersister::class);
        $dossierPersister->expects('persist')->with($draftDecision, null, $attachmentEvents);

        $draftDecisionStrategy = new DraftDecisionStrategy(
            $dossierPersister,
            $draftDecisionRequestMapper,
            $draftDecisionAttachmentSynchronizer,
            $draftDecisionMainDocumentRequestMapper,
            $dossierMainDocumentSynchronizer,
        );

        self::assertSame($draftDecision, $draftDecisionStrategy->create(
            $draftDecisionRequestDto,
            $organisation,
            $department,
            $subject,
            $dossierExternalId,
            $documentPrefix,
        ));
    }

    public function testCreateWithoutMainDocument(): void
    {
        $organisation = Mockery::mock(Organisation::class);
        $department = Mockery::mock(Department::class);

        $draftDecisionRequestDto = new DraftDecisionRequestDto(
            Uuid::v6(),
            null,
            $this->getFaker()->sentence(),
            DossierTitle::create($this->getFaker()->sentence()),
            [],
            $this->getFaker()->plainDate(),
            $this->getFaker()->dossierNumber(),
            $this->getFaker()->plainDate(),
        );

        $draftDecisionRequestMapper = Mockery::mock(DraftDecisionRequestMapper::class);
        $draftDecisionRequestMapper->expects('create')->andReturn(new DraftDecision());

        $draftDecisionStrategy = new DraftDecisionStrategy(
            Mockery::mock(DossierPersister::class),
            $draftDecisionRequestMapper,
            Mockery::mock(DraftDecisionAttachmentSynchronizer::class),
            Mockery::mock(DraftDecisionMainDocumentRequestMapper::class),
            Mockery::mock(DossierMainDocumentSynchronizer::class),
        );

        self::expectException(InvalidArgumentException::class);

        $draftDecisionStrategy->create(
            $draftDecisionRequestDto,
            $organisation,
            $department,
            null,
            $this->getFaker()->externalId(),
            $this->getFaker()->documentPrefix(),
        );
    }

    public function testUpdate(): void
    {
        $organisation = Mockery::mock(Organisation::class);
        $department = Mockery::mock(Department::class);
        $subject = Mockery::mock(Subject::class);

        $mainDocumentRequestDto = new DraftDecisionMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->attachmentType(DraftDecisionMainDocument::getAllowedTypes()),
        );
        $draftDecisionRequestDto = new DraftDecisionRequestDto(
            Uuid::v6(),
            null,
            $this->getFaker()->sentence(),
            DossierTitle::create($this->getFaker()->sentence()),
            [],
            $this->getFaker()->plainDate(),
            $this->getFaker()->dossierNumber(),
            $this->getFaker()->plainDate(),
            $mainDocumentRequestDto,
        );

        $draftDecision = new DraftDecision();
        $mainDocumentSnapshot = MainDocumentSnapshot::of($draftDecision);
        $attachmentEvents = [];

        $draftDecisionRequestMapper = Mockery::mock(DraftDecisionRequestMapper::class);
        $draftDecisionRequestMapper->expects('update')
            ->with($draftDecision, $draftDecisionRequestDto, $organisation, $department, $subject);

        $draftDecisionMainDocumentRequestMapper = Mockery::mock(DraftDecisionMainDocumentRequestMapper::class);

        $dossierMainDocumentSynchronizer = Mockery::mock(DossierMainDocumentSynchronizer::class);
        $dossierMainDocumentSynchronizer->expects('update')
            ->with($draftDecision, $mainDocumentRequestDto, $draftDecisionMainDocumentRequestMapper);

        $draftDecisionAttachmentSynchronizer = Mockery::mock(DraftDecisionAttachmentSynchronizer::class);
        $draftDecisionAttachmentSynchronizer->expects('update')
            ->with($draftDecision, $draftDecisionRequestDto->attachments)
            ->andReturn($attachmentEvents);

        $dossierPersister = Mockery::mock(DossierPersister::class);
        $dossierPersister->expects('snapshot')->with($draftDecision)->andReturn($mainDocumentSnapshot);
        $dossierPersister->expects('persist')->with($draftDecision, $mainDocumentSnapshot, $attachmentEvents);

        $draftDecisionStrategy = new DraftDecisionStrategy(
            $dossierPersister,
            $draftDecisionRequestMapper,
            $draftDecisionAttachmentSynchronizer,
            $draftDecisionMainDocumentRequestMapper,
            $dossierMainDocumentSynchronizer,
        );

        $draftDecisionStrategy->update($draftDecision, $draftDecisionRequestDto, $organisation, $department, $subject);
    }
}
