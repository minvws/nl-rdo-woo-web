<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Dossier\Covenant;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PublicationApi\Api\Dossier\Covenant\CovenantResource;
use PublicationApi\Api\Dossier\Covenant\Uploads\Attachment\CovenantUploadAttachmentResource;
use PublicationApi\Api\Dossier\Covenant\Uploads\MainDocument\CovenantUploadMainDocumentResource;
use PublicationApi\Domain\Upload\UploadStatus;
use PublicationApi\Tests\Integration\Api\Dossier\ApiPublicationV1DossierTestCase;
use Shared\Controller\Public\Dossier\DossierFileController;
use Shared\Domain\Department\Department;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileType;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use Shared\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use Shared\Domain\Publication\PublicUrlGenerator;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\FileInfoFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\DocumentPrefixFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantAttachmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantMainDocumentFactory;
use Shared\Tests\Factory\Publication\Subject\SubjectFactory;
use Shared\Validator\PlainDate\PlainDateAfterOrEqual;
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

final class CovenantPublicationV1Test extends ApiPublicationV1DossierTestCase
{
    public function getDossierApiUriSegment(): string
    {
        return 'covenant';
    }

    public function testGetCovenantCollection(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $covenant = CovenantFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDateBetween('-9 years', 'now'),
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'departments' => [$department],
            'previousVersionLink' => $this->getFaker()->url(),
            'parties' => [$this->getFaker()->words(3, true), $this->getFaker()->words(3, true)],
        ]);
        CovenantMainDocumentFactory::createOne(['dossier' => $covenant]);
        CovenantAttachmentFactory::createOne([
            'dossier' => $covenant,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        $result = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation));
        self::assertResponseIsSuccessful();
        self::assertCount(1, $result->toArray());
        self::assertJsonContains([['externalId' => $covenant->getExternalId()?->toString()]]);
    }

    public function testGetCovenant(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $subject = SubjectFactory::createOne();
        $covenant = CovenantFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDateBetween('-9 years', 'now'),
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'departments' => [$department],
            'previousVersionLink' => $this->getFaker()->url(),
            'parties' => [$this->getFaker()->words(3, true), $this->getFaker()->words(3, true)],
            'subject' => $subject,
        ]);
        $covenantMainDocument = CovenantMainDocumentFactory::createOne(['dossier' => $covenant]);
        $covenantAttachment = CovenantAttachmentFactory::createOne([
            'dossier' => $covenant,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        $response = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $covenant));

        self::assertResponseIsSuccessful();

        $dossierPathHelper = $this->fromContainer(DossierPathHelper::class);
        $publicUrlGenerator = $this->fromContainer(PublicUrlGenerator::class);
        $expectedResponse = [
            'id' => (string) $covenant->getId(),
            'externalId' => $covenant->getExternalId()?->toString(),
            'organisation' => [
                'id' => $organisation->getId()->toString(),
                'name' => $organisation->getName(),
            ],
            'dossierNumber' => $covenant->getDossierNr(),
            'title' => (string) $covenant->getTitle(),
            'summary' => $covenant->getSummary(),
            'subject' => [
                'id' => $subject->getId()->toString(),
                'name' => $subject->getName(),
            ],
            'department' => [
                'id' => (string) $department->getId(),
                'name' => $department->getName(),
            ],
            'publicationDate' => $covenant->getPublicationDate()?->format('Y-m-d'),
            'status' => $covenant->getStatus()->value,
            'mainDocument' => [
                'id' => (string) $covenantMainDocument->getId(),
                'type' => $covenantMainDocument->getType()->value,
                'language' => $covenantMainDocument->getLanguage()->value,
                'formalDate' => $covenantMainDocument->getFormalDate()->format('Y-m-d'),
                'grounds' => $covenantMainDocument->getGrounds(),
                'fileName' => $covenantMainDocument->getFileInfo()->getName(),
                'uploadStatus' => UploadStatus::PROCESSED->value,
                '_links' => [
                    'upload' => [
                        'href' => $publicUrlGenerator->buildUrlFromRoute(
                            CovenantUploadMainDocumentResource::ROUTE_NAME_MAIN_DOCUMENT_UPLOAD,
                            [
                                'organisationId' => $covenant->getOrganisation()->getId(),
                                'dossierExternalId' => $covenant->getExternalId()?->toString(),
                            ],
                        )->toString(),
                    ],
                    'public' => ['href' => $dossierPathHelper->getAbsoluteDetailsPath($covenant)],
                    'file' => [
                        'href' => $publicUrlGenerator->buildUrlFromRoute(
                            DossierFileController::ROUTE_NAME_DOSSIER_FILE_DOWNLOAD,
                            [
                                'prefix' => $covenant->getDocumentPrefix(),
                                'dossierId' => $covenant->getDossierNr(),
                                'type' => DossierFileType::MAIN_DOCUMENT->value,
                                'id' => $covenantMainDocument->getId(),
                            ],
                        )->toString(),
                    ],
                ],
            ],
            'attachments' => [
                [
                    'id' => (string) $covenantAttachment->getId(),
                    'type' => $covenantAttachment->getType()->value,
                    'language' => $covenantAttachment->getLanguage()->value,
                    'formalDate' => $covenantAttachment->getFormalDate()->format('Y-m-d'),
                    'grounds' => $covenantAttachment->getGrounds(),
                    'fileName' => $covenantAttachment->getFileInfo()->getName(),
                    'externalId' => $covenantAttachment->getExternalId()?->toString(),
                    'uploadStatus' => UploadStatus::PROCESSED->value,
                    '_links' => [
                        'upload' => [
                            'href' => $publicUrlGenerator->buildUrlFromRoute(
                                CovenantUploadAttachmentResource::ROUTE_NAME_UPLOAD,
                                [
                                    'organisationId' => $covenant->getOrganisation()->getId(),
                                    'dossierExternalId' => $covenant->getExternalId()?->toString(),
                                    'attachmentExternalId' => $covenantAttachment->getExternalId()?->toString(),
                                ],
                            )->toString(),
                        ],
                        'public' => ['href' => $dossierPathHelper->getAbsoluteDetailsPath($covenant)],
                        'file' => [
                            'href' => $publicUrlGenerator->buildUrlFromRoute(
                                DossierFileController::ROUTE_NAME_DOSSIER_FILE_DOWNLOAD,
                                [
                                    'prefix' => $covenant->getDocumentPrefix(),
                                    'dossierId' => $covenant->getDossierNr(),
                                    'type' => DossierFileType::ATTACHMENT->value,
                                    'id' => $covenantAttachment->getId(),
                                ],
                            )->toString(),
                        ],
                    ],
                ],
            ],
            'dateFrom' => $covenant->getDateFrom()?->format('Y-m-d'),
            'dateTo' => $covenant->getDateTo()?->format('Y-m-d'),
            'previousVersionLink' => $covenant->getPreviousVersionLink(),
            'parties' => $covenant->getParties(),
            '_links' => [
                'self' => ['href' => $this->buildPublicUrl($organisation, $covenant)],
                'public' => ['href' => $dossierPathHelper->getAbsoluteDetailsPath($covenant)],
            ],
        ];

        self::assertSame($expectedResponse, $response->toArray());
        self::assertMatchesResourceItemJsonSchema(CovenantResource::class);
    }

    public function testGetFromIncorrectOrganisation(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $covenant = CovenantFactory::createOne([
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
        ]);

        self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $covenant));
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        self::assertJsonEquals([
            'type' => 'errors#resource-not-found',
            'title' => 'Resource Not Found',
            'status' => Response::HTTP_NOT_FOUND,
            'detail' => sprintf('Covenant with id %s was not found', $covenant->getExternalId()),
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
            'detail' => sprintf('Covenant with id %s was not found', $unknownExternalId),
        ]);
    }

    public function testCreateCovenant(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        self::assertDatabaseCount(Covenant::class, 0);

        $data = $this->createValidCovenantDataPayload($department, $subject, $this->getFaker()->numberBetween(1, 3));
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(CovenantResource::class);

        self::assertDatabaseCount(Covenant::class, 1);
    }

    public function testCreateCovenantWithoutSubject(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        self::assertDatabaseCount(Covenant::class, 0);

        $data = $this->createValidCovenantDataPayload($department, null, 1);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(CovenantResource::class);

        self::assertDatabaseCount(Covenant::class, 1);
    }

    public function testCreateCovenantWithoutMainDocument(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();

        self::assertDatabaseCount(Covenant::class, 0);

        $data = $this->createValidCovenantDataPayload($department, $subject, 0);
        unset($data['mainDocument']);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [[
            'code' => Type::INVALID_TYPE_ERROR,
            'propertyPath' => 'mainDocument',
        ], ]]);
    }

    public function testCreateCovenantWithoutAttachments(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        self::assertDatabaseCount(Covenant::class, 0);

        $data = $this->createValidCovenantDataPayload($department, $subject, 0);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(CovenantResource::class);

        self::assertDatabaseCount(Covenant::class, 1);
    }

    public function testCreateCovenantWithTooLongExternalId(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();

        $data = $this->createValidCovenantDataPayload($department, $subject, 1);

        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, str_repeat('x', 129)), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @param array<string,array<array-key,mixed>> $dataOverrides
     * @param array<string,array<array-key,mixed>> $violations
     */
    #[DataProvider('createCovenantValidationDataProvider')]
    public function testCreateCovenantWithValidationError(array $dataOverrides, array $violations): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        self::assertDatabaseCount(Covenant::class, 0);

        $data = array_merge($this->createValidCovenantDataPayload($department, $subject, 1), $dataOverrides);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [$violations]]);
    }

    /**
     * @return array<string,array<array-key,mixed>>
     */
    public static function createCovenantValidationDataProvider(): array
    {
        return [
            'dateFrom in the future' => [
                [
                    'dateFrom' => CarbonImmutable::now()->addDay()->format('Y-m-d'),
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
                        'type' => AttachmentType::COVENANT,
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
                            'type' => AttachmentType::COVENANT->value,
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

    public function testUpdateCovenant(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $covenant = CovenantFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDateBetween('-9 years', 'now'),
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
        ]);
        CovenantMainDocumentFactory::createOne(['dossier' => $covenant]);
        CovenantAttachmentFactory::createOne(['dossier' => $covenant]);

        self::assertDatabaseHas(Covenant::class, [
            'title' => (string) $covenant->getTitle(),
            'summary' => $covenant->getSummary(),
        ]);

        $data = $this->createValidCovenantDataPayload($department, null, 0);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $covenant), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(CovenantResource::class);

        self::assertDatabaseHas(Covenant::class, [
            'dossierNr' => $data['dossierNumber'],
            'documentPrefix' => $covenant->getDocumentPrefix(),
            'summary' => $data['summary'],
            'title' => $data['title'],
            'previousVersionLink' => $data['previousVersionLink'],
        ]);
    }

    /**
     * @param array<string,array<array-key,mixed>> $dataOverrides
     * @param array<string,array<array-key,mixed>> $violations
     */
    #[DataProvider('updateCovenantValidationDataProvider')]
    public function testUpdateCovenantWithValidationErrors(array $dataOverrides, array $violations): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $covenant = CovenantFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDateBetween('-9 years', 'now'),
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'departments' => [$department],
            'status' => DossierStatus::CONCEPT,
        ]);
        CovenantMainDocumentFactory::createOne(['dossier' => $covenant]);
        CovenantAttachmentFactory::createOne(['dossier' => $covenant]);

        self::assertDatabaseHas(Covenant::class, [
            'title' => (string) $covenant->getTitle(),
            'summary' => $covenant->getSummary(),
        ]);

        $data = array_merge($this->createValidCovenantDataPayload($department, null, 1), $dataOverrides);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $covenant), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [$violations]]);

        self::assertDatabaseHas(Covenant::class, [
            'title' => (string) $covenant->getTitle(),
            'summary' => $covenant->getSummary(),
        ]);
    }

    /**
     * @return array<string,array<array-key,mixed>>
     */
    public static function updateCovenantValidationDataProvider(): array
    {
        return [
            'dateFrom in the future' => [
                [
                    'dateFrom' => CarbonImmutable::now()->addDay()->format('Y-m-d'),
                ],
                [
                    'code' => PlainDateBeforeOrEqual::PLAIN_DATE_BEFORE_OR_EQUAL_ERROR,
                    'propertyPath' => 'dateFrom',
                ],
            ],
            'dateFrom too far in history' => [
                [
                    'dateFrom' => CarbonImmutable::now()->subYears(10)->subDay()->format('Y-m-d'),
                ],
                [
                    'code' => PlainDateAfterOrEqual::PLAIN_DATE_AFTER_OR_EQUAL_ERROR,
                    'propertyPath' => 'dateFrom',
                ],
            ],
            'exceeds max attachments per dossier' => [
                [
                    'attachments' => array_map(
                        static fn ($i) => [
                            'fileName' => sprintf('file%s.pdf', $i),
                            'formalDate' => CarbonImmutable::now()->format('Y-m-d'),
                            'type' => AttachmentType::COVENANT->value,
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

    public function testUpdateCovenantWithNonConceptState(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $covenant = CovenantFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDateBetween('-9 years', 'now'),
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => $this->getFaker()->randomElement(DossierStatus::nonConceptCases()),
        ]);
        CovenantMainDocumentFactory::createOne(['dossier' => $covenant]);
        CovenantAttachmentFactory::createOne(['dossier' => $covenant]);

        self::assertDatabaseHas(Covenant::class, [
            'title' => (string) $covenant->getTitle(),
            'summary' => $covenant->getSummary(),
        ]);

        $data = $this->createValidCovenantDataPayload($department, null, 0);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $covenant), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        self::assertDatabaseHas(Covenant::class, [
            'title' => (string) $covenant->getTitle(),
            'summary' => $covenant->getSummary(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function createValidCovenantDataPayload(Department $department, ?Subject $subject, int $attachmentCount): array
    {
        return [
            'title' => $this->getFaker()->sentence(),
            'dossierNumber' => $this->getFaker()->slug(2),
            'dateFrom' => $this->getFaker()->dateTimeBetween('-3 weeks', '-2 week')->format('Y-m-d'),
            'dateTo' => $this->getFaker()->dateTimeBetween('-2 weeks', '-1 week')->format('Y-m-d'),
            'publicationDate' => $this->getFaker()->plainDateBetween('-2 weeks', '-1 week')->format('Y-m-d'),
            'summary' => $this->getFaker()->sentence(),
            'departmentId' => $department->getId(),
            'subjectId' => $subject?->getId(),
            'previousVersionLink' => $this->getFaker()->url(),
            'parties' => [
                $this->getFaker()->words(3, true),
                $this->getFaker()->words(3, true),
            ],
            'mainDocument' => [
                'fileName' => $this->getFaker()->fileNameForGroup(UploadGroupId::MAIN_DOCUMENTS)->toString(),
                'formalDate' => $this->getFaker()->date(),
                'type' => AttachmentType::COVENANT,
                'language' => $this->getFaker()->randomElement(AttachmentLanguage::cases()),
            ],
            'attachments' => $this->createValidAttachmentsPayload($attachmentCount, CovenantAttachment::getAllowedTypes()),
        ];
    }

    public function testUpdateCovenantWithSameAttachmentsMetadataIsIgnored(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $covenant = CovenantFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDateBetween('-9 years', 'now'),
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
            'parties' => [
                $this->getFaker()->words(3, true),
                $this->getFaker()->words(3, true),
            ],
        ]);
        $mainDocument = CovenantMainDocumentFactory::createOne(['dossier' => $covenant]);
        $attachment = CovenantAttachmentFactory::createOne([
            'dossier' => $covenant,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        self::assertDatabaseCount(CovenantAttachment::class, 1);

        $data = [
            'title' => (string) $covenant->getTitle(),
            'dossierNumber' => $covenant->getDossierNr(),
            'dateFrom' => $covenant->getDateFrom()?->format('Y-m-d'),
            'dateTo' => $covenant->getDateTo()?->format('Y-m-d'),
            'publicationDate' => $covenant->getPublicationDate()?->format('Y-m-d'),
            'summary' => $covenant->getSummary(),
            'departmentId' => $department->getId(),
            'subjectId' => $covenant->getSubject()?->getId(),
            'previousVersionLink' => $covenant->getPreviousVersionLink(),
            'parties' => $covenant->getParties(),
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
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $covenant), ['json' => $data]);
        self::assertResponseIsSuccessful();

        self::assertDatabaseCount(CovenantAttachment::class, 1);
        self::assertDatabaseHas(CovenantAttachment::class, [
            'id' => $attachment->getId(),
            'dossier' => ['id' => $covenant->getId()],
        ]);
    }

    public function testUpdateCovenantWithChangedAttachmentsMetadataIsUpdated(): void
    {
        $changedFileName = 'new-file.pdf';

        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $covenant = CovenantFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDateBetween('-9 years', 'now'),
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
            'parties' => [
                $this->getFaker()->words(3, true),
                $this->getFaker()->words(3, true),
            ],
        ]);
        $mainDocument = CovenantMainDocumentFactory::createOne([
            'dossier' => $covenant,
            'fileInfo' => FileInfoFactory::createOne(['uploaded' => false]),
        ]);
        $attachment = CovenantAttachmentFactory::createOne([
            'dossier' => $covenant,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        $data = [
            'title' => (string) $covenant->getTitle(),
            'dossierNumber' => $covenant->getDossierNr(),
            'dateFrom' => $covenant->getDateFrom()?->format('Y-m-d'),
            'dateTo' => $covenant->getDateTo()?->format('Y-m-d'),
            'publicationDate' => $covenant->getPublicationDate()?->format('Y-m-d'),
            'summary' => $covenant->getSummary(),
            'departmentId' => $department->getId(),
            'subjectId' => $covenant->getSubject()?->getId(),
            'previousVersionLink' => $covenant->getPreviousVersionLink(),
            'parties' => $covenant->getParties(),
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
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $covenant), ['json' => $data]);
        self::assertResponseIsSuccessful();

        self::assertDatabaseCount(CovenantAttachment::class, 1);
        self::assertDatabaseHas(CovenantAttachment::class, [
            'id' => $attachment->getId(),
            'dossier' => ['id' => $covenant->getId()],
            'fileInfo.name' => $changedFileName,
        ]);
    }

    public function testUpdateCovenantWithOneNewAttachmentAndOneExistingIsPartiallyUpdated(): void
    {
        $changedFileName = 'new-file.pdf';
        $newAttachmentExternalId = $this->getFaker()->externalId();

        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $covenant = CovenantFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDateBetween('-9 years', 'now'),
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
            'parties' => [
                $this->getFaker()->words(3, true),
                $this->getFaker()->words(3, true),
            ],
        ]);
        $mainDocument = CovenantMainDocumentFactory::createOne(['dossier' => $covenant]);
        $attachment1 = CovenantAttachmentFactory::createOne([
            'dossier' => $covenant,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        $data = [
            'title' => (string) $covenant->getTitle(),
            'dossierNumber' => $covenant->getDossierNr(),
            'dateFrom' => $covenant->getDateFrom()?->format('Y-m-d'),
            'dateTo' => $covenant->getDateTo()?->format('Y-m-d'),
            'publicationDate' => $covenant->getPublicationDate()?->format('Y-m-d'),
            'summary' => $covenant->getSummary(),
            'departmentId' => $department->getId(),
            'subjectId' => $covenant->getSubject()?->getId(),
            'previousVersionLink' => $covenant->getPreviousVersionLink(),
            'parties' => $covenant->getParties(),
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

        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $covenant), ['json' => $data]);
        self::assertResponseIsSuccessful();

        self::assertDatabaseCount(CovenantAttachment::class, 2);

        self::assertDatabaseHas(CovenantAttachment::class, [
            'id' => $attachment1->getId(),
            'dossier' => ['id' => $covenant->getId()],
            'fileInfo.name' => $attachment1->getFileInfo()->getName(),
        ]);

        self::assertDatabaseHas(CovenantAttachment::class, [
            'externalId' => $newAttachmentExternalId,
            'dossier' => ['id' => $covenant->getId()],
            'fileInfo.name' => $changedFileName,
        ]);
    }

    public function testUpdateCovenantWithLessAttachmentsAndOneExistingIsPartiallyDeleted(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $covenant = CovenantFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDateBetween('-9 years', 'now'),
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
            'parties' => [
                $this->getFaker()->words(3, true),
                $this->getFaker()->words(3, true),
            ],
        ]);
        $mainDocument = CovenantMainDocumentFactory::createOne(['dossier' => $covenant]);
        $attachment1 = CovenantAttachmentFactory::createOne([
            'dossier' => $covenant,
            'externalId' => $this->getFaker()->externalId(),
        ]);
        $attachment2 = CovenantAttachmentFactory::createOne([
            'dossier' => $covenant,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        $data = [
            'title' => (string) $covenant->getTitle(),
            'dossierNumber' => $covenant->getDossierNr(),
            'dateFrom' => $covenant->getDateFrom()?->format('Y-m-d'),
            'dateTo' => $covenant->getDateTo()?->format('Y-m-d'),
            'publicationDate' => $covenant->getPublicationDate()?->format('Y-m-d'),
            'summary' => $covenant->getSummary(),
            'departmentId' => $department->getId(),
            'subjectId' => $covenant->getSubject()?->getId(),
            'previousVersionLink' => $covenant->getPreviousVersionLink(),
            'parties' => $covenant->getParties(),
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

        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $covenant), ['json' => $data]);
        self::assertResponseIsSuccessful();

        self::assertDatabaseCount(CovenantAttachment::class, 1);

        self::assertDatabaseHas(CovenantAttachment::class, [
            'id' => $attachment1->getId(),
            'dossier' => ['id' => $covenant->getId()],
            'fileInfo.name' => $attachment1->getFileInfo()->getName(),
        ]);

        self::assertDatabaseMissing(CovenantAttachment::class, [
            'id' => $attachment2->getId(),
        ]);
    }
}
