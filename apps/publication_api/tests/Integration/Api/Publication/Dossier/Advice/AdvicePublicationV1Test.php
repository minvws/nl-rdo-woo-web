<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Publication\Dossier\Advice;

use Carbon\CarbonImmutable;
use DateTime;
use PHPUnit\Framework\Attributes\DataProvider;
use PublicationApi\Api\Publication\Dossier\Advice\AdviceDto;
use PublicationApi\Tests\Integration\Api\Publication\Dossier\ApiPublicationV1DossierTestCase;
use Shared\Domain\Department\Department;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\Advice\Advice;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Advice\AdviceAttachmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Advice\AdviceFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Advice\AdviceMainDocumentFactory;
use Shared\Tests\Factory\Publication\Subject\SubjectFactory;
use Shared\Validator\EntityExists;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\Type;

use function array_merge;

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
            'date_from' => $this->getFaker()->dateTime(),
            'externalId' => $this->getFaker()->slug(1),
            'organisation' => $organisation,
            'departments' => [$department],
        ]);
        AdviceMainDocumentFactory::createOne(['dossier' => $advice]);
        AdviceAttachmentFactory::createOne(['dossier' => $advice]);

        $result = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation));
        self::assertResponseIsSuccessful();
        self::assertCount(1, $result->toArray());
        self::assertJsonContains([['externalId' => $advice->getExternalId()]]);
    }

    public function testGetAdvice(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $advice = AdviceFactory::createOne([
            'date_from' => $this->getFaker()->dateTime(),
            'externalId' => $this->getFaker()->slug(1),
            'organisation' => $organisation,
            'departments' => [$department],
        ]);
        $adviceMainDocument = AdviceMainDocumentFactory::createOne(['dossier' => $advice]);
        $adviceAttachment = AdviceAttachmentFactory::createOne(['dossier' => $advice]);

        $response = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $advice));

        self::assertResponseIsSuccessful();

        $expectedResponse = [
            'id' => (string) $advice->getId(),
            'externalId' => $advice->getExternalId(),
            'organisation' => [
                'id' => (string) $advice->getOrganisation()->getId(),
                'name' => $advice->getOrganisation()->getName(),
            ],
            'prefix' => $advice->getDocumentPrefix(),
            'dossierNumber' => $advice->getDossierNr(),
            'internalReference' => '',
            'title' => $advice->getTitle(),
            'summary' => $advice->getSummary(),
            'subject' => $advice->getSubject()?->getName(),
            'department' => [
                'id' => (string) $department->getId(),
                'name' => $department->getName(),
            ],
            'publicationDate' => $advice->getPublicationDate()?->format(DateTime::RFC3339),
            'status' => $advice->getStatus()->value,
            'mainDocument' => [
                'id' => (string) $adviceMainDocument->getId(),
                'type' => $adviceMainDocument->getType()->value,
                'language' => $adviceMainDocument->getLanguage()->value,
                'formalDate' => $adviceMainDocument->getFormalDate()->format(DateTime::RFC3339),
                'internalReference' => $adviceMainDocument->getInternalReference(),
                'grounds' => $adviceMainDocument->getGrounds(),
                'fileName' => $adviceMainDocument->getFileInfo()->getName(),
            ],
            'attachments' => [
                [
                    'id' => (string) $adviceAttachment->getId(),
                    'type' => $adviceAttachment->getType()->value,
                    'language' => $adviceAttachment->getLanguage()->value,
                    'formalDate' => $adviceAttachment->getFormalDate()->format(DateTime::RFC3339),
                    'internalReference' => $adviceAttachment->getInternalReference(),
                    'grounds' => $adviceAttachment->getGrounds(),
                    'fileName' => $adviceAttachment->getFileInfo()->getName(),
                    'externalId' => $adviceAttachment->getExternalId()?->__toString(),
                ],
            ],
            'dossierDate' => $advice->getDateFrom()?->format(DateTime::RFC3339),
        ];

        self::assertSame($expectedResponse, $response->toArray());
        self::assertMatchesResourceItemJsonSchema(AdviceDto::class);
    }

    public function testGetFromIncorrectOrganisation(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $advice = AdviceFactory::createOne([
            'departments' => [$department],
            'externalId' => $this->getFaker()->slug(1),
        ]);

        self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $advice));
        self::assertResponseStatusCodeSame(404);
    }

    public function testGetWithUnknownExternalId(): void
    {
        $organisation = OrganisationFactory::createOne();

        self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $this->getFaker()->uuid()));

        self::assertResponseStatusCodeSame(404);
    }

    public function testCreateAdvice(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();

        self::assertDatabaseCount(Advice::class, 0);

        $data = $this->createValidAdviceDataPayload($department, $subject, $this->getFaker()->numberBetween(1, 3));
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(AdviceDto::class);

        self::assertDatabaseCount(Advice::class, 1);
    }

    public function testCreateAdviceWithoutSubject(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();

        self::assertDatabaseCount(Advice::class, 0);

        $data = $this->createValidAdviceDataPayload($department, null, 1);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(AdviceDto::class);

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

        self::assertDatabaseCount(Advice::class, 0);

        $data = $this->createValidAdviceDataPayload($department, $subject, 0);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(AdviceDto::class);

        self::assertDatabaseCount(Advice::class, 1);
    }

    public function testCreateAdviceWithMoreThanOneRequestForAdviceAttachment(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();

        self::assertDatabaseCount(Advice::class, 0);

        $data = $this->createValidAdviceDataPayload($department, $subject, 0);
        $attachments = $this->createAttachments(2);
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
                            'formalDate' => CarbonImmutable::now()->addDay()->format(DateTime::RFC3339),
                            'type' => 'invalid',
                            'language' => AttachmentLanguage::ENGLISH,
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

    public function testUpdateAdvice(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $advice = AdviceFactory::createOne([
            'date_from' => $this->getFaker()->dateTime(),
            'departments' => [$department],
            'externalId' => $this->getFaker()->slug(1),
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
        ]);
        AdviceMainDocumentFactory::createOne(['dossier' => $advice]);
        AdviceAttachmentFactory::createOne(['dossier' => $advice]);

        self::assertDatabaseHas(Advice::class, [
            'title' => $advice->getTitle(),
            'summary' => $advice->getSummary(),
        ]);

        $data = $this->createValidAdviceDataPayload($department, null, 0);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $advice), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(AdviceDto::class);

        self::assertDatabaseHas(Advice::class, [
            'dossierNr' => $data['dossierNumber'],
            'internalReference' => $data['internalReference'],
            'documentPrefix' => $data['prefix'],
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
            'date_from' => $this->getFaker()->dateTime(),
            'externalId' => $this->getFaker()->slug(1),
            'organisation' => $organisation,
            'departments' => [$department],
            'status' => DossierStatus::CONCEPT,
        ]);
        AdviceMainDocumentFactory::createOne(['dossier' => $advice]);
        AdviceAttachmentFactory::createOne(['dossier' => $advice]);

        self::assertDatabaseHas(Advice::class, [
            'title' => $advice->getTitle(),
            'summary' => $advice->getSummary(),
        ]);

        $data = array_merge($this->createValidAdviceDataPayload($department, null, 1), $dataOverrides);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $advice), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [$violations]]);

        self::assertDatabaseHas(Advice::class, [
            'title' => $advice->getTitle(),
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
                    'dossierDate' => CarbonImmutable::now()->addDay()->format(DateTime::RFC3339),
                ],
                [
                    'code' => LessThanOrEqual::TOO_HIGH_ERROR,
                    'propertyPath' => 'dateFrom',
                ],
            ],
        ];
    }

    public function testUpdateAdviceWithNonConceptState(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $advice = AdviceFactory::createOne([
            'date_from' => $this->getFaker()->dateTime(),
            'departments' => [$department],
            'externalId' => $this->getFaker()->slug(1),
            'organisation' => $organisation,
            'status' => $this->getFaker()->randomElement(DossierStatus::nonConceptCases()),
        ]);
        AdviceMainDocumentFactory::createOne(['dossier' => $advice]);
        AdviceAttachmentFactory::createOne(['dossier' => $advice]);

        self::assertDatabaseHas(Advice::class, [
            'title' => $advice->getTitle(),
            'summary' => $advice->getSummary(),
        ]);

        $data = $this->createValidAdviceDataPayload($department, null, 0);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $advice), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        self::assertDatabaseHas(Advice::class, [
            'title' => $advice->getTitle(),
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
