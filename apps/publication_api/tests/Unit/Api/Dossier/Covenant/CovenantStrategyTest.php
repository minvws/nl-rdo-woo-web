<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\Covenant;

use Mockery;
use PublicationApi\Api\Dossier\Covenant\CovenantAttachmentSynchronizer;
use PublicationApi\Api\Dossier\Covenant\CovenantMainDocumentRequestDto;
use PublicationApi\Api\Dossier\Covenant\CovenantMainDocumentSynchronizer;
use PublicationApi\Api\Dossier\Covenant\CovenantPersister;
use PublicationApi\Api\Dossier\Covenant\CovenantRequestDto;
use PublicationApi\Api\Dossier\Covenant\CovenantRequestMapper;
use PublicationApi\Api\Dossier\Covenant\CovenantSnapshot;
use PublicationApi\Api\Dossier\Covenant\CovenantStrategy;
use PublicationApi\Api\Dossier\NoticeNotPublicSynchronizer;
use PublicationApi\Api\NoticeNotPublic\NoticeNotPublicRequestDto;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Ground;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;
use Symfony\Component\Uid\Uuid;

use function array_map;

final class CovenantStrategyTest extends UnitTestCase
{
    public function testDossierType(): void
    {
        $covenantStrategy = new CovenantStrategy(
            Mockery::mock(CovenantPersister::class),
            Mockery::mock(CovenantRequestMapper::class),
            Mockery::mock(CovenantAttachmentSynchronizer::class),
            Mockery::mock(CovenantMainDocumentSynchronizer::class),
            Mockery::mock(NoticeNotPublicSynchronizer::class),
        );

        self::assertSame(Covenant::class, $covenantStrategy->dossierType());
    }

    public function testCreateWithMainDocument(): void
    {
        $dossierExternalId = $this->getFaker()->externalId();
        $documentPrefix = $this->getFaker()->documentPrefix();
        $organisation = Mockery::mock(Organisation::class);
        $department = Mockery::mock(Department::class);
        $subject = Mockery::mock(Subject::class);

        $mainDocumentRequestDto = new CovenantMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
        );
        $covenantRequestDto = new CovenantRequestDto(
            Uuid::v6(),
            null,
            $this->getFaker()->sentence(),
            DossierTitle::create($this->getFaker()->sentence()),
            [],
            $this->getFaker()->plainDate(),
            null,
            $this->getFaker()->dossierNumber(),
            $this->getFaker()->plainDate(),
            [$this->getFaker()->company(), $this->getFaker()->company()],
            $this->getFaker()->url(),
            $mainDocumentRequestDto,
            null,
        );

        $covenant = new Covenant();
        $attachmentEvents = [];

        $covenantRequestMapper = Mockery::mock(CovenantRequestMapper::class);
        $covenantRequestMapper->expects('create')->with(
            $covenantRequestDto,
            $organisation,
            $department,
            $subject,
            $dossierExternalId,
            $documentPrefix,
        )->andReturn($covenant);

        $covenantMainDocumentSynchronizer = Mockery::mock(CovenantMainDocumentSynchronizer::class);
        $covenantMainDocumentSynchronizer->expects('create')->with($covenant, $mainDocumentRequestDto);

        $covenantAttachmentSynchronizer = Mockery::mock(CovenantAttachmentSynchronizer::class);
        $covenantAttachmentSynchronizer->expects('create')
            ->with($covenant, $covenantRequestDto->attachments)
            ->andReturn($attachmentEvents);

        $covenantPersister = Mockery::mock(CovenantPersister::class);
        $covenantPersister->expects('persist')->with($covenant, null, $attachmentEvents);

        $covenantStrategy = new CovenantStrategy(
            $covenantPersister,
            $covenantRequestMapper,
            $covenantAttachmentSynchronizer,
            $covenantMainDocumentSynchronizer,
            Mockery::mock(NoticeNotPublicSynchronizer::class),
        );

        self::assertSame($covenant, $covenantStrategy->create(
            $covenantRequestDto,
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
        $covenantRequestDto = new CovenantRequestDto(
            Uuid::v6(),
            null,
            $this->getFaker()->sentence(),
            DossierTitle::create($this->getFaker()->sentence()),
            [],
            $this->getFaker()->plainDate(),
            null,
            $this->getFaker()->dossierNumber(),
            $this->getFaker()->plainDate(),
            [$this->getFaker()->company(), $this->getFaker()->company()],
            $this->getFaker()->url(),
            null,
            $noticeNotPublicRequestDto,
        );

        $covenant = new Covenant();
        $attachmentEvents = [];

        $covenantRequestMapper = Mockery::mock(CovenantRequestMapper::class);
        $covenantRequestMapper->expects('create')->andReturn($covenant);

        $noticeNotPublicSynchronizer = Mockery::mock(NoticeNotPublicSynchronizer::class);
        $noticeNotPublicSynchronizer->expects('create')->with($covenant, $noticeNotPublicRequestDto);

        $covenantAttachmentSynchronizer = Mockery::mock(CovenantAttachmentSynchronizer::class);
        $covenantAttachmentSynchronizer->expects('create')->andReturn($attachmentEvents);

        $covenantPersister = Mockery::mock(CovenantPersister::class);
        $covenantPersister->expects('persist')->with($covenant, null, $attachmentEvents);

        $covenantStrategy = new CovenantStrategy(
            $covenantPersister,
            $covenantRequestMapper,
            $covenantAttachmentSynchronizer,
            Mockery::mock(CovenantMainDocumentSynchronizer::class),
            $noticeNotPublicSynchronizer,
        );

        self::assertSame($covenant, $covenantStrategy->create(
            $covenantRequestDto,
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

        $mainDocumentRequestDto = new CovenantMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
        );
        $covenantRequestDto = new CovenantRequestDto(
            Uuid::v6(),
            null,
            $this->getFaker()->sentence(),
            DossierTitle::create($this->getFaker()->sentence()),
            [],
            $this->getFaker()->plainDate(),
            null,
            $this->getFaker()->dossierNumber(),
            $this->getFaker()->plainDate(),
            [$this->getFaker()->company(), $this->getFaker()->company()],
            $this->getFaker()->url(),
            $mainDocumentRequestDto,
            null,
        );

        $covenant = new Covenant();
        $covenantSnapshot = CovenantSnapshot::of($covenant);
        $attachmentEvents = [];

        $covenantRequestMapper = Mockery::mock(CovenantRequestMapper::class);
        $covenantRequestMapper->expects('update')->with($covenant, $covenantRequestDto, $organisation, $department, $subject);

        $noticeNotPublicSynchronizer = Mockery::mock(NoticeNotPublicSynchronizer::class);
        $noticeNotPublicSynchronizer->expects('delete')->with($covenant);

        $covenantMainDocumentSynchronizer = Mockery::mock(CovenantMainDocumentSynchronizer::class);
        $covenantMainDocumentSynchronizer->expects('update')->with($covenant, $mainDocumentRequestDto);

        $covenantAttachmentSynchronizer = Mockery::mock(CovenantAttachmentSynchronizer::class);
        $covenantAttachmentSynchronizer->expects('update')
            ->with($covenant, $covenantRequestDto->attachments)
            ->andReturn($attachmentEvents);

        $covenantPersister = Mockery::mock(CovenantPersister::class);
        $covenantPersister->expects('snapshot')->with($covenant)->andReturn($covenantSnapshot);
        $covenantPersister->expects('persist')->with($covenant, $covenantSnapshot, $attachmentEvents);

        $covenantStrategy = new CovenantStrategy(
            $covenantPersister,
            $covenantRequestMapper,
            $covenantAttachmentSynchronizer,
            $covenantMainDocumentSynchronizer,
            $noticeNotPublicSynchronizer,
        );

        $covenantStrategy->update($covenant, $covenantRequestDto, $organisation, $department, $subject);
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
        $covenantRequestDto = new CovenantRequestDto(
            Uuid::v6(),
            null,
            $this->getFaker()->sentence(),
            DossierTitle::create($this->getFaker()->sentence()),
            [],
            $this->getFaker()->plainDate(),
            null,
            $this->getFaker()->dossierNumber(),
            $this->getFaker()->plainDate(),
            [$this->getFaker()->company(), $this->getFaker()->company()],
            $this->getFaker()->url(),
            null,
            $noticeNotPublicRequestDto,
        );

        $covenant = new Covenant();
        $covenantSnapshot = CovenantSnapshot::of($covenant);
        $attachmentEvents = [];

        $covenantRequestMapper = Mockery::mock(CovenantRequestMapper::class);
        $covenantRequestMapper->expects('update')->with($covenant, $covenantRequestDto, $organisation, $department, null);

        $covenantMainDocumentSynchronizer = Mockery::mock(CovenantMainDocumentSynchronizer::class);
        $covenantMainDocumentSynchronizer->expects('delete')->with($covenant);

        $noticeNotPublicSynchronizer = Mockery::mock(NoticeNotPublicSynchronizer::class);
        $noticeNotPublicSynchronizer->expects('update')->with($covenant, $noticeNotPublicRequestDto);

        $covenantAttachmentSynchronizer = Mockery::mock(CovenantAttachmentSynchronizer::class);
        $covenantAttachmentSynchronizer->expects('update')->andReturn($attachmentEvents);

        $covenantPersister = Mockery::mock(CovenantPersister::class);
        $covenantPersister->expects('snapshot')->with($covenant)->andReturn($covenantSnapshot);
        $covenantPersister->expects('persist')->with($covenant, $covenantSnapshot, $attachmentEvents);

        $covenantStrategy = new CovenantStrategy(
            $covenantPersister,
            $covenantRequestMapper,
            $covenantAttachmentSynchronizer,
            $covenantMainDocumentSynchronizer,
            $noticeNotPublicSynchronizer,
        );

        $covenantStrategy->update($covenant, $covenantRequestDto, $organisation, $department, null);
    }
}
