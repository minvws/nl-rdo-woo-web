<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Dossier\Disposition;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PublicationApi\Api\Dossier\Disposition\DispositionResource;
use PublicationApi\Domain\Upload\UploadStatus;
use PublicationApi\Tests\Integration\Api\Dossier\ApiPublicationV1DossierTestCase;
use Shared\Domain\Department\Department;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\Disposition\Disposition;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionMainDocument;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\FileInfoFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\DocumentPrefixFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Disposition\DispositionAttachmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Disposition\DispositionFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Disposition\DispositionMainDocumentFactory;
use Shared\Tests\Factory\Publication\Subject\SubjectFactory;
use Shared\Validator\EntityExists;
use Shared\Validator\PlainDate\PlainDateBeforeOrEqual;
use Shared\ValueObject\PlainDate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Type;

use function array_merge;
use function sprintf;

final class DispositionPublicationV1Test extends ApiPublicationV1DossierTestCase
{
    public function getDossierApiUriSegment(): string
    {
        return 'disposition';
    }

    public function testGetDispositionCollection(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $disposition = DispositionFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'departments' => [$department],
        ]);
        DispositionMainDocumentFactory::createOne(['dossier' => $disposition]);
        DispositionAttachmentFactory::createOne(['dossier' => $disposition]);

        $result = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation));
        self::assertResponseIsSuccessful();
        self::assertCount(1, $result->toArray());
        self::assertJsonContains([['externalId' => $disposition->getExternalId()?->__toString()]]);
    }

    public function testGetDisposition(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $disposition = DispositionFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'departments' => [$department],
        ]);
        $dispositionMainDocument = DispositionMainDocumentFactory::createOne(['dossier' => $disposition]);
        $dispositionAttachment = DispositionAttachmentFactory::createOne(['dossier' => $disposition]);

        $response = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $disposition));

        self::assertResponseIsSuccessful();

        $expectedResponse = [
            'id' => (string) $disposition->getId(),
            'externalId' => $disposition->getExternalId()?->__toString(),
            'organisation' => [
                'id' => (string) $disposition->getOrganisation()->getId(),
                'name' => $disposition->getOrganisation()->getName(),
            ],
            'dossierNumber' => $disposition->getDossierNr(),
            'title' => $disposition->getTitle(),
            'summary' => $disposition->getSummary(),
            'subject' => $disposition->getSubject()?->getName(),
            'department' => [
                'id' => (string) $department->getId(),
                'name' => $department->getName(),
            ],
            'publicationDate' => $disposition->getPublicationDate()?->format('Y-m-d'),
            'status' => $disposition->getStatus()->value,
            'mainDocument' => [
                'id' => (string) $dispositionMainDocument->getId(),
                'type' => $dispositionMainDocument->getType()->value,
                'language' => $dispositionMainDocument->getLanguage()->value,
                'formalDate' => $dispositionMainDocument->getFormalDate()->format('Y-m-d'),
                'grounds' => $dispositionMainDocument->getGrounds(),
                'fileName' => $dispositionMainDocument->getFileInfo()->getName(),
                'uploadStatus' => UploadStatus::PROCESSED->value,
            ],
            'attachments' => [
                [
                    'id' => (string) $dispositionAttachment->getId(),
                    'type' => $dispositionAttachment->getType()->value,
                    'language' => $dispositionAttachment->getLanguage()->value,
                    'formalDate' => $dispositionAttachment->getFormalDate()->format('Y-m-d'),
                    'grounds' => $dispositionAttachment->getGrounds(),
                    'fileName' => $dispositionAttachment->getFileInfo()->getName(),
                    'externalId' => $dispositionAttachment->getExternalId()?->__toString(),
                    'uploadStatus' => UploadStatus::PROCESSED->value,
                ],
            ],
            'dossierDate' => $disposition->getDateFrom()?->format('Y-m-d'),
        ];

        self::assertSame($expectedResponse, $response->toArray());
        self::assertMatchesResourceItemJsonSchema(DispositionResource::class);
    }

    public function testGetFromIncorrectOrganisation(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $disposition = DispositionFactory::createOne([
            'externalId' => $this->getFaker()->externalId(),
            'departments' => [$department],
        ]);

        self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $disposition));
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        self::assertJsonEquals([
            'type' => 'errors#resource-not-found',
            'title' => 'Resource Not Found',
            'status' => Response::HTTP_NOT_FOUND,
            'detail' => sprintf('Disposition with id %s was not found', $disposition->getExternalId()),
        ]);
    }

    public function testGetWithUnknownExternalId(): void
    {
        $organisation = OrganisationFactory::createOne();
        $unknownExternalId = $this->getFaker()->word();

        self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $unknownExternalId));

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        self::assertJsonEquals([
            'type' => 'errors#resource-not-found',
            'title' => 'Resource Not Found',
            'status' => Response::HTTP_NOT_FOUND,
            'detail' => sprintf('Disposition with id %s was not found', $unknownExternalId),
        ]);
    }

    public function testCreateDisposition(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        self::assertDatabaseCount(Disposition::class, 0);

        $data = $this->createValidDispositionDataPayload($department, $subject, $this->getFaker()->numberBetween(1, 3));
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(DispositionResource::class);

        self::assertDatabaseCount(Disposition::class, 1);
    }

    public function testCreateDispositionWithoutSubject(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        self::assertDatabaseCount(Disposition::class, 0);

        $data = $this->createValidDispositionDataPayload($department, null, 1);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(DispositionResource::class);

        self::assertDatabaseCount(Disposition::class, 1);
    }

    public function testCreateDispositionWithoutMainDocument(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();

        self::assertDatabaseCount(Disposition::class, 0);

        $data = $this->createValidDispositionDataPayload($department, $subject, 0);
        unset($data['mainDocument']);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [[
            'code' => Type::INVALID_TYPE_ERROR,
            'propertyPath' => 'mainDocument',
        ], ]]);
    }

    public function testCreateDispositionWithoutAttachments(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        self::assertDatabaseCount(Disposition::class, 0);

        $data = $this->createValidDispositionDataPayload($department, $subject, 0);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(DispositionResource::class);

        self::assertDatabaseCount(Disposition::class, 1);
    }

    /**
     * @param array<string,array<array-key,mixed>> $dataOverrides
     * @param array<string,array<array-key,mixed>> $violations
     */
    #[DataProvider('createDispositionValidationDataProvider')]
    public function testCreateDispositionWithValidationError(array $dataOverrides, array $violations): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        self::assertDatabaseCount(Disposition::class, 0);

        $data = array_merge($this->createValidDispositionDataPayload($department, $subject, 1), $dataOverrides);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [$violations]]);
    }

    /**
     * @return array<string,array<array-key,mixed>>
     */
    public static function createDispositionValidationDataProvider(): array
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

    public function testUpdateDisposition(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $disposition = DispositionFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'externalId' => $this->getFaker()->externalId(),
            'departments' => [$department],
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
        ]);
        DispositionMainDocumentFactory::createOne(['dossier' => $disposition]);
        DispositionAttachmentFactory::createOne(['dossier' => $disposition]);

        self::assertDatabaseHas(Disposition::class, [
            'title' => $disposition->getTitle(),
            'summary' => $disposition->getSummary(),
        ]);

        $data = $this->createValidDispositionDataPayload($department, null, 0);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $disposition), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(DispositionResource::class);

        self::assertDatabaseHas(Disposition::class, [
            'dossierNr' => $data['dossierNumber'],
            'documentPrefix' => $disposition->getDocumentPrefix(),
            'summary' => $data['summary'],
            'title' => $data['title'],
        ]);
    }

    public function testUpdateDispositionWithOnlyNewAttachmentsDeletesOldAttachments(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $disposition = DispositionFactory::createOne([
            'dateFrom' => PlainDate::create('2022-01-01'),
            'dateTo' => PlainDate::create('2022-01-02'),
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
        ]);
        DispositionMainDocumentFactory::createOne(['dossier' => $disposition]);
        DispositionAttachmentFactory::createOne(['dossier' => $disposition]);

        self::assertDatabaseCount(DispositionAttachment::class, 1);

        $data = [
            'title' => $disposition->getTitle(),
            'dossierNumber' => $disposition->getDossierNr(),
            'dossierDate' => $disposition->getDateFrom()?->format('Y-m-d'),
            'publicationDate' => $disposition->getPublicationDate()?->format('Y-m-d'),
            'summary' => $disposition->getSummary(),
            'departmentId' => $department->getId(),
            'subjectId' => $disposition->getSubject()?->getId(),
            'mainDocument' => [
                'fileName' => $disposition->getMainDocument()?->getFileInfo()->getName(),
                'formalDate' => $disposition->getMainDocument()?->getFormalDate()->format('Y-m-d'),
                'type' => $disposition->getMainDocument()?->getType()->value,
                'language' => $disposition->getMainDocument()?->getLanguage()->value,
            ],
            'attachments' => [],
        ];
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $disposition), ['json' => $data]);
        self::assertResponseIsSuccessful();

        self::assertDatabaseCount(DispositionAttachment::class, 0);
    }

    /**
     * @param array<string,array<array-key,mixed>> $dataOverrides
     * @param array<string,array<array-key,mixed>> $violations
     */
    #[DataProvider('updateDispositionValidationDataProvider')]
    public function testUpdateDispositionWithValidationErrors(array $dataOverrides, array $violations): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $disposition = DispositionFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'departments' => [$department],
            'status' => DossierStatus::CONCEPT,
        ]);
        DispositionMainDocumentFactory::createOne(['dossier' => $disposition]);
        DispositionAttachmentFactory::createOne(['dossier' => $disposition]);

        self::assertDatabaseHas(Disposition::class, [
            'title' => $disposition->getTitle(),
            'summary' => $disposition->getSummary(),
        ]);

        $data = array_merge($this->createValidDispositionDataPayload($department, null, 1), $dataOverrides);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $disposition), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [$violations]]);

        self::assertDatabaseHas(Disposition::class, [
            'title' => $disposition->getTitle(),
            'summary' => $disposition->getSummary(),
        ]);
    }

    /**
     * @return array<string,array<array-key,mixed>>
     */
    public static function updateDispositionValidationDataProvider(): array
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

    public function testUpdateDispositionWithNonConceptState(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $disposition = DispositionFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'externalId' => $this->getFaker()->externalId(),
            'departments' => [$department],
            'organisation' => $organisation,
            'status' => $this->getFaker()->randomElement(DossierStatus::nonConceptCases()),
        ]);
        DispositionMainDocumentFactory::createOne(['dossier' => $disposition]);
        DispositionAttachmentFactory::createOne(['dossier' => $disposition]);

        self::assertDatabaseHas(Disposition::class, [
            'title' => $disposition->getTitle(),
            'summary' => $disposition->getSummary(),
        ]);

        $data = $this->createValidDispositionDataPayload($department, null, 0);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $disposition), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [['message' => 'dossier update is not allowed in non-concept state']]]);

        self::assertDatabaseHas(Disposition::class, [
            'title' => $disposition->getTitle(),
            'summary' => $disposition->getSummary(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function createValidDispositionDataPayload(Department $department, ?Subject $subject, int $attachmentCount): array
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
                'type' => $this->getFaker()->randomElement(DispositionMainDocument::getAllowedTypes()),
                'language' => $this->getFaker()->randomElement(AttachmentLanguage::cases()),
            ],
            'attachments' => $this->createValidAttachmentsPayload($attachmentCount, DispositionAttachment::getAllowedTypes()),
        ];
    }

    public function testUpdateDispositionWithSameAttachmentsMetadataIsIgnored(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $disposition = DispositionFactory::createOne([
            'dateFrom' => PlainDate::create('2022-01-01'),
            'dateTo' => PlainDate::create('2022-01-02'),
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
        ]);
        $mainDocument = DispositionMainDocumentFactory::createOne(['dossier' => $disposition]);
        $attachment = DispositionAttachmentFactory::createOne([
            'dossier' => $disposition,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        self::assertDatabaseCount(DispositionAttachment::class, 1);

        $data = [
            'title' => $disposition->getTitle(),
            'dossierNumber' => $disposition->getDossierNr(),
            'dossierDate' => $disposition->getDateFrom()?->format('Y-m-d'),
            'publicationDate' => $disposition->getPublicationDate()?->format('Y-m-d'),
            'summary' => $disposition->getSummary(),
            'departmentId' => $department->getId(),
            'subjectId' => $disposition->getSubject()?->getId(),
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
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $disposition), ['json' => $data]);
        self::assertResponseIsSuccessful();

        self::assertDatabaseCount(DispositionAttachment::class, 1);
        self::assertDatabaseHas(DispositionAttachment::class, [
            'id' => $attachment->getId(),
            'dossier' => ['id' => $disposition->getId()],
        ]);
    }

    public function testUpdateDispositionWithChangedAttachmentsMetadataIsUpdated(): void
    {
        $changedFileName = 'new-file.pdf';

        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $disposition = DispositionFactory::createOne([
            'dateFrom' => PlainDate::create('2022-01-01'),
            'dateTo' => PlainDate::create('2022-01-02'),
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
        ]);
        $mainDocument = DispositionMainDocumentFactory::createOne([
            'dossier' => $disposition,
            'fileInfo' => FileInfoFactory::createOne(['uploaded' => false]),
        ]);
        $attachment = DispositionAttachmentFactory::createOne([
            'dossier' => $disposition,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        $data = [
            'title' => $disposition->getTitle(),
            'dossierNumber' => $disposition->getDossierNr(),
            'dossierDate' => $disposition->getDateFrom()?->format('Y-m-d'),
            'publicationDate' => $disposition->getPublicationDate()?->format('Y-m-d'),
            'summary' => $disposition->getSummary(),
            'departmentId' => $department->getId(),
            'subjectId' => $disposition->getSubject()?->getId(),
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
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $disposition), ['json' => $data]);
        self::assertResponseIsSuccessful();

        self::assertDatabaseCount(DispositionAttachment::class, 1);
        self::assertDatabaseHas(DispositionAttachment::class, [
            'id' => $attachment->getId(),
            'dossier' => ['id' => $disposition->getId()],
            'fileInfo.name' => $changedFileName,
        ]);
    }

    public function testUpdateDispositionWithOneNewAttachmentAndOneExistingIsPartiallyUpdated(): void
    {
        $changedFileName = 'new-file.pdf';
        $newAttachmentExternalId = $this->getFaker()->externalId();

        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $disposition = DispositionFactory::createOne([
            'dateFrom' => PlainDate::create('2022-01-01'),
            'dateTo' => PlainDate::create('2022-01-02'),
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
        ]);
        $mainDocument = DispositionMainDocumentFactory::createOne(['dossier' => $disposition]);
        $attachment1 = DispositionAttachmentFactory::createOne([
            'dossier' => $disposition,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        $data = [
            'title' => $disposition->getTitle(),
            'dossierNumber' => $disposition->getDossierNr(),
            'dossierDate' => $disposition->getDateFrom()?->format('Y-m-d'),
            'publicationDate' => $disposition->getPublicationDate()?->format('Y-m-d'),
            'summary' => $disposition->getSummary(),
            'departmentId' => $department->getId(),
            'subjectId' => $disposition->getSubject()?->getId(),
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

        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $disposition), ['json' => $data]);
        self::assertResponseIsSuccessful();

        self::assertDatabaseCount(DispositionAttachment::class, 2);

        self::assertDatabaseHas(DispositionAttachment::class, [
            'id' => $attachment1->getId(),
            'dossier' => ['id' => $disposition->getId()],
            'fileInfo.name' => $attachment1->getFileInfo()->getName(),
        ]);

        self::assertDatabaseHas(DispositionAttachment::class, [
            'externalId' => $newAttachmentExternalId,
            'dossier' => ['id' => $disposition->getId()],
            'fileInfo.name' => $changedFileName,
        ]);
    }
}
