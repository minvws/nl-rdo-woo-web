<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Dossier\ComplaintJudgement;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PublicationApi\Api\Dossier\ComplaintJudgement\ComplaintJudgementResource;
use PublicationApi\Domain\Upload\UploadStatus;
use PublicationApi\Tests\Integration\Api\Dossier\ApiPublicationV1DossierTestCase;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgement;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementMainDocument;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\DocumentPrefixFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementMainDocumentFactory;
use Shared\Tests\Factory\Publication\Subject\SubjectFactory;
use Shared\Validator\EntityExists;
use Shared\Validator\PlainDate\PlainDateBeforeOrEqual;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;
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
            'dateFrom' => $this->getFaker()->plainDate(),
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'departments' => [$department],
        ]);
        ComplaintJudgementMainDocumentFactory::createOne(['dossier' => $complaintJudgement]);

        $result = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation));
        self::assertResponseIsSuccessful();
        self::assertCount(1, $result->toArray());
        self::assertJsonContains([['externalId' => $complaintJudgement->getExternalId()?->__toString()]]);
    }

    public function testGetComplaintJudgement(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $complaintJudgement = ComplaintJudgementFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'departments' => [$department],
        ]);
        $complaintJudgementMainDocument = ComplaintJudgementMainDocumentFactory::createOne(['dossier' => $complaintJudgement]);

        $response = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildComplaintJudgementUrl($organisation, $complaintJudgement));

        self::assertResponseIsSuccessful();

        $expectedResponse = [
            'id' => (string) $complaintJudgement->getId(),
            'externalId' => $complaintJudgement->getExternalId()?->__toString(),
            'organisation' => [
                'id' => (string) $complaintJudgement->getOrganisation()->getId(),
                'name' => $complaintJudgement->getOrganisation()->getName(),
            ],
            'dossierNumber' => $complaintJudgement->getDossierNr(),
            'title' => $complaintJudgement->getTitle(),
            'summary' => $complaintJudgement->getSummary(),
            'subject' => $complaintJudgement->getSubject()?->getName(),
            'department' => [
                'id' => (string) $department->getId(),
                'name' => $department->getName(),
            ],
            'publicationDate' => $complaintJudgement->getPublicationDate()?->format('Y-m-d'),
            'status' => $complaintJudgement->getStatus()->value,
            'mainDocument' => [
                'id' => (string) $complaintJudgementMainDocument->getId(),
                'type' => $complaintJudgementMainDocument->getType()->value,
                'language' => $complaintJudgementMainDocument->getLanguage()->value,
                'formalDate' => $complaintJudgementMainDocument->getFormalDate()->format('Y-m-d'),
                'grounds' => $complaintJudgementMainDocument->getGrounds(),
                'fileName' => $complaintJudgementMainDocument->getFileInfo()->getName(),
                'uploadStatus' => UploadStatus::PROCESSED->value,
            ],
            'dossierDate' => $complaintJudgement->getDateFrom()?->format('Y-m-d'),
        ];

        self::assertSame($expectedResponse, $response->toArray());
        self::assertMatchesResourceItemJsonSchema(ComplaintJudgementResource::class);
    }

    public function testGetFromIncorrectOrganisation(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $complaintJudgement = ComplaintJudgementFactory::createOne([
            'externalId' => $this->getFaker()->externalId(),
            'departments' => [$department],
        ]);

        self::createPublicationApiRequest(Request::METHOD_GET, $this->buildComplaintJudgementUrl($organisation, $complaintJudgement));
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        self::assertJsonEquals([
            'type' => 'errors#resource-not-found',
            'title' => 'Resource Not Found',
            'status' => Response::HTTP_NOT_FOUND,
            'detail' => sprintf('ComplaintJudgement with id %s was not found', $complaintJudgement->getExternalId()),
        ]);
    }

    public function testGetWithUnknownExternalId(): void
    {
        $organisation = OrganisationFactory::createOne();
        $unknownExternalId = $this->getFaker()->word();

        self::createPublicationApiRequest(Request::METHOD_GET, $this->buildComplaintJudgementUrl($organisation, $unknownExternalId));

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        self::assertJsonEquals([
            'type' => 'errors#resource-not-found',
            'title' => 'Resource Not Found',
            'status' => Response::HTTP_NOT_FOUND,
            'detail' => sprintf('ComplaintJudgement with id %s was not found', $unknownExternalId),
        ]);
    }

    public function testCreateComplaintJudgement(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        self::assertDatabaseCount(ComplaintJudgement::class, 0);

        $data = $this->createValidComplaintJudgementDataPayload($department, $subject);
        $externalId = $data['externalId'];
        Assert::string($externalId);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildComplaintJudgementUrl($organisation, $externalId), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(ComplaintJudgementResource::class);

        self::assertDatabaseCount(ComplaintJudgement::class, 1);
    }

    public function testCreateComplaintJudgementWithoutSubject(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        self::assertDatabaseCount(ComplaintJudgement::class, 0);

        $data = $this->createValidComplaintJudgementDataPayload($department, null);
        $externalId = $data['externalId'];
        Assert::string($externalId);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildComplaintJudgementUrl($organisation, $externalId), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(ComplaintJudgementResource::class);

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
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

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
            'dateFrom' => $this->getFaker()->plainDate(),
            'externalId' => $this->getFaker()->externalId(),
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
        self::assertMatchesResourceItemJsonSchema(ComplaintJudgementResource::class);

        self::assertDatabaseHas(ComplaintJudgement::class, [
            'dossierNr' => $data['dossierNumber'],
            'documentPrefix' => $complaintJudgement->getDocumentPrefix(),
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
            'dateFrom' => $this->getFaker()->plainDate(),
            'externalId' => $this->getFaker()->externalId(),
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
                    'dossierDate' => CarbonImmutable::now()->addDay()->format('Y-m-d'),
                ],
                [
                    'code' => PlainDateBeforeOrEqual::PLAIN_DATE_BEFORE_OR_EQUAL_ERROR,
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
            'dateFrom' => $this->getFaker()->plainDate(),
            'externalId' => $this->getFaker()->externalId(),
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
        self::assertJsonContains(['violations' => [['message' => 'dossier update is not allowed in non-concept state']]]);

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
            'externalId' => $this->getFaker()->externalId()->__toString(),
            'dossierNumber' => $this->getFaker()->slug(2),
            'dossierDate' => $this->getFaker()->dateTimeBetween('-3 weeks', '-2 week')->format('Y-m-d'),
            'publicationDate' => $this->getFaker()->plainDateBetween('-2 weeks', '-1 week')->format('Y-m-d'),
            'summary' => $this->getFaker()->sentence(),
            'departmentId' => $department->getId(),
            'subjectId' => $subject?->getId(),
            'mainDocument' => [
                'fileName' => $this->getFaker()->fileNameForGroup(UploadGroupId::MAIN_DOCUMENTS)->toString(),
                'formalDate' => $this->getFaker()->date(),
                'type' => $this->getFaker()->randomElement(ComplaintJudgementMainDocument::getAllowedTypes()),
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

        return sprintf('/api/publication/v1/organisation/%s/dossiers/%s/external/%s', $organisationId, $this->getDossierApiUriSegment(), $dossierId);
    }
}
