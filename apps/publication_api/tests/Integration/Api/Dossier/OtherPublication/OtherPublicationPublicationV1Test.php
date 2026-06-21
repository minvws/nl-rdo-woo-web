<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Dossier\OtherPublication;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PublicationApi\Api\Dossier\OtherPublication\OtherPublicationResource;
use PublicationApi\Api\Dossier\OtherPublication\Uploads\Attachment\OtherPublicationUploadAttachmentResource;
use PublicationApi\Api\Dossier\OtherPublication\Uploads\MainDocument\OtherPublicationUploadMainDocumentResource;
use PublicationApi\Domain\Upload\UploadStatus;
use PublicationApi\Tests\Integration\Api\Dossier\ApiPublicationV1DossierTestCase;
use Shared\Controller\Public\Dossier\DossierFileController;
use Shared\Domain\Department\Department;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileType;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublication;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationAttachment;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationMainDocument;
use Shared\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use Shared\Domain\Publication\PublicUrlGenerator;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\FileInfoFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\DocumentPrefixFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\OtherPublication\OtherPublicationAttachmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\OtherPublication\OtherPublicationFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\OtherPublication\OtherPublicationMainDocumentFactory;
use Shared\Tests\Factory\Publication\Subject\SubjectFactory;
use Shared\Validator\PlainDate\PlainDateBeforeOrEqual;
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

final class OtherPublicationPublicationV1Test extends ApiPublicationV1DossierTestCase
{
    public function getDossierApiUriSegment(): string
    {
        return 'other-publication';
    }

    public function testGetOtherPublicationCollection(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $otherPublication = OtherPublicationFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'departments' => [$department],
        ]);
        OtherPublicationMainDocumentFactory::createOne(['dossier' => $otherPublication]);
        OtherPublicationAttachmentFactory::createOne([
            'dossier' => $otherPublication,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        $result = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation));
        self::assertResponseIsSuccessful();
        self::assertCount(1, $result->toArray());
        self::assertJsonContains([['externalId' => $otherPublication->getExternalId()?->toString()]]);
    }

    public function testGetOtherPublication(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $subject = SubjectFactory::createOne();
        $otherPublication = OtherPublicationFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'departments' => [$department],
            'subject' => $subject,
        ]);
        $otherPublicationMainDocument = OtherPublicationMainDocumentFactory::createOne(['dossier' => $otherPublication]);
        $otherPublicationAttachment = OtherPublicationAttachmentFactory::createOne([
            'dossier' => $otherPublication,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        $response = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $otherPublication));

        self::assertResponseIsSuccessful();

        $dossierPathHelper = $this->fromContainer(DossierPathHelper::class);
        $publicUrlGenerator = $this->fromContainer(PublicUrlGenerator::class);
        $expectedResponse = [
            'id' => (string) $otherPublication->getId(),
            'externalId' => $otherPublication->getExternalId()?->toString(),
            'organisation' => [
                'id' => $organisation->getId()->toString(),
                'name' => $organisation->getName(),
            ],
            'dossierNumber' => $otherPublication->getDossierNr(),
            'title' => (string) $otherPublication->getTitle(),
            'summary' => $otherPublication->getSummary(),
            'subject' => [
                'id' => $subject->getId()->toString(),
                'name' => $subject->getName(),
            ],
            'department' => [
                'id' => (string) $department->getId(),
                'name' => $department->getName(),
            ],
            'publicationDate' => $otherPublication->getPublicationDate()?->format('Y-m-d'),
            'status' => $otherPublication->getStatus()->value,
            'mainDocument' => [
                'id' => (string) $otherPublicationMainDocument->getId(),
                'type' => $otherPublicationMainDocument->getType()->value,
                'language' => $otherPublicationMainDocument->getLanguage()->value,
                'formalDate' => $otherPublicationMainDocument->getFormalDate()->format('Y-m-d'),
                'grounds' => $otherPublicationMainDocument->getGrounds(),
                'fileName' => $otherPublicationMainDocument->getFileInfo()->getName(),
                'uploadStatus' => UploadStatus::PROCESSED->value,
                '_links' => [
                    'upload' => [
                        'href' => $publicUrlGenerator->buildUrlFromRoute(
                            OtherPublicationUploadMainDocumentResource::ROUTE_NAME_MAIN_DOCUMENT_UPLOAD,
                            [
                                'organisationId' => $otherPublication->getOrganisation()->getId(),
                                'dossierExternalId' => $otherPublication->getExternalId(),
                            ],
                        )->toString(),
                    ],
                    'public' => ['href' => $dossierPathHelper->getAbsoluteDetailsPath($otherPublication)],
                    'file' => [
                        'href' => $publicUrlGenerator->buildUrlFromRoute(
                            DossierFileController::ROUTE_NAME_DOSSIER_FILE_DOWNLOAD,
                            [
                                'prefix' => $otherPublication->getDocumentPrefix(),
                                'dossierId' => $otherPublication->getDossierNr(),
                                'type' => DossierFileType::MAIN_DOCUMENT->value,
                                'id' => $otherPublicationMainDocument->getId(),
                            ],
                        )->toString(),
                    ],
                ],
            ],
            'attachments' => [
                [
                    'id' => (string) $otherPublicationAttachment->getId(),
                    'type' => $otherPublicationAttachment->getType()->value,
                    'language' => $otherPublicationAttachment->getLanguage()->value,
                    'formalDate' => $otherPublicationAttachment->getFormalDate()->format('Y-m-d'),
                    'grounds' => $otherPublicationAttachment->getGrounds(),
                    'fileName' => $otherPublicationAttachment->getFileInfo()->getName(),
                    'externalId' => $otherPublicationAttachment->getExternalId()?->toString(),
                    'uploadStatus' => UploadStatus::PROCESSED->value,
                    '_links' => [
                        'upload' => [
                            'href' => $publicUrlGenerator->buildUrlFromRoute(
                                OtherPublicationUploadAttachmentResource::ROUTE_NAME_UPLOAD,
                                [
                                    'organisationId' => $otherPublication->getOrganisation()->getId(),
                                    'dossierExternalId' => $otherPublication->getExternalId(),
                                    'attachmentExternalId' => $otherPublicationAttachment->getExternalId(),
                                ],
                            )->toString(),
                        ],
                        'public' => ['href' => $dossierPathHelper->getAbsoluteDetailsPath($otherPublication)],
                        'file' => [
                            'href' => $publicUrlGenerator->buildUrlFromRoute(
                                DossierFileController::ROUTE_NAME_DOSSIER_FILE_DOWNLOAD,
                                [
                                    'prefix' => $otherPublication->getDocumentPrefix(),
                                    'dossierId' => $otherPublication->getDossierNr(),
                                    'type' => DossierFileType::ATTACHMENT->value,
                                    'id' => $otherPublicationAttachment->getId(),
                                ],
                            )->toString(),
                        ],
                    ],
                ],
            ],
            'dossierDate' => $otherPublication->getDateFrom()?->format('Y-m-d'),
            '_links' => [
                'self' => ['href' => $this->buildPublicUrl($organisation, $otherPublication)],
                'public' => ['href' => $dossierPathHelper->getAbsoluteDetailsPath($otherPublication)],
            ],
        ];

        self::assertSame($expectedResponse, $response->toArray());
        self::assertMatchesResourceItemJsonSchema(OtherPublicationResource::class);
    }

    public function testGetFromIncorrectOrganisation(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $otherPublication = OtherPublicationFactory::createOne([
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
        ]);

        self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $otherPublication));
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        self::assertJsonEquals([
            'type' => 'errors#resource-not-found',
            'title' => 'Resource Not Found',
            'status' => Response::HTTP_NOT_FOUND,
            'detail' => sprintf('OtherPublication with id %s was not found', $otherPublication->getExternalId()),
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
            'detail' => sprintf('OtherPublication with id %s was not found', $unknownExternalId),
        ]);
    }

    public function testCreateOtherPublication(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        self::assertDatabaseCount(OtherPublication::class, 0);

        $data = $this->createValidOtherPublicationDataPayload($department, $subject, $this->getFaker()->numberBetween(1, 3));
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(OtherPublicationResource::class);

        self::assertDatabaseCount(OtherPublication::class, 1);
    }

    public function testCreateOtherPublicationWithoutSubject(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        self::assertDatabaseCount(OtherPublication::class, 0);

        $data = $this->createValidOtherPublicationDataPayload($department, null, 1);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(OtherPublicationResource::class);

        self::assertDatabaseCount(OtherPublication::class, 1);
    }

    public function testCreateOtherPublicationWithoutMainDocument(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();

        self::assertDatabaseCount(OtherPublication::class, 0);

        $data = $this->createValidOtherPublicationDataPayload($department, $subject, 0);
        unset($data['mainDocument']);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [[
            'code' => Type::INVALID_TYPE_ERROR,
            'propertyPath' => 'mainDocument',
        ], ]]);
    }

    public function testCreateOtherPublicationWithoutAttachments(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        self::assertDatabaseCount(OtherPublication::class, 0);

        $data = $this->createValidOtherPublicationDataPayload($department, $subject, 0);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(OtherPublicationResource::class);

        self::assertDatabaseCount(OtherPublication::class, 1);
    }

    public function testCreateOtherPublicationWithTooLongExternalId(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();

        $data = $this->createValidOtherPublicationDataPayload($department, $subject, 1);

        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, str_repeat('x', 129)), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @param array<string,array<array-key,mixed>> $dataOverrides
     * @param array<string,array<array-key,mixed>> $violations
     */
    #[DataProvider('createOtherPublicationValidationDataProvider')]
    public function testCreateOtherPublicationWithValidationError(array $dataOverrides, array $violations): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        self::assertDatabaseCount(OtherPublication::class, 0);

        $data = array_merge($this->createValidOtherPublicationDataPayload($department, $subject, 1), $dataOverrides);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [$violations]]);
    }

    /**
     * @return array<string,array<array-key,mixed>>
     */
    public static function createOtherPublicationValidationDataProvider(): array
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
                            'fileName' => 'filename.pdf',
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

    public function testUpdateOtherPublication(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $otherPublication = OtherPublicationFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
        ]);
        OtherPublicationMainDocumentFactory::createOne(['dossier' => $otherPublication]);
        OtherPublicationAttachmentFactory::createOne(['dossier' => $otherPublication]);

        self::assertDatabaseHas(OtherPublication::class, [
            'title' => (string) $otherPublication->getTitle(),
            'summary' => $otherPublication->getSummary(),
        ]);

        $data = $this->createValidOtherPublicationDataPayload($department, null, 0);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $otherPublication), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(OtherPublicationResource::class);

        self::assertDatabaseHas(OtherPublication::class, [
            'dossierNr' => $data['dossierNumber'],
            'documentPrefix' => $otherPublication->getDocumentPrefix(),
            'summary' => $data['summary'],
            'title' => $data['title'],
        ]);
    }

    /**
     * @param array<string,array<array-key,mixed>> $dataOverrides
     * @param array<string,array<array-key,mixed>> $violations
     */
    #[DataProvider('updateOtherPublicationValidationDataProvider')]
    public function testUpdateOtherPublicationWithValidationErrors(array $dataOverrides, array $violations): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $otherPublication = OtherPublicationFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'departments' => [$department],
            'status' => DossierStatus::CONCEPT,
        ]);
        OtherPublicationMainDocumentFactory::createOne(['dossier' => $otherPublication]);
        OtherPublicationAttachmentFactory::createOne(['dossier' => $otherPublication]);

        self::assertDatabaseHas(OtherPublication::class, [
            'title' => (string) $otherPublication->getTitle(),
            'summary' => $otherPublication->getSummary(),
        ]);

        $data = array_merge($this->createValidOtherPublicationDataPayload($department, null, 1), $dataOverrides);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $otherPublication), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [$violations]]);

        self::assertDatabaseHas(OtherPublication::class, [
            'title' => (string) $otherPublication->getTitle(),
            'summary' => $otherPublication->getSummary(),
        ]);
    }

    /**
     * @return array<string,array<array-key,mixed>>
     */
    public static function updateOtherPublicationValidationDataProvider(): array
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

    public function testUpdateOtherPublicationWithNonConceptState(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $otherPublication = OtherPublicationFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => $this->getFaker()->randomElement(DossierStatus::nonConceptCases()),
        ]);
        OtherPublicationMainDocumentFactory::createOne(['dossier' => $otherPublication]);
        OtherPublicationAttachmentFactory::createOne(['dossier' => $otherPublication]);

        self::assertDatabaseHas(OtherPublication::class, [
            'title' => (string) $otherPublication->getTitle(),
            'summary' => $otherPublication->getSummary(),
        ]);

        $data = $this->createValidOtherPublicationDataPayload($department, null, 0);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $otherPublication), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        self::assertDatabaseHas(OtherPublication::class, [
            'title' => (string) $otherPublication->getTitle(),
            'summary' => $otherPublication->getSummary(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function createValidOtherPublicationDataPayload(Department $department, ?Subject $subject, int $attachmentCount): array
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
                'type' => $this->getFaker()->randomElement(OtherPublicationMainDocument::getAllowedTypes()),
                'language' => $this->getFaker()->randomElement(AttachmentLanguage::cases()),
            ],
            'attachments' => $this->createValidAttachmentsPayload($attachmentCount, OtherPublicationAttachment::getAllowedTypes()),
        ];
    }

    public function testUpdateOtherPublicationWithSameAttachmentsMetadataIsIgnored(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $otherPublication = OtherPublicationFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
        ]);
        $mainDocument = OtherPublicationMainDocumentFactory::createOne(['dossier' => $otherPublication]);
        $attachment = OtherPublicationAttachmentFactory::createOne([
            'dossier' => $otherPublication,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        self::assertDatabaseCount(OtherPublicationAttachment::class, 1);

        $data = [
            'title' => (string) $otherPublication->getTitle(),
            'dossierNumber' => $otherPublication->getDossierNr(),
            'dossierDate' => $otherPublication->getDateFrom()?->format('Y-m-d'),
            'publicationDate' => $otherPublication->getPublicationDate()?->format('Y-m-d'),
            'summary' => $otherPublication->getSummary(),
            'departmentId' => $department->getId(),
            'subjectId' => $otherPublication->getSubject()?->getId(),
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
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $otherPublication), ['json' => $data]);
        self::assertResponseIsSuccessful();

        self::assertDatabaseCount(OtherPublicationAttachment::class, 1);
        self::assertDatabaseHas(OtherPublicationAttachment::class, [
            'id' => $attachment->getId(),
            'dossier' => ['id' => $otherPublication->getId()],
        ]);
    }

    public function testUpdateOtherPublicationWithChangedAttachmentsMetadataIsUpdated(): void
    {
        $changedFileName = 'new-file.pdf';

        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $otherPublication = OtherPublicationFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
        ]);
        $mainDocument = OtherPublicationMainDocumentFactory::createOne([
            'dossier' => $otherPublication,
            'fileInfo' => FileInfoFactory::createOne(['uploaded' => false]),
        ]);
        $attachment = OtherPublicationAttachmentFactory::createOne([
            'dossier' => $otherPublication,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        $data = [
            'title' => (string) $otherPublication->getTitle(),
            'dossierNumber' => $otherPublication->getDossierNr(),
            'dossierDate' => $otherPublication->getDateFrom()?->format('Y-m-d'),
            'publicationDate' => $otherPublication->getPublicationDate()?->format('Y-m-d'),
            'summary' => $otherPublication->getSummary(),
            'departmentId' => $department->getId(),
            'subjectId' => $otherPublication->getSubject()?->getId(),
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
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $otherPublication), ['json' => $data]);
        self::assertResponseIsSuccessful();

        self::assertDatabaseCount(OtherPublicationAttachment::class, 1);
        self::assertDatabaseHas(OtherPublicationAttachment::class, [
            'id' => $attachment->getId(),
            'dossier' => ['id' => $otherPublication->getId()],
            'fileInfo.name' => $changedFileName,
        ]);
    }

    public function testUpdateOtherPublicationWithOneNewAttachmentAndOneExistingIsPartiallyUpdated(): void
    {
        $changedFileName = 'new-file.pdf';
        $newAttachmentExternalId = $this->getFaker()->externalId();

        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $otherPublication = OtherPublicationFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
        ]);
        $mainDocument = OtherPublicationMainDocumentFactory::createOne(['dossier' => $otherPublication]);
        $attachment1 = OtherPublicationAttachmentFactory::createOne([
            'dossier' => $otherPublication,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        $data = [
            'title' => (string) $otherPublication->getTitle(),
            'dossierNumber' => $otherPublication->getDossierNr(),
            'dossierDate' => $otherPublication->getDateFrom()?->format('Y-m-d'),
            'publicationDate' => $otherPublication->getPublicationDate()?->format('Y-m-d'),
            'summary' => $otherPublication->getSummary(),
            'departmentId' => $department->getId(),
            'subjectId' => $otherPublication->getSubject()?->getId(),
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

        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $otherPublication), ['json' => $data]);
        self::assertResponseIsSuccessful();

        self::assertDatabaseCount(OtherPublicationAttachment::class, 2);

        self::assertDatabaseHas(OtherPublicationAttachment::class, [
            'id' => $attachment1->getId(),
            'dossier' => ['id' => $otherPublication->getId()],
            'fileInfo.name' => $attachment1->getFileInfo()->getName(),
        ]);

        self::assertDatabaseHas(OtherPublicationAttachment::class, [
            'externalId' => $newAttachmentExternalId,
            'dossier' => ['id' => $otherPublication->getId()],
            'fileInfo.name' => $changedFileName,
        ]);
    }

    public function testUpdateOtherPublicationWithLessAttachmentsAndOneExistingIsPartiallyDeleted(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $otherPublication = OtherPublicationFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
        ]);
        $mainDocument = OtherPublicationMainDocumentFactory::createOne(['dossier' => $otherPublication]);
        $attachment1 = OtherPublicationAttachmentFactory::createOne([
            'dossier' => $otherPublication,
            'externalId' => $this->getFaker()->externalId(),
        ]);
        $attachment2 = OtherPublicationAttachmentFactory::createOne([
            'dossier' => $otherPublication,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        $data = [
            'title' => (string) $otherPublication->getTitle(),
            'dossierNumber' => $otherPublication->getDossierNr(),
            'dossierDate' => $otherPublication->getDateFrom()?->format('Y-m-d'),
            'publicationDate' => $otherPublication->getPublicationDate()?->format('Y-m-d'),
            'summary' => $otherPublication->getSummary(),
            'departmentId' => $department->getId(),
            'subjectId' => $otherPublication->getSubject()?->getId(),
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

        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $otherPublication), ['json' => $data]);
        self::assertResponseIsSuccessful();

        self::assertDatabaseCount(OtherPublicationAttachment::class, 1);

        self::assertDatabaseHas(OtherPublicationAttachment::class, [
            'id' => $attachment1->getId(),
            'dossier' => ['id' => $otherPublication->getId()],
            'fileInfo.name' => $attachment1->getFileInfo()->getName(),
        ]);

        self::assertDatabaseMissing(OtherPublicationAttachment::class, [
            'id' => $attachment2->getId(),
        ]);
    }
}
