<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Publication\Dossier\Covenant;

use Carbon\CarbonImmutable;
use DateTime;
use PHPUnit\Framework\Attributes\DataProvider;
use PublicationApi\Api\Publication\Dossier\Covenant\CovenantDto;
use PublicationApi\Tests\Integration\Api\Publication\Dossier\ApiPublicationV1DossierTestCase;
use Shared\Domain\Department\Department;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantAttachmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantMainDocumentFactory;
use Shared\Tests\Factory\Publication\Subject\SubjectFactory;
use Shared\Validator\EntityExists;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\Type;

use function array_merge;

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
            'date_from' => $this->getFaker()->dateTime(),
            'externalId' => $this->getFaker()->slug(1),
            'organisation' => $organisation,
            'departments' => [$department],
            'previousVersionLink' => $this->getFaker()->url(),
            'parties' => [$this->getFaker()->words(3, true), $this->getFaker()->words(3, true)],
        ]);
        CovenantMainDocumentFactory::createOne(['dossier' => $covenant]);
        CovenantAttachmentFactory::createOne(['dossier' => $covenant]);

        $result = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation));
        self::assertResponseIsSuccessful();
        self::assertCount(1, $result->toArray());
        self::assertJsonContains([['externalId' => $covenant->getExternalId()]]);
    }

    public function testGetCovenant(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $covenant = CovenantFactory::createOne([
            'date_from' => $this->getFaker()->dateTime(),
            'externalId' => $this->getFaker()->slug(1),
            'organisation' => $organisation,
            'departments' => [$department],
            'previousVersionLink' => $this->getFaker()->url(),
            'parties' => [$this->getFaker()->words(3, true), $this->getFaker()->words(3, true)],
        ]);
        $covenantMainDocument = CovenantMainDocumentFactory::createOne(['dossier' => $covenant]);
        $covenantAttachment = CovenantAttachmentFactory::createOne(['dossier' => $covenant]);

        $response = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $covenant));

        self::assertResponseIsSuccessful();

        $expectedResponse = [
            'id' => (string) $covenant->getId(),
            'externalId' => $covenant->getExternalId(),
            'organisation' => [
                'id' => (string) $covenant->getOrganisation()->getId(),
                'name' => $covenant->getOrganisation()->getName(),
            ],
            'prefix' => $covenant->getDocumentPrefix(),
            'dossierNumber' => $covenant->getDossierNr(),
            'internalReference' => '',
            'title' => $covenant->getTitle(),
            'summary' => $covenant->getSummary(),
            'subject' => $covenant->getSubject()?->getName(),
            'department' => [
                'id' => (string) $department->getId(),
                'name' => $department->getName(),
            ],
            'publicationDate' => $covenant->getPublicationDate()?->format(DateTime::RFC3339),
            'status' => $covenant->getStatus()->value,
            'mainDocument' => [
                'id' => (string) $covenantMainDocument->getId(),
                'type' => $covenantMainDocument->getType()->value,
                'language' => $covenantMainDocument->getLanguage()->value,
                'formalDate' => $covenantMainDocument->getFormalDate()->format(DateTime::RFC3339),
                'internalReference' => $covenantMainDocument->getInternalReference(),
                'grounds' => $covenantMainDocument->getGrounds(),
                'fileName' => $covenantMainDocument->getFileInfo()->getName(),
            ],
            'attachments' => [
                [
                    'id' => (string) $covenantAttachment->getId(),
                    'type' => $covenantAttachment->getType()->value,
                    'language' => $covenantAttachment->getLanguage()->value,
                    'formalDate' => $covenantAttachment->getFormalDate()->format(DateTime::RFC3339),
                    'internalReference' => $covenantAttachment->getInternalReference(),
                    'grounds' => $covenantAttachment->getGrounds(),
                    'fileName' => $covenantAttachment->getFileInfo()->getName(),
                    'externalId' => $covenantAttachment->getExternalId(),
                ],
            ],
            'dateFrom' => $covenant->getDateFrom()?->format(DateTime::RFC3339),
            'dateTo' => $covenant->getDateTo()?->format(DateTime::RFC3339),
            'previousVersionLink' => $covenant->getPreviousVersionLink(),
            'parties' => $covenant->getParties(),
        ];

        self::assertSame($expectedResponse, $response->toArray());
        self::assertMatchesResourceItemJsonSchema(CovenantDto::class);
    }

    public function testGetFromIncorrectOrganisation(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $covenant = CovenantFactory::createOne([
            'departments' => [$department],
            'externalId' => $this->getFaker()->slug(1),
        ]);

        self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $covenant));
        self::assertResponseStatusCodeSame(404);
    }

    public function testGetWithUnknownExternalId(): void
    {
        $organisation = OrganisationFactory::createOne();

        self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $this->getFaker()->uuid()));

        self::assertResponseStatusCodeSame(404);
    }

    public function testCreateCovenant(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();

        self::assertDatabaseCount(Covenant::class, 0);

        $data = $this->createValidCovenantDataPayload($department, $subject, $this->getFaker()->numberBetween(1, 3));
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(CovenantDto::class);

        self::assertDatabaseCount(Covenant::class, 1);
    }

    public function testCreateCovenantWithoutSubject(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();

        self::assertDatabaseCount(Covenant::class, 0);

        $data = $this->createValidCovenantDataPayload($department, null, 1);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(CovenantDto::class);

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

        self::assertDatabaseCount(Covenant::class, 0);

        $data = $this->createValidCovenantDataPayload($department, $subject, 0);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(CovenantDto::class);

        self::assertDatabaseCount(Covenant::class, 1);
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
                    'dateFrom' => CarbonImmutable::now()->addDay()->format(DateTime::RFC3339),
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

    public function testUpdateCovenant(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $covenant = CovenantFactory::createOne([
            'date_from' => $this->getFaker()->dateTime(),
            'departments' => [$department],
            'externalId' => $this->getFaker()->slug(1),
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
        ]);
        CovenantMainDocumentFactory::createOne(['dossier' => $covenant]);
        CovenantAttachmentFactory::createOne(['dossier' => $covenant]);

        self::assertDatabaseHas(Covenant::class, [
            'title' => $covenant->getTitle(),
            'summary' => $covenant->getSummary(),
        ]);

        $data = $this->createValidCovenantDataPayload($department, null, 0);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $covenant), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(CovenantDto::class);

        self::assertDatabaseHas(Covenant::class, [
            'dossierNr' => $data['dossierNumber'],
            'internalReference' => $data['internalReference'],
            'documentPrefix' => $data['prefix'],
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
            'date_from' => $this->getFaker()->dateTime(),
            'externalId' => $this->getFaker()->slug(1),
            'organisation' => $organisation,
            'departments' => [$department],
            'status' => DossierStatus::CONCEPT,
        ]);
        CovenantMainDocumentFactory::createOne(['dossier' => $covenant]);
        CovenantAttachmentFactory::createOne(['dossier' => $covenant]);

        self::assertDatabaseHas(Covenant::class, [
            'title' => $covenant->getTitle(),
            'summary' => $covenant->getSummary(),
        ]);

        $data = array_merge($this->createValidCovenantDataPayload($department, null, 1), $dataOverrides);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $covenant), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [$violations]]);

        self::assertDatabaseHas(Covenant::class, [
            'title' => $covenant->getTitle(),
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
                    'dateFrom' => CarbonImmutable::now()->addDay()->format(DateTime::RFC3339),
                ],
                [
                    'code' => LessThanOrEqual::TOO_HIGH_ERROR,
                    'propertyPath' => 'dateFrom',
                ],
            ],
        ];
    }

    public function testUpdateCovenantWithNonConceptState(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $covenant = CovenantFactory::createOne([
            'date_from' => $this->getFaker()->dateTime(),
            'departments' => [$department],
            'externalId' => $this->getFaker()->slug(1),
            'organisation' => $organisation,
            'status' => $this->getFaker()->randomElement(DossierStatus::nonConceptCases()),
        ]);
        CovenantMainDocumentFactory::createOne(['dossier' => $covenant]);
        CovenantAttachmentFactory::createOne(['dossier' => $covenant]);

        self::assertDatabaseHas(Covenant::class, [
            'title' => $covenant->getTitle(),
            'summary' => $covenant->getSummary(),
        ]);

        $data = $this->createValidCovenantDataPayload($department, null, 0);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $covenant), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        self::assertDatabaseHas(Covenant::class, [
            'title' => $covenant->getTitle(),
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
            'internalReference' => $this->getFaker()->optional(default: '')->uuid(),
            'prefix' => $this->getFaker()->slug(2),
            'dateFrom' => $this->getFaker()->dateTimeBetween('-3 weeks', '-2 week')->format(DateTime::RFC3339),
            'dateTo' => $this->getFaker()->dateTimeBetween('-2 weeks', '-1 week')->format(DateTime::RFC3339),
            'publicationDate' => $this->getFaker()->dateTimeBetween('-2 weeks', '-1 week')->format(DateTime::RFC3339),
            'summary' => $this->getFaker()->sentence(),
            'departmentId' => $department->getId(),
            'subjectId' => $subject?->getId(),
            'previousVersionLink' => $this->getFaker()->url(),
            'parties' => [
                $this->getFaker()->words(3, true),
                $this->getFaker()->words(3, true),
            ],
            'mainDocument' => [
                'filename' => $this->getFaker()->word(),
                'formalDate' => $this->getFaker()->date(DateTime::RFC3339),
                'type' => AttachmentType::COVENANT,
                'language' => $this->getFaker()->randomElement(AttachmentLanguage::cases()),
            ],
            'attachments' => $this->createAttachments($attachmentCount),
        ];
    }
}
