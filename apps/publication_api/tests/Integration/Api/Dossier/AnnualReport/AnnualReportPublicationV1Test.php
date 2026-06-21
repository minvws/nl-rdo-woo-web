<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Dossier\AnnualReport;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PublicationApi\Api\Dossier\AnnualReport\AnnualReportResource;
use PublicationApi\Api\Dossier\AnnualReport\Uploads\Attachment\AnnualReportUploadAttachmentResource;
use PublicationApi\Api\Dossier\AnnualReport\Uploads\MainDocument\AnnualReportUploadMainDocumentResource;
use PublicationApi\Domain\Upload\UploadStatus;
use PublicationApi\Tests\Integration\Api\Dossier\ApiPublicationV1DossierTestCase;
use Shared\Controller\Public\Dossier\DossierFileController;
use Shared\Domain\Department\Department;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileType;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportAttachment;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportMainDocument;
use Shared\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use Shared\Domain\Publication\PublicUrlGenerator;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\FileInfoFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\DocumentPrefixFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\AnnualReport\AnnualReportAttachmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\AnnualReport\AnnualReportFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\AnnualReport\AnnualReportMainDocumentFactory;
use Shared\Tests\Factory\Publication\Subject\SubjectFactory;
use Shared\Validator\Violation\ConstraintViolationBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\Type;

use function array_map;
use function array_merge;
use function range;
use function sprintf;
use function str_repeat;

final class AnnualReportPublicationV1Test extends ApiPublicationV1DossierTestCase
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
        AnnualReportAttachmentFactory::createOne([
            'dossier' => $annualReport,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        $result = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation));
        self::assertResponseIsSuccessful();
        self::assertCount(1, $result->toArray());
        self::assertJsonContains([['externalId' => $annualReport->getExternalId()?->toString()]]);
    }

    public function testGetAnnualReport(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $subject = SubjectFactory::createOne();
        $annualReport = AnnualReportFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'departments' => [$department],
            'subject' => $subject,
        ]);
        $annualReportMainDocument = AnnualReportMainDocumentFactory::createOne(['dossier' => $annualReport]);
        $annualReportAttachment = AnnualReportAttachmentFactory::createOne([
            'dossier' => $annualReport,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        $response = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $annualReport));

        self::assertResponseIsSuccessful();

        $dossierPathHelper = $this->fromContainer(DossierPathHelper::class);
        $publicUrlGenerator = $this->fromContainer(PublicUrlGenerator::class);
        $expectedResponse = [
            'id' => (string) $annualReport->getId(),
            'externalId' => $annualReport->getExternalId()?->toString(),
            'organisation' => [
                'id' => $organisation->getId()->toString(),
                'name' => $organisation->getName(),
            ],
            'dossierNumber' => $annualReport->getDossierNr(),
            'title' => (string) $annualReport->getTitle(),
            'summary' => $annualReport->getSummary(),
            'subject' => [
                'id' => $subject->getId()->toString(),
                'name' => $subject->getName(),
            ],
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
                '_links' => [
                    'upload' => [
                        'href' => $publicUrlGenerator->buildUrlFromRoute(
                            AnnualReportUploadMainDocumentResource::ROUTE_NAME_MAIN_DOCUMENT_UPLOAD,
                            [
                                'organisationId' => $annualReport->getOrganisation()->getId(),
                                'dossierExternalId' => $annualReport->getExternalId(),
                            ],
                        )->toString(),
                    ],
                    'public' => ['href' => $dossierPathHelper->getAbsoluteDetailsPath($annualReport)],
                    'file' => [
                        'href' => $publicUrlGenerator->buildUrlFromRoute(
                            DossierFileController::ROUTE_NAME_DOSSIER_FILE_DOWNLOAD,
                            [
                                'prefix' => $annualReport->getDocumentPrefix(),
                                'dossierId' => $annualReport->getDossierNr(),
                                'type' => DossierFileType::MAIN_DOCUMENT->value,
                                'id' => $annualReportMainDocument->getId(),
                            ],
                        )->toString(),
                    ],
                ],
            ],
            'attachments' => [
                [
                    'id' => (string) $annualReportAttachment->getId(),
                    'type' => $annualReportAttachment->getType()->value,
                    'language' => $annualReportAttachment->getLanguage()->value,
                    'formalDate' => $annualReportAttachment->getFormalDate()->format('Y-m-d'),
                    'grounds' => $annualReportAttachment->getGrounds(),
                    'fileName' => $annualReportAttachment->getFileInfo()->getName(),
                    'externalId' => $annualReportAttachment->getExternalId()?->toString(),
                    'uploadStatus' => UploadStatus::PROCESSED->value,
                    '_links' => [
                        'upload' => [
                            'href' => $publicUrlGenerator->buildUrlFromRoute(
                                AnnualReportUploadAttachmentResource::ROUTE_NAME_UPLOAD,
                                [
                                    'organisationId' => $annualReport->getOrganisation()->getId(),
                                    'dossierExternalId' => $annualReport->getExternalId(),
                                    'attachmentExternalId' => $annualReportAttachment->getExternalId(),
                                ],
                            )->toString(),
                        ],
                        'public' => ['href' => $dossierPathHelper->getAbsoluteDetailsPath($annualReport)],
                        'file' => [
                            'href' => $publicUrlGenerator->buildUrlFromRoute(
                                DossierFileController::ROUTE_NAME_DOSSIER_FILE_DOWNLOAD,
                                [
                                    'prefix' => $annualReport->getDocumentPrefix(),
                                    'dossierId' => $annualReport->getDossierNr(),
                                    'type' => DossierFileType::ATTACHMENT->value,
                                    'id' => $annualReportAttachment->getId(),
                                ],
                            )->toString(),
                        ],
                    ],
                ],
            ],
            'year' => (int) $annualReport->getDateFrom()?->format('Y'),
            '_links' => [
                'self' => ['href' => $this->buildPublicUrl($organisation, $annualReport)],
                'public' => ['href' => $dossierPathHelper->getAbsoluteDetailsPath($annualReport)],
            ],
        ];

        self::assertSame($expectedResponse, $response->toArray());
        self::assertMatchesResourceItemJsonSchema(AnnualReportResource::class);
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
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        self::assertJsonEquals([
            'type' => 'errors#resource-not-found',
            'title' => 'Resource Not Found',
            'status' => Response::HTTP_NOT_FOUND,
            'detail' => sprintf('AnnualReport with id %s was not found', $annualReport->getExternalId()),
        ]);
    }

    public function testGetAnnualReportWithUnknownExternalId(): void
    {
        $organisation = OrganisationFactory::createOne();
        $unknownExternalId = $this->getFaker()->uuid();

        self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $unknownExternalId));

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        self::assertJsonEquals([
            'type' => 'errors#resource-not-found',
            'title' => 'Resource Not Found',
            'status' => Response::HTTP_NOT_FOUND,
            'detail' => sprintf('AnnualReport with id %s was not found', $unknownExternalId),
        ]);
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
        self::assertMatchesResourceItemJsonSchema(AnnualReportResource::class);

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
        self::assertMatchesResourceItemJsonSchema(AnnualReportResource::class);

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
        self::assertMatchesResourceItemJsonSchema(AnnualReportResource::class);

        self::assertDatabaseCount(AnnualReport::class, 1);
    }

    public function testCreateAnnualReportWithTooLongExternalId(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();

        $data = $this->createValidAnnualReportDataPayload($department, $subject, 1);

        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, str_repeat('x', 129)), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
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
                    'code' => ConstraintViolationBuilder::ENTITY_MISSING_ERROR,
                    'propertyPath' => 'subjectId',
                ],
            ],
            'unknown departmentId' => [
                [
                    'departmentId' => '00000000-0000-0000-0000-000000000000',
                ],
                [
                    'code' => ConstraintViolationBuilder::ENTITY_MISSING_ERROR,
                    'propertyPath' => 'departmentId',
                ],
            ],
            'exceeds max attachments per dossier' => [
                [
                    'attachments' => array_map(
                        static fn ($i) => [
                            'fileName' => sprintf('file%s.pdf', $i),
                            'formalDate' => CarbonImmutable::now()->format('Y-m-d'),
                            'type' => AttachmentType::ACCOUNTABILITY_REPORT->value,
                            'language' => AttachmentLanguage::ENG->value,
                            'externalId' => sprintf('external-id-%s', $i),
                        ],
                        range(1, AbstractAttachment::MAX_ATTACHMENTS_PER_DOSSIER + 1),
                    ),
                ],
                [
                    'code' => Count::TOO_MANY_ERROR,
                    'propertyPath' => 'attachments',
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
            'title' => (string) $annualReport->getTitle(),
            'summary' => $annualReport->getSummary(),
        ]);

        $data = $this->createValidAnnualReportDataPayload($department, null, 0);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $annualReport), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(AnnualReportResource::class);

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
            'title' => (string) $annualReport->getTitle(),
            'summary' => $annualReport->getSummary(),
        ]);

        $data = array_merge($this->createValidAnnualReportDataPayload($department, null, 1), $dataOverrides);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $annualReport), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [$violations]]);

        self::assertDatabaseHas(AnnualReport::class, [
            'title' => (string) $annualReport->getTitle(),
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
            'exceeds max attachments per dossier' => [
                [
                    'attachments' => array_map(
                        static fn ($i) => [
                            'fileName' => sprintf('file%s.pdf', $i),
                            'formalDate' => CarbonImmutable::now()->format('Y-m-d'),
                            'type' => AttachmentType::ACCOUNTABILITY_REPORT->value,
                            'language' => AttachmentLanguage::ENG->value,
                            'externalId' => sprintf('external-id-%s', $i),
                        ],
                        range(1, AbstractAttachment::MAX_ATTACHMENTS_PER_DOSSIER + 1),
                    ),
                ],
                [
                    'code' => Count::TOO_MANY_ERROR,
                    'propertyPath' => 'attachments',
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
            'title' => (string) $annualReport->getTitle(),
            'summary' => $annualReport->getSummary(),
        ]);

        $data = $this->createValidAnnualReportDataPayload($department, null, 0);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $annualReport), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        self::assertDatabaseHas(AnnualReport::class, [
            'title' => (string) $annualReport->getTitle(),
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
                'fileName' => $this->getFaker()->fileNameForGroup(UploadGroupId::MAIN_DOCUMENTS)->toString(),
                'formalDate' => $this->getFaker()->date('Y-m-d'),
                'type' => $this->getFaker()->randomElement(AnnualReportMainDocument::getAllowedTypes()),
                'language' => $this->getFaker()->randomElement(AttachmentLanguage::cases()),
            ],
            'attachments' => $this->createValidAttachmentsPayload($attachmentCount, AnnualReportAttachment::getAllowedTypes()),
        ];
    }

    public function testUpdateAnnualReportWithSameAttachmentsMetadataIsIgnored(): void
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
        $mainDocument = AnnualReportMainDocumentFactory::createOne(['dossier' => $annualReport]);
        $attachment = AnnualReportAttachmentFactory::createOne([
            'dossier' => $annualReport,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        self::assertDatabaseCount(AnnualReportAttachment::class, 1);

        $data = [
            'title' => (string) $annualReport->getTitle(),
            'dossierNumber' => $annualReport->getDossierNr(),
            'year' => (int) $annualReport->getDateFrom()?->format('Y'),
            'publicationDate' => $annualReport->getPublicationDate()?->format('Y-m-d'),
            'summary' => $annualReport->getSummary(),
            'departmentId' => $department->getId(),
            'subjectId' => $annualReport->getSubject()?->getId(),
            'mainDocument' => [
                'fileName' => $mainDocument->getFileInfo()->getName(),
                'formalDate' => $mainDocument->getFormalDate()->format('Y-m-d'),
                'type' => $mainDocument->getType()->value,
                'language' => $mainDocument->getLanguage()->value,
            ],
            'attachments' => [
                [
                    'fileName' => $attachment->getFileInfo()->getName(),
                    'formalDate' => $attachment->getFormalDate()->format('Y-m-d'),
                    'language' => $attachment->getLanguage(),
                    'type' => $attachment->getType(),
                    'externalId' => $attachment->getExternalId()?->toString(),
                ],
            ],
        ];
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $annualReport), ['json' => $data]);
        self::assertResponseIsSuccessful();

        self::assertDatabaseCount(AnnualReportAttachment::class, 1);
        self::assertDatabaseHas(AnnualReportAttachment::class, [
            'id' => $attachment->getId(),
            'dossier' => ['id' => $annualReport->getId()],
        ]);
    }

    public function testUpdateAnnualReportWithChangedAttachmentsMetadataIsUpdated(): void
    {
        $changedFileName = 'new-file.pdf';

        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $annualReport = AnnualReportFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
        ]);
        $mainDocument = AnnualReportMainDocumentFactory::createOne([
            'dossier' => $annualReport,
            'fileInfo' => FileInfoFactory::createOne(['uploaded' => false]),
        ]);
        $attachment = AnnualReportAttachmentFactory::createOne([
            'dossier' => $annualReport,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        $data = [
            'title' => (string) $annualReport->getTitle(),
            'dossierNumber' => $annualReport->getDossierNr(),
            'year' => (int) $annualReport->getDateFrom()?->format('Y'),
            'publicationDate' => $annualReport->getPublicationDate()?->format('Y-m-d'),
            'summary' => $annualReport->getSummary(),
            'departmentId' => $department->getId(),
            'subjectId' => $annualReport->getSubject()?->getId(),
            'mainDocument' => [
                'fileName' => $mainDocument->getFileInfo()->getName(),
                'formalDate' => $mainDocument->getFormalDate()->format('Y-m-d'),
                'type' => $mainDocument->getType()->value,
                'language' => $mainDocument->getLanguage()->value,
            ],
            'attachments' => [
                [
                    'fileName' => $changedFileName,
                    'formalDate' => $attachment->getFormalDate()->format('Y-m-d'),
                    'language' => $attachment->getLanguage(),
                    'type' => $attachment->getType(),
                    'externalId' => $attachment->getExternalId()?->toString(),
                ],
            ],
        ];
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $annualReport), ['json' => $data]);
        self::assertResponseIsSuccessful();

        self::assertDatabaseCount(AnnualReportAttachment::class, 1);
        self::assertDatabaseHas(AnnualReportAttachment::class, [
            'id' => $attachment->getId(),
            'dossier' => ['id' => $annualReport->getId()],
            'fileInfo.name' => $changedFileName,
        ]);
    }

    public function testUpdateAnnualReportWithOneNewAttachmentAndOneExistingIsPartiallyUpdated(): void
    {
        $changedFileName = 'new-file.pdf';
        $newAttachmentExternalId = $this->getFaker()->externalId();

        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $annualReport = AnnualReportFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
        ]);
        $mainDocument = AnnualReportMainDocumentFactory::createOne(['dossier' => $annualReport]);
        $attachment1 = AnnualReportAttachmentFactory::createOne([
            'dossier' => $annualReport,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        $data = [
            'title' => (string) $annualReport->getTitle(),
            'dossierNumber' => $annualReport->getDossierNr(),
            'year' => (int) $annualReport->getDateFrom()?->format('Y'),
            'publicationDate' => $annualReport->getPublicationDate()?->format('Y-m-d'),
            'summary' => $annualReport->getSummary(),
            'departmentId' => $department->getId(),
            'subjectId' => $annualReport->getSubject()?->getId(),
            'mainDocument' => [
                'fileName' => $mainDocument->getFileInfo()->getName(),
                'formalDate' => $mainDocument->getFormalDate()->format('Y-m-d'),
                'type' => $mainDocument->getType()->value,
                'language' => $mainDocument->getLanguage()->value,
            ],
            'attachments' => [
                [
                    'fileName' => $attachment1->getFileInfo()->getName(),
                    'formalDate' => $attachment1->getFormalDate()->format('Y-m-d'),
                    'language' => $attachment1->getLanguage(),
                    'type' => $attachment1->getType(),
                    'externalId' => $attachment1->getExternalId()?->toString(),
                ],
                [
                    'fileName' => $changedFileName,
                    'formalDate' => $attachment1->getFormalDate()->format('Y-m-d'),
                    'language' => $attachment1->getLanguage(),
                    'type' => $attachment1->getType(),
                    'externalId' => $newAttachmentExternalId->toString(),
                ],
            ],
        ];

        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $annualReport), ['json' => $data]);
        self::assertResponseIsSuccessful();

        self::assertDatabaseCount(AnnualReportAttachment::class, 2);

        self::assertDatabaseHas(AnnualReportAttachment::class, [
            'id' => $attachment1->getId(),
            'dossier' => ['id' => $annualReport->getId()],
            'fileInfo.name' => $attachment1->getFileInfo()->getName(),
        ]);

        self::assertDatabaseHas(AnnualReportAttachment::class, [
            'externalId' => $newAttachmentExternalId,
            'dossier' => ['id' => $annualReport->getId()],
            'fileInfo.name' => $changedFileName,
        ]);
    }

    public function testUpdateAnnualReportWithLessAttachmentsAndOneExistingIsPartiallyDeleted(): void
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
        $mainDocument = AnnualReportMainDocumentFactory::createOne(['dossier' => $annualReport]);
        $attachment1 = AnnualReportAttachmentFactory::createOne([
            'dossier' => $annualReport,
            'externalId' => $this->getFaker()->externalId(),
        ]);
        $attachment2 = AnnualReportAttachmentFactory::createOne([
            'dossier' => $annualReport,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        $data = [
            'title' => (string) $annualReport->getTitle(),
            'dossierNumber' => $annualReport->getDossierNr(),
            'year' => (int) $annualReport->getDateFrom()?->format('Y'),
            'publicationDate' => $annualReport->getPublicationDate()?->format('Y-m-d'),
            'summary' => $annualReport->getSummary(),
            'departmentId' => $department->getId(),
            'subjectId' => $annualReport->getSubject()?->getId(),
            'mainDocument' => [
                'fileName' => $mainDocument->getFileInfo()->getName(),
                'formalDate' => $mainDocument->getFormalDate()->format('Y-m-d'),
                'type' => $mainDocument->getType()->value,
                'language' => $mainDocument->getLanguage()->value,
            ],
            'attachments' => [
                [
                    'fileName' => $attachment1->getFileInfo()->getName(),
                    'formalDate' => $attachment1->getFormalDate()->format('Y-m-d'),
                    'language' => $attachment1->getLanguage(),
                    'type' => $attachment1->getType(),
                    'externalId' => $attachment1->getExternalId()?->toString(),
                ],
            ],
        ];

        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $annualReport), ['json' => $data]);
        self::assertResponseIsSuccessful();

        self::assertDatabaseCount(AnnualReportAttachment::class, 1);

        self::assertDatabaseHas(AnnualReportAttachment::class, [
            'id' => $attachment1->getId(),
            'dossier' => ['id' => $annualReport->getId()],
            'fileInfo.name' => $attachment1->getFileInfo()->getName(),
        ]);

        self::assertDatabaseMissing(AnnualReportAttachment::class, [
            'id' => $attachment2->getId(),
        ]);
    }
}
