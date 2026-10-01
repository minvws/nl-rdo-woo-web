<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\Disposition;

use Mockery;
use PublicationApi\Api\Dossier\Disposition\DispositionAttachmentSynchronizer;
use PublicationApi\Api\Dossier\Disposition\DispositionMainDocumentRequestDto;
use PublicationApi\Api\Dossier\Disposition\DispositionMainDocumentSynchronizer;
use PublicationApi\Api\Dossier\Disposition\DispositionPersister;
use PublicationApi\Api\Dossier\Disposition\DispositionRequestDto;
use PublicationApi\Api\Dossier\Disposition\DispositionRequestMapper;
use PublicationApi\Api\Dossier\Disposition\DispositionSnapshot;
use PublicationApi\Api\Dossier\Disposition\DispositionStrategy;
use PublicationApi\Api\Dossier\NoticeNotPublicSynchronizer;
use PublicationApi\Api\NoticeNotPublic\NoticeNotPublicRequestDto;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\Type\Disposition\Disposition;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionMainDocument;
use Shared\Domain\Publication\Ground;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;
use Symfony\Component\Uid\Uuid;

use function array_map;

final class DispositionStrategyTest extends UnitTestCase
{
    public function testDossierType(): void
    {
        $dispositionStrategy = new DispositionStrategy(
            Mockery::mock(DispositionPersister::class),
            Mockery::mock(DispositionRequestMapper::class),
            Mockery::mock(DispositionAttachmentSynchronizer::class),
            Mockery::mock(DispositionMainDocumentSynchronizer::class),
            Mockery::mock(NoticeNotPublicSynchronizer::class),
        );

        self::assertSame(Disposition::class, $dispositionStrategy->dossierType());
    }

    public function testCreateWithMainDocument(): void
    {
        $dossierExternalId = $this->getFaker()->externalId();
        $documentPrefix = $this->getFaker()->documentPrefix();
        $organisation = Mockery::mock(Organisation::class);
        $department = Mockery::mock(Department::class);
        $subject = Mockery::mock(Subject::class);

        $mainDocumentRequestDto = new DispositionMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->attachmentType(DispositionMainDocument::getAllowedTypes()),
        );
        $dispositionRequestDto = new DispositionRequestDto(
            Uuid::v6(),
            null,
            $this->getFaker()->sentence(),
            DossierTitle::create($this->getFaker()->sentence()),
            [],
            $this->getFaker()->plainDate(),
            $this->getFaker()->dossierNumber(),
            $this->getFaker()->plainDate(),
            $mainDocumentRequestDto,
            null,
        );

        $disposition = new Disposition();
        $attachmentEvents = [];

        $dispositionRequestMapper = Mockery::mock(DispositionRequestMapper::class);
        $dispositionRequestMapper->expects('create')->with(
            $dispositionRequestDto,
            $organisation,
            $department,
            $subject,
            $dossierExternalId,
            $documentPrefix,
        )->andReturn($disposition);

        $dispositionMainDocumentSynchronizer = Mockery::mock(DispositionMainDocumentSynchronizer::class);
        $dispositionMainDocumentSynchronizer->expects('create')->with($disposition, $mainDocumentRequestDto);

        $dispositionAttachmentSynchronizer = Mockery::mock(DispositionAttachmentSynchronizer::class);
        $dispositionAttachmentSynchronizer->expects('create')
            ->with($disposition, $dispositionRequestDto->attachments)
            ->andReturn($attachmentEvents);

        $dispositionPersister = Mockery::mock(DispositionPersister::class);
        $dispositionPersister->expects('persist')->with($disposition, null, $attachmentEvents);

        $dispositionStrategy = new DispositionStrategy(
            $dispositionPersister,
            $dispositionRequestMapper,
            $dispositionAttachmentSynchronizer,
            $dispositionMainDocumentSynchronizer,
            Mockery::mock(NoticeNotPublicSynchronizer::class),
        );

        self::assertSame($disposition, $dispositionStrategy->create(
            $dispositionRequestDto,
            $organisation,
            $department,
            $subject,
            $dossierExternalId,
            $documentPrefix,
        ));
    }

    public function testCreateWithNoticeNotPublic(): void
    {
        $dossierExternalId = $this->getFaker()->externalId();
        $documentPrefix = $this->getFaker()->documentPrefix();
        $organisation = Mockery::mock(Organisation::class);
        $department = Mockery::mock(Department::class);

        $noticeNotPublicRequestDto = new NoticeNotPublicRequestDto(
            $this->getFaker()->plainDate(),
            $this->getFaker()->sentence(),
            array_map(Ground::from(...), $this->getFaker()->grounds()),
        );
        $dispositionRequestDto = new DispositionRequestDto(
            Uuid::v6(),
            null,
            $this->getFaker()->sentence(),
            DossierTitle::create($this->getFaker()->sentence()),
            [],
            $this->getFaker()->plainDate(),
            $this->getFaker()->dossierNumber(),
            $this->getFaker()->plainDate(),
            null,
            $noticeNotPublicRequestDto,
        );

        $disposition = new Disposition();
        $attachmentEvents = [];

        $dispositionRequestMapper = Mockery::mock(DispositionRequestMapper::class);
        $dispositionRequestMapper->expects('create')->andReturn($disposition);

        $noticeNotPublicSynchronizer = Mockery::mock(NoticeNotPublicSynchronizer::class);
        $noticeNotPublicSynchronizer->expects('create')->with($disposition, $noticeNotPublicRequestDto);

        $dispositionAttachmentSynchronizer = Mockery::mock(DispositionAttachmentSynchronizer::class);
        $dispositionAttachmentSynchronizer->expects('create')->andReturn($attachmentEvents);

        $dispositionPersister = Mockery::mock(DispositionPersister::class);
        $dispositionPersister->expects('persist')->with($disposition, null, $attachmentEvents);

        $dispositionStrategy = new DispositionStrategy(
            $dispositionPersister,
            $dispositionRequestMapper,
            $dispositionAttachmentSynchronizer,
            Mockery::mock(DispositionMainDocumentSynchronizer::class),
            $noticeNotPublicSynchronizer,
        );

        self::assertSame($disposition, $dispositionStrategy->create(
            $dispositionRequestDto,
            $organisation,
            $department,
            null,
            $dossierExternalId,
            $documentPrefix,
        ));
    }

    public function testUpdateWithMainDocumentRemovesNoticeNotPublic(): void
    {
        $organisation = Mockery::mock(Organisation::class);
        $department = Mockery::mock(Department::class);
        $subject = Mockery::mock(Subject::class);

        $mainDocumentRequestDto = new DispositionMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->attachmentType(DispositionMainDocument::getAllowedTypes()),
        );
        $dispositionRequestDto = new DispositionRequestDto(
            Uuid::v6(),
            null,
            $this->getFaker()->sentence(),
            DossierTitle::create($this->getFaker()->sentence()),
            [],
            $this->getFaker()->plainDate(),
            $this->getFaker()->dossierNumber(),
            $this->getFaker()->plainDate(),
            $mainDocumentRequestDto,
            null,
        );

        $disposition = new Disposition();
        $dispositionSnapshot = DispositionSnapshot::of($disposition);
        $attachmentEvents = [];

        $dispositionRequestMapper = Mockery::mock(DispositionRequestMapper::class);
        $dispositionRequestMapper->expects('update')->with($disposition, $dispositionRequestDto, $organisation, $department, $subject);

        $noticeNotPublicSynchronizer = Mockery::mock(NoticeNotPublicSynchronizer::class);
        $noticeNotPublicSynchronizer->expects('delete')->with($disposition);

        $dispositionMainDocumentSynchronizer = Mockery::mock(DispositionMainDocumentSynchronizer::class);
        $dispositionMainDocumentSynchronizer->expects('update')->with($disposition, $mainDocumentRequestDto);

        $dispositionAttachmentSynchronizer = Mockery::mock(DispositionAttachmentSynchronizer::class);
        $dispositionAttachmentSynchronizer->expects('update')
            ->with($disposition, $dispositionRequestDto->attachments)
            ->andReturn($attachmentEvents);

        $dispositionPersister = Mockery::mock(DispositionPersister::class);
        $dispositionPersister->expects('snapshot')->with($disposition)->andReturn($dispositionSnapshot);
        $dispositionPersister->expects('persist')->with($disposition, $dispositionSnapshot, $attachmentEvents);

        $dispositionStrategy = new DispositionStrategy(
            $dispositionPersister,
            $dispositionRequestMapper,
            $dispositionAttachmentSynchronizer,
            $dispositionMainDocumentSynchronizer,
            $noticeNotPublicSynchronizer,
        );

        $dispositionStrategy->update($disposition, $dispositionRequestDto, $organisation, $department, $subject);
    }

    public function testUpdateWithNoticeNotPublicRemovesMainDocument(): void
    {
        $organisation = Mockery::mock(Organisation::class);
        $department = Mockery::mock(Department::class);

        $noticeNotPublicRequestDto = new NoticeNotPublicRequestDto(
            $this->getFaker()->plainDate(),
            $this->getFaker()->sentence(),
            array_map(Ground::from(...), $this->getFaker()->grounds()),
        );
        $dispositionRequestDto = new DispositionRequestDto(
            Uuid::v6(),
            null,
            $this->getFaker()->sentence(),
            DossierTitle::create($this->getFaker()->sentence()),
            [],
            $this->getFaker()->plainDate(),
            $this->getFaker()->dossierNumber(),
            $this->getFaker()->plainDate(),
            null,
            $noticeNotPublicRequestDto,
        );

        $disposition = new Disposition();
        $dispositionSnapshot = DispositionSnapshot::of($disposition);
        $attachmentEvents = [];

        $dispositionRequestMapper = Mockery::mock(DispositionRequestMapper::class);
        $dispositionRequestMapper->expects('update')->with($disposition, $dispositionRequestDto, $organisation, $department, null);

        $dispositionMainDocumentSynchronizer = Mockery::mock(DispositionMainDocumentSynchronizer::class);
        $dispositionMainDocumentSynchronizer->expects('delete')->with($disposition);

        $noticeNotPublicSynchronizer = Mockery::mock(NoticeNotPublicSynchronizer::class);
        $noticeNotPublicSynchronizer->expects('update')->with($disposition, $noticeNotPublicRequestDto);

        $dispositionAttachmentSynchronizer = Mockery::mock(DispositionAttachmentSynchronizer::class);
        $dispositionAttachmentSynchronizer->expects('update')->andReturn($attachmentEvents);

        $dispositionPersister = Mockery::mock(DispositionPersister::class);
        $dispositionPersister->expects('snapshot')->with($disposition)->andReturn($dispositionSnapshot);
        $dispositionPersister->expects('persist')->with($disposition, $dispositionSnapshot, $attachmentEvents);

        $dispositionStrategy = new DispositionStrategy(
            $dispositionPersister,
            $dispositionRequestMapper,
            $dispositionAttachmentSynchronizer,
            $dispositionMainDocumentSynchronizer,
            $noticeNotPublicSynchronizer,
        );

        $dispositionStrategy->update($disposition, $dispositionRequestDto, $organisation, $department, null);
    }
}
