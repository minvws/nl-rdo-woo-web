<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Publication\Dossier\ComplaintJudgement;

use Carbon\CarbonImmutable;
use DateTime;
use PHPUnit\Framework\Attributes\DataProvider;
use PublicationApi\Api\Publication\Dossier\ComplaintJudgement\ComplaintJudgementDto;
use PublicationApi\Tests\Integration\Api\Publication\Dossier\ApiPublicationV1DossierTestCase;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgement;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementMainDocumentFactory;
use Shared\Tests\Factory\Publication\Subject\SubjectFactory;
use Shared\Validator\EntityExists;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\Type;
use Webmozart\Assert\Assert;

use function array_merge;
use function is_string;
use function sprintf;

final class ComplaintJudgementPublicationV1Test extends ApiPublicationV1DossierTestCase
{
    public function getDossierApiUriSegment(): string
    {
        return 'complaint-judgement';
    }

    public function testGetComplaintJudgementCollection(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $complaintJudgement = ComplaintJudgementFactory::createOne([
            'date_from' => $this->getFaker()->dateTime(),
            'externalId' => $this->getFaker()->word(),
            'organisation' => $organisation,
            'departments' => [$department],
        ]);
        ComplaintJudgementMainDocumentFactory::createOne(['dossier' => $complaintJudgement]);

        $result = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation));
        self::assertResponseIsSuccessful();
        self::assertCount(1, $result->toArray());
        self::assertJsonContains([['externalId' => $complaintJudgement->getExternalId()]]);
    }

    public function testGetComplaintJudgement(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $complaintJudgement = ComplaintJudgementFactory::createOne([
            'date_from' => $this->getFaker()->dateTime(),
            'externalId' => $this->getFaker()->word(),
            'organisation' => $organisation,
            'departments' => [$department],
        ]);
        $complaintJudgementMainDocument = ComplaintJudgementMainDocumentFactory::createOne(['dossier' => $complaintJudgement]);

        $response = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildComplaintJudgementUrl($organisation, $complaintJudgement));

        self::assertResponseIsSuccessful();

        $expectedResponse = [
            'id' => (string) $complaintJudgement->getId(),
            'externalId' => $complaintJudgement->getExternalId(),
            'organisation' => [
                'id' => (string) $complaintJudgement->getOrganisation()->getId(),
                'name' => $complaintJudgement->getOrganisation()->getName(),
            ],
            'prefix' => $complaintJudgement->getDocumentPrefix(),
            'dossierNumber' => $complaintJudgement->getDossierNr(),
            'internalReference' => '',
            'title' => $complaintJudgement->getTitle(),
            'summary' => $complaintJudgement->getSummary(),
            'subject' => $complaintJudgement->getSubject()?->getName(),
            'department' => [
                'id' => (string) $department->getId(),
                'name' => $department->getName(),
            ],
            'publicationDate' => $complaintJudgement->getPublicationDate()?->format(DateTime::RFC3339),
            'status' => $complaintJudgement->getStatus()->value,
            'mainDocument' => [
                'id' => (string) $complaintJudgementMainDocument->getId(),
                'type' => $complaintJudgementMainDocument->getType()->value,
                'language' => $complaintJudgementMainDocument->getLanguage()->value,
                'formalDate' => $complaintJudgementMainDocument->getFormalDate()->format(DateTime::RFC3339),
                'internalReference' => $complaintJudgementMainDocument->getInternalReference(),
                'grounds' => $complaintJudgementMainDocument->getGrounds(),
                'fileName' => $complaintJudgementMainDocument->getFileInfo()->getName(),
            ],
            'dossierDate' => $complaintJudgement->getDateFrom()?->format(DateTime::RFC3339),
        ];

        self::assertSame($expectedResponse, $response->toArray());
        self::assertMatchesResourceItemJsonSchema(ComplaintJudgementDto::class);
    }

    public function testGetFromIncorrectOrganisation(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $complaintJudgement = ComplaintJudgementFactory::createOne([
            'externalId' => $this->getFaker()->word(),
            'departments' => [$department],
        ]);

        self::createPublicationApiRequest(Request::METHOD_GET, $this->buildComplaintJudgementUrl($organisation, $complaintJudgement));
        self::assertResponseStatusCodeSame(404);
    }

    public function testGetWithUnknownExternalId(): void
    {
        $organisation = OrganisationFactory::createOne();

        self::createPublicationApiRequest(Request::METHOD_GET, $this->buildComplaintJudgementUrl($organisation, $this->getFaker()->word()));

        self::assertResponseStatusCodeSame(404);
    }

    public function testCreateComplaintJudgement(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();

        self::assertDatabaseCount(ComplaintJudgement::class, 0);

        $data = $this->createValidComplaintJudgementDataPayload($department, $subject);
        $externalId = $data['externalId'];
        Assert::string($externalId);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildComplaintJudgementUrl($organisation, $externalId), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(ComplaintJudgementDto::class);

        self::assertDatabaseCount(ComplaintJudgement::class, 1);
    }

    public function testCreateComplaintJudgementWithoutSubject(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();

        self::assertDatabaseCount(ComplaintJudgement::class, 0);

        $data = $this->createValidComplaintJudgementDataPayload($department, null);
        $externalId = $data['externalId'];
        Assert::string($externalId);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildComplaintJudgementUrl($organisation, $externalId), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(ComplaintJudgementDto::class);

        self::assertDatabaseCount(ComplaintJudgement::class, 1);
    }

    public function testCreateComplaintJudgementWithoutMainDocument(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();

        self::assertDatabaseCount(ComplaintJudgement::class, 0);

        $data = $this->createValidComplaintJudgementDataPayload($department, $subject);
        $externalId = $data['externalId'];
        Assert::string($externalId);
        unset($data['mainDocument']);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildComplaintJudgementUrl($organisation, $externalId), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [[
            'code' => Type::INVALID_TYPE_ERROR,
            'propertyPath' => 'mainDocument',
        ], ]]);
    }

    /**
     * @param array<string,array<array-key,mixed>> $dataOverrides
     * @param array<string,array<array-key,mixed>> $violations
     */
    #[DataProvider('createComplaintJudgementValidationDataProvider')]
    public function testCreateComplaintJudgementWithValidationError(array $dataOverrides, array $violations): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();

        self::assertDatabaseCount(ComplaintJudgement::class, 0);

        $data = array_merge($this->createValidComplaintJudgementDataPayload($department, $subject), $dataOverrides);
        $externalId = $data['externalId'];
        Assert::string($externalId);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildComplaintJudgementUrl($organisation, $externalId), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [$violations]]);
    }

    /**
     * @return array<string,array<array-key,mixed>>
     */
    public static function createComplaintJudgementValidationDataProvider(): array
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

    public function testUpdateComplaintJudgement(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $complaintJudgement = ComplaintJudgementFactory::createOne([
            'date_from' => $this->getFaker()->dateTime(),
            'externalId' => $this->getFaker()->word(),
            'departments' => [$department],
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
        ]);
        ComplaintJudgementMainDocumentFactory::createOne(['dossier' => $complaintJudgement]);

        self::assertDatabaseHas(ComplaintJudgement::class, [
            'title' => $complaintJudgement->getTitle(),
            'summary' => $complaintJudgement->getSummary(),
        ]);

        $data = $this->createValidComplaintJudgementDataPayload($department, null);
        self::createPublicationApiRequest(
            Request::METHOD_PUT,
            $this->buildComplaintJudgementUrl($organisation, $complaintJudgement),
            ['json' => $data],
        );
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(ComplaintJudgementDto::class);

        self::assertDatabaseHas(ComplaintJudgement::class, [
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
    #[DataProvider('updateComplaintJudgementValidationDataProvider')]
    public function testUpdateComplaintJudgementWithValidationErrors(array $dataOverrides, array $violations): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $complaintJudgement = ComplaintJudgementFactory::createOne([
            'date_from' => $this->getFaker()->dateTime(),
            'externalId' => $this->getFaker()->word(),
            'organisation' => $organisation,
            'departments' => [$department],
            'status' => DossierStatus::CONCEPT,
        ]);
        ComplaintJudgementMainDocumentFactory::createOne(['dossier' => $complaintJudgement]);

        $response = self::createPublicationApiRequest(
            Request::METHOD_GET,
            $this->buildComplaintJudgementUrl($organisation, $complaintJudgement),
        );
        self::assertArraySubset([
            'title' => $complaintJudgement->getTitle(),
            'summary' => $complaintJudgement->getSummary(),
        ], $response->toArray());

        $data = array_merge($this->createValidComplaintJudgementDataPayload($department, null), $dataOverrides);
        self::createPublicationApiRequest(
            Request::METHOD_PUT,
            $this->buildComplaintJudgementUrl($organisation, $complaintJudgement),
            ['json' => $data],
        );
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [$violations]]);

        self::assertDatabaseHas(ComplaintJudgement::class, [
            'title' => $complaintJudgement->getTitle(),
            'summary' => $complaintJudgement->getSummary(),
        ]);
    }

    /**
     * @return array<string,array<array-key,mixed>>
     */
    public static function updateComplaintJudgementValidationDataProvider(): array
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

    public function testUpdateComplaintJudgementWithNonConceptState(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $complaintJudgement = ComplaintJudgementFactory::createOne([
            'date_from' => $this->getFaker()->dateTime(),
            'externalId' => $this->getFaker()->word(),
            'departments' => [$department],
            'organisation' => $organisation,
            'status' => $this->getFaker()->randomElement(DossierStatus::nonConceptCases()),
        ]);
        ComplaintJudgementMainDocumentFactory::createOne(['dossier' => $complaintJudgement]);

        self::assertDatabaseHas(ComplaintJudgement::class, [
            'title' => $complaintJudgement->getTitle(),
            'summary' => $complaintJudgement->getSummary(),
        ]);

        $data = $this->createValidComplaintJudgementDataPayload($department, null);
        self::createPublicationApiRequest(
            Request::METHOD_PUT,
            $this->buildComplaintJudgementUrl($organisation, $complaintJudgement),
            ['json' => $data],
        );
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [['message' => 'dossier update not allowed, in non-concept state']]]);

        self::assertDatabaseHas(ComplaintJudgement::class, [
            'title' => $complaintJudgement->getTitle(),
            'summary' => $complaintJudgement->getSummary(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function createValidComplaintJudgementDataPayload(Department $department, ?Subject $subject): array
    {
        return [
            'title' => $this->getFaker()->sentence(),
            'externalId' => $this->getFaker()->word(),
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
        ];
    }

    protected function buildComplaintJudgementUrl(Uuid|Organisation $organisation, string|ComplaintJudgement|null $dossier = null): string
    {
        $organisationId = $organisation instanceof Uuid ? $organisation : $organisation->getId();

        if ($dossier === null) {
            return sprintf('/api/publication/v1/organisation/%s/dossiers/%s', $organisationId, $this->getDossierApiUriSegment());
        }

        $dossierId = is_string($dossier) ? $dossier : $dossier->getExternalId();

        return sprintf('/api/publication/v1/organisation/%s/dossiers/%s/E:%s', $organisationId, $this->getDossierApiUriSegment(), $dossierId);
    }
}
