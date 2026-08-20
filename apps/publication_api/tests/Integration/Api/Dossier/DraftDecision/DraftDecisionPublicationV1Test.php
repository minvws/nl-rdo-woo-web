<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Dossier\DraftDecision;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PublicationApi\Api\Dossier\DraftDecision\DraftDecisionResource;
use PublicationApi\Api\Dossier\DraftDecision\Uploads\Attachment\DraftDecisionUploadAttachmentResource;
use PublicationApi\Api\Dossier\DraftDecision\Uploads\MainDocument\DraftDecisionUploadMainDocumentResource;
use PublicationApi\Domain\OpenApi\Links\ApiUrlGenerator;
use PublicationApi\Domain\Upload\UploadStatus;
use PublicationApi\Tests\Integration\Api\Dossier\ApiPublicationV1DossierTestCase;
use Shared\Controller\Public\Dossier\DossierFileController;
use Shared\Domain\Department\Department;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Citation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileType;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecision;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecisionAttachment;
use Shared\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use Shared\Domain\Publication\PublicUrlGenerator;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\FileInfoFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\DocumentPrefixFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\DraftDecision\DraftDecisionAttachmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\DraftDecision\DraftDecisionFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\DraftDecision\DraftDecisionMainDocumentFactory;
use Shared\Tests\Factory\Publication\Subject\SubjectFactory;
use Shared\Validator\PlainDate\PlainDateBeforeOrEqual;
use Shared\Validator\Violation\ConstraintViolationBuilder;
use Shared\ValueObject\PlainDate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Type;

use function array_map;
use function array_merge;
use function range;
use function sprintf;
use function str_repeat;

final class DraftDecisionPublicationV1Test extends ApiPublicationV1DossierTestCase
{
    public function getDossierApiUriSegment(): string
    {
        return 'draft-decision';
    }

    public function testGetDraftDecisionCollection(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $draftDecision = DraftDecisionFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'departments' => [$department],
        ]);
        DraftDecisionMainDocumentFactory::createOne(['dossier' => $draftDecision]);
        DraftDecisionAttachmentFactory::createOne([
            'dossier' => $draftDecision,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        $result = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation));
        self::assertResponseIsSuccessful();
        $data = $result->toArray();
        self::assertArrayHasKey('items', $data);
        self::assertArrayHasKey('hasNextPage', $data);
        /** @var array<array-key, mixed> $items */
        $items = $data['items'];
        self::assertCount(1, $items);
        self::assertJsonContains(['items' => [['externalId' => $draftDecision->getExternalId()?->toString()]]]);
    }

    public function testGetDraftDecision(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $subject = SubjectFactory::createOne();
        $draftDecision = DraftDecisionFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'departments' => [$department],
            'subject' => $subject,
        ]);
        $draftDecisionMainDocument = DraftDecisionMainDocumentFactory::createOne(['dossier' => $draftDecision]);
        $draftDecisionAttachment = DraftDecisionAttachmentFactory::createOne([
            'dossier' => $draftDecision,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        $response = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $draftDecision));

        self::assertResponseIsSuccessful();

        $apiUrlGenerator = $this->fromContainer(ApiUrlGenerator::class);
        $dossierPathHelper = $this->fromContainer(DossierPathHelper::class);
        $publicUrlGenerator = $this->fromContainer(PublicUrlGenerator::class);
        $expectedResponse = [
            'id' => (string) $draftDecision->getId(),
            'externalId' => $draftDecision->getExternalId()?->toString(),
            'organisation' => [
                'id' => $organisation->getId()->toString(),
                'name' => $organisation->getName(),
            ],
            'dossierNumber' => $draftDecision->getDossierNumber(),
            'title' => (string) $draftDecision->getTitle(),
            'summary' => $draftDecision->getSummary(),
            'subject' => [
                'id' => $subject->getId()->toString(),
                'name' => $subject->getName(),
            ],
            'department' => [
                'id' => (string) $department->getId(),
                'name' => $department->getName(),
            ],
            'publicationDate' => $draftDecision->getPublicationDate()?->format('Y-m-d'),
            'status' => $draftDecision->getStatus()->value,
            'mainDocument' => [
                'id' => (string) $draftDecisionMainDocument->getId(),
                'type' => $draftDecisionMainDocument->getType()->value,
                'language' => $draftDecisionMainDocument->getLanguage()->value,
                'formalDate' => $draftDecisionMainDocument->getFormalDate()->format('Y-m-d'),
                'fileName' => $draftDecisionMainDocument->getFileInfo()->getName(),
                'uploadStatus' => UploadStatus::PROCESSED->value,
                '_links' => [
                    'upload' => [
                        'href' => $apiUrlGenerator->buildUrlFromRoute(
                            DraftDecisionUploadMainDocumentResource::ROUTE_NAME_MAIN_DOCUMENT_UPLOAD,
                            [
                                'organisationId' => $draftDecision->getOrganisation()->getId(),
                                'dossierExternalId' => $draftDecision->getExternalId(),
                            ],
                        )->toString(),
                    ],
                    'public' => ['href' => $dossierPathHelper->getAbsoluteDetailsPath($draftDecision)],
                    'file' => [
                        'href' => $publicUrlGenerator->buildUrlFromRoute(
                            DossierFileController::ROUTE_NAME_DOSSIER_FILE_DOWNLOAD,
                            [
                                'documentPrefix' => $draftDecision->getDocumentPrefix(),
                                'dossierNumber' => $draftDecision->getDossierNumber(),
                                'type' => DossierFileType::MAIN_DOCUMENT->value,
                                'id' => $draftDecisionMainDocument->getId(),
                            ],
                        )->toString(),
                    ],
                ],
            ],
            'attachments' => [
                [
                    'id' => (string) $draftDecisionAttachment->getId(),
                    'type' => $draftDecisionAttachment->getType()->value,
                    'language' => $draftDecisionAttachment->getLanguage()->value,
                    'formalDate' => $draftDecisionAttachment->getFormalDate()->format('Y-m-d'),
                    'fileName' => $draftDecisionAttachment->getFileInfo()->getName(),
                    'externalId' => $draftDecisionAttachment->getExternalId()?->toString(),
                    'uploadStatus' => UploadStatus::PROCESSED->value,
                    '_links' => [
                        'upload' => [
                            'href' => $apiUrlGenerator->buildUrlFromRoute(
                                DraftDecisionUploadAttachmentResource::ROUTE_NAME_UPLOAD,
                                [
                                    'organisationId' => $draftDecision->getOrganisation()->getId(),
                                    'dossierExternalId' => $draftDecision->getExternalId(),
                                    'attachmentExternalId' => $draftDecisionAttachment->getExternalId(),
                                ],
                            )->toString(),
                        ],
                        'public' => ['href' => $dossierPathHelper->getAbsoluteDetailsPath($draftDecision)],
                        'file' => [
                            'href' => $publicUrlGenerator->buildUrlFromRoute(
                                DossierFileController::ROUTE_NAME_DOSSIER_FILE_DOWNLOAD,
                                [
                                    'documentPrefix' => $draftDecision->getDocumentPrefix(),
                                    'dossierNumber' => $draftDecision->getDossierNumber(),
                                    'type' => DossierFileType::ATTACHMENT->value,
                                    'id' => $draftDecisionAttachment->getId(),
                                ],
                            )->toString(),
                        ],
                    ],
                ],
            ],
            'dossierDate' => $draftDecision->getDateFrom()?->format('Y-m-d'),
            '_links' => [
                'self' => ['href' => $this->buildApiUrl($organisation, $draftDecision)],
                'public' => ['href' => $dossierPathHelper->getAbsoluteDetailsPath($draftDecision)],
            ],
        ];

        self::assertSame($expectedResponse, $response->toArray());
        self::assertMatchesResourceItemJsonSchema(DraftDecisionResource::class);
    }

    public function testGetFromIncorrectOrganisation(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $draftDecision = DraftDecisionFactory::createOne([
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
        ]);

        self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $draftDecision));
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        self::assertJsonEquals([
            'type' => 'errors#resource-not-found',
            'title' => 'Resource Not Found',
            'status' => Response::HTTP_NOT_FOUND,
            'detail' => sprintf('DraftDecision with id %s was not found', $draftDecision->getExternalId()),
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
            'detail' => sprintf('DraftDecision with id %s was not found', $unknownExternalId),
        ]);
    }

    public function testCreateDraftDecision(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        self::assertDatabaseCount(DraftDecision::class, 0);

        $data = $this->createValidDraftDecisionDataPayload($department, $subject, $this->getFaker()->numberBetween(1, 3));
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(DraftDecisionResource::class);
        self::assertJsonContains(['status' => DossierStatus::CONCEPT->value]);
        self::assertDatabaseCount(DraftDecision::class, 1);
    }

    public function testCreateDraftDecisionWithoutSubject(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        self::assertDatabaseCount(DraftDecision::class, 0);

        $data = $this->createValidDraftDecisionDataPayload($department, null, 1);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(DraftDecisionResource::class);

        self::assertDatabaseCount(DraftDecision::class, 1);
    }

    public function testCreateDraftDecisionWithoutMainDocument(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        self::assertDatabaseCount(DraftDecision::class, 0);

        $data = $this->createValidDraftDecisionDataPayload($department, $subject, 1);
        unset($data['mainDocument']);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [[
            'code' => NotNull::IS_NULL_ERROR,
            'propertyPath' => 'mainDocument',
        ], ]]);

        self::assertDatabaseCount(DraftDecision::class, 0);
    }

    public function testCreateDraftDecisionWithoutRequiredAttachmentType(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        self::assertDatabaseCount(DraftDecision::class, 0);

        $data = $this->createValidDraftDecisionDataPayload($department, $subject, 1);
        $data['attachments'] = [];
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        self::assertDatabaseCount(DraftDecision::class, 0);
    }

    public function testCreateDraftDecisionIgnoresGroundsInPayload(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        $data = $this->createValidDraftDecisionDataPayload($department, $subject, 1);

        $mainDocument = $data['mainDocument'];
        self::assertIsArray($mainDocument);
        $mainDocument['grounds'] = [$this->getFaker()->randomElement(Citation::ALL_GROUND_KEYS)];
        $data['mainDocument'] = $mainDocument;

        $attachments = $data['attachments'];
        self::assertIsArray($attachments);
        $attachment = $attachments[0];
        self::assertIsArray($attachment);
        $attachment['grounds'] = [$this->getFaker()->randomElement(Citation::ALL_GROUND_KEYS)];
        $attachments[0] = $attachment;
        $data['attachments'] = $attachments;

        $url = $this->buildUrl($organisation, $this->getFaker()->slug(1));
        $response = self::createPublicationApiRequest(Request::METHOD_PUT, $url, ['json' => $data]);
        self::assertResponseIsSuccessful();

        $responseData = $response->toArray();
        self::assertIsArray($responseData['mainDocument']);
        self::assertArrayNotHasKey('grounds', $responseData['mainDocument']);
        self::assertIsArray($responseData['attachments']);
        self::assertIsArray($responseData['attachments'][0]);
        self::assertArrayNotHasKey('grounds', $responseData['attachments'][0]);

        $attachments = DraftDecisionAttachmentFactory::all();
        self::assertCount(1, $attachments);
        self::assertSame([], $attachments[0]->getGrounds());
    }

    public function testCreateDraftDecisionWithTooLongExternalId(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();

        $data = $this->createValidDraftDecisionDataPayload($department, $subject, 1);

        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, str_repeat('x', 129)), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testCreateDraftDecisionWithExternalIdAlreadyUsedByComplaintJudgementReturnsConflict(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        $data = $this->createValidDraftDecisionDataPayload($department, $subject, 1);
        $externalId = $this->getFaker()->externalId();
        ComplaintJudgementFactory::createOne([
            'externalId' => $externalId,
            'organisation' => $organisation,
            'departments' => [$department],
            'subject' => $subject,
        ]);

        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $externalId), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
        self::assertJsonContains(['detail' => 'ExternalId already in use by type complaint-judgement']);
    }

    /**
     * @param array<string,array<array-key,mixed>> $dataOverrides
     * @param array<string,array<array-key,mixed>> $violations
     */
    #[DataProvider('createDraftDecisionValidationDataProvider')]
    public function testCreateDraftDecisionWithValidationError(array $dataOverrides, array $violations): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        self::assertDatabaseCount(DraftDecision::class, 0);

        $data = array_merge($this->createValidDraftDecisionDataPayload($department, $subject, 1), $dataOverrides);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [$violations]]);
    }

    /**
     * @return array<string,array<array-key,mixed>>
     */
    public static function createDraftDecisionValidationDataProvider(): array
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
                        'type' => AttachmentType::LEGISLATIVE_PROPOSAL,
                        'language' => 'invalid',
                    ],
                ],
                [
                    'code' => Type::INVALID_TYPE_ERROR,
                    'propertyPath' => 'mainDocument.language',
                ],
            ],
            'mainDocument type not allowed' => [
                [
                    'mainDocument' => [
                        'fileName' => 'filename.pdf',
                        'formalDate' => CarbonImmutable::now()->format('Y-m-d'),
                        'type' => AttachmentType::REQUEST_FOR_ADVICE->value,
                        'language' => AttachmentLanguage::ENG->value,
                    ],
                ],
                [
                    'code' => Choice::NO_SUCH_CHOICE_ERROR,
                    'propertyPath' => 'mainDocument.type',
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

    public function testUpdateDraftDecision(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $draftDecision = DraftDecisionFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
        ]);
        DraftDecisionMainDocumentFactory::createOne(['dossier' => $draftDecision]);
        DraftDecisionAttachmentFactory::createOne(['dossier' => $draftDecision]);

        self::assertDatabaseHas(DraftDecision::class, [
            'title' => (string) $draftDecision->getTitle(),
            'summary' => $draftDecision->getSummary(),
        ]);

        $data = $this->createValidDraftDecisionDataPayload($department, null, 1);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $draftDecision), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(DraftDecisionResource::class);

        self::assertDatabaseHas(DraftDecision::class, [
            'dossierNumber' => $data['dossierNumber'],
            'documentPrefix' => $draftDecision->getDocumentPrefix(),
            'summary' => $data['summary'],
            'title' => $data['title'],
        ]);
    }

    /**
     * @param array<string,array<array-key,mixed>> $dataOverrides
     * @param array<string,array<array-key,mixed>> $violations
     */
    #[DataProvider('updateDraftDecisionValidationDataProvider')]
    public function testUpdateDraftDecisionWithValidationErrors(array $dataOverrides, array $violations): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $draftDecision = DraftDecisionFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'departments' => [$department],
            'status' => DossierStatus::CONCEPT,
        ]);
        DraftDecisionMainDocumentFactory::createOne(['dossier' => $draftDecision]);
        DraftDecisionAttachmentFactory::createOne(['dossier' => $draftDecision]);

        self::assertDatabaseHas(DraftDecision::class, [
            'title' => (string) $draftDecision->getTitle(),
            'summary' => $draftDecision->getSummary(),
        ]);

        $data = array_merge($this->createValidDraftDecisionDataPayload($department, null, 1), $dataOverrides);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $draftDecision), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [$violations]]);

        self::assertDatabaseHas(DraftDecision::class, [
            'title' => (string) $draftDecision->getTitle(),
            'summary' => $draftDecision->getSummary(),
        ]);
    }

    /**
     * @return array<string,array<array-key,mixed>>
     */
    public static function updateDraftDecisionValidationDataProvider(): array
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

    public function testUpdateDraftDecisionWithNonConceptState(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $draftDecision = DraftDecisionFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => DossierStatus::SCHEDULED,
        ]);
        DraftDecisionMainDocumentFactory::createOne(['dossier' => $draftDecision]);
        DraftDecisionAttachmentFactory::createOne([
            'dossier' => $draftDecision,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        self::assertDatabaseHas(DraftDecision::class, [
            'title' => (string) $draftDecision->getTitle(),
            'summary' => $draftDecision->getSummary(),
        ]);

        $data = $this->createValidDraftDecisionDataPayload($department, null, 1);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $draftDecision), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        self::assertDatabaseHas(DraftDecision::class, [
            'title' => (string) $draftDecision->getTitle(),
            'summary' => $draftDecision->getSummary(),
        ]);
    }

    public function testUpdateDraftDecisionWithSameAttachmentsMetadataIsIgnored(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $draftDecision = DraftDecisionFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
        ]);
        $mainDocument = DraftDecisionMainDocumentFactory::createOne(['dossier' => $draftDecision]);
        $attachment = DraftDecisionAttachmentFactory::createOne([
            'dossier' => $draftDecision,
            'externalId' => $this->getFaker()->externalId(),
            'type' => AttachmentType::REQUEST_FOR_ADVICE,
            'grounds' => [],
        ]);

        self::assertDatabaseCount(DraftDecisionAttachment::class, 1);

        $data = [
            'title' => (string) $draftDecision->getTitle(),
            'dossierNumber' => $draftDecision->getDossierNumber(),
            'dossierDate' => $draftDecision->getDateFrom()?->format('Y-m-d'),
            'publicationDate' => $draftDecision->getPublicationDate()?->format('Y-m-d'),
            'summary' => $draftDecision->getSummary(),
            'departmentId' => $department->getId(),
            'subjectId' => $draftDecision->getSubject()?->getId(),
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
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $draftDecision), ['json' => $data]);
        self::assertResponseIsSuccessful();

        self::assertDatabaseCount(DraftDecisionAttachment::class, 1);
        self::assertDatabaseHas(DraftDecisionAttachment::class, [
            'id' => $attachment->getId(),
            'dossier' => ['id' => $draftDecision->getId()],
        ]);
    }

    public function testUpdateDraftDecisionWithChangedAttachmentsMetadataIsUpdated(): void
    {
        $changedFileName = 'new-file.pdf';

        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $draftDecision = DraftDecisionFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
        ]);
        $mainDocument = DraftDecisionMainDocumentFactory::createOne([
            'dossier' => $draftDecision,
            'fileInfo' => FileInfoFactory::createOne(['uploaded' => false]),
        ]);
        $attachment = DraftDecisionAttachmentFactory::createOne([
            'dossier' => $draftDecision,
            'externalId' => $this->getFaker()->externalId(),
            'type' => AttachmentType::REQUEST_FOR_ADVICE,
        ]);

        $data = [
            'title' => (string) $draftDecision->getTitle(),
            'dossierNumber' => $draftDecision->getDossierNumber(),
            'dossierDate' => $draftDecision->getDateFrom()?->format('Y-m-d'),
            'publicationDate' => $draftDecision->getPublicationDate()?->format('Y-m-d'),
            'summary' => $draftDecision->getSummary(),
            'departmentId' => $department->getId(),
            'subjectId' => $draftDecision->getSubject()?->getId(),
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
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $draftDecision), ['json' => $data]);
        self::assertResponseIsSuccessful();

        self::assertDatabaseCount(DraftDecisionAttachment::class, 1);
        self::assertDatabaseHas(DraftDecisionAttachment::class, [
            'id' => $attachment->getId(),
            'dossier' => ['id' => $draftDecision->getId()],
            'fileInfo.name' => $changedFileName,
        ]);
    }

    public function testUpdateDraftDecisionWithOneNewAttachmentAndOneExistingIsPartiallyUpdated(): void
    {
        $changedFileName = 'new-file.pdf';
        $newAttachmentExternalId = $this->getFaker()->externalId();

        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $draftDecision = DraftDecisionFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
        ]);
        $mainDocument = DraftDecisionMainDocumentFactory::createOne(['dossier' => $draftDecision]);
        $attachment1 = DraftDecisionAttachmentFactory::createOne([
            'dossier' => $draftDecision,
            'externalId' => $this->getFaker()->externalId(),
            'type' => AttachmentType::REQUEST_FOR_ADVICE,
        ]);

        $data = [
            'title' => (string) $draftDecision->getTitle(),
            'dossierNumber' => $draftDecision->getDossierNumber(),
            'dossierDate' => $draftDecision->getDateFrom()?->format('Y-m-d'),
            'publicationDate' => $draftDecision->getPublicationDate()?->format('Y-m-d'),
            'summary' => $draftDecision->getSummary(),
            'departmentId' => $department->getId(),
            'subjectId' => $draftDecision->getSubject()?->getId(),
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

        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $draftDecision), ['json' => $data]);
        self::assertResponseIsSuccessful();

        self::assertDatabaseCount(DraftDecisionAttachment::class, 2);

        self::assertDatabaseHas(DraftDecisionAttachment::class, [
            'id' => $attachment1->getId(),
            'dossier' => ['id' => $draftDecision->getId()],
            'fileInfo.name' => $attachment1->getFileInfo()->getName(),
        ]);

        self::assertDatabaseHas(DraftDecisionAttachment::class, [
            'externalId' => $newAttachmentExternalId,
            'dossier' => ['id' => $draftDecision->getId()],
            'fileInfo.name' => $changedFileName,
        ]);
    }

    public function testUpdateDraftDecisionWithLessAttachmentsAndOneExistingIsPartiallyDeleted(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $draftDecision = DraftDecisionFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
        ]);
        $mainDocument = DraftDecisionMainDocumentFactory::createOne(['dossier' => $draftDecision]);
        $attachment1 = DraftDecisionAttachmentFactory::createOne([
            'dossier' => $draftDecision,
            'externalId' => $this->getFaker()->externalId(),
            'type' => AttachmentType::REQUEST_FOR_ADVICE,
        ]);
        $attachment2 = DraftDecisionAttachmentFactory::createOne([
            'dossier' => $draftDecision,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        $data = [
            'title' => (string) $draftDecision->getTitle(),
            'dossierNumber' => $draftDecision->getDossierNumber(),
            'dossierDate' => $draftDecision->getDateFrom()?->format('Y-m-d'),
            'publicationDate' => $draftDecision->getPublicationDate()?->format('Y-m-d'),
            'summary' => $draftDecision->getSummary(),
            'departmentId' => $department->getId(),
            'subjectId' => $draftDecision->getSubject()?->getId(),
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

        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $draftDecision), ['json' => $data]);
        self::assertResponseIsSuccessful();

        self::assertDatabaseCount(DraftDecisionAttachment::class, 1);

        self::assertDatabaseHas(DraftDecisionAttachment::class, [
            'id' => $attachment1->getId(),
            'dossier' => ['id' => $draftDecision->getId()],
            'fileInfo.name' => $attachment1->getFileInfo()->getName(),
        ]);

        self::assertDatabaseMissing(DraftDecisionAttachment::class, [
            'id' => $attachment2->getId(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function createValidDraftDecisionDataPayload(Department $department, ?Subject $subject, int $attachmentCount): array
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
                'type' => AttachmentType::LEGISLATIVE_PROPOSAL,
                'language' => $this->getFaker()->randomElement(AttachmentLanguage::cases()),
            ],
            'attachments' => $this->createDraftDecisionAttachmentsPayload($attachmentCount),
        ];
    }

    /**
     * A valid DraftDecision requires at least one attachment of type request-for-advice or
     * policy-document, and its attachments never have grounds.
     *
     * @return list<array<string, mixed>>
     */
    private function createDraftDecisionAttachmentsPayload(int $attachmentCount): array
    {
        $attachments = [];
        for ($i = 0; $i < $attachmentCount; $i++) {
            $type = $i === 0
                ? $this->getFaker()->randomElement([AttachmentType::REQUEST_FOR_ADVICE, AttachmentType::POLICY_DOCUMENT])
                : $this->getFaker()->randomElement(DraftDecisionAttachment::getAllowedTypes());

            $attachments[] = [
                'fileName' => $this->getFaker()->fileNameForGroup(UploadGroupId::ATTACHMENTS)->toString(),
                'formalDate' => $this->getFaker()->date(),
                'language' => $this->getFaker()->randomElement(AttachmentLanguage::cases()),
                'type' => $type,
                'externalId' => $this->getFaker()->externalId()->toString(),
            ];
        }

        return $attachments;
    }

    public function testUpdatePublishedDraftDecisionIgnoresPublicationDateChange(): void
    {
        $newTitle = 'Updated title for published draft decision';
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $draftDecision = DraftDecisionFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => DossierStatus::PUBLISHED,
            'publicationDate' => PlainDate::create('2025-01-02'),
        ]);
        $mainDocument = DraftDecisionMainDocumentFactory::createOne(['dossier' => $draftDecision]);
        $attachment = DraftDecisionAttachmentFactory::createOne([
            'dossier' => $draftDecision,
            'externalId' => $this->getFaker()->externalId(),
            'type' => AttachmentType::REQUEST_FOR_ADVICE,
            'grounds' => [],
        ]);

        $data = [
            'title' => $newTitle,
            'dossierNumber' => $draftDecision->getDossierNumber(),
            'dossierDate' => $draftDecision->getDateFrom()?->format('Y-m-d'),
            'publicationDate' => '2025-02-02',
            'summary' => $draftDecision->getSummary(),
            'departmentId' => $department->getId(),
            'subjectId' => $draftDecision->getSubject()?->getId(),
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

        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $draftDecision), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertJsonContains(['publicationDate' => '2025-01-02', 'title' => $newTitle]);

        self::assertDatabaseHas(DraftDecision::class, ['title' => $newTitle]);
    }
}
