<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Dossier\Advice;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PublicationApi\Api\Dossier\Advice\AdviceResource;
use PublicationApi\Api\Dossier\Advice\Uploads\Attachment\AdviceUploadAttachmentResource;
use PublicationApi\Api\Dossier\Advice\Uploads\MainDocument\AdviceUploadMainDocumentResource;
use PublicationApi\Domain\Upload\UploadStatus;
use PublicationApi\Tests\Integration\Api\Dossier\ApiPublicationV1DossierTestCase;
use Shared\Controller\Public\Dossier\DossierFileController;
use Shared\Domain\Department\Department;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileType;
use Shared\Domain\Publication\Dossier\Type\Advice\Advice;
use Shared\Domain\Publication\Dossier\Type\Advice\AdviceAttachment;
use Shared\Domain\Publication\Dossier\Type\Advice\AdviceMainDocument;
use Shared\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use Shared\Domain\Publication\PublicUrlGenerator;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\FileInfoFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\DocumentPrefixFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Advice\AdviceAttachmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Advice\AdviceFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Advice\AdviceMainDocumentFactory;
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

final class AdvicePublicationV1Test extends ApiPublicationV1DossierTestCase
{
    public function getDossierApiUriSegment(): string
    {
        return 'advice';
    }

    public function testGetAdviceCollection(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $advice = AdviceFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'departments' => [$department],
        ]);
        AdviceMainDocumentFactory::createOne(['dossier' => $advice]);
        AdviceAttachmentFactory::createOne([
            'dossier' => $advice,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        $result = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation));
        self::assertResponseIsSuccessful();
        self::assertCount(1, $result->toArray());
        self::assertJsonContains([['externalId' => $advice->getExternalId()?->toString()]]);
    }

    public function testGetAdvice(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $subject = SubjectFactory::createOne();
        $advice = AdviceFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'departments' => [$department],
            'subject' => $subject,
        ]);
        $adviceMainDocument = AdviceMainDocumentFactory::createOne(['dossier' => $advice]);
        $adviceAttachment = AdviceAttachmentFactory::createOne([
            'dossier' => $advice,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        $response = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $advice));

        self::assertResponseIsSuccessful();

        $dossierPathHelper = $this->fromContainer(DossierPathHelper::class);
        $publicUrlGenerator = $this->fromContainer(PublicUrlGenerator::class);
        $expectedResponse = [
            'id' => (string) $advice->getId(),
            'externalId' => $advice->getExternalId()?->toString(),
            'organisation' => [
                'id' => $organisation->getId()->toString(),
                'name' => $organisation->getName(),
            ],
            'dossierNumber' => $advice->getDossierNr(),
            'title' => (string) $advice->getTitle(),
            'summary' => $advice->getSummary(),
            'subject' => [
                'id' => $subject->getId()->toString(),
                'name' => $subject->getName(),
            ],
            'department' => [
                'id' => (string) $department->getId(),
                'name' => $department->getName(),
            ],
            'publicationDate' => $advice->getPublicationDate()?->format('Y-m-d'),
            'status' => $advice->getStatus()->value,
            'mainDocument' => [
                'id' => (string) $adviceMainDocument->getId(),
                'type' => $adviceMainDocument->getType()->value,
                'language' => $adviceMainDocument->getLanguage()->value,
                'formalDate' => $adviceMainDocument->getFormalDate()->format('Y-m-d'),
                'grounds' => $adviceMainDocument->getGrounds(),
                'fileName' => $adviceMainDocument->getFileInfo()->getName(),
                'uploadStatus' => UploadStatus::PROCESSED->value,
                '_links' => [
                    'upload' => [
                        'href' => $publicUrlGenerator->buildUrlFromRoute(
                            AdviceUploadMainDocumentResource::ROUTE_NAME_MAIN_DOCUMENT_UPLOAD,
                            [
                                'organisationId' => $advice->getOrganisation()->getId(),
                                'dossierExternalId' => $advice->getExternalId(),
                            ],
                        )->toString(),
                    ],
                    'public' => ['href' => $dossierPathHelper->getAbsoluteDetailsPath($advice)],
                    'file' => [
                        'href' => $publicUrlGenerator->buildUrlFromRoute(
                            DossierFileController::ROUTE_NAME_DOSSIER_FILE_DOWNLOAD,
                            [
                                'prefix' => $advice->getDocumentPrefix(),
                                'dossierId' => $advice->getDossierNr(),
                                'type' => DossierFileType::MAIN_DOCUMENT->value,
                                'id' => $adviceMainDocument->getId(),
                            ],
                        )->toString(),
                    ],
                ],
            ],
            'attachments' => [
                [
                    'id' => (string) $adviceAttachment->getId(),
                    'type' => $adviceAttachment->getType()->value,
                    'language' => $adviceAttachment->getLanguage()->value,
                    'formalDate' => $adviceAttachment->getFormalDate()->format('Y-m-d'),
                    'grounds' => $adviceAttachment->getGrounds(),
                    'fileName' => $adviceAttachment->getFileInfo()->getName(),
                    'externalId' => $adviceAttachment->getExternalId()?->toString(),
                    'uploadStatus' => UploadStatus::PROCESSED->value,
                    '_links' => [
                        'upload' => [
                            'href' => $publicUrlGenerator->buildUrlFromRoute(
                                AdviceUploadAttachmentResource::ROUTE_NAME_UPLOAD,
                                [
                                    'organisationId' => $advice->getOrganisation()->getId(),
                                    'dossierExternalId' => $advice->getExternalId(),
                                    'attachmentExternalId' => $adviceAttachment->getExternalId(),
                                ],
                            )->toString(),
                        ],
                        'public' => ['href' => $dossierPathHelper->getAbsoluteDetailsPath($advice)],
                        'file' => [
                            'href' => $publicUrlGenerator->buildUrlFromRoute(
                                DossierFileController::ROUTE_NAME_DOSSIER_FILE_DOWNLOAD,
                                [
                                    'prefix' => $advice->getDocumentPrefix(),
                                    'dossierId' => $advice->getDossierNr(),
                                    'type' => DossierFileType::ATTACHMENT->value,
                                    'id' => $adviceAttachment->getId(),
                                ],
                            )->toString(),
                        ],
                    ],
                ],
            ],
            'dossierDate' => $advice->getDateFrom()?->format('Y-m-d'),
            '_links' => [
                'self' => ['href' => $this->buildPublicUrl($organisation, $advice)],
                'public' => ['href' => $dossierPathHelper->getAbsoluteDetailsPath($advice)],
            ],
        ];

        self::assertEquals($expectedResponse, $response->toArray());
        self::assertMatchesResourceItemJsonSchema(AdviceResource::class);
    }

    public function testGetFromIncorrectOrganisation(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $advice = AdviceFactory::createOne([
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
        ]);

        self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $advice));
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        self::assertJsonEquals([
            'type' => 'errors#resource-not-found',
            'title' => 'Resource Not Found',
            'status' => Response::HTTP_NOT_FOUND,
            'detail' => sprintf('Advice with id %s was not found', $advice->getExternalId()),
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
            'detail' => sprintf('Advice with id %s was not found', $unknownExternalId),
        ]);
    }

    public function testCreateAdvice(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        self::assertDatabaseCount(Advice::class, 0);

        $data = $this->createValidAdviceDataPayload($department, $subject, $this->getFaker()->numberBetween(1, 3));
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(AdviceResource::class);

        self::assertDatabaseCount(Advice::class, 1);
    }

    public function testCreateAdviceWithoutSubject(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        self::assertDatabaseCount(Advice::class, 0);

        $data = $this->createValidAdviceDataPayload($department, null, 1);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(AdviceResource::class);

        self::assertDatabaseCount(Advice::class, 1);
    }

    public function testCreateAdviceWithoutMainDocument(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();

        self::assertDatabaseCount(Advice::class, 0);

        $data = $this->createValidAdviceDataPayload($department, $subject, 0);
        unset($data['mainDocument']);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [[
            'code' => Type::INVALID_TYPE_ERROR,
            'propertyPath' => 'mainDocument',
        ], ]]);
    }

    public function testCreateAdviceWithoutAttachments(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        self::assertDatabaseCount(Advice::class, 0);

        $data = $this->createValidAdviceDataPayload($department, $subject, 0);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(AdviceResource::class);

        self::assertDatabaseCount(Advice::class, 1);
    }

    public function testCreateAdviceWithTooLongExternalId(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();

        $data = $this->createValidAdviceDataPayload($department, $subject, 1);

        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, str_repeat('x', 129)), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testCreateAdviceWithMoreThanOneRequestForAdviceAttachment(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        self::assertDatabaseCount(Advice::class, 0);

        $data = $this->createValidAdviceDataPayload($department, $subject, 0);
        $attachments = $this->createValidAttachmentsPayload(2, AdviceAttachment::getAllowedTypes());
        $attachments[0]['type'] = AttachmentType::REQUEST_FOR_ADVICE;
        $attachments[1]['type'] = AttachmentType::REQUEST_FOR_ADVICE;
        $data['attachments'] = $attachments;
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [['message' => 'dossier should have at most one attachment of type "c_a40458df"']]]);

        self::assertDatabaseCount(Advice::class, 0);
    }

    /**
     * @param array<string,array<array-key,mixed>> $dataOverrides
     * @param array<string,array<array-key,mixed>> $violations
     */
    #[DataProvider('createAdviceValidationDataProvider')]
    public function testCreateAdviceWithValidationError(array $dataOverrides, array $violations): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        self::assertDatabaseCount(Advice::class, 0);

        $data = array_merge($this->createValidAdviceDataPayload($department, $subject, 1), $dataOverrides);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [$violations]]);
    }

    /**
     * @return array<string,array<array-key,mixed>>
     */
    public static function createAdviceValidationDataProvider(): array
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
                            'formalDate' => CarbonImmutable::now()->addDay()->format('Y-m-d'),
                            'type' => 'invalid',
                            'language' => AttachmentLanguage::ENG,
                            'fileName' => 'fileName.pdf',
                            'externalId' => 'externalId',
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
                            'type' => AttachmentType::REQUEST_FOR_ADVICE->value,
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

    public function testUpdateAdvice(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $advice = AdviceFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
        ]);
        AdviceMainDocumentFactory::createOne(['dossier' => $advice]);
        AdviceAttachmentFactory::createOne(['dossier' => $advice]);

        self::assertDatabaseHas(Advice::class, [
            'title' => (string) $advice->getTitle(),
            'summary' => $advice->getSummary(),
        ]);

        $data = $this->createValidAdviceDataPayload($department, null, 0);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $advice), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(AdviceResource::class);

        self::assertDatabaseHas(Advice::class, [
            'dossierNr' => $data['dossierNumber'],
            'documentPrefix' => $advice->getDocumentPrefix(),
            'summary' => $data['summary'],
            'title' => $data['title'],
        ]);
    }

    /**
     * @param array<string,array<array-key,mixed>> $dataOverrides
     * @param array<string,array<array-key,mixed>> $violations
     */
    #[DataProvider('updateAdviceValidationDataProvider')]
    public function testUpdateAdviceWithValidationErrors(array $dataOverrides, array $violations): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $advice = AdviceFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'departments' => [$department],
            'status' => DossierStatus::CONCEPT,
        ]);
        AdviceMainDocumentFactory::createOne(['dossier' => $advice]);
        AdviceAttachmentFactory::createOne(['dossier' => $advice]);

        self::assertDatabaseHas(Advice::class, [
            'title' => (string) $advice->getTitle(),
            'summary' => $advice->getSummary(),
        ]);

        $data = array_merge($this->createValidAdviceDataPayload($department, null, 1), $dataOverrides);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $advice), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [$violations]]);

        self::assertDatabaseHas(Advice::class, [
            'title' => (string) $advice->getTitle(),
            'summary' => $advice->getSummary(),
        ]);
    }

    /**
     * @return array<string,array<array-key,mixed>>
     */
    public static function updateAdviceValidationDataProvider(): array
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
                            'type' => AttachmentType::REQUEST_FOR_ADVICE->value,
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

    public function testUpdateAdviceWithNonConceptState(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $advice = AdviceFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => $this->getFaker()->randomElement(DossierStatus::nonConceptCases()),
        ]);
        AdviceMainDocumentFactory::createOne(['dossier' => $advice]);
        AdviceAttachmentFactory::createOne(['dossier' => $advice]);

        self::assertDatabaseHas(Advice::class, [
            'title' => (string) $advice->getTitle(),
            'summary' => $advice->getSummary(),
        ]);

        $data = $this->createValidAdviceDataPayload($department, null, 0);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $advice), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        self::assertDatabaseHas(Advice::class, [
            'title' => (string) $advice->getTitle(),
            'summary' => $advice->getSummary(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function createValidAdviceDataPayload(Department $department, ?Subject $subject, int $attachmentCount): array
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
                'type' => $this->getFaker()->randomElement(AdviceMainDocument::getAllowedTypes()),
                'language' => $this->getFaker()->randomElement(AttachmentLanguage::cases()),
            ],
            'attachments' => $this->createValidAttachmentsPayload($attachmentCount, AdviceAttachment::getAllowedTypes()),
        ];
    }

    public function testUpdateAdviceWithSameAttachmentsMetadataIsIgnored(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $advice = AdviceFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
        ]);
        $mainDocument = AdviceMainDocumentFactory::createOne(['dossier' => $advice]);
        $attachment = AdviceAttachmentFactory::createOne([
            'dossier' => $advice,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        self::assertDatabaseCount(AdviceAttachment::class, 1);
        self::assertDatabaseHas(AdviceAttachment::class, [
            'id' => $attachment->getId(),
            'dossier' => ['id' => $advice->getId()],
        ]);

        $data = [
            'title' => (string) $advice->getTitle(),
            'dossierNumber' => $advice->getDossierNr(),
            'dossierDate' => $advice->getDateFrom()?->format('Y-m-d'),
            'publicationDate' => $advice->getPublicationDate()?->format('Y-m-d'),
            'summary' => $advice->getSummary(),
            'departmentId' => $department->getId(),
            'subjectId' => $advice->getSubject()?->getId(),
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
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $advice), ['json' => $data]);
        self::assertResponseIsSuccessful();

        self::assertDatabaseCount(AdviceAttachment::class, 1);
        self::assertDatabaseHas(AdviceAttachment::class, [
            'id' => $attachment->getId(),
            'dossier' => ['id' => $advice->getId()],
        ]);
    }

    public function testUpdateAdviceWithChangedAttachmentsMetadataIsUpdated(): void
    {
        $changedFileName = 'new-file.pdf';

        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $advice = AdviceFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
        ]);
        $mainDocument = AdviceMainDocumentFactory::createOne([
            'dossier' => $advice,
            'fileInfo' => FileInfoFactory::createOne(['uploaded' => false]),
        ]);
        $attachment = AdviceAttachmentFactory::createOne([
            'dossier' => $advice,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        $data = [
            'title' => (string) $advice->getTitle(),
            'dossierNumber' => $advice->getDossierNr(),
            'dossierDate' => $advice->getDateFrom()?->format('Y-m-d'),
            'publicationDate' => $advice->getPublicationDate()?->format('Y-m-d'),
            'summary' => $advice->getSummary(),
            'departmentId' => $department->getId(),
            'subjectId' => $advice->getSubject()?->getId(),
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
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $advice), ['json' => $data]);
        self::assertResponseIsSuccessful();

        self::assertDatabaseCount(AdviceAttachment::class, 1);
        self::assertDatabaseHas(AdviceAttachment::class, [
            'id' => $attachment->getId(),
            'dossier' => ['id' => $advice->getId()],
            'fileInfo.name' => $changedFileName,
        ]);
    }

    public function testUpdateAdviceWithOneNewAttachmentAndOneExistingIsPartiallyUpdated(): void
    {
        $changedFileName = 'new-file.pdf';
        $newAttachmentType = $this->getFaker()->unique()->randomElement(AttachmentType::cases());
        $newAttachmentExternalId = $this->getFaker()->externalId();

        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $advice = AdviceFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
        ]);
        $mainDocument = AdviceMainDocumentFactory::createOne(['dossier' => $advice]);
        $originalAttachment = AdviceAttachmentFactory::createOne([
            'dossier' => $advice,
            'externalId' => $this->getFaker()->externalId(),
            'type' => $this->getFaker()->unique()->randomElement(AdviceAttachment::getAllowedTypes()),
        ]);

        $data = [
            'title' => (string) $advice->getTitle(),
            'dossierNumber' => $advice->getDossierNr(),
            'dossierDate' => $advice->getDateFrom()?->format('Y-m-d'),
            'publicationDate' => $advice->getPublicationDate()?->format('Y-m-d'),
            'summary' => $advice->getSummary(),
            'departmentId' => $department->getId(),
            'subjectId' => $advice->getSubject()?->getId(),
            'mainDocument' => [
                'fileName' => $mainDocument->getFileInfo()->getName(),
                'formalDate' => $mainDocument->getFormalDate()->format('Y-m-d'),
                'type' => $mainDocument->getType()->value,
                'language' => $mainDocument->getLanguage()->value,
            ],
            'attachments' => [
                [
                    'fileName' => $originalAttachment->getFileInfo()->getName(),
                    'formalDate' => $originalAttachment->getFormalDate()->format('Y-m-d'),
                    'language' => $originalAttachment->getLanguage(),
                    'type' => $originalAttachment->getType(),
                    'externalId' => $originalAttachment->getExternalId()?->toString(),
                ],
                [
                    'fileName' => $changedFileName,
                    'formalDate' => $originalAttachment->getFormalDate()->format('Y-m-d'),
                    'language' => $originalAttachment->getLanguage(),
                    'type' => $newAttachmentType,
                    'externalId' => $newAttachmentExternalId->toString(),
                ],
            ],
        ];

        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $advice), ['json' => $data]);
        self::assertResponseIsSuccessful();

        self::assertDatabaseCount(AdviceAttachment::class, 2);

        self::assertDatabaseHas(AdviceAttachment::class, [
            'id' => $originalAttachment->getId(),
            'dossier' => ['id' => $advice->getId()],
            'fileInfo.name' => $originalAttachment->getFileInfo()->getName(),
        ]);

        self::assertDatabaseHas(AdviceAttachment::class, [
            'externalId' => $newAttachmentExternalId,
            'dossier' => ['id' => $advice->getId()],
            'fileInfo.name' => $changedFileName,
        ]);
    }

    public function testUpdateAdviceWithLessAttachmentsAndOneExistingIsPartiallyDeleted(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $advice = AdviceFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
        ]);
        $mainDocument = AdviceMainDocumentFactory::createOne(['dossier' => $advice]);
        $attachment1 = AdviceAttachmentFactory::createOne([
            'dossier' => $advice,
            'externalId' => $this->getFaker()->externalId(),
        ]);
        $attachment2 = AdviceAttachmentFactory::createOne([
            'dossier' => $advice,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        $data = [
            'title' => (string) $advice->getTitle(),
            'dossierNumber' => $advice->getDossierNr(),
            'dossierDate' => $advice->getDateFrom()?->format('Y-m-d'),
            'publicationDate' => $advice->getPublicationDate()?->format('Y-m-d'),
            'summary' => $advice->getSummary(),
            'departmentId' => $department->getId(),
            'subjectId' => $advice->getSubject()?->getId(),
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

        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $advice), ['json' => $data]);
        self::assertResponseIsSuccessful();

        self::assertDatabaseCount(AdviceAttachment::class, 1);

        self::assertDatabaseHas(AdviceAttachment::class, [
            'id' => $attachment1->getId(),
            'dossier' => ['id' => $advice->getId()],
            'fileInfo.name' => $attachment1->getFileInfo()->getName(),
        ]);

        self::assertDatabaseMissing(AdviceAttachment::class, [
            'id' => $attachment2->getId(),
        ]);
    }
}
