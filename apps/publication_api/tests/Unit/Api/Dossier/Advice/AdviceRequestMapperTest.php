<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\Advice;

use Mockery;
use PublicationApi\Api\Dossier\Advice\AdviceMainDocumentRequestDto;
use PublicationApi\Api\Dossier\Advice\AdviceRequestDto;
use PublicationApi\Api\Dossier\Advice\AdviceRequestMapper;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\Advice\AdviceMainDocument;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;
use Symfony\Component\Uid\Uuid;

final class AdviceRequestMapperTest extends UnitTestCase
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

        $organisation = Mockery::mock(Organisation::class);
        $department = Mockery::mock(Department::class);
        $subject = Mockery::mock(Subject::class);

        $adviceRequestDto = new AdviceRequestDto(
            Uuid::v6(),
            null,
            $summary,
            $title,
            [],
            $dossierDate,
            $dossierNumber,
            $publicationDate,
            new AdviceMainDocumentRequestDto(
                $this->getFaker()->fileName(),
                $this->getFaker()->plainDate(),
                $this->getFaker()->attachmentLanguage(),
                $this->getFaker()->attachmentType(AdviceMainDocument::getAllowedTypes()),
            ),
        );

        $adviceRequestMapper = new AdviceRequestMapper();

        $advice = $adviceRequestMapper->create(
            $adviceRequestDto,
            $organisation,
            $department,
            $subject,
            $dossierExternalId,
            $documentPrefix,
        );

        self::assertSame($dossierExternalId, $advice->getExternalId());
        self::assertSame(DossierStatus::CONCEPT, $advice->getStatus());
        self::assertSame($documentPrefix, $advice->getDocumentPrefix());
        self::assertSame($dossierDate, $advice->getDateFrom());
        self::assertSame($publicationDate, $advice->getPublicationDate());
        self::assertSame($dossierNumber, $advice->getDossierNumber());
        self::assertSame($summary, $advice->getSummary());
        self::assertSame($title, $advice->getTitle());
        self::assertSame($organisation, $advice->getOrganisation());
        self::assertSame($subject, $advice->getSubject());
        self::assertSame([$department], $advice->getDepartments()->getValues());
    }
}
