<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Dossier\ComplaintJudgement;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PublicationApi\Api\Dossier\ComplaintJudgement\ComplaintJudgementResource;
use PublicationApi\Api\Dossier\ComplaintJudgement\Uploads\MainDocument\ComplaintJudgementUploadMainDocumentResource;
use PublicationApi\Domain\OpenApi\Links\ApiUrlGenerator;
use PublicationApi\Domain\Upload\UploadStatus;
use PublicationApi\Tests\Integration\Api\Dossier\ApiPublicationV1DossierTestCase;
use Shared\Controller\Public\Dossier\DossierFileController;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Citation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileType;
use Shared\Domain\Publication\Dossier\NoticeNotPublic\NoticeNotPublic;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgement;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementMainDocument;
use Shared\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use Shared\Domain\Publication\PublicUrlGenerator;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\DocumentPrefixFactory;
use Shared\Tests\Factory\Publication\Dossier\NoticeNotPublic\NoticeNotPublicFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\Advice\AdviceFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementMainDocumentFactory;
use Shared\Tests\Factory\Publication\Subject\SubjectFactory;
use Shared\Validator\PlainDate\PlainDateBeforeOrEqual;
use Shared\Validator\Violation\ConstraintViolationBuilder;
use Shared\ValueObject\PlainDate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints\Type;
use Webmozart\Assert\Assert;

use function array_merge;
use function is_string;
use function sprintf;
use function str_repeat;

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
        $data = $result->toArray();
        self::assertArrayHasKey('items', $data);
        self::assertArrayHasKey('hasNextPage', $data);
        /** @var array<array-key, mixed> $items */
        $items = $data['items'];
        self::assertCount(1, $items);
        self::assertJsonContains(['items' => [['externalId' => $complaintJudgement->getExternalId()?->toString()]]]);
    }

    public function testGetComplaintJudgement(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $subject = SubjectFactory::createOne();
        $complaintJudgement = ComplaintJudgementFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'departments' => [$department],
            'subject' => $subject,
        ]);
        $complaintJudgementMainDocument = ComplaintJudgementMainDocumentFactory::createOne(['dossier' => $complaintJudgement]);

        $response = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildComplaintJudgementUrl($organisation, $complaintJudgement));

        self::assertResponseIsSuccessful();

        $apiUrlGenerator = $this->fromContainer(ApiUrlGenerator::class);
        $dossierPathHelper = $this->fromContainer(DossierPathHelper::class);
        $publicUrlGenerator = $this->fromContainer(PublicUrlGenerator::class);
        $expectedResponse = [
            'id' => (string) $complaintJudgement->getId(),
            'externalId' => $complaintJudgement->getExternalId()?->toString(),
            'organisation' => [
                'id' => $organisation->getId()->toString(),
                'name' => $organisation->getName(),
            ],
            'dossierNumber' => $complaintJudgement->getDossierNumber(),
            'title' => (string) $complaintJudgement->getTitle(),
            'summary' => $complaintJudgement->getSummary(),
            'subject' => [
                'id' => $subject->getId()->toString(),
                'name' => $subject->getName(),
                'landingPage' => null,
            ],
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
                '_links' => [
                    'upload' => [
                        'href' => $apiUrlGenerator->buildUrlFromRoute(
                            ComplaintJudgementUploadMainDocumentResource::ROUTE_NAME_MAIN_DOCUMENT_UPLOAD,
                            [
                                'organisationId' => $complaintJudgement->getOrganisation()->getId(),
                                'dossierExternalId' => $complaintJudgement->getExternalId(),
                            ],
                        )->toString(),
                    ],
                    'public' => ['href' => $dossierPathHelper->getAbsoluteDetailsPath($complaintJudgement)],
                    'file' => [
                        'href' => $publicUrlGenerator->buildUrlFromRoute(
                            DossierFileController::ROUTE_NAME_DOSSIER_FILE_DOWNLOAD,
                            [
                                'documentPrefix' => $complaintJudgement->getDocumentPrefix(),
                                'dossierNumber' => $complaintJudgement->getDossierNumber(),
                                'type' => DossierFileType::MAIN_DOCUMENT->value,
                                'id' => $complaintJudgementMainDocument->getId(),
                            ],
                        )->toString(),
                    ],
                ],
            ],
            'noticeNotPublic' => null,
            'dossierDate' => $complaintJudgement->getDateFrom()?->format('Y-m-d'),
            '_links' => [
                'self' => ['href' => $this->buildApiUrl($organisation, $complaintJudgement)],
                'public' => ['href' => $dossierPathHelper->getAbsoluteDetailsPath($complaintJudgement)],
            ],
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

    public function testCreateComplaintJudgementWithoutMainDocumentNorNotice(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        self::assertDatabaseCount(ComplaintJudgement::class, 0);

        $data = $this->createValidComplaintJudgementDataPayload($department, $subject);
        $externalId = $data['externalId'];
        Assert::string($externalId);
        unset($data['mainDocument']);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildComplaintJudgementUrl($organisation, $externalId), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [[
            'message' => 'A dossier must contain at least a main document or a notice not public',
        ], ]]);
    }

    public function testCreateComplaintJudgeenetWithTooLongExternalId(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();

        $data = $this->createValidComplaintJudgementDataPayload($department, $subject);

        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, str_repeat('x', 129)), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testCreateComplaintJudgementWithExternalIdAlreadyUsedByAdviceReturnsConflict(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        $data = $this->createValidComplaintJudgementDataPayload($department, $subject);
        $externalId = $this->getFaker()->externalId();
        $data['externalId'] = $externalId->toString();
        AdviceFactory::createOne([
            'externalId' => $externalId,
            'organisation' => $organisation,
            'departments' => [$department],
            'subject' => $subject,
        ]);

        self::createPublicationApiRequest(
            Request::METHOD_PUT,
            $this->buildComplaintJudgementUrl($organisation, $externalId->toString()),
            ['json' => $data],
        );
        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
        self::assertJsonContains(['detail' => 'ExternalId already in use by type advice']);
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
            'title' => (string) $complaintJudgement->getTitle(),
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
            'dossierNumber' => $data['dossierNumber'],
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
            'title' => (string) $complaintJudgement->getTitle(),
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
            'title' => (string) $complaintJudgement->getTitle(),
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

    /**
     * @return array<string, mixed>
     */
    private function createValidComplaintJudgementDataPayload(Department $department, ?Subject $subject): array
    {
        return [
            'title' => $this->getFaker()->sentence(),
            'externalId' => $this->getFaker()->externalId()->toString(),
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

    public function testCreateComplaintJudgementWithBothMainDocumentAndNoticeNotPublic(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        self::assertDatabaseCount(ComplaintJudgement::class, 0);

        $data = $this->createValidComplaintJudgementDataPayload($department, $subject);
        $data['noticeNotPublic'] = [
            'formalDate' => $this->getFaker()->plainDate()->format('Y-m-d'),
            'grounds' => [$this->getFaker()->randomElement(Citation::ALL_GROUND_KEYS)],
        ];
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [[
            'message' => 'A dossier cannot have both a main document and a notice not public',
        ]]]);

        self::assertDatabaseCount(ComplaintJudgement::class, 0);
    }

    public function testCreateComplaintJudgementWithNoticeNotPublic(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        self::assertDatabaseCount(ComplaintJudgement::class, 0);

        $data = $this->createValidComplaintJudgementDataPayload($department, $subject);
        unset($data['mainDocument']);
        $data['noticeNotPublic'] = [
            'formalDate' => $this->getFaker()->plainDate()->format('Y-m-d'),
            'documentName' => $this->getFaker()->sentence(),
            'grounds' => [$this->getFaker()->randomElement(Citation::ALL_GROUND_KEYS)],
            'explanation' => $this->getFaker()->sentence(),
        ];
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(ComplaintJudgementResource::class);
        self::assertDatabaseCount(ComplaintJudgement::class, 1);
        self::assertDatabaseCount(NoticeNotPublic::class, 1);
    }

    public function testCreateComplaintJudgementWithNoticeNotPublicAndEmptyGrounds(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        self::assertDatabaseCount(ComplaintJudgement::class, 0);

        $data = $this->createValidComplaintJudgementDataPayload($department, $subject);
        unset($data['mainDocument']);
        $data['noticeNotPublic'] = [
            'formalDate' => $this->getFaker()->plainDate()->format('Y-m-d'),
            'grounds' => [],
        ];
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        self::assertDatabaseCount(ComplaintJudgement::class, 0);
    }

    public function testCreateComplaintJudgementWithNoticeNotPublicAndMissingFormalDate(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        self::assertDatabaseCount(ComplaintJudgement::class, 0);

        $data = $this->createValidComplaintJudgementDataPayload($department, $subject);
        unset($data['mainDocument']);
        $data['noticeNotPublic'] = [
            'grounds' => [$this->getFaker()->randomElement(Citation::ALL_GROUND_KEYS)],
        ];
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        self::assertDatabaseCount(ComplaintJudgement::class, 0);
    }

    public function testUpdateComplaintJudgementTransitionsFromMainDocumentToNoticeNotPublic(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $complaintJudgement = ComplaintJudgementFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
        ]);
        $mainDocument = ComplaintJudgementMainDocumentFactory::createOne(['dossier' => $complaintJudgement]);
        $mainDocumentId = $mainDocument->getId();

        self::assertDatabaseHas(ComplaintJudgementMainDocument::class, [
            'id' => $mainDocumentId,
        ]);
        self::assertDatabaseCount(NoticeNotPublic::class, 0);

        $data = $this->createValidComplaintJudgementDataPayload($department, null);
        unset($data['mainDocument']);
        $data['noticeNotPublic'] = [
            'formalDate' => $this->getFaker()->plainDate()->format('Y-m-d'),
            'documentName' => $this->getFaker()->sentence(),
            'grounds' => [$this->getFaker()->randomElement(Citation::ALL_GROUND_KEYS)],
            'explanation' => $this->getFaker()->sentence(),
        ];

        $response = self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $complaintJudgement), ['json' => $data]);
        self::assertResponseIsSuccessful();

        $responseData = $response->toArray();
        self::assertNull($responseData['mainDocument']);
        self::assertNotNull($responseData['noticeNotPublic']);
        $responseNoticeNotPublic = $responseData['noticeNotPublic'];
        self::assertIsArray($responseNoticeNotPublic);
        self::assertEquals($data['noticeNotPublic']['formalDate'], $responseNoticeNotPublic['formalDate']);

        self::assertDatabaseCount(NoticeNotPublic::class, 1);
        self::assertDatabaseMissing(ComplaintJudgementMainDocument::class, [
            'id' => $mainDocumentId,
        ]);
    }

    public function testUpdateComplaintJudgementTransitionsFromNoticeNotPublicToMainDocument(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $complaintJudgement = ComplaintJudgementFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
        ]);
        NoticeNotPublicFactory::createOne(['dossier' => $complaintJudgement]);

        self::assertDatabaseCount(NoticeNotPublic::class, 1);
        self::assertDatabaseCount(ComplaintJudgementMainDocument::class, 0);

        $data = $this->createValidComplaintJudgementDataPayload($department, null);
        unset($data['noticeNotPublic']);

        $response = self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $complaintJudgement), ['json' => $data]);
        self::assertResponseIsSuccessful();

        $responseData = $response->toArray();
        self::assertNotNull($responseData['mainDocument']);
        self::assertNull($responseData['noticeNotPublic']);

        self::assertDatabaseCount(NoticeNotPublic::class, 0);
        self::assertDatabaseCount(ComplaintJudgementMainDocument::class, 1);
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

    public function testUpdatePublishedComplaintJudgementIgnoresPublicationDateChange(): void
    {
        $newTitle = 'Updated title for published complaint judgement';
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $complaintJudgement = ComplaintJudgementFactory::createOne([
            'dateFrom' => $this->getFaker()->plainDate(),
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => DossierStatus::PUBLISHED,
            'publicationDate' => PlainDate::create('2025-01-02'),
        ]);
        $mainDocument = ComplaintJudgementMainDocumentFactory::createOne(['dossier' => $complaintJudgement]);

        $data = [
            'title' => $newTitle,
            'externalId' => $complaintJudgement->getExternalId()?->toString(),
            'dossierNumber' => $complaintJudgement->getDossierNumber(),
            'dossierDate' => $complaintJudgement->getDateFrom()?->format('Y-m-d'),
            'publicationDate' => '2025-02-02',
            'summary' => $complaintJudgement->getSummary(),
            'departmentId' => $department->getId(),
            'subjectId' => $complaintJudgement->getSubject()?->getId(),
            'mainDocument' => [
                'fileName' => $mainDocument->getFileInfo()->getName(),
                'formalDate' => $mainDocument->getFormalDate()->format('Y-m-d'),
                'type' => $mainDocument->getType()->value,
                'language' => $mainDocument->getLanguage()->value,
            ],
        ];

        self::createPublicationApiRequest(
            Request::METHOD_PUT,
            $this->buildComplaintJudgementUrl($organisation, $complaintJudgement),
            ['json' => $data],
        );
        self::assertResponseIsSuccessful();
        self::assertJsonContains(['publicationDate' => '2025-01-02', 'title' => $newTitle]);

        self::assertDatabaseHas(ComplaintJudgement::class, ['title' => $newTitle]);
    }
}
