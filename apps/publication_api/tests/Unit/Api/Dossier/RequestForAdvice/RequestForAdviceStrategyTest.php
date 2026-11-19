<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\RequestForAdvice;

use Mockery;
use PublicationApi\Api\Dossier\DossierAttachmentSynchronizer;
use PublicationApi\Api\Dossier\DossierMainDocumentOrNoticeSynchronizer;
use PublicationApi\Api\Dossier\DossierPersister;
use PublicationApi\Api\Dossier\MainDocumentSnapshot;
use PublicationApi\Api\Dossier\RequestForAdvice\RequestForAdviceMainDocumentRequestDto;
use PublicationApi\Api\Dossier\RequestForAdvice\RequestForAdviceMainDocumentRequestMapper;
use PublicationApi\Api\Dossier\RequestForAdvice\RequestForAdviceRequestDto;
use PublicationApi\Api\Dossier\RequestForAdvice\RequestForAdviceRequestMapper;
use PublicationApi\Api\Dossier\RequestForAdvice\RequestForAdviceStrategy;
use PublicationApi\Api\NoticeNotPublic\NoticeNotPublicRequestDto;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdvice;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceMainDocument;
use Shared\Domain\Publication\Ground;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;
use Symfony\Component\Uid\Uuid;

use function array_map;

final class RequestForAdviceStrategyTest extends UnitTestCase
{
    public function testDossierType(): void
    {
        $requestForAdviceStrategy = new RequestForAdviceStrategy(
            Mockery::mock(DossierPersister::class),
            Mockery::mock(RequestForAdviceRequestMapper::class),
            Mockery::mock(DossierAttachmentSynchronizer::class),
            Mockery::mock(RequestForAdviceMainDocumentRequestMapper::class),
            Mockery::mock(DossierMainDocumentOrNoticeSynchronizer::class),
        );

        self::assertSame(RequestForAdvice::class, $requestForAdviceStrategy->dossierType());
    }

    public function testCreateWithMainDocument(): void
    {
        $dossierExternalId = $this->getFaker()->externalId();
        $documentPrefix = $this->getFaker()->documentPrefix();
        $organisation = Mockery::mock(Organisation::class);
        $department = Mockery::mock(Department::class);
        $subject = Mockery::mock(Subject::class);

        $mainDocumentRequestDto = new RequestForAdviceMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->attachmentType(RequestForAdviceMainDocument::getAllowedTypes()),
        );
        $requestForAdviceRequestDto = new RequestForAdviceRequestDto(
            Uuid::v6(),
            null,
            $this->getFaker()->sentence(),
            DossierTitle::create($this->getFaker()->sentence()),
            [],
            $this->getFaker()->plainDate(),
            $this->getFaker()->dossierNumber(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->url(),
            [$this->getFaker()->company()],
            $mainDocumentRequestDto,
            null,
        );

        $requestForAdvice = new RequestForAdvice();
        $attachmentEvents = [];

        $requestForAdviceRequestMapper = Mockery::mock(RequestForAdviceRequestMapper::class);
        $requestForAdviceRequestMapper->expects('create')
            ->with(
                $requestForAdviceRequestDto,
                $organisation,
                $department,
                $subject,
                $dossierExternalId,
                $documentPrefix,
            )
            ->andReturn($requestForAdvice);

        $requestForAdviceMainDocumentRequestMapper = Mockery::mock(RequestForAdviceMainDocumentRequestMapper::class);

        $dossierMainDocumentOrNoticeSynchronizer = Mockery::mock(DossierMainDocumentOrNoticeSynchronizer::class);
        $dossierMainDocumentOrNoticeSynchronizer->expects('create')
            ->with($requestForAdvice, $mainDocumentRequestDto, null, $requestForAdviceMainDocumentRequestMapper);

        $dossierAttachmentSynchronizer = Mockery::mock(DossierAttachmentSynchronizer::class);
        $dossierAttachmentSynchronizer->expects('create')
            ->with($requestForAdvice, $requestForAdviceRequestDto->attachments)
            ->andReturn($attachmentEvents);

        $dossierPersister = Mockery::mock(DossierPersister::class);
        $dossierPersister->expects('persist')->with($requestForAdvice, null, $attachmentEvents);

        $requestForAdviceStrategy = new RequestForAdviceStrategy(
            $dossierPersister,
            $requestForAdviceRequestMapper,
            $dossierAttachmentSynchronizer,
            $requestForAdviceMainDocumentRequestMapper,
            $dossierMainDocumentOrNoticeSynchronizer,
        );

        self::assertSame($requestForAdvice, $requestForAdviceStrategy->create(
            $requestForAdviceRequestDto,
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
        $requestForAdviceRequestDto = new RequestForAdviceRequestDto(
            Uuid::v6(),
            null,
            $this->getFaker()->sentence(),
            DossierTitle::create($this->getFaker()->sentence()),
            [],
            $this->getFaker()->plainDate(),
            $this->getFaker()->dossierNumber(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->url(),
            [$this->getFaker()->company()],
            null,
            $noticeNotPublicRequestDto,
        );

        $requestForAdvice = new RequestForAdvice();
        $attachmentEvents = [];

        $requestForAdviceRequestMapper = Mockery::mock(RequestForAdviceRequestMapper::class);
        $requestForAdviceRequestMapper->expects('create')->andReturn($requestForAdvice);

        $requestForAdviceMainDocumentRequestMapper = Mockery::mock(RequestForAdviceMainDocumentRequestMapper::class);

        $dossierMainDocumentOrNoticeSynchronizer = Mockery::mock(DossierMainDocumentOrNoticeSynchronizer::class);
        $dossierMainDocumentOrNoticeSynchronizer->expects('create')
            ->with($requestForAdvice, null, $noticeNotPublicRequestDto, $requestForAdviceMainDocumentRequestMapper);

        $dossierAttachmentSynchronizer = Mockery::mock(DossierAttachmentSynchronizer::class);
        $dossierAttachmentSynchronizer->expects('create')->andReturn($attachmentEvents);

        $dossierPersister = Mockery::mock(DossierPersister::class);
        $dossierPersister->expects('persist')->with($requestForAdvice, null, $attachmentEvents);

        $requestForAdviceStrategy = new RequestForAdviceStrategy(
            $dossierPersister,
            $requestForAdviceRequestMapper,
            $dossierAttachmentSynchronizer,
            $requestForAdviceMainDocumentRequestMapper,
            $dossierMainDocumentOrNoticeSynchronizer,
        );

        self::assertSame($requestForAdvice, $requestForAdviceStrategy->create(
            $requestForAdviceRequestDto,
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

        $mainDocumentRequestDto = new RequestForAdviceMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->attachmentType(RequestForAdviceMainDocument::getAllowedTypes()),
        );
        $requestForAdviceRequestDto = new RequestForAdviceRequestDto(
            Uuid::v6(),
            null,
            $this->getFaker()->sentence(),
            DossierTitle::create($this->getFaker()->sentence()),
            [],
            $this->getFaker()->plainDate(),
            $this->getFaker()->dossierNumber(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->url(),
            [$this->getFaker()->company()],
            $mainDocumentRequestDto,
            null,
        );

        $requestForAdvice = new RequestForAdvice();
        $mainDocumentSnapshot = MainDocumentSnapshot::of($requestForAdvice);
        $attachmentEvents = [];

        $requestForAdviceRequestMapper = Mockery::mock(RequestForAdviceRequestMapper::class);
        $requestForAdviceRequestMapper->expects('update')->with($requestForAdvice, $requestForAdviceRequestDto, $organisation, $department, $subject);

        $requestForAdviceMainDocumentRequestMapper = Mockery::mock(RequestForAdviceMainDocumentRequestMapper::class);

        $dossierMainDocumentOrNoticeSynchronizer = Mockery::mock(DossierMainDocumentOrNoticeSynchronizer::class);
        $dossierMainDocumentOrNoticeSynchronizer->expects('update')
            ->with($requestForAdvice, $mainDocumentRequestDto, null, $requestForAdviceMainDocumentRequestMapper);

        $dossierAttachmentSynchronizer = Mockery::mock(DossierAttachmentSynchronizer::class);
        $dossierAttachmentSynchronizer->expects('update')
            ->with($requestForAdvice, $requestForAdviceRequestDto->attachments)
            ->andReturn($attachmentEvents);

        $dossierPersister = Mockery::mock(DossierPersister::class);
        $dossierPersister->expects('snapshot')->with($requestForAdvice)->andReturn($mainDocumentSnapshot);
        $dossierPersister->expects('persist')->with($requestForAdvice, $mainDocumentSnapshot, $attachmentEvents);

        $requestForAdviceStrategy = new RequestForAdviceStrategy(
            $dossierPersister,
            $requestForAdviceRequestMapper,
            $dossierAttachmentSynchronizer,
            $requestForAdviceMainDocumentRequestMapper,
            $dossierMainDocumentOrNoticeSynchronizer,
        );

        $requestForAdviceStrategy->update($requestForAdvice, $requestForAdviceRequestDto, $organisation, $department, $subject);
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
        $requestForAdviceRequestDto = new RequestForAdviceRequestDto(
            Uuid::v6(),
            null,
            $this->getFaker()->sentence(),
            DossierTitle::create($this->getFaker()->sentence()),
            [],
            $this->getFaker()->plainDate(),
            $this->getFaker()->dossierNumber(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->url(),
            [$this->getFaker()->company()],
            null,
            $noticeNotPublicRequestDto,
        );

        $requestForAdvice = new RequestForAdvice();
        $mainDocumentSnapshot = MainDocumentSnapshot::of($requestForAdvice);
        $attachmentEvents = [];

        $requestForAdviceRequestMapper = Mockery::mock(RequestForAdviceRequestMapper::class);
        $requestForAdviceRequestMapper->expects('update')->with($requestForAdvice, $requestForAdviceRequestDto, $organisation, $department, null);

        $requestForAdviceMainDocumentRequestMapper = Mockery::mock(RequestForAdviceMainDocumentRequestMapper::class);

        $dossierMainDocumentOrNoticeSynchronizer = Mockery::mock(DossierMainDocumentOrNoticeSynchronizer::class);
        $dossierMainDocumentOrNoticeSynchronizer->expects('update')
            ->with($requestForAdvice, null, $noticeNotPublicRequestDto, $requestForAdviceMainDocumentRequestMapper);

        $dossierAttachmentSynchronizer = Mockery::mock(DossierAttachmentSynchronizer::class);
        $dossierAttachmentSynchronizer->expects('update')->andReturn($attachmentEvents);

        $dossierPersister = Mockery::mock(DossierPersister::class);
        $dossierPersister->expects('snapshot')->with($requestForAdvice)->andReturn($mainDocumentSnapshot);
        $dossierPersister->expects('persist')->with($requestForAdvice, $mainDocumentSnapshot, $attachmentEvents);

        $requestForAdviceStrategy = new RequestForAdviceStrategy(
            $dossierPersister,
            $requestForAdviceRequestMapper,
            $dossierAttachmentSynchronizer,
            $requestForAdviceMainDocumentRequestMapper,
            $dossierMainDocumentOrNoticeSynchronizer,
        );

        $requestForAdviceStrategy->update($requestForAdvice, $requestForAdviceRequestDto, $organisation, $department, null);
    }
}
