<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\Covenant;

use Mockery;
use PublicationApi\Api\Dossier\Covenant\CovenantMainDocumentRequestDto;
use PublicationApi\Api\Dossier\Covenant\CovenantMainDocumentRequestMapper;
use PublicationApi\Api\Dossier\Covenant\CovenantRequestDto;
use PublicationApi\Api\Dossier\Covenant\CovenantRequestMapper;
use PublicationApi\Api\Dossier\Covenant\CovenantStrategy;
use PublicationApi\Api\Dossier\DossierAttachmentSynchronizer;
use PublicationApi\Api\Dossier\DossierMainDocumentOrNoticeSynchronizer;
use PublicationApi\Api\Dossier\DossierPersister;
use PublicationApi\Api\Dossier\MainDocumentSnapshot;
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
            Mockery::mock(DossierPersister::class),
            Mockery::mock(CovenantRequestMapper::class),
            Mockery::mock(DossierAttachmentSynchronizer::class),
            Mockery::mock(CovenantMainDocumentRequestMapper::class),
            Mockery::mock(DossierMainDocumentOrNoticeSynchronizer::class),
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

        $covenantMainDocumentRequestMapper = Mockery::mock(CovenantMainDocumentRequestMapper::class);

        $dossierMainDocumentOrNoticeSynchronizer = Mockery::mock(DossierMainDocumentOrNoticeSynchronizer::class);
        $dossierMainDocumentOrNoticeSynchronizer->expects('create')
            ->with($covenant, $mainDocumentRequestDto, null, $covenantMainDocumentRequestMapper);

        $dossierAttachmentSynchronizer = Mockery::mock(DossierAttachmentSynchronizer::class);
        $dossierAttachmentSynchronizer->expects('create')
            ->with($covenant, $covenantRequestDto->attachments)
            ->andReturn($attachmentEvents);

        $dossierPersister = Mockery::mock(DossierPersister::class);
        $dossierPersister->expects('persist')->with($covenant, null, $attachmentEvents);

        $covenantStrategy = new CovenantStrategy(
            $dossierPersister,
            $covenantRequestMapper,
            $dossierAttachmentSynchronizer,
            $covenantMainDocumentRequestMapper,
            $dossierMainDocumentOrNoticeSynchronizer,
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

        $covenantMainDocumentRequestMapper = Mockery::mock(CovenantMainDocumentRequestMapper::class);

        $dossierMainDocumentOrNoticeSynchronizer = Mockery::mock(DossierMainDocumentOrNoticeSynchronizer::class);
        $dossierMainDocumentOrNoticeSynchronizer->expects('create')
            ->with($covenant, null, $noticeNotPublicRequestDto, $covenantMainDocumentRequestMapper);

        $dossierAttachmentSynchronizer = Mockery::mock(DossierAttachmentSynchronizer::class);
        $dossierAttachmentSynchronizer->expects('create')->andReturn($attachmentEvents);

        $dossierPersister = Mockery::mock(DossierPersister::class);
        $dossierPersister->expects('persist')->with($covenant, null, $attachmentEvents);

        $covenantStrategy = new CovenantStrategy(
            $dossierPersister,
            $covenantRequestMapper,
            $dossierAttachmentSynchronizer,
            $covenantMainDocumentRequestMapper,
            $dossierMainDocumentOrNoticeSynchronizer,
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
        $mainDocumentSnapshot = MainDocumentSnapshot::of($covenant);
        $attachmentEvents = [];

        $covenantRequestMapper = Mockery::mock(CovenantRequestMapper::class);
        $covenantRequestMapper->expects('update')->with($covenant, $covenantRequestDto, $organisation, $department, $subject);

        $covenantMainDocumentRequestMapper = Mockery::mock(CovenantMainDocumentRequestMapper::class);

        $dossierMainDocumentOrNoticeSynchronizer = Mockery::mock(DossierMainDocumentOrNoticeSynchronizer::class);
        $dossierMainDocumentOrNoticeSynchronizer->expects('update')
            ->with($covenant, $mainDocumentRequestDto, null, $covenantMainDocumentRequestMapper);

        $dossierAttachmentSynchronizer = Mockery::mock(DossierAttachmentSynchronizer::class);
        $dossierAttachmentSynchronizer->expects('update')
            ->with($covenant, $covenantRequestDto->attachments)
            ->andReturn($attachmentEvents);

        $dossierPersister = Mockery::mock(DossierPersister::class);
        $dossierPersister->expects('snapshot')->with($covenant)->andReturn($mainDocumentSnapshot);
        $dossierPersister->expects('persist')->with($covenant, $mainDocumentSnapshot, $attachmentEvents);

        $covenantStrategy = new CovenantStrategy(
            $dossierPersister,
            $covenantRequestMapper,
            $dossierAttachmentSynchronizer,
            $covenantMainDocumentRequestMapper,
            $dossierMainDocumentOrNoticeSynchronizer,
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
        $mainDocumentSnapshot = MainDocumentSnapshot::of($covenant);
        $attachmentEvents = [];

        $covenantRequestMapper = Mockery::mock(CovenantRequestMapper::class);
        $covenantRequestMapper->expects('update')->with($covenant, $covenantRequestDto, $organisation, $department, null);

        $covenantMainDocumentRequestMapper = Mockery::mock(CovenantMainDocumentRequestMapper::class);

        $dossierMainDocumentOrNoticeSynchronizer = Mockery::mock(DossierMainDocumentOrNoticeSynchronizer::class);
        $dossierMainDocumentOrNoticeSynchronizer->expects('update')
            ->with($covenant, null, $noticeNotPublicRequestDto, $covenantMainDocumentRequestMapper);

        $dossierAttachmentSynchronizer = Mockery::mock(DossierAttachmentSynchronizer::class);
        $dossierAttachmentSynchronizer->expects('update')->andReturn($attachmentEvents);

        $dossierPersister = Mockery::mock(DossierPersister::class);
        $dossierPersister->expects('snapshot')->with($covenant)->andReturn($mainDocumentSnapshot);
        $dossierPersister->expects('persist')->with($covenant, $mainDocumentSnapshot, $attachmentEvents);

        $covenantStrategy = new CovenantStrategy(
            $dossierPersister,
            $covenantRequestMapper,
            $dossierAttachmentSynchronizer,
            $covenantMainDocumentRequestMapper,
            $dossierMainDocumentOrNoticeSynchronizer,
        );

        $covenantStrategy->update($covenant, $covenantRequestDto, $organisation, $department, null);
    }
}
