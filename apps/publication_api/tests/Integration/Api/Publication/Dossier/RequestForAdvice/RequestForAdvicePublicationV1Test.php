<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Publication\Dossier\RequestForAdvice;

use Carbon\CarbonImmutable;
use DateTime;
use PHPUnit\Framework\Attributes\DataProvider;
use PublicationApi\Api\Publication\Dossier\RequestForAdvice\RequestForAdviceDto;
use PublicationApi\Tests\Integration\Api\Publication\Dossier\ApiPublicationV1DossierTestCase;
use Shared\Domain\Department\Department;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdvice;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceAttachmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceMainDocumentFactory;
use Shared\Tests\Factory\Publication\Subject\SubjectFactory;
use Shared\Validator\EntityExists;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\Type;

use function array_merge;

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
            'date_from' => $this->getFaker()->dateTime(),
            'externalId' => $this->getFaker()->slug(1),
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
        self::assertJsonContains([['externalId' => $requestForAdvice->getExternalId()]]);
    }

    public function testGetRequestForAdvice(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $requestForAdvice = RequestForAdviceFactory::createOne([
            'date_from' => $this->getFaker()->dateTime(),
            'externalId' => $this->getFaker()->slug(1),
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
            'externalId' => $requestForAdvice->getExternalId(),
            'organisation' => [
                'id' => (string) $requestForAdvice->getOrganisation()->getId(),
                'name' => $requestForAdvice->getOrganisation()->getName(),
            ],
            'prefix' => $requestForAdvice->getDocumentPrefix(),
            'dossierNumber' => $requestForAdvice->getDossierNr(),
            'internalReference' => '',
            'title' => $requestForAdvice->getTitle(),
            'summary' => $requestForAdvice->getSummary(),
            'subject' => $requestForAdvice->getSubject()?->getName(),
            'department' => [
                'id' => (string) $department->getId(),
                'name' => $department->getName(),
            ],
            'publicationDate' => $requestForAdvice->getPublicationDate()?->format(DateTime::RFC3339),
            'status' => $requestForAdvice->getStatus()->value,
            'mainDocument' => [
                'id' => (string) $requestForAdviceMainDocument->getId(),
                'type' => $requestForAdviceMainDocument->getType()->value,
                'language' => $requestForAdviceMainDocument->getLanguage()->value,
                'formalDate' => $requestForAdviceMainDocument->getFormalDate()->format(DateTime::RFC3339),
                'internalReference' => $requestForAdviceMainDocument->getInternalReference(),
                'grounds' => $requestForAdviceMainDocument->getGrounds(),
                'fileName' => $requestForAdviceMainDocument->getFileInfo()->getName(),
            ],
            'attachments' => [
                [
                    'id' => (string) $requestForAdviceAttachment->getId(),
                    'type' => $requestForAdviceAttachment->getType()->value,
                    'language' => $requestForAdviceAttachment->getLanguage()->value,
                    'formalDate' => $requestForAdviceAttachment->getFormalDate()->format(DateTime::RFC3339),
                    'internalReference' => $requestForAdviceAttachment->getInternalReference(),
                    'grounds' => $requestForAdviceAttachment->getGrounds(),
                    'fileName' => $requestForAdviceAttachment->getFileInfo()->getName(),
                    'externalId' => $requestForAdviceAttachment->getExternalId()?->__toString(),
                ],
            ],
            'dossierDate' => $requestForAdvice->getDateFrom()?->format(DateTime::RFC3339),
            'link' => $requestForAdvice->getLink(),
            'advisoryBodies' => $requestForAdvice->getAdvisoryBodies(),
        ];

        self::assertSame($expectedResponse, $response->toArray());
        self::assertMatchesResourceItemJsonSchema(RequestForAdviceDto::class);
    }

    public function testGetFromIncorrectOrganisation(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $requestForAdvice = RequestForAdviceFactory::createOne([
            'departments' => [$department],
            'externalId' => $this->getFaker()->slug(1),
        ]);

        self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $requestForAdvice));
        self::assertResponseStatusCodeSame(404);
    }

    public function testGetWithUnknownExternalId(): void
    {
        $organisation = OrganisationFactory::createOne();

        self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $this->getFaker()->uuid()));

        self::assertResponseStatusCodeSame(404);
    }

    public function testCreateRequestForAdvice(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();

        self::assertDatabaseCount(RequestForAdvice::class, 0);

        $data = $this->createValidRequestForAdviceDataPayload($department, $subject, $this->getFaker()->numberBetween(1, 3));
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(RequestForAdviceDto::class);

        self::assertDatabaseCount(RequestForAdvice::class, 1);
    }

    public function testCreateRequestForAdviceWithoutSubject(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();

        self::assertDatabaseCount(RequestForAdvice::class, 0);

        $data = $this->createValidRequestForAdviceDataPayload($department, null, 1);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(RequestForAdviceDto::class);

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

        self::assertDatabaseCount(RequestForAdvice::class, 0);

        $data = $this->createValidRequestForAdviceDataPayload($department, $subject, 0);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(RequestForAdviceDto::class);

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

    public function testUpdateRequestForAdvice(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $requestForAdvice = RequestForAdviceFactory::createOne([
            'date_from' => $this->getFaker()->dateTime(),
            'departments' => [$department],
            'externalId' => $this->getFaker()->slug(1),
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
        self::assertMatchesResourceItemJsonSchema(RequestForAdviceDto::class);

        self::assertDatabaseHas(RequestForAdvice::class, [
            'dossierNr' => $data['dossierNumber'],
            'internalReference' => $data['internalReference'],
            'documentPrefix' => $data['prefix'],
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
            'date_from' => $this->getFaker()->dateTime(),
            'externalId' => $this->getFaker()->slug(1),
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
                    'dossierDate' => CarbonImmutable::now()->addDay()->format(DateTime::RFC3339),
                ],
                [
                    'code' => LessThanOrEqual::TOO_HIGH_ERROR,
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
            'date_from' => $this->getFaker()->dateTime(),
            'departments' => [$department],
            'externalId' => $this->getFaker()->slug(1),
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
            'internalReference' => $this->getFaker()->optional(default: '')->uuid(),
            'prefix' => $this->getFaker()->slug(2),
            'dossierDate' => $this->getFaker()->dateTimeBetween('-3 weeks', '-2 week')->format(DateTime::RFC3339),
            'publicationDate' => $this->getFaker()->dateTimeBetween('-2 weeks', '-1 week')->format(DateTime::RFC3339),
            'summary' => $this->getFaker()->sentence(),
            'departmentId' => $department->getId(),
            'subjectId' => $subject?->getId(),
            'link' => $this->getFaker()->url(),
            'advisoryBodies' => $this->getFaker()->boolean() ? [] : [$this->getFaker()->words(3, true)],
            'mainDocument' => [
                'filename' => $this->getFaker()->word(),
                'formalDate' => $this->getFaker()->date(DateTime::RFC3339),
                'type' => AttachmentType::REQUEST_FOR_ADVICE,
                'language' => $this->getFaker()->randomElement(AttachmentLanguage::cases()),
            ],
            'attachments' => $this->createAttachments($attachmentCount),
        ];
    }
}
