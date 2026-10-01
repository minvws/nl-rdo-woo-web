<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\ComplaintJudgement;

use Mockery;
use PublicationApi\Api\Dossier\ComplaintJudgement\ComplaintJudgementMainDocumentRequestDto;
use PublicationApi\Api\Dossier\ComplaintJudgement\ComplaintJudgementMainDocumentSynchronizer;
use PublicationApi\Api\Dossier\ComplaintJudgement\ComplaintJudgementPersister;
use PublicationApi\Api\Dossier\ComplaintJudgement\ComplaintJudgementRequestDto;
use PublicationApi\Api\Dossier\ComplaintJudgement\ComplaintJudgementRequestMapper;
use PublicationApi\Api\Dossier\ComplaintJudgement\ComplaintJudgementSnapshot;
use PublicationApi\Api\Dossier\ComplaintJudgement\ComplaintJudgementStrategy;
use PublicationApi\Api\Dossier\NoticeNotPublicSynchronizer;
use PublicationApi\Api\NoticeNotPublic\NoticeNotPublicRequestDto;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgement;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementMainDocument;
use Shared\Domain\Publication\Ground;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;
use Symfony\Component\Uid\Uuid;

use function array_map;

final class ComplaintJudgementStrategyTest extends UnitTestCase
{
    public function testDossierType(): void
    {
        $complaintJudgementStrategy = new ComplaintJudgementStrategy(
            Mockery::mock(ComplaintJudgementPersister::class),
            Mockery::mock(ComplaintJudgementRequestMapper::class),
            Mockery::mock(ComplaintJudgementMainDocumentSynchronizer::class),
            Mockery::mock(NoticeNotPublicSynchronizer::class),
        );

        self::assertSame(ComplaintJudgement::class, $complaintJudgementStrategy->dossierType());
    }

    public function testCreateWithMainDocument(): void
    {
        $dossierExternalId = $this->getFaker()->externalId();
        $documentPrefix = $this->getFaker()->documentPrefix();
        $organisation = Mockery::mock(Organisation::class);
        $department = Mockery::mock(Department::class);
        $subject = Mockery::mock(Subject::class);

        $mainDocumentRequestDto = new ComplaintJudgementMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->attachmentType(ComplaintJudgementMainDocument::getAllowedTypes()),
        );
        $complaintJudgementRequestDto = new ComplaintJudgementRequestDto(
            Uuid::v6(),
            null,
            $this->getFaker()->sentence(),
            DossierTitle::create($this->getFaker()->sentence()),
            $this->getFaker()->plainDate(),
            $this->getFaker()->dossierNumber(),
            $this->getFaker()->plainDate(),
            $mainDocumentRequestDto,
            null,
        );

        $complaintJudgement = new ComplaintJudgement();

        $complaintJudgementRequestMapper = Mockery::mock(ComplaintJudgementRequestMapper::class);
        $complaintJudgementRequestMapper->expects('create')->with(
            $complaintJudgementRequestDto,
            $organisation,
            $department,
            $subject,
            $dossierExternalId,
            $documentPrefix,
        )->andReturn($complaintJudgement);

        $complaintJudgementMainDocumentSynchronizer = Mockery::mock(ComplaintJudgementMainDocumentSynchronizer::class);
        $complaintJudgementMainDocumentSynchronizer->expects('create')->with($complaintJudgement, $mainDocumentRequestDto);

        $complaintJudgementPersister = Mockery::mock(ComplaintJudgementPersister::class);
        $complaintJudgementPersister->expects('persist')->with($complaintJudgement, null);

        $complaintJudgementStrategy = new ComplaintJudgementStrategy(
            $complaintJudgementPersister,
            $complaintJudgementRequestMapper,
            $complaintJudgementMainDocumentSynchronizer,
            Mockery::mock(NoticeNotPublicSynchronizer::class),
        );

        self::assertSame($complaintJudgement, $complaintJudgementStrategy->create(
            $complaintJudgementRequestDto,
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
        $complaintJudgementRequestDto = new ComplaintJudgementRequestDto(
            Uuid::v6(),
            null,
            $this->getFaker()->sentence(),
            DossierTitle::create($this->getFaker()->sentence()),
            $this->getFaker()->plainDate(),
            $this->getFaker()->dossierNumber(),
            $this->getFaker()->plainDate(),
            null,
            $noticeNotPublicRequestDto,
        );

        $complaintJudgement = new ComplaintJudgement();

        $complaintJudgementRequestMapper = Mockery::mock(ComplaintJudgementRequestMapper::class);
        $complaintJudgementRequestMapper->expects('create')->andReturn($complaintJudgement);

        $noticeNotPublicSynchronizer = Mockery::mock(NoticeNotPublicSynchronizer::class);
        $noticeNotPublicSynchronizer->expects('create')->with($complaintJudgement, $noticeNotPublicRequestDto);

        $complaintJudgementPersister = Mockery::mock(ComplaintJudgementPersister::class);
        $complaintJudgementPersister->expects('persist')->with($complaintJudgement, null);

        $complaintJudgementStrategy = new ComplaintJudgementStrategy(
            $complaintJudgementPersister,
            $complaintJudgementRequestMapper,
            Mockery::mock(ComplaintJudgementMainDocumentSynchronizer::class),
            $noticeNotPublicSynchronizer,
        );

        self::assertSame($complaintJudgement, $complaintJudgementStrategy->create(
            $complaintJudgementRequestDto,
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

        $mainDocumentRequestDto = new ComplaintJudgementMainDocumentRequestDto(
            $this->getFaker()->fileName(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->attachmentType(ComplaintJudgementMainDocument::getAllowedTypes()),
        );
        $complaintJudgementRequestDto = new ComplaintJudgementRequestDto(
            Uuid::v6(),
            null,
            $this->getFaker()->sentence(),
            DossierTitle::create($this->getFaker()->sentence()),
            $this->getFaker()->plainDate(),
            $this->getFaker()->dossierNumber(),
            $this->getFaker()->plainDate(),
            $mainDocumentRequestDto,
            null,
        );

        $complaintJudgement = new ComplaintJudgement();
        $complaintJudgementSnapshot = ComplaintJudgementSnapshot::of($complaintJudgement);

        $complaintJudgementRequestMapper = Mockery::mock(ComplaintJudgementRequestMapper::class);
        $complaintJudgementRequestMapper->expects('update')
            ->with($complaintJudgement, $complaintJudgementRequestDto, $organisation, $department, $subject);

        $noticeNotPublicSynchronizer = Mockery::mock(NoticeNotPublicSynchronizer::class);
        $noticeNotPublicSynchronizer->expects('delete')->with($complaintJudgement);

        $complaintJudgementMainDocumentSynchronizer = Mockery::mock(ComplaintJudgementMainDocumentSynchronizer::class);
        $complaintJudgementMainDocumentSynchronizer->expects('update')->with($complaintJudgement, $mainDocumentRequestDto);

        $complaintJudgementPersister = Mockery::mock(ComplaintJudgementPersister::class);
        $complaintJudgementPersister->expects('snapshot')->with($complaintJudgement)->andReturn($complaintJudgementSnapshot);
        $complaintJudgementPersister->expects('persist')->with($complaintJudgement, $complaintJudgementSnapshot);

        $complaintJudgementStrategy = new ComplaintJudgementStrategy(
            $complaintJudgementPersister,
            $complaintJudgementRequestMapper,
            $complaintJudgementMainDocumentSynchronizer,
            $noticeNotPublicSynchronizer,
        );

        $complaintJudgementStrategy->update($complaintJudgement, $complaintJudgementRequestDto, $organisation, $department, $subject);
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
        $complaintJudgementRequestDto = new ComplaintJudgementRequestDto(
            Uuid::v6(),
            null,
            $this->getFaker()->sentence(),
            DossierTitle::create($this->getFaker()->sentence()),
            $this->getFaker()->plainDate(),
            $this->getFaker()->dossierNumber(),
            $this->getFaker()->plainDate(),
            null,
            $noticeNotPublicRequestDto,
        );

        $complaintJudgement = new ComplaintJudgement();
        $complaintJudgementSnapshot = ComplaintJudgementSnapshot::of($complaintJudgement);

        $complaintJudgementRequestMapper = Mockery::mock(ComplaintJudgementRequestMapper::class);
        $complaintJudgementRequestMapper->expects('update')
            ->with($complaintJudgement, $complaintJudgementRequestDto, $organisation, $department, null);

        $complaintJudgementMainDocumentSynchronizer = Mockery::mock(ComplaintJudgementMainDocumentSynchronizer::class);
        $complaintJudgementMainDocumentSynchronizer->expects('delete')->with($complaintJudgement);

        $noticeNotPublicSynchronizer = Mockery::mock(NoticeNotPublicSynchronizer::class);
        $noticeNotPublicSynchronizer->expects('update')->with($complaintJudgement, $noticeNotPublicRequestDto);

        $complaintJudgementPersister = Mockery::mock(ComplaintJudgementPersister::class);
        $complaintJudgementPersister->expects('snapshot')->with($complaintJudgement)->andReturn($complaintJudgementSnapshot);
        $complaintJudgementPersister->expects('persist')->with($complaintJudgement, $complaintJudgementSnapshot);

        $complaintJudgementStrategy = new ComplaintJudgementStrategy(
            $complaintJudgementPersister,
            $complaintJudgementRequestMapper,
            $complaintJudgementMainDocumentSynchronizer,
            $noticeNotPublicSynchronizer,
        );

        $complaintJudgementStrategy->update($complaintJudgement, $complaintJudgementRequestDto, $organisation, $department, null);
    }
}
