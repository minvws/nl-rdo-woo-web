<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\DraftDecision;

use InvalidArgumentException;
use Mockery;
use PublicationApi\Api\Dossier\DraftDecision\DraftDecisionAttachmentSynchronizer;
use PublicationApi\Api\Dossier\DraftDecision\DraftDecisionMainDocumentRequestDto;
use PublicationApi\Api\Dossier\DraftDecision\DraftDecisionMainDocumentSynchronizer;
use PublicationApi\Api\Dossier\DraftDecision\DraftDecisionPersister;
use PublicationApi\Api\Dossier\DraftDecision\DraftDecisionRequestDto;
use PublicationApi\Api\Dossier\DraftDecision\DraftDecisionRequestMapper;
use PublicationApi\Api\Dossier\DraftDecision\DraftDecisionSnapshot;
use PublicationApi\Api\Dossier\DraftDecision\DraftDecisionStrategy;
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
            Mockery::mock(DraftDecisionPersister::class),
            Mockery::mock(DraftDecisionRequestMapper::class),
            Mockery::mock(DraftDecisionAttachmentSynchronizer::class),
            Mockery::mock(DraftDecisionMainDocumentSynchronizer::class),
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

        $draftDecisionMainDocumentSynchronizer = Mockery::mock(DraftDecisionMainDocumentSynchronizer::class);
        $draftDecisionMainDocumentSynchronizer->expects('create')->with($draftDecision, $mainDocumentRequestDto);

        $draftDecisionAttachmentSynchronizer = Mockery::mock(DraftDecisionAttachmentSynchronizer::class);
        $draftDecisionAttachmentSynchronizer->expects('create')
            ->with($draftDecision, $draftDecisionRequestDto->attachments)
            ->andReturn($attachmentEvents);

        $draftDecisionPersister = Mockery::mock(DraftDecisionPersister::class);
        $draftDecisionPersister->expects('persist')->with($draftDecision, null, $attachmentEvents);

        $draftDecisionStrategy = new DraftDecisionStrategy(
            $draftDecisionPersister,
            $draftDecisionRequestMapper,
            $draftDecisionAttachmentSynchronizer,
            $draftDecisionMainDocumentSynchronizer,
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
            Mockery::mock(DraftDecisionPersister::class),
            $draftDecisionRequestMapper,
            Mockery::mock(DraftDecisionAttachmentSynchronizer::class),
            Mockery::mock(DraftDecisionMainDocumentSynchronizer::class),
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
        $draftDecisionSnapshot = DraftDecisionSnapshot::of($draftDecision);
        $attachmentEvents = [];

        $draftDecisionRequestMapper = Mockery::mock(DraftDecisionRequestMapper::class);
        $draftDecisionRequestMapper->expects('update')
            ->with($draftDecision, $draftDecisionRequestDto, $organisation, $department, $subject);

        $draftDecisionMainDocumentSynchronizer = Mockery::mock(DraftDecisionMainDocumentSynchronizer::class);
        $draftDecisionMainDocumentSynchronizer->expects('update')->with($draftDecision, $mainDocumentRequestDto);

        $draftDecisionAttachmentSynchronizer = Mockery::mock(DraftDecisionAttachmentSynchronizer::class);
        $draftDecisionAttachmentSynchronizer->expects('update')
            ->with($draftDecision, $draftDecisionRequestDto->attachments)
            ->andReturn($attachmentEvents);

        $draftDecisionPersister = Mockery::mock(DraftDecisionPersister::class);
        $draftDecisionPersister->expects('snapshot')->with($draftDecision)->andReturn($draftDecisionSnapshot);
        $draftDecisionPersister->expects('persist')->with($draftDecision, $draftDecisionSnapshot, $attachmentEvents);

        $draftDecisionStrategy = new DraftDecisionStrategy(
            $draftDecisionPersister,
            $draftDecisionRequestMapper,
            $draftDecisionAttachmentSynchronizer,
            $draftDecisionMainDocumentSynchronizer,
        );

        $draftDecisionStrategy->update($draftDecision, $draftDecisionRequestDto, $organisation, $department, $subject);
    }
}
