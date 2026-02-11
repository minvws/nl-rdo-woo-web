<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Publication\Dossier\Disposition;

use Carbon\CarbonImmutable;
use DateTime;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PublicationApi\Api\Publication\Dossier\Disposition\DispositionDto;
use PublicationApi\Tests\Integration\Api\Publication\Dossier\ApiPublicationV1DossierTestCase;
use Shared\Domain\Department\Department;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\Disposition\Disposition;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Disposition\DispositionAttachmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Disposition\DispositionFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Disposition\DispositionMainDocumentFactory;
use Shared\Tests\Factory\Publication\Subject\SubjectFactory;
use Shared\Validator\EntityExists;
use Shared\ValueObject\ExternalId;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\Type;

use function array_merge;

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
            'date_from' => $this->getFaker()->dateTime(),
            'externalId' => $this->getFaker()->slug(1),
            'organisation' => $organisation,
            'departments' => [$department],
        ]);
        DispositionMainDocumentFactory::createOne(['dossier' => $disposition]);
        DispositionAttachmentFactory::createOne(['dossier' => $disposition]);

        $result = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation));
        self::assertResponseIsSuccessful();
        self::assertCount(1, $result->toArray());
        self::assertJsonContains([['externalId' => $disposition->getExternalId()]]);
    }

    public function testGetDisposition(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $disposition = DispositionFactory::createOne([
            'date_from' => $this->getFaker()->dateTime(),
            'externalId' => $this->getFaker()->slug(1),
            'organisation' => $organisation,
            'departments' => [$department],
        ]);
        $dispositionMainDocument = DispositionMainDocumentFactory::createOne(['dossier' => $disposition]);
        $dispositionAttachment = DispositionAttachmentFactory::createOne(['dossier' => $disposition]);

        $response = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $disposition));

        self::assertResponseIsSuccessful();

        $expectedResponse = [
            'id' => (string) $disposition->getId(),
            'externalId' => $disposition->getExternalId(),
            'organisation' => [
                'id' => (string) $disposition->getOrganisation()->getId(),
                'name' => $disposition->getOrganisation()->getName(),
            ],
            'prefix' => $disposition->getDocumentPrefix(),
            'dossierNumber' => $disposition->getDossierNr(),
            'internalReference' => '',
            'title' => $disposition->getTitle(),
            'summary' => $disposition->getSummary(),
            'subject' => $disposition->getSubject()?->getName(),
            'department' => [
                'id' => (string) $department->getId(),
                'name' => $department->getName(),
            ],
            'publicationDate' => $disposition->getPublicationDate()?->format(DateTime::RFC3339),
            'status' => $disposition->getStatus()->value,
            'mainDocument' => [
                'id' => (string) $dispositionMainDocument->getId(),
                'type' => $dispositionMainDocument->getType()->value,
                'language' => $dispositionMainDocument->getLanguage()->value,
                'formalDate' => $dispositionMainDocument->getFormalDate()->format(DateTime::RFC3339),
                'internalReference' => $dispositionMainDocument->getInternalReference(),
                'grounds' => $dispositionMainDocument->getGrounds(),
                'fileName' => $dispositionMainDocument->getFileInfo()->getName(),
            ],
            'attachments' => [
                [
                    'id' => (string) $dispositionAttachment->getId(),
                    'type' => $dispositionAttachment->getType()->value,
                    'language' => $dispositionAttachment->getLanguage()->value,
                    'formalDate' => $dispositionAttachment->getFormalDate()->format(DateTime::RFC3339),
                    'internalReference' => $dispositionAttachment->getInternalReference(),
                    'grounds' => $dispositionAttachment->getGrounds(),
                    'fileName' => $dispositionAttachment->getFileInfo()->getName(),
                    'externalId' => $dispositionAttachment->getExternalId()?->__toString(),
                ],
            ],
            'dossierDate' => $disposition->getDateFrom()?->format(DateTime::RFC3339),
        ];

        self::assertSame($expectedResponse, $response->toArray());
        self::assertMatchesResourceItemJsonSchema(DispositionDto::class);
    }

    public function testGetFromIncorrectOrganisation(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $disposition = DispositionFactory::createOne([
            'externalId' => $this->getFaker()->slug(1),
            'departments' => [$department],
        ]);

        self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $disposition));
        self::assertResponseStatusCodeSame(404);
    }

    public function testGetWithUnknownExternalId(): void
    {
        $organisation = OrganisationFactory::createOne();

        self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $this->getFaker()->word()));

        self::assertResponseStatusCodeSame(404);
    }

    public function testCreateDisposition(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();

        self::assertDatabaseCount(Disposition::class, 0);

        $data = $this->createValidDispositionDataPayload($department, $subject, $this->getFaker()->numberBetween(1, 3));
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(DispositionDto::class);

        self::assertDatabaseCount(Disposition::class, 1);
    }

    public function testCreateDispositionWithoutSubject(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();

        self::assertDatabaseCount(Disposition::class, 0);

        $data = $this->createValidDispositionDataPayload($department, null, 1);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(DispositionDto::class);

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

        self::assertDatabaseCount(Disposition::class, 0);

        $data = $this->createValidDispositionDataPayload($department, $subject, 0);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(DispositionDto::class);

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
                    'dossierDate' => CarbonImmutable::now()->addDay()->format(DateTime::RFC3339),
                ],
                [
                    'code' => LessThanOrEqual::TOO_HIGH_ERROR,
                    'propertyPath' => 'dateFrom',
                ],
            ],
            'null internal reference' => [
                [
                    'internalReference' => null,
                ],
                [
                    'code' => Type::INVALID_TYPE_ERROR,
                    'propertyPath' => 'internalReference',
                ],
            ],
            'invalid mainDocument language' => [
                [
                    'mainDocument' => [
                        'filename' => 'filename.pdf',
                        'formalDate' => CarbonImmutable::now()->addDay()->format(DateTime::RFC3339),
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
                            'formalDate' => CarbonImmutable::now()->addDay()->format(DateTime::RFC3339),
                            'type' => 'invalid',
                            'language' => AttachmentLanguage::ENGLISH,
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
            'date_from' => $this->getFaker()->dateTime(),
            'externalId' => $this->getFaker()->slug(1),
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
        self::assertMatchesResourceItemJsonSchema(DispositionDto::class);

        self::assertDatabaseHas(Disposition::class, [
            'dossierNr' => $data['dossierNumber'],
            'internalReference' => $data['internalReference'],
            'documentPrefix' => $data['prefix'],
            'summary' => $data['summary'],
            'title' => $data['title'],
        ]);
    }

    public function testUpdateDispositionWithOnlyNewAttachmentsDeletesOldAttachments(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $disposition = DispositionFactory::createOne([
            'date_from' => DateTimeImmutable::createFromFormat('Y-m-d', '2022-01-01'),
            'date_to' => DateTime::createFromFormat('Y-m-d', '2022-01-02'),
            'departments' => [$department],
            'externalId' => $this->getFaker()->uuid(),
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
        ]);
        DispositionMainDocumentFactory::createOne(['dossier' => $disposition]);
        DispositionAttachmentFactory::createOne(['dossier' => $disposition]);

        self::assertDatabaseCount(DispositionAttachment::class, 1);

        $data = [
            'title' => $disposition->getTitle(),
            'dossierNumber' => $disposition->getDossierNr(),
            'internalReference' => $disposition->getInternalReference(),
            'prefix' => $disposition->getDocumentPrefix(),
            'dossierDate' => $disposition->getDateFrom()?->format(DateTime::RFC3339),
            'publicationDate' => $disposition->getPublicationDate()?->format(DateTime::RFC3339),
            'summary' => $disposition->getSummary(),
            'departmentId' => $department->getId(),
            'subjectId' => $disposition->getSubject()?->getId(),
            'mainDocument' => [
                'filename' => $disposition->getMainDocument()?->getFileInfo()->getName(),
                'formalDate' => $disposition->getMainDocument()?->getFormalDate()->format(DateTime::RFC3339),
                'type' => $disposition->getMainDocument()?->getType()->value,
                'language' => $disposition->getMainDocument()?->getLanguage()->value,
            ],
            'attachments' => [],
        ];
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $disposition), ['json' => $data]);
        self::assertResponseIsSuccessful();

        self::assertDatabaseCount(DispositionAttachment::class, 0);
    }

    public function testUpdateDispositionWithSameAttachmentsReplacesThem(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $disposition = DispositionFactory::createOne([
            'date_from' => DateTimeImmutable::createFromFormat('Y-m-d', '2022-01-01'),
            'date_to' => DateTime::createFromFormat('Y-m-d', '2022-01-02'),
            'departments' => [$department],
            'externalId' => $this->getFaker()->word(),
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
        ]);
        DispositionMainDocumentFactory::createOne(['dossier' => $disposition]);
        $attachment = DispositionAttachmentFactory::createOne([
            'dossier' => $disposition,
            'externalId' => ExternalId::create($this->getFaker()->uuid()),
        ]);

        self::assertDatabaseCount(DispositionAttachment::class, 1);
        self::assertDatabaseHas(DispositionAttachment::class, [
            'id' => $attachment->getId(),
            'dossier' => ['id' => $disposition->getId()],
        ]);

        $data = [
            'title' => $disposition->getTitle(),
            'dossierNumber' => $disposition->getDossierNr(),
            'internalReference' => $disposition->getInternalReference(),
            'prefix' => $disposition->getDocumentPrefix(),
            'dossierDate' => $disposition->getDateFrom()?->format(DateTime::RFC3339),
            'publicationDate' => $disposition->getPublicationDate()?->format(DateTime::RFC3339),
            'summary' => $disposition->getSummary(),
            'departmentId' => $department->getId(),
            'subjectId' => $disposition->getSubject()?->getId(),
            'mainDocument' => [
                'filename' => $disposition->getMainDocument()?->getFileInfo()->getName(),
                'formalDate' => $disposition->getMainDocument()?->getFormalDate()->format(DateTime::RFC3339),
                'type' => $disposition->getMainDocument()?->getType()->value,
                'language' => $disposition->getMainDocument()?->getLanguage()->value,
            ],
            'attachments' => [
                [
                    'fileName' => $attachment->getFileInfo()->getName(),
                    'formalDate' => $attachment->getFormalDate()->format(DateTime::RFC3339),
                    'language' => $attachment->getLanguage(),
                    'type' => $attachment->getType(),
                    'externalId' => $attachment->getExternalId()?->__toString(),
                ],
            ],
        ];
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $disposition), ['json' => $data]);
        self::assertResponseIsSuccessful();

        self::assertDatabaseCount(DispositionAttachment::class, 1);
        self::assertDatabaseMissing(DispositionAttachment::class, [
            'id' => $attachment->getId(),
            'dossier' => ['id' => $disposition->getId()],
        ]);
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
            'date_from' => $this->getFaker()->dateTime(),
            'externalId' => $this->getFaker()->slug(1),
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
                    'dossierDate' => CarbonImmutable::now()->addDay()->format(DateTime::RFC3339),
                ],
                [
                    'code' => LessThanOrEqual::TOO_HIGH_ERROR,
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
            'date_from' => $this->getFaker()->dateTime(),
            'externalId' => $this->getFaker()->slug(1),
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
        self::assertJsonContains(['violations' => [['message' => 'dossier update not allowed, in non-concept state']]]);

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
            'internalReference' => $this->getFaker()->optional(default: '')->uuid(),
            'prefix' => $this->getFaker()->slug(2),
            'dossierDate' => $this->getFaker()->dateTimeBetween('-3 weeks', '-2 week')->format(DateTime::RFC3339),
            'publicationDate' => $this->getFaker()->dateTimeBetween('-2 weeks', '-1 week')->format(DateTime::RFC3339),
            'summary' => $this->getFaker()->sentence(),
            'departmentId' => $department->getId(),
            'subjectId' => $subject?->getId(),
            'mainDocument' => [
                'filename' => $this->getFaker()->word(),
                'formalDate' => $this->getFaker()->date(DateTime::RFC3339),
                'type' => $this->getFaker()->randomElement(AttachmentType::cases()),
                'language' => $this->getFaker()->randomElement(AttachmentLanguage::cases()),
            ],
            'attachments' => $this->createAttachments($attachmentCount),
        ];
    }
}
