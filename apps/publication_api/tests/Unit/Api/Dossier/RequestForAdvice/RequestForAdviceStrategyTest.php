<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\RequestForAdvice;

use Mockery;
use PublicationApi\Api\Dossier\NoticeNotPublicSynchronizer;
use PublicationApi\Api\Dossier\RequestForAdvice\RequestForAdviceAttachmentSynchronizer;
use PublicationApi\Api\Dossier\RequestForAdvice\RequestForAdviceMainDocumentRequestDto;
use PublicationApi\Api\Dossier\RequestForAdvice\RequestForAdviceMainDocumentSynchronizer;
use PublicationApi\Api\Dossier\RequestForAdvice\RequestForAdvicePersister;
use PublicationApi\Api\Dossier\RequestForAdvice\RequestForAdviceRequestDto;
use PublicationApi\Api\Dossier\RequestForAdvice\RequestForAdviceRequestMapper;
use PublicationApi\Api\Dossier\RequestForAdvice\RequestForAdviceSnapshot;
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
            Mockery::mock(RequestForAdvicePersister::class),
            Mockery::mock(RequestForAdviceRequestMapper::class),
            Mockery::mock(RequestForAdviceAttachmentSynchronizer::class),
            Mockery::mock(RequestForAdviceMainDocumentSynchronizer::class),
            Mockery::mock(NoticeNotPublicSynchronizer::class),
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

        $requestForAdviceMainDocumentSynchronizer = Mockery::mock(RequestForAdviceMainDocumentSynchronizer::class);
        $requestForAdviceMainDocumentSynchronizer->expects('create')->with($requestForAdvice, $mainDocumentRequestDto);

        $requestForAdviceAttachmentSynchronizer = Mockery::mock(RequestForAdviceAttachmentSynchronizer::class);
        $requestForAdviceAttachmentSynchronizer->expects('create')
            ->with($requestForAdvice, $requestForAdviceRequestDto->attachments)
            ->andReturn($attachmentEvents);

        $requestForAdvicePersister = Mockery::mock(RequestForAdvicePersister::class);
        $requestForAdvicePersister->expects('persist')->with($requestForAdvice, null, $attachmentEvents);

        $requestForAdviceStrategy = new RequestForAdviceStrategy(
            $requestForAdvicePersister,
            $requestForAdviceRequestMapper,
            $requestForAdviceAttachmentSynchronizer,
            $requestForAdviceMainDocumentSynchronizer,
            Mockery::mock(NoticeNotPublicSynchronizer::class),
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

        $noticeNotPublicSynchronizer = Mockery::mock(NoticeNotPublicSynchronizer::class);
        $noticeNotPublicSynchronizer->expects('create')->with($requestForAdvice, $noticeNotPublicRequestDto);

        $requestForAdviceAttachmentSynchronizer = Mockery::mock(RequestForAdviceAttachmentSynchronizer::class);
        $requestForAdviceAttachmentSynchronizer->expects('create')->andReturn($attachmentEvents);

        $requestForAdvicePersister = Mockery::mock(RequestForAdvicePersister::class);
        $requestForAdvicePersister->expects('persist')->with($requestForAdvice, null, $attachmentEvents);

        $requestForAdviceStrategy = new RequestForAdviceStrategy(
            $requestForAdvicePersister,
            $requestForAdviceRequestMapper,
            $requestForAdviceAttachmentSynchronizer,
            Mockery::mock(RequestForAdviceMainDocumentSynchronizer::class),
            $noticeNotPublicSynchronizer,
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
        $requestForAdviceSnapshot = RequestForAdviceSnapshot::of($requestForAdvice);
        $attachmentEvents = [];

        $requestForAdviceRequestMapper = Mockery::mock(RequestForAdviceRequestMapper::class);
        $requestForAdviceRequestMapper->expects('update')->with($requestForAdvice, $requestForAdviceRequestDto, $organisation, $department, $subject);

        $noticeNotPublicSynchronizer = Mockery::mock(NoticeNotPublicSynchronizer::class);
        $noticeNotPublicSynchronizer->expects('delete')->with($requestForAdvice);

        $requestForAdviceMainDocumentSynchronizer = Mockery::mock(RequestForAdviceMainDocumentSynchronizer::class);
        $requestForAdviceMainDocumentSynchronizer->expects('update')->with($requestForAdvice, $mainDocumentRequestDto);

        $requestForAdviceAttachmentSynchronizer = Mockery::mock(RequestForAdviceAttachmentSynchronizer::class);
        $requestForAdviceAttachmentSynchronizer->expects('update')
            ->with($requestForAdvice, $requestForAdviceRequestDto->attachments)
            ->andReturn($attachmentEvents);

        $requestForAdvicePersister = Mockery::mock(RequestForAdvicePersister::class);
        $requestForAdvicePersister->expects('snapshot')->with($requestForAdvice)->andReturn($requestForAdviceSnapshot);
        $requestForAdvicePersister->expects('persist')->with($requestForAdvice, $requestForAdviceSnapshot, $attachmentEvents);

        $requestForAdviceStrategy = new RequestForAdviceStrategy(
            $requestForAdvicePersister,
            $requestForAdviceRequestMapper,
            $requestForAdviceAttachmentSynchronizer,
            $requestForAdviceMainDocumentSynchronizer,
            $noticeNotPublicSynchronizer,
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
        $requestForAdviceSnapshot = RequestForAdviceSnapshot::of($requestForAdvice);
        $attachmentEvents = [];

        $requestForAdviceRequestMapper = Mockery::mock(RequestForAdviceRequestMapper::class);
        $requestForAdviceRequestMapper->expects('update')->with($requestForAdvice, $requestForAdviceRequestDto, $organisation, $department, null);

        $requestForAdviceMainDocumentSynchronizer = Mockery::mock(RequestForAdviceMainDocumentSynchronizer::class);
        $requestForAdviceMainDocumentSynchronizer->expects('delete')->with($requestForAdvice);

        $noticeNotPublicSynchronizer = Mockery::mock(NoticeNotPublicSynchronizer::class);
        $noticeNotPublicSynchronizer->expects('update')->with($requestForAdvice, $noticeNotPublicRequestDto);

        $requestForAdviceAttachmentSynchronizer = Mockery::mock(RequestForAdviceAttachmentSynchronizer::class);
        $requestForAdviceAttachmentSynchronizer->expects('update')->andReturn($attachmentEvents);

        $requestForAdvicePersister = Mockery::mock(RequestForAdvicePersister::class);
        $requestForAdvicePersister->expects('snapshot')->with($requestForAdvice)->andReturn($requestForAdviceSnapshot);
        $requestForAdvicePersister->expects('persist')->with($requestForAdvice, $requestForAdviceSnapshot, $attachmentEvents);

        $requestForAdviceStrategy = new RequestForAdviceStrategy(
            $requestForAdvicePersister,
            $requestForAdviceRequestMapper,
            $requestForAdviceAttachmentSynchronizer,
            $requestForAdviceMainDocumentSynchronizer,
            $noticeNotPublicSynchronizer,
        );

        $requestForAdviceStrategy->update($requestForAdvice, $requestForAdviceRequestDto, $organisation, $department, null);
    }
}
