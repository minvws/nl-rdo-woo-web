<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Dossier\RequestForAdvice;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PublicationApi\Api\Dossier\RequestForAdvice\RequestForAdviceResource;
use PublicationApi\Domain\Upload\UploadStatus;
use PublicationApi\Tests\Integration\Api\Dossier\ApiPublicationV1DossierTestCase;
use Shared\Domain\Department\Department;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdvice;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceAttachment;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\FileInfoFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\DocumentPrefixFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceAttachmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceMainDocumentFactory;
use Shared\Tests\Factory\Publication\Subject\SubjectFactory;
use Shared\Validator\EntityExists;
use Shared\Validator\PlainDate\PlainDateBeforeOrEqual;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Type;

use function array_merge;
use function sprintf;

final class RequestForAdvicePublicationV1Test extends ApiPublicationV1DossierTestCase
{
    public function getDossierApiUriSegment(): string
    {
        return 'request-for-advice';
    }

    public function testGetRequestForAdviceCollection(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $requestForAdvice = RequestForAdviceFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'departments' => [$department],
            'link' => $this->getFaker()->url(),
            'advisoryBodies' => [$this->getFaker()->words(3, true)],
        ]);
        RequestForAdviceMainDocumentFactory::createOne(['dossier' => $requestForAdvice]);
        RequestForAdviceAttachmentFactory::createOne(['dossier' => $requestForAdvice]);

        $result = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation));
        self::assertResponseIsSuccessful();
        self::assertCount(1, $result->toArray());
        self::assertJsonContains([['externalId' => $requestForAdvice->getExternalId()?->__toString()]]);
    }

    public function testGetRequestForAdvice(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $requestForAdvice = RequestForAdviceFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'departments' => [$department],
            'link' => $this->getFaker()->url(),
            'advisoryBodies' => [$this->getFaker()->words(3, true)],
        ]);
        $requestForAdviceMainDocument = RequestForAdviceMainDocumentFactory::createOne(['dossier' => $requestForAdvice]);
        $requestForAdviceAttachment = RequestForAdviceAttachmentFactory::createOne(['dossier' => $requestForAdvice]);

        $response = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $requestForAdvice));

        self::assertResponseIsSuccessful();

        $expectedResponse = [
            'id' => (string) $requestForAdvice->getId(),
            'externalId' => $requestForAdvice->getExternalId()?->__toString(),
            'organisation' => [
                'id' => (string) $requestForAdvice->getOrganisation()->getId(),
                'name' => $requestForAdvice->getOrganisation()->getName(),
            ],
            'dossierNumber' => $requestForAdvice->getDossierNr(),
            'title' => $requestForAdvice->getTitle(),
            'summary' => $requestForAdvice->getSummary(),
            'subject' => $requestForAdvice->getSubject()?->getName(),
            'department' => [
                'id' => (string) $department->getId(),
                'name' => $department->getName(),
            ],
            'publicationDate' => $requestForAdvice->getPublicationDate()?->format('Y-m-d'),
            'status' => $requestForAdvice->getStatus()->value,
            'mainDocument' => [
                'id' => (string) $requestForAdviceMainDocument->getId(),
                'type' => $requestForAdviceMainDocument->getType()->value,
                'language' => $requestForAdviceMainDocument->getLanguage()->value,
                'formalDate' => $requestForAdviceMainDocument->getFormalDate()->format('Y-m-d'),
                'grounds' => $requestForAdviceMainDocument->getGrounds(),
                'fileName' => $requestForAdviceMainDocument->getFileInfo()->getName(),
                'uploadStatus' => UploadStatus::PROCESSED->value,
            ],
            'attachments' => [
                [
                    'id' => (string) $requestForAdviceAttachment->getId(),
                    'type' => $requestForAdviceAttachment->getType()->value,
                    'language' => $requestForAdviceAttachment->getLanguage()->value,
                    'formalDate' => $requestForAdviceAttachment->getFormalDate()->format('Y-m-d'),
                    'grounds' => $requestForAdviceAttachment->getGrounds(),
                    'fileName' => $requestForAdviceAttachment->getFileInfo()->getName(),
                    'externalId' => $requestForAdviceAttachment->getExternalId()?->__toString(),
                    'uploadStatus' => UploadStatus::PROCESSED->value,
                ],
            ],
            'dossierDate' => $requestForAdvice->getDateFrom()?->format('Y-m-d'),
            'link' => $requestForAdvice->getLink(),
            'advisoryBodies' => $requestForAdvice->getAdvisoryBodies(),
        ];

        self::assertSame($expectedResponse, $response->toArray());
        self::assertMatchesResourceItemJsonSchema(RequestForAdviceResource::class);
    }

    public function testGetFromIncorrectOrganisation(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $requestForAdvice = RequestForAdviceFactory::createOne([
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
        ]);

        self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $requestForAdvice));
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        self::assertJsonEquals([
            'type' => 'errors#resource-not-found',
            'title' => 'Resource Not Found',
            'status' => Response::HTTP_NOT_FOUND,
            'detail' => sprintf('RequestForAdvice with id %s was not found', $requestForAdvice->getExternalId()),
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
            'detail' => sprintf('RequestForAdvice with id %s was not found', $unknownExternalId),
        ]);
    }

    public function testCreateRequestForAdvice(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        self::assertDatabaseCount(RequestForAdvice::class, 0);

        $data = $this->createValidRequestForAdviceDataPayload($department, $subject, $this->getFaker()->numberBetween(1, 3));
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(RequestForAdviceResource::class);

        self::assertDatabaseCount(RequestForAdvice::class, 1);
    }

    public function testCreateRequestForAdviceWithoutSubject(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        self::assertDatabaseCount(RequestForAdvice::class, 0);

        $data = $this->createValidRequestForAdviceDataPayload($department, null, 1);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(RequestForAdviceResource::class);

        self::assertDatabaseCount(RequestForAdvice::class, 1);
    }

    public function testCreateRequestForAdviceWithoutMainDocument(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();

        self::assertDatabaseCount(RequestForAdvice::class, 0);

        $data = $this->createValidRequestForAdviceDataPayload($department, $subject, 0);
        unset($data['mainDocument']);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [[
            'code' => Type::INVALID_TYPE_ERROR,
            'propertyPath' => 'mainDocument',
        ], ]]);
    }

    public function testCreateRequestForAdviceWithoutAttachments(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        self::assertDatabaseCount(RequestForAdvice::class, 0);

        $data = $this->createValidRequestForAdviceDataPayload($department, $subject, 0);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(RequestForAdviceResource::class);

        self::assertDatabaseCount(RequestForAdvice::class, 1);
    }

    /**
     * @param array<string,array<array-key,mixed>> $dataOverrides
     * @param array<string,array<array-key,mixed>> $violations
     */
    #[DataProvider('createRequestForAdviceValidationDataProvider')]
    public function testCreateRequestForAdviceWithValidationError(array $dataOverrides, array $violations): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        self::assertDatabaseCount(RequestForAdvice::class, 0);

        $data = array_merge($this->createValidRequestForAdviceDataPayload($department, $subject, 1), $dataOverrides);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [$violations]]);
    }

    /**
     * @return array<string,array<array-key,mixed>>
     */
    public static function createRequestForAdviceValidationDataProvider(): array
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
                        'type' => AttachmentType::REQUEST_FOR_ADVICE,
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

    public function testUpdateRequestForAdvice(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $requestForAdvice = RequestForAdviceFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
        ]);
        RequestForAdviceMainDocumentFactory::createOne(['dossier' => $requestForAdvice]);
        RequestForAdviceAttachmentFactory::createOne(['dossier' => $requestForAdvice]);

        self::assertDatabaseHas(RequestForAdvice::class, [
            'title' => $requestForAdvice->getTitle(),
            'summary' => $requestForAdvice->getSummary(),
        ]);

        $data = $this->createValidRequestForAdviceDataPayload($department, null, 0);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $requestForAdvice), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(RequestForAdviceResource::class);

        self::assertDatabaseHas(RequestForAdvice::class, [
            'dossierNr' => $data['dossierNumber'],
            'documentPrefix' => $requestForAdvice->getDocumentPrefix(),
            'summary' => $data['summary'],
            'title' => $data['title'],
            'link' => $data['link'],
        ]);
    }

    /**
     * @param array<string,array<array-key,mixed>> $dataOverrides
     * @param array<string,array<array-key,mixed>> $violations
     */
    #[DataProvider('updateRequestForAdviceValidationDataProvider')]
    public function testUpdateRequestForAdviceWithValidationErrors(array $dataOverrides, array $violations): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $requestForAdvice = RequestForAdviceFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'departments' => [$department],
            'status' => DossierStatus::CONCEPT,
        ]);
        RequestForAdviceMainDocumentFactory::createOne(['dossier' => $requestForAdvice]);
        RequestForAdviceAttachmentFactory::createOne(['dossier' => $requestForAdvice]);

        self::assertDatabaseHas(RequestForAdvice::class, [
            'title' => $requestForAdvice->getTitle(),
            'summary' => $requestForAdvice->getSummary(),
        ]);

        $data = array_merge($this->createValidRequestForAdviceDataPayload($department, null, 1), $dataOverrides);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $requestForAdvice), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [$violations]]);

        self::assertDatabaseHas(RequestForAdvice::class, [
            'title' => $requestForAdvice->getTitle(),
            'summary' => $requestForAdvice->getSummary(),
        ]);
    }

    /**
     * @return array<string,array<array-key,mixed>>
     */
    public static function updateRequestForAdviceValidationDataProvider(): array
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

    public function testUpdateRequestForAdviceWithNonConceptState(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $requestForAdvice = RequestForAdviceFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => $this->getFaker()->randomElement(DossierStatus::nonConceptCases()),
        ]);
        RequestForAdviceMainDocumentFactory::createOne(['dossier' => $requestForAdvice]);
        RequestForAdviceAttachmentFactory::createOne(['dossier' => $requestForAdvice]);

        self::assertDatabaseHas(RequestForAdvice::class, [
            'title' => $requestForAdvice->getTitle(),
            'summary' => $requestForAdvice->getSummary(),
        ]);

        $data = $this->createValidRequestForAdviceDataPayload($department, null, 0);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $requestForAdvice), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        self::assertDatabaseHas(RequestForAdvice::class, [
            'title' => $requestForAdvice->getTitle(),
            'summary' => $requestForAdvice->getSummary(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function createValidRequestForAdviceDataPayload(Department $department, ?Subject $subject, int $attachmentCount): array
    {
        return [
            'title' => $this->getFaker()->sentence(),
            'dossierNumber' => $this->getFaker()->slug(2),
            'dossierDate' => $this->getFaker()->dateTimeBetween('-3 weeks', '-2 week')->format('Y-m-d'),
            'publicationDate' => $this->getFaker()->plainDateBetween('-2 weeks', '-1 week')->format('Y-m-d'),
            'summary' => $this->getFaker()->sentence(),
            'departmentId' => $department->getId(),
            'subjectId' => $subject?->getId(),
            'link' => $this->getFaker()->url(),
            'advisoryBodies' => $this->getFaker()->boolean() ? [] : [$this->getFaker()->words(3, true)],
            'mainDocument' => [
                'fileName' => $this->getFaker()->fileNameForGroup(UploadGroupId::MAIN_DOCUMENTS)->toString(),
                'formalDate' => $this->getFaker()->date(),
                'type' => AttachmentType::REQUEST_FOR_ADVICE,
                'language' => $this->getFaker()->randomElement(AttachmentLanguage::cases()),
            ],
            'attachments' => $this->createValidAttachmentsPayload($attachmentCount, RequestForAdviceAttachment::getAllowedTypes()),
        ];
    }

    public function testUpdateRequestForAdviceWithSameAttachmentsMetadataIsIgnored(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $requestForAdvice = RequestForAdviceFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
        ]);
        $mainDocument = RequestForAdviceMainDocumentFactory::createOne(['dossier' => $requestForAdvice]);
        $attachment = RequestForAdviceAttachmentFactory::createOne([
            'dossier' => $requestForAdvice,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        self::assertDatabaseCount(RequestForAdviceAttachment::class, 1);

        $data = [
            'title' => $requestForAdvice->getTitle(),
            'dossierNumber' => $requestForAdvice->getDossierNr(),
            'dossierDate' => $requestForAdvice->getDateFrom()?->format('Y-m-d'),
            'publicationDate' => $requestForAdvice->getPublicationDate()?->format('Y-m-d'),
            'summary' => $requestForAdvice->getSummary(),
            'departmentId' => $department->getId(),
            'subjectId' => $requestForAdvice->getSubject()?->getId(),
            'link' => $requestForAdvice->getLink(),
            'advisoryBodies' => $requestForAdvice->getAdvisoryBodies(),
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
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $requestForAdvice), ['json' => $data]);
        self::assertResponseIsSuccessful();

        self::assertDatabaseCount(RequestForAdviceAttachment::class, 1);
        self::assertDatabaseHas(RequestForAdviceAttachment::class, [
            'id' => $attachment->getId(),
            'dossier' => ['id' => $requestForAdvice->getId()],
        ]);
    }

    public function testUpdateRequestForAdviceWithChangedAttachmentsMetadataIsUpdated(): void
    {
        $changedFileName = 'new-file.pdf';

        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $requestForAdvice = RequestForAdviceFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
        ]);
        $mainDocument = RequestForAdviceMainDocumentFactory::createOne([
            'dossier' => $requestForAdvice,
            'fileInfo' => FileInfoFactory::createOne(['uploaded' => false]),
        ]);
        $attachment = RequestForAdviceAttachmentFactory::createOne([
            'dossier' => $requestForAdvice,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        $data = [
            'title' => $requestForAdvice->getTitle(),
            'dossierNumber' => $requestForAdvice->getDossierNr(),
            'dossierDate' => $requestForAdvice->getDateFrom()?->format('Y-m-d'),
            'publicationDate' => $requestForAdvice->getPublicationDate()?->format('Y-m-d'),
            'summary' => $requestForAdvice->getSummary(),
            'departmentId' => $department->getId(),
            'subjectId' => $requestForAdvice->getSubject()?->getId(),
            'link' => $requestForAdvice->getLink(),
            'advisoryBodies' => $requestForAdvice->getAdvisoryBodies(),
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
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $requestForAdvice), ['json' => $data]);
        self::assertResponseIsSuccessful();

        self::assertDatabaseCount(RequestForAdviceAttachment::class, 1);
        self::assertDatabaseHas(RequestForAdviceAttachment::class, [
            'id' => $attachment->getId(),
            'dossier' => ['id' => $requestForAdvice->getId()],
            'fileInfo.name' => $changedFileName,
        ]);
    }

    public function testUpdateRequestForAdviceWithOneNewAttachmentAndOneExistingIsPartiallyUpdated(): void
    {
        $changedFileName = 'new-file.pdf';
        $newAttachmentExternalId = $this->getFaker()->externalId();

        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $requestForAdvice = RequestForAdviceFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
        ]);
        $mainDocument = RequestForAdviceMainDocumentFactory::createOne(['dossier' => $requestForAdvice]);
        $attachment1 = RequestForAdviceAttachmentFactory::createOne([
            'dossier' => $requestForAdvice,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        $data = [
            'title' => $requestForAdvice->getTitle(),
            'dossierNumber' => $requestForAdvice->getDossierNr(),
            'dossierDate' => $requestForAdvice->getDateFrom()?->format('Y-m-d'),
            'publicationDate' => $requestForAdvice->getPublicationDate()?->format('Y-m-d'),
            'summary' => $requestForAdvice->getSummary(),
            'departmentId' => $department->getId(),
            'subjectId' => $requestForAdvice->getSubject()?->getId(),
            'link' => $requestForAdvice->getLink(),
            'advisoryBodies' => $requestForAdvice->getAdvisoryBodies(),
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

        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $requestForAdvice), ['json' => $data]);
        self::assertResponseIsSuccessful();

        self::assertDatabaseCount(RequestForAdviceAttachment::class, 2);

        self::assertDatabaseHas(RequestForAdviceAttachment::class, [
            'id' => $attachment1->getId(),
            'dossier' => ['id' => $requestForAdvice->getId()],
            'fileInfo.name' => $attachment1->getFileInfo()->getName(),
        ]);

        self::assertDatabaseHas(RequestForAdviceAttachment::class, [
            'externalId' => $newAttachmentExternalId,
            'dossier' => ['id' => $requestForAdvice->getId()],
            'fileInfo.name' => $changedFileName,
        ]);
    }

    public function testUpdateRequestForAdviceWithLessAttachmentsAndOneExistingIsPartiallyDeleted(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $requestForAdvice = RequestForAdviceFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
        ]);
        $mainDocument = RequestForAdviceMainDocumentFactory::createOne(['dossier' => $requestForAdvice]);
        $attachment1 = RequestForAdviceAttachmentFactory::createOne([
            'dossier' => $requestForAdvice,
            'externalId' => $this->getFaker()->externalId(),
        ]);
        $attachment2 = RequestForAdviceAttachmentFactory::createOne([
            'dossier' => $requestForAdvice,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        $data = [
            'title' => $requestForAdvice->getTitle(),
            'dossierNumber' => $requestForAdvice->getDossierNr(),
            'dossierDate' => $requestForAdvice->getDateFrom()?->format('Y-m-d'),
            'publicationDate' => $requestForAdvice->getPublicationDate()?->format('Y-m-d'),
            'summary' => $requestForAdvice->getSummary(),
            'departmentId' => $department->getId(),
            'subjectId' => $requestForAdvice->getSubject()?->getId(),
            'link' => $requestForAdvice->getLink(),
            'advisoryBodies' => $requestForAdvice->getAdvisoryBodies(),
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

        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $requestForAdvice), ['json' => $data]);
        self::assertResponseIsSuccessful();

        self::assertDatabaseCount(RequestForAdviceAttachment::class, 1);

        self::assertDatabaseHas(RequestForAdviceAttachment::class, [
            'id' => $attachment1->getId(),
            'dossier' => ['id' => $requestForAdvice->getId()],
            'fileInfo.name' => $attachment1->getFileInfo()->getName(),
        ]);

        self::assertDatabaseMissing(RequestForAdviceAttachment::class, [
            'id' => $attachment2->getId(),
        ]);
    }
}
