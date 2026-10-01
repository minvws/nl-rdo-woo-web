<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\RequestForAdvice;

use Mockery;
use PublicationApi\Api\Dossier\RequestForAdvice\RequestForAdviceMainDocumentRequestDto;
use PublicationApi\Api\Dossier\RequestForAdvice\RequestForAdviceRequestDto;
use PublicationApi\Api\Dossier\RequestForAdvice\RequestForAdviceRequestMapper;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceMainDocument;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;
use Symfony\Component\Uid\Uuid;

final class RequestForAdviceRequestMapperTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $dossierExternalId = $this->getFaker()->externalId();
        $documentPrefix = $this->getFaker()->documentPrefix();
        $dossierDate = $this->getFaker()->plainDate();
        $publicationDate = $this->getFaker()->plainDate();
        $dossierNumber = $this->getFaker()->dossierNumber();
        $summary = $this->getFaker()->sentence();
        $title = DossierTitle::create($this->getFaker()->sentence());
        $link = $this->getFaker()->url();
        $advisoryBodies = [$this->getFaker()->company()];

        $organisation = Mockery::mock(Organisation::class);
        $department = Mockery::mock(Department::class);
        $subject = Mockery::mock(Subject::class);

        $requestForAdviceRequestDto = new RequestForAdviceRequestDto(
            Uuid::v6(),
            null,
            $summary,
            $title,
            [],
            $dossierDate,
            $dossierNumber,
            $publicationDate,
            $link,
            $advisoryBodies,
            new RequestForAdviceMainDocumentRequestDto(
                $this->getFaker()->fileName(),
                $this->getFaker()->plainDate(),
                $this->getFaker()->attachmentLanguage(),
                $this->getFaker()->attachmentType(RequestForAdviceMainDocument::getAllowedTypes()),
            ),
        );

        $requestForAdviceRequestMapper = new RequestForAdviceRequestMapper();

        $requestForAdvice = $requestForAdviceRequestMapper->create(
            $requestForAdviceRequestDto,
            $organisation,
            $department,
            $subject,
            $dossierExternalId,
            $documentPrefix,
        );

        self::assertSame($dossierExternalId, $requestForAdvice->getExternalId());
        self::assertSame(DossierStatus::CONCEPT, $requestForAdvice->getStatus());
        self::assertSame($documentPrefix, $requestForAdvice->getDocumentPrefix());
        self::assertSame($dossierDate, $requestForAdvice->getDateFrom());
        self::assertSame($publicationDate, $requestForAdvice->getPublicationDate());
        self::assertSame($dossierNumber, $requestForAdvice->getDossierNumber());
        self::assertSame($summary, $requestForAdvice->getSummary());
        self::assertSame($title, $requestForAdvice->getTitle());
        self::assertSame($organisation, $requestForAdvice->getOrganisation());
        self::assertSame($subject, $requestForAdvice->getSubject());
        self::assertSame([$department], $requestForAdvice->getDepartments()->getValues());
        self::assertSame($link, $requestForAdvice->getLink());
        self::assertSame($advisoryBodies, $requestForAdvice->getAdvisoryBodies());
    }
}
