<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Publication\Dossier\AnnualReport;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PublicationApi\Api\Publication\Dossier\AnnualReport\AnnualReportResponseDto;
use PublicationApi\Api\Publication\UploadStatus;
use PublicationApi\Tests\Integration\Api\Publication\Dossier\ApiPublicationV1DossierTestCase;
use Shared\Domain\Department\Department;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportAttachment;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportMainDocument;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\DocumentPrefixFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\AnnualReport\AnnualReportAttachmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\AnnualReport\AnnualReportFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\AnnualReport\AnnualReportMainDocumentFactory;
use Shared\Tests\Factory\Publication\Subject\SubjectFactory;
use Shared\Validator\EntityExists;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Type;

use function array_merge;

final class AnnualReportTest extends ApiPublicationV1DossierTestCase
{
    public function getDossierApiUriSegment(): string
    {
        return 'annual-report';
    }

    public function testGetAnnualReportCollection(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $annualReport = AnnualReportFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'departments' => [$department],
        ]);
        AnnualReportMainDocumentFactory::createOne(['dossier' => $annualReport]);
        AnnualReportAttachmentFactory::createOne(['dossier' => $annualReport]);

        $result = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation));
        self::assertResponseIsSuccessful();
        self::assertCount(1, $result->toArray());
        self::assertJsonContains([['externalId' => $annualReport->getExternalId()?->__toString()]]);
    }

    public function testGetAnnualReport(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $annualReport = AnnualReportFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'departments' => [$department],
        ]);
        $annualReportMainDocument = AnnualReportMainDocumentFactory::createOne(['dossier' => $annualReport]);
        $annualReportAttachment = AnnualReportAttachmentFactory::createOne(['dossier' => $annualReport]);

        $response = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $annualReport));

        self::assertResponseIsSuccessful();

        $expectedResponse = [
            'id' => (string) $annualReport->getId(),
            'externalId' => $annualReport->getExternalId()?->__toString(),
            'organisation' => [
                'id' => (string) $annualReport->getOrganisation()->getId(),
                'name' => $annualReport->getOrganisation()->getName(),
            ],
            'dossierNumber' => $annualReport->getDossierNr(),
            'title' => $annualReport->getTitle(),
            'summary' => $annualReport->getSummary(),
            'subject' => $annualReport->getSubject()?->getName(),
            'department' => [
                'id' => (string) $department->getId(),
                'name' => $department->getName(),
            ],
            'publicationDate' => $annualReport->getPublicationDate()?->format('Y-m-d'),
            'status' => $annualReport->getStatus()->value,
            'mainDocument' => [
                'id' => (string) $annualReportMainDocument->getId(),
                'type' => $annualReportMainDocument->getType()->value,
                'language' => $annualReportMainDocument->getLanguage()->value,
                'formalDate' => $annualReportMainDocument->getFormalDate()->format('Y-m-d'),
                'grounds' => $annualReportMainDocument->getGrounds(),
                'fileName' => $annualReportMainDocument->getFileInfo()->getName(),
                'uploadStatus' => UploadStatus::PROCESSED->value,
            ],
            'attachments' => [
                [
                    'id' => (string) $annualReportAttachment->getId(),
                    'type' => $annualReportAttachment->getType()->value,
                    'language' => $annualReportAttachment->getLanguage()->value,
                    'formalDate' => $annualReportAttachment->getFormalDate()->format('Y-m-d'),
                    'grounds' => $annualReportAttachment->getGrounds(),
                    'fileName' => $annualReportAttachment->getFileInfo()->getName(),
                    'externalId' => $annualReportAttachment->getExternalId()?->__toString(),
                    'uploadStatus' => UploadStatus::PROCESSED->value,
                ],
            ],
            'year' => (int) $annualReport->getDateFrom()?->format('Y'),
        ];

        self::assertSame($expectedResponse, $response->toArray());
        self::assertMatchesResourceItemJsonSchema(AnnualReportResponseDto::class);
    }

    public function testGetAnnualReportFromIncorrectOrganisation(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $annualReport = AnnualReportFactory::createOne([
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
        ]);

        self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $annualReport));
        self::assertResponseStatusCodeSame(404);
    }

    public function testGetAnnualReportWithUnknownExternalId(): void
    {
        $organisation = OrganisationFactory::createOne();

        self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $this->getFaker()->uuid()));

        self::assertResponseStatusCodeSame(404);
    }

    public function testCreateAnnualReport(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        self::assertDatabaseCount(AnnualReport::class, 0);

        $data = $this->createValidAnnualReportDataPayload($department, $subject, $this->getFaker()->numberBetween(1, 3));
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(AnnualReportResponseDto::class);

        self::assertDatabaseCount(AnnualReport::class, 1);
    }

    public function testCreateAnnualReportWithoutSubject(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        self::assertDatabaseCount(AnnualReport::class, 0);

        $data = $this->createValidAnnualReportDataPayload($department, null, 1);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(AnnualReportResponseDto::class);

        self::assertDatabaseCount(AnnualReport::class, 1);
    }

    public function testCreateAnnualReportWithoutMainDocument(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        self::assertDatabaseCount(AnnualReport::class, 0);

        $data = $this->createValidAnnualReportDataPayload($department, $subject, 0);
        unset($data['mainDocument']);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [[
            'code' => Type::INVALID_TYPE_ERROR,
            'propertyPath' => 'mainDocument',
        ], ]]);
    }

    public function testCreateAnnualReportWithoutAttachments(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        self::assertDatabaseCount(AnnualReport::class, 0);

        $data = $this->createValidAnnualReportDataPayload($department, $subject, 0);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(AnnualReportResponseDto::class);

        self::assertDatabaseCount(AnnualReport::class, 1);
    }

    /**
     * @param array<string, array<array-key, mixed>> $dataOverrides
     * @param array<string, array<array-key, mixed>> $violations
     */
    #[DataProvider('createAnnualReportValidationDataProvider')]
    public function testCreateAnnualReportWithValidationError(array $dataOverrides, array $violations): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();

        self::assertDatabaseCount(AnnualReport::class, 0);

        $data = array_merge($this->createValidAnnualReportDataPayload($department, $subject, 1), $dataOverrides);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [$violations]]);
    }

    /**
     * @return array<string, array<array-key, mixed>>
     */
    public static function createAnnualReportValidationDataProvider(): array
    {
        return [
            'invalid mainDocument language' => [
                [
                    'mainDocument' => [
                        'fileName' => 'filename.pdf',
                        'formalDate' => CarbonImmutable::now()->addDay()->format('Y-m-d'),
                        'type' => AttachmentType::ACCOUNTABILITY_REPORT,
                        'language' => 'invalid',
                    ],
                ],
                [
                    'code' => Type::INVALID_TYPE_ERROR,
                    'propertyPath' => 'mainDocument.language',
                ],
            ],
            'invalid attachment type' => [
                [
                    'attachments' => [
                        [
                            'fileName' => 'file.pdf',
                            'formalDate' => CarbonImmutable::now()->addDay()->format('Y-m-d'),
                            'type' => 'invalid',
                            'language' => AttachmentLanguage::ENG,
                        ],
                    ],
                ],
                [
                    'code' => Type::INVALID_TYPE_ERROR,
                    'propertyPath' => 'attachments[0].type',
                ],
            ],
            'invalid subjectId format' => [
                [
                    'subjectId' => 'fooasdasd',
                ],
                [
                    'code' => Type::INVALID_TYPE_ERROR,
                    'propertyPath' => 'subjectId',
                ],
            ],
            'unknown subjectId' => [
                [
                    'subjectId' => '00000000-0000-0000-0000-000000000000',
                ],
                [
                    'code' => EntityExists::ENTITY_EXISTS_ERROR,
                    'propertyPath' => 'subjectId',
                ],
            ],
            'unknown departmentId' => [
                [
                    'departmentId' => '00000000-0000-0000-0000-000000000000',
                ],
                [
                    'code' => EntityExists::ENTITY_EXISTS_ERROR,
                    'propertyPath' => 'departmentId',
                ],
            ],
        ];
    }

    public function testUpdateAnnualReport(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $annualReport = AnnualReportFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
        ]);
        AnnualReportMainDocumentFactory::createOne(['dossier' => $annualReport]);
        AnnualReportAttachmentFactory::createOne(['dossier' => $annualReport]);

        self::assertDatabaseHas(AnnualReport::class, [
            'title' => $annualReport->getTitle(),
            'summary' => $annualReport->getSummary(),
        ]);

        $data = $this->createValidAnnualReportDataPayload($department, null, 0);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $annualReport), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(AnnualReportResponseDto::class);

        self::assertDatabaseHas(AnnualReport::class, [
            'dossierNr' => $data['dossierNumber'],
            'documentPrefix' => $annualReport->getDocumentPrefix(),
            'summary' => $data['summary'],
            'title' => $data['title'],
        ]);
    }

    /**
     * @param array<string, array<array-key, mixed>> $dataOverrides
     * @param array<string, array<array-key, mixed>> $violations
     */
    #[DataProvider('updateAnnualReportValidationDataProvider')]
    public function testUpdateAnnualReportWithValidationErrors(array $dataOverrides, array $violations): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $annualReport = AnnualReportFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'departments' => [$department],
            'status' => DossierStatus::CONCEPT,
        ]);
        AnnualReportMainDocumentFactory::createOne(['dossier' => $annualReport]);
        AnnualReportAttachmentFactory::createOne(['dossier' => $annualReport]);

        self::assertDatabaseHas(AnnualReport::class, [
            'title' => $annualReport->getTitle(),
            'summary' => $annualReport->getSummary(),
        ]);

        $data = array_merge($this->createValidAnnualReportDataPayload($department, null, 1), $dataOverrides);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $annualReport), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [$violations]]);

        self::assertDatabaseHas(AnnualReport::class, [
            'title' => $annualReport->getTitle(),
            'summary' => $annualReport->getSummary(),
        ]);
    }

    /**
     * @return array<string, array<array-key, mixed>>
     */
    public static function updateAnnualReportValidationDataProvider(): array
    {
        return [
            'year invalid format' => [
                [
                    'year' => '1980',
                ],
                [
                    'code' => Type::INVALID_TYPE_ERROR,
                    'propertyPath' => 'year',
                ],
            ],
        ];
    }

    public function testUpdateAnnualReportWithNonConceptState(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $annualReport = AnnualReportFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => $this->getFaker()->randomElement(DossierStatus::nonConceptCases()),
        ]);
        AnnualReportMainDocumentFactory::createOne(['dossier' => $annualReport]);
        AnnualReportAttachmentFactory::createOne(['dossier' => $annualReport]);

        self::assertDatabaseHas(AnnualReport::class, [
            'title' => $annualReport->getTitle(),
            'summary' => $annualReport->getSummary(),
        ]);

        $data = $this->createValidAnnualReportDataPayload($department, null, 0);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $annualReport), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        self::assertDatabaseHas(AnnualReport::class, [
            'title' => $annualReport->getTitle(),
            'summary' => $annualReport->getSummary(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function createValidAnnualReportDataPayload(Department $department, ?Subject $subject, int $attachmentCount): array
    {
        return [
            'title' => $this->getFaker()->sentence(),
            'dossierNumber' => $this->getFaker()->slug(2),
            'year' => $this->getFaker()->numberBetween(CarbonImmutable::now()->subYears(9)->year, CarbonImmutable::now()->year),
            'publicationDate' => $this->getFaker()->plainDateBetween('-2 weeks', '-1 week')->format('Y-m-d'),
            'summary' => $this->getFaker()->sentence(),
            'departmentId' => $department->getId(),
            'subjectId' => $subject?->getId(),
            'mainDocument' => [
                'fileName' => $this->getFaker()->word(),
                'formalDate' => $this->getFaker()->date('Y-m-d'),
                'type' => $this->getFaker()->randomElement(AnnualReportMainDocument::getAllowedTypes()),
                'language' => $this->getFaker()->randomElement(AttachmentLanguage::cases()),
            ],
            'attachments' => $this->createValidAttachmentsPayload($attachmentCount, AnnualReportAttachment::getAllowedTypes()),
        ];
    }
}
