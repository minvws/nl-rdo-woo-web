<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\Advice;

use Mockery;
use PublicationApi\Api\Dossier\Advice\AdviceAttachmentSynchronizer;
use PublicationApi\Api\Dossier\Advice\AdviceMainDocumentRequestDto;
use PublicationApi\Api\Dossier\Advice\AdviceMainDocumentSynchronizer;
use PublicationApi\Api\Dossier\Advice\AdvicePersister;
use PublicationApi\Api\Dossier\Advice\AdviceRequestDto;
use PublicationApi\Api\Dossier\Advice\AdviceRequestMapper;
use PublicationApi\Api\Dossier\Advice\AdviceSnapshot;
use PublicationApi\Api\Dossier\Advice\AdviceStrategy;
use PublicationApi\Api\Dossier\NoticeNotPublicSynchronizer;
use PublicationApi\Api\NoticeNotPublic\NoticeNotPublicRequestDto;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\Type\Advice\Advice;
use Shared\Domain\Publication\Dossier\Type\Advice\AdviceMainDocument;
use Shared\Domain\Publication\Ground;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;
use Symfony\Component\Uid\Uuid;

use function array_map;

final class AdviceStrategyTest extends UnitTestCase
{
    public function testDossierType(): void
    {
        $adviceStrategy = new AdviceStrategy(
            Mockery::mock(AdvicePersister::class),
            Mockery::mock(AdviceRequestMapper::class),
            Mockery::mock(AdviceAttachmentSynchronizer::class),
            Mockery::mock(AdviceMainDocumentSynchronizer::class),
            Mockery::mock(NoticeNotPublicSynchronizer::class),
        );

        self::assertSame(Advice::class, $adviceStrategy->dossierType());
    }

    public function testCreateWithMainDocument(): void
    {
        $dossierExternalId = $this->getFaker()->externalId();
        $documentPrefix = $this->getFaker()->documentPrefix();
        $organisation = Mockery::mock(Organisation::class);
        $department = Mockery::mock(Department::class);
        $subject = Mockery::mock(Subject::class);

        $mainDocumentRequestDto = new AdviceMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->attachmentType(AdviceMainDocument::getAllowedTypes()),
        );
        $adviceRequestDto = new AdviceRequestDto(
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

        $advice = new Advice();
        $attachmentEvents = [];

        $adviceRequestMapper = Mockery::mock(AdviceRequestMapper::class);
        $adviceRequestMapper->expects('create')->with(
            $adviceRequestDto,
            $organisation,
            $department,
            $subject,
            $dossierExternalId,
            $documentPrefix,
        )->andReturn($advice);

        $adviceMainDocumentSynchronizer = Mockery::mock(AdviceMainDocumentSynchronizer::class);
        $adviceMainDocumentSynchronizer->expects('create')->with($advice, $mainDocumentRequestDto);

        $adviceAttachmentSynchronizer = Mockery::mock(AdviceAttachmentSynchronizer::class);
        $adviceAttachmentSynchronizer->expects('create')
            ->with($advice, $adviceRequestDto->attachments)
            ->andReturn($attachmentEvents);

        $advicePersister = Mockery::mock(AdvicePersister::class);
        $advicePersister->expects('persist')->with($advice, null, $attachmentEvents);

        $adviceStrategy = new AdviceStrategy(
            $advicePersister,
            $adviceRequestMapper,
            $adviceAttachmentSynchronizer,
            $adviceMainDocumentSynchronizer,
            Mockery::mock(NoticeNotPublicSynchronizer::class),
        );

        self::assertSame($advice, $adviceStrategy->create(
            $adviceRequestDto,
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
        $adviceRequestDto = new AdviceRequestDto(
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

        $advice = new Advice();
        $attachmentEvents = [];

        $adviceRequestMapper = Mockery::mock(AdviceRequestMapper::class);
        $adviceRequestMapper->expects('create')->andReturn($advice);

        $noticeNotPublicSynchronizer = Mockery::mock(NoticeNotPublicSynchronizer::class);
        $noticeNotPublicSynchronizer->expects('create')->with($advice, $noticeNotPublicRequestDto);

        $adviceAttachmentSynchronizer = Mockery::mock(AdviceAttachmentSynchronizer::class);
        $adviceAttachmentSynchronizer->expects('create')->andReturn($attachmentEvents);

        $advicePersister = Mockery::mock(AdvicePersister::class);
        $advicePersister->expects('persist')->with($advice, null, $attachmentEvents);

        $adviceStrategy = new AdviceStrategy(
            $advicePersister,
            $adviceRequestMapper,
            $adviceAttachmentSynchronizer,
            Mockery::mock(AdviceMainDocumentSynchronizer::class),
            $noticeNotPublicSynchronizer,
        );

        self::assertSame($advice, $adviceStrategy->create(
            $adviceRequestDto,
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

        $mainDocumentRequestDto = new AdviceMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->attachmentType(AdviceMainDocument::getAllowedTypes()),
        );
        $adviceRequestDto = new AdviceRequestDto(
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

        $advice = new Advice();
        $adviceSnapshot = AdviceSnapshot::of($advice);
        $attachmentEvents = [];

        $adviceRequestMapper = Mockery::mock(AdviceRequestMapper::class);
        $adviceRequestMapper->expects('update')->with($advice, $adviceRequestDto, $organisation, $department, $subject);

        $noticeNotPublicSynchronizer = Mockery::mock(NoticeNotPublicSynchronizer::class);
        $noticeNotPublicSynchronizer->expects('delete')->with($advice);

        $adviceMainDocumentSynchronizer = Mockery::mock(AdviceMainDocumentSynchronizer::class);
        $adviceMainDocumentSynchronizer->expects('update')->with($advice, $mainDocumentRequestDto);

        $adviceAttachmentSynchronizer = Mockery::mock(AdviceAttachmentSynchronizer::class);
        $adviceAttachmentSynchronizer->expects('update')
            ->with($advice, $adviceRequestDto->attachments)
            ->andReturn($attachmentEvents);

        $advicePersister = Mockery::mock(AdvicePersister::class);
        $advicePersister->expects('snapshot')->with($advice)->andReturn($adviceSnapshot);
        $advicePersister->expects('persist')->with($advice, $adviceSnapshot, $attachmentEvents);

        $adviceStrategy = new AdviceStrategy(
            $advicePersister,
            $adviceRequestMapper,
            $adviceAttachmentSynchronizer,
            $adviceMainDocumentSynchronizer,
            $noticeNotPublicSynchronizer,
        );

        $adviceStrategy->update($advice, $adviceRequestDto, $organisation, $department, $subject);
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
        $adviceRequestDto = new AdviceRequestDto(
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

        $advice = new Advice();
        $adviceSnapshot = AdviceSnapshot::of($advice);
        $attachmentEvents = [];

        $adviceRequestMapper = Mockery::mock(AdviceRequestMapper::class);
        $adviceRequestMapper->expects('update')->with($advice, $adviceRequestDto, $organisation, $department, null);

        $adviceMainDocumentSynchronizer = Mockery::mock(AdviceMainDocumentSynchronizer::class);
        $adviceMainDocumentSynchronizer->expects('delete')->with($advice);

        $noticeNotPublicSynchronizer = Mockery::mock(NoticeNotPublicSynchronizer::class);
        $noticeNotPublicSynchronizer->expects('update')->with($advice, $noticeNotPublicRequestDto);

        $adviceAttachmentSynchronizer = Mockery::mock(AdviceAttachmentSynchronizer::class);
        $adviceAttachmentSynchronizer->expects('update')->andReturn($attachmentEvents);

        $advicePersister = Mockery::mock(AdvicePersister::class);
        $advicePersister->expects('snapshot')->with($advice)->andReturn($adviceSnapshot);
        $advicePersister->expects('persist')->with($advice, $adviceSnapshot, $attachmentEvents);

        $adviceStrategy = new AdviceStrategy(
            $advicePersister,
            $adviceRequestMapper,
            $adviceAttachmentSynchronizer,
            $adviceMainDocumentSynchronizer,
            $noticeNotPublicSynchronizer,
        );

        $adviceStrategy->update($advice, $adviceRequestDto, $organisation, $department, null);
    }
}
