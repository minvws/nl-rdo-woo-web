<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Dossier\InvestigationReport;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PublicationApi\Api\Dossier\InvestigationReport\InvestigationReportResource;
use PublicationApi\Domain\Upload\UploadStatus;
use PublicationApi\Tests\Integration\Api\Dossier\ApiPublicationV1DossierTestCase;
use Shared\Domain\Department\Department;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportAttachment;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportMainDocument;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\FileInfoFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\DocumentPrefixFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\InvestigationReport\InvestigationReportAttachmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\InvestigationReport\InvestigationReportFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\InvestigationReport\InvestigationReportMainDocumentFactory;
use Shared\Tests\Factory\Publication\Subject\SubjectFactory;
use Shared\Validator\EntityExists;
use Shared\Validator\PlainDate\PlainDateBeforeOrEqual;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Type;

use function array_merge;
use function sprintf;

final class InvestigationReportPublicationV1Test extends ApiPublicationV1DossierTestCase
{
    public function getDossierApiUriSegment(): string
    {
        return 'investigation-report';
    }

    public function testGetInvestigationReportCollection(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $investigationReport = InvestigationReportFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'departments' => [$department],
        ]);
        InvestigationReportMainDocumentFactory::createOne(['dossier' => $investigationReport]);
        InvestigationReportAttachmentFactory::createOne(['dossier' => $investigationReport]);

        $result = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation));
        self::assertResponseIsSuccessful();
        self::assertCount(1, $result->toArray());
        self::assertJsonContains([['externalId' => $investigationReport->getExternalId()?->__toString()]]);
    }

    public function testGetInvestigationReport(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $investigationReport = InvestigationReportFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'departments' => [$department],
        ]);
        $investigationReportMainDocument = InvestigationReportMainDocumentFactory::createOne(['dossier' => $investigationReport]);
        $investigationReportAttachment = InvestigationReportAttachmentFactory::createOne(['dossier' => $investigationReport]);

        $response = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $investigationReport));

        self::assertResponseIsSuccessful();

        $expectedResponse = [
            'id' => (string) $investigationReport->getId(),
            'externalId' => $investigationReport->getExternalId()?->__toString(),
            'organisation' => [
                'id' => (string) $investigationReport->getOrganisation()->getId(),
                'name' => $investigationReport->getOrganisation()->getName(),
            ],
            'dossierNumber' => $investigationReport->getDossierNr(),
            'title' => $investigationReport->getTitle(),
            'summary' => $investigationReport->getSummary(),
            'subject' => $investigationReport->getSubject()?->getName(),
            'department' => [
                'id' => (string) $department->getId(),
                'name' => $department->getName(),
            ],
            'publicationDate' => $investigationReport->getPublicationDate()?->format('Y-m-d'),
            'status' => $investigationReport->getStatus()->value,
            'mainDocument' => [
                'id' => (string) $investigationReportMainDocument->getId(),
                'type' => $investigationReportMainDocument->getType()->value,
                'language' => $investigationReportMainDocument->getLanguage()->value,
                'formalDate' => $investigationReportMainDocument->getFormalDate()->format('Y-m-d'),
                'grounds' => $investigationReportMainDocument->getGrounds(),
                'fileName' => $investigationReportMainDocument->getFileInfo()->getName(),
                'uploadStatus' => UploadStatus::PROCESSED->value,
            ],
            'attachments' => [
                [
                    'id' => (string) $investigationReportAttachment->getId(),
                    'type' => $investigationReportAttachment->getType()->value,
                    'language' => $investigationReportAttachment->getLanguage()->value,
                    'formalDate' => $investigationReportAttachment->getFormalDate()->format('Y-m-d'),
                    'grounds' => $investigationReportAttachment->getGrounds(),
                    'fileName' => $investigationReportAttachment->getFileInfo()->getName(),
                    'externalId' => $investigationReportAttachment->getExternalId()?->__toString(),
                    'uploadStatus' => UploadStatus::PROCESSED->value,
                ],
            ],
            'dossierDate' => $investigationReport->getDateFrom()?->format('Y-m-d'),
        ];

        self::assertSame($expectedResponse, $response->toArray());
        self::assertMatchesResourceItemJsonSchema(InvestigationReportResource::class);
    }

    public function testGetFromIncorrectOrganisation(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $investigationReport = InvestigationReportFactory::createOne([
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
        ]);

        self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $investigationReport));
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        self::assertJsonEquals([
            'type' => 'errors#resource-not-found',
            'title' => 'Resource Not Found',
            'status' => Response::HTTP_NOT_FOUND,
            'detail' => sprintf('InvestigationReport with id %s was not found', $investigationReport->getExternalId()),
        ]);
    }

    public function testGetWithUnknownExternalId(): void
    {
        $organisation = OrganisationFactory::createOne();
        $unknownExternalId = $this->getFaker()->uuid();

        self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $unknownExternalId));

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        self::assertJsonEquals([
            'type' => 'errors#resource-not-found',
            'title' => 'Resource Not Found',
            'status' => Response::HTTP_NOT_FOUND,
            'detail' => sprintf('InvestigationReport with id %s was not found', $unknownExternalId),
        ]);
    }

    public function testCreateInvestigationReport(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        self::assertDatabaseCount(InvestigationReport::class, 0);

        $data = $this->createValidInvestigationReportDataPayload($department, $subject, $this->getFaker()->numberBetween(1, 3));
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(InvestigationReportResource::class);

        self::assertDatabaseCount(InvestigationReport::class, 1);
    }

    public function testCreateInvestigationReportWithoutSubject(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        self::assertDatabaseCount(InvestigationReport::class, 0);

        $data = $this->createValidInvestigationReportDataPayload($department, null, 1);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(InvestigationReportResource::class);

        self::assertDatabaseCount(InvestigationReport::class, 1);
    }

    public function testCreateInvestigationReportWithoutMainDocument(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();

        self::assertDatabaseCount(InvestigationReport::class, 0);

        $data = $this->createValidInvestigationReportDataPayload($department, $subject, 0);
        unset($data['mainDocument']);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [[
            'code' => Type::INVALID_TYPE_ERROR,
            'propertyPath' => 'mainDocument',
        ], ]]);
    }

    public function testCreateInvestigationReportWithoutAttachments(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        self::assertDatabaseCount(InvestigationReport::class, 0);

        $data = $this->createValidInvestigationReportDataPayload($department, $subject, 0);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(InvestigationReportResource::class);

        self::assertDatabaseCount(InvestigationReport::class, 1);
    }

    /**
     * @param array<string,array<array-key,mixed>> $dataOverrides
     * @param array<string,array<array-key,mixed>> $violations
     */
    #[DataProvider('createInvestigationReportValidationDataProvider')]
    public function testCreateInvestigationReportWithValidationError(array $dataOverrides, array $violations): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        self::assertDatabaseCount(InvestigationReport::class, 0);

        $data = array_merge($this->createValidInvestigationReportDataPayload($department, $subject, 1), $dataOverrides);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [$violations]]);
    }

    /**
     * @return array<string,array<array-key,mixed>>
     */
    public static function createInvestigationReportValidationDataProvider(): array
    {
        return [
            'dossierDate in the future' => [
                [
                    'dossierDate' => CarbonImmutable::now()->addDay()->format('Y-m-d'),
                ],
                [
                    'code' => PlainDateBeforeOrEqual::PLAIN_DATE_BEFORE_OR_EQUAL_ERROR,
                    'propertyPath' => 'dateFrom',
                ],
            ],
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
                            'fileName' => 'attachment.pdf',
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

    public function testUpdateInvestigationReport(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $investigationReport = InvestigationReportFactory::createOne([
            'date_from' => $this->getFaker()->plainDate(),
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
        ]);
        InvestigationReportMainDocumentFactory::createOne(['dossier' => $investigationReport]);
        InvestigationReportAttachmentFactory::createOne(['dossier' => $investigationReport]);

        self::assertDatabaseHas(InvestigationReport::class, [
            'title' => $investigationReport->getTitle(),
            'summary' => $investigationReport->getSummary(),
        ]);

        $data = $this->createValidInvestigationReportDataPayload($department, null, 0);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $investigationReport), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(InvestigationReportResource::class);

        self::assertDatabaseHas(InvestigationReport::class, [
            'dossierNr' => $data['dossierNumber'],
            'documentPrefix' => $investigationReport->getDocumentPrefix(),
            'summary' => $data['summary'],
            'title' => $data['title'],
        ]);
    }

    /**
     * @param array<string,array<array-key,mixed>> $dataOverrides
     * @param array<string,array<array-key,mixed>> $violations
     */
    #[DataProvider('updateInvestigationReportValidationDataProvider')]
    public function testUpdateInvestigationReportWithValidationErrors(array $dataOverrides, array $violations): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $investigationReport = InvestigationReportFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'departments' => [$department],
            'status' => DossierStatus::CONCEPT,
        ]);
        InvestigationReportMainDocumentFactory::createOne(['dossier' => $investigationReport]);
        InvestigationReportAttachmentFactory::createOne(['dossier' => $investigationReport]);

        self::assertDatabaseHas(InvestigationReport::class, [
            'title' => $investigationReport->getTitle(),
            'summary' => $investigationReport->getSummary(),
        ]);

        $data = array_merge($this->createValidInvestigationReportDataPayload($department, null, 1), $dataOverrides);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $investigationReport), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [$violations]]);

        self::assertDatabaseHas(InvestigationReport::class, [
            'title' => $investigationReport->getTitle(),
            'summary' => $investigationReport->getSummary(),
        ]);
    }

    /**
     * @return array<string,array<array-key,mixed>>
     */
    public static function updateInvestigationReportValidationDataProvider(): array
    {
        return [
            'dossierDate in the future' => [
                [
                    'dossierDate' => CarbonImmutable::now()->addDay()->format('Y-m-d'),
                ],
                [
                    'code' => PlainDateBeforeOrEqual::PLAIN_DATE_BEFORE_OR_EQUAL_ERROR,
                    'propertyPath' => 'dateFrom',
                ],
            ],
        ];
    }

    public function testUpdateInvestigationReportWithNonConceptState(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $investigationReport = InvestigationReportFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => $this->getFaker()->randomElement(DossierStatus::nonConceptCases()),
        ]);
        InvestigationReportMainDocumentFactory::createOne(['dossier' => $investigationReport]);
        InvestigationReportAttachmentFactory::createOne(['dossier' => $investigationReport]);

        self::assertDatabaseHas(InvestigationReport::class, [
            'title' => $investigationReport->getTitle(),
            'summary' => $investigationReport->getSummary(),
        ]);

        $data = $this->createValidInvestigationReportDataPayload($department, null, 0);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $investigationReport), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        self::assertDatabaseHas(InvestigationReport::class, [
            'title' => $investigationReport->getTitle(),
            'summary' => $investigationReport->getSummary(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function createValidInvestigationReportDataPayload(Department $department, ?Subject $subject, int $attachmentCount): array
    {
        return [
            'title' => $this->getFaker()->sentence(),
            'dossierNumber' => $this->getFaker()->slug(2),
            'dossierDate' => $this->getFaker()->dateTimeBetween('-3 weeks', '-2 week')->format('Y-m-d'),
            'publicationDate' => $this->getFaker()->plainDateBetween('-2 weeks', '-1 week')->format('Y-m-d'),
            'summary' => $this->getFaker()->sentence(),
            'departmentId' => $department->getId(),
            'subjectId' => $subject?->getId(),
            'mainDocument' => [
                'fileName' => $this->getFaker()->fileNameForGroup(UploadGroupId::MAIN_DOCUMENTS)->toString(),
                'formalDate' => $this->getFaker()->date(),
                'type' => $this->getFaker()->randomElement(InvestigationReportMainDocument::getAllowedTypes()),
                'language' => $this->getFaker()->randomElement(AttachmentLanguage::cases()),
            ],
            'attachments' => $this->createValidAttachmentsPayload($attachmentCount, InvestigationReportAttachment::getAllowedTypes()),
        ];
    }

    public function testUpdateInvestigationReportWithSameAttachmentsMetadataIsIgnored(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $investigationReport = InvestigationReportFactory::createOne([
            'date_from' => $this->getFaker()->plainDate(),
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
        ]);
        $mainDocument = InvestigationReportMainDocumentFactory::createOne(['dossier' => $investigationReport]);
        $attachment = InvestigationReportAttachmentFactory::createOne([
            'dossier' => $investigationReport,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        self::assertDatabaseCount(InvestigationReportAttachment::class, 1);

        $data = [
            'title' => $investigationReport->getTitle(),
            'dossierNumber' => $investigationReport->getDossierNr(),
            'dossierDate' => $investigationReport->getDateFrom()?->format('Y-m-d'),
            'publicationDate' => $investigationReport->getPublicationDate()?->format('Y-m-d'),
            'summary' => $investigationReport->getSummary(),
            'departmentId' => $department->getId(),
            'subjectId' => $investigationReport->getSubject()?->getId(),
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
                    'externalId' => $attachment->getExternalId()?->__toString(),
                ],
            ],
        ];
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $investigationReport), ['json' => $data]);
        self::assertResponseIsSuccessful();

        self::assertDatabaseCount(InvestigationReportAttachment::class, 1);
        self::assertDatabaseHas(InvestigationReportAttachment::class, [
            'id' => $attachment->getId(),
            'dossier' => ['id' => $investigationReport->getId()],
        ]);
    }

    public function testUpdateInvestigationReportWithChangedAttachmentsMetadataIsUpdated(): void
    {
        $changedFileName = 'new-file.pdf';

        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $investigationReport = InvestigationReportFactory::createOne([
            'date_from' => $this->getFaker()->plainDate(),
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
        ]);
        $mainDocument = InvestigationReportMainDocumentFactory::createOne([
            'dossier' => $investigationReport,
            'fileInfo' => FileInfoFactory::createOne(['uploaded' => false]),
        ]);
        $attachment = InvestigationReportAttachmentFactory::createOne([
            'dossier' => $investigationReport,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        $data = [
            'title' => $investigationReport->getTitle(),
            'dossierNumber' => $investigationReport->getDossierNr(),
            'dossierDate' => $investigationReport->getDateFrom()?->format('Y-m-d'),
            'publicationDate' => $investigationReport->getPublicationDate()?->format('Y-m-d'),
            'summary' => $investigationReport->getSummary(),
            'departmentId' => $department->getId(),
            'subjectId' => $investigationReport->getSubject()?->getId(),
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
                    'externalId' => $attachment->getExternalId()?->__toString(),
                ],
            ],
        ];
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $investigationReport), ['json' => $data]);
        self::assertResponseIsSuccessful();

        self::assertDatabaseCount(InvestigationReportAttachment::class, 1);
        self::assertDatabaseHas(InvestigationReportAttachment::class, [
            'id' => $attachment->getId(),
            'dossier' => ['id' => $investigationReport->getId()],
            'fileInfo.name' => $changedFileName,
        ]);
    }

    public function testUpdateInvestigationReportWithOneNewAttachmentAndOneExistingIsPartiallyUpdated(): void
    {
        $changedFileName = 'new-file.pdf';
        $newAttachmentExternalId = $this->getFaker()->externalId();

        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $investigationReport = InvestigationReportFactory::createOne([
            'date_from' => $this->getFaker()->plainDate(),
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
        ]);
        $mainDocument = InvestigationReportMainDocumentFactory::createOne(['dossier' => $investigationReport]);
        $attachment1 = InvestigationReportAttachmentFactory::createOne([
            'dossier' => $investigationReport,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        $data = [
            'title' => $investigationReport->getTitle(),
            'dossierNumber' => $investigationReport->getDossierNr(),
            'dossierDate' => $investigationReport->getDateFrom()?->format('Y-m-d'),
            'publicationDate' => $investigationReport->getPublicationDate()?->format('Y-m-d'),
            'summary' => $investigationReport->getSummary(),
            'departmentId' => $department->getId(),
            'subjectId' => $investigationReport->getSubject()?->getId(),
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
                    'externalId' => $attachment1->getExternalId()?->__toString(),
                ],
                [
                    'fileName' => $changedFileName,
                    'formalDate' => $attachment1->getFormalDate()->format('Y-m-d'),
                    'language' => $attachment1->getLanguage(),
                    'type' => $attachment1->getType(),
                    'externalId' => $newAttachmentExternalId->__toString(),
                ],
            ],
        ];

        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $investigationReport), ['json' => $data]);
        self::assertResponseIsSuccessful();

        self::assertDatabaseCount(InvestigationReportAttachment::class, 2);

        self::assertDatabaseHas(InvestigationReportAttachment::class, [
            'id' => $attachment1->getId(),
            'dossier' => ['id' => $investigationReport->getId()],
            'fileInfo.name' => $attachment1->getFileInfo()->getName(),
        ]);

        self::assertDatabaseHas(InvestigationReportAttachment::class, [
            'externalId' => $newAttachmentExternalId,
            'dossier' => ['id' => $investigationReport->getId()],
            'fileInfo.name' => $changedFileName,
        ]);
    }

    public function testUpdateInvestigationReportWithLessAttachmentsAndOneExistingIsPartiallyDeleted(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $investigationReport = InvestigationReportFactory::createOne([
            'date_from' => $this->getFaker()->plainDate(),
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
        ]);
        $mainDocument = InvestigationReportMainDocumentFactory::createOne(['dossier' => $investigationReport]);
        $attachment1 = InvestigationReportAttachmentFactory::createOne([
            'dossier' => $investigationReport,
            'externalId' => $this->getFaker()->externalId(),
        ]);
        $attachment2 = InvestigationReportAttachmentFactory::createOne([
            'dossier' => $investigationReport,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        $data = [
            'title' => $investigationReport->getTitle(),
            'dossierNumber' => $investigationReport->getDossierNr(),
            'dossierDate' => $investigationReport->getDateFrom()?->format('Y-m-d'),
            'publicationDate' => $investigationReport->getPublicationDate()?->format('Y-m-d'),
            'summary' => $investigationReport->getSummary(),
            'departmentId' => $department->getId(),
            'subjectId' => $investigationReport->getSubject()?->getId(),
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
                    'externalId' => $attachment1->getExternalId()?->__toString(),
                ],
            ],
        ];

        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $investigationReport), ['json' => $data]);
        self::assertResponseIsSuccessful();

        self::assertDatabaseCount(InvestigationReportAttachment::class, 1);

        self::assertDatabaseHas(InvestigationReportAttachment::class, [
            'id' => $attachment1->getId(),
            'dossier' => ['id' => $investigationReport->getId()],
            'fileInfo.name' => $attachment1->getFileInfo()->getName(),
        ]);

        self::assertDatabaseMissing(InvestigationReportAttachment::class, [
            'id' => $attachment2->getId(),
        ]);
    }
}
