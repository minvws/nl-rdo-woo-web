<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Publication\Dossier\WooDecision;

use Carbon\CarbonImmutable;
use DateTime;
use PHPUnit\Framework\Attributes\DataProvider;
use PublicationApi\Api\Publication\Dossier\WooDecision\WooDecisionDto;
use PublicationApi\Tests\Integration\Api\Publication\Dossier\ApiPublicationV1DossierTestCase;
use Shared\Domain\Department\Department;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Decision\DecisionType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Domain\Publication\Dossier\Type\WooDecision\PublicationReason;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\SourceType;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\DocumentFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionAttachmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionMainDocumentFactory;
use Shared\Tests\Factory\Publication\Subject\SubjectFactory;
use Shared\Validator\EntityExists;
use Shared\ValueObject\ExternalId;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\Type;

use function array_merge;

final class WooDecisionPublicationV1Test extends ApiPublicationV1DossierTestCase
{
    public function getDossierApiUriSegment(): string
    {
        return 'woo-decision';
    }

    public function testGetWooDecisionCollection(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();

        $wooDecision = WooDecisionFactory::createOne([
            'departments' => [$department],
            'organisation' => $organisation,
            'previewDate' => $this->getFaker()->dateTime(),
            'externalId' => $this->getFaker()->slug(1),
        ]);
        WooDecisionMainDocumentFactory::createOne(['dossier' => $wooDecision]);
        WooDecisionAttachmentFactory::createOne(['dossier' => $wooDecision]);
        DocumentFactory::createOne(['dossiers' => [$wooDecision]]);

        $result = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation));
        self::assertResponseIsSuccessful();
        self::assertCount(1, $result->toArray());

        self::assertJsonContains([['externalId' => $wooDecision->getExternalId()]]);
    }

    public function testGetWooDecisionCollectionDoesNotContainWooDecisionWithoutExternalId(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();

        $wooDecision1 = WooDecisionFactory::createOne([
            'departments' => [$department],
            'organisation' => $organisation,
            'previewDate' => $this->getFaker()->dateTime(),
            'externalId' => $this->getFaker()->slug(1),
        ]);
        WooDecisionMainDocumentFactory::createOne(['dossier' => $wooDecision1]);
        WooDecisionAttachmentFactory::createOne(['dossier' => $wooDecision1]);
        DocumentFactory::createOne(['dossiers' => [$wooDecision1]]);

        $wooDecision2 = WooDecisionFactory::createOne([
            'departments' => [$department],
            'organisation' => $organisation,
            'previewDate' => $this->getFaker()->dateTime(),
            'externalId' => null,
        ]);
        WooDecisionMainDocumentFactory::createOne(['dossier' => $wooDecision2]);

        $result = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation));
        self::assertResponseIsSuccessful();
        self::assertCount(1, $result->toArray());
        self::assertJsonContains([['externalId' => $wooDecision1->getExternalId()]]);
    }

    public function testGetWooDecision(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $wooDecision = WooDecisionFactory::createOne([
            'departments' => [$department],
            'externalId' => $this->getFaker()->slug(1),
            'organisation' => $organisation,
            'previewDate' => $this->getFaker()->dateTime(),
        ]);
        $wooDecisionMainDocument = WooDecisionMainDocumentFactory::createOne(['dossier' => $wooDecision]);
        $wooDecisionAttachment = WooDecisionAttachmentFactory::createOne(['dossier' => $wooDecision]);

        // watch it: documents are sorted by documentNr
        $wooDecisionDocument1 = DocumentFactory::createOne([
            'documentNr' => 'A',
            'dossiers' => [$wooDecision],
        ]);
        $wooDecisionDocument2 = DocumentFactory::createOne([
            'documentNr' => 'B',
            'dossiers' => [$wooDecision],
            'refersTo' => [$wooDecisionDocument1],
        ]);

        $response = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $wooDecision));

        self::assertResponseIsSuccessful();

        $expectedResponse = [
            'id' => (string) $wooDecision->getId(),
            'externalId' => $wooDecision->getExternalId(),
            'organisation' => [
                'id' => (string) $wooDecision->getOrganisation()->getId(),
                'name' => $wooDecision->getOrganisation()->getName(),
            ],
            'prefix' => $wooDecision->getDocumentPrefix(),
            'dossierNumber' => $wooDecision->getDossierNr(),
            'internalReference' => '',
            'title' => $wooDecision->getTitle(),
            'summary' => $wooDecision->getSummary(),
            'subject' => $wooDecision->getSubject()?->getName(),
            'department' => [
                'id' => (string) $department->getId(),
                'name' => $department->getName(),
            ],
            'publicationDate' => $wooDecision->getPublicationDate()?->format(DateTime::RFC3339),
            'status' => $wooDecision->getStatus()->value,
            'mainDocument' => [
                'id' => (string) $wooDecisionMainDocument->getId(),
                'type' => $wooDecisionMainDocument->getType()->value,
                'language' => $wooDecisionMainDocument->getLanguage()->value,
                'formalDate' => $wooDecisionMainDocument->getFormalDate()->format(DateTime::RFC3339),
                'internalReference' => $wooDecisionMainDocument->getInternalReference(),
                'grounds' => $wooDecisionMainDocument->getGrounds(),
                'fileName' => $wooDecisionMainDocument->getFileInfo()->getName(),
            ],
            'attachments' => [
                [
                    'id' => (string) $wooDecisionAttachment->getId(),
                    'type' => $wooDecisionAttachment->getType()->value,
                    'language' => $wooDecisionAttachment->getLanguage()->value,
                    'formalDate' => $wooDecisionAttachment->getFormalDate()->format(DateTime::RFC3339),
                    'internalReference' => $wooDecisionAttachment->getInternalReference(),
                    'grounds' => $wooDecisionAttachment->getGrounds(),
                    'fileName' => $wooDecisionAttachment->getFileInfo()->getName(),
                    'externalId' => $wooDecisionAttachment->getExternalId()?->__toString(),
                ],
            ],
            'dossierDateFrom' => $wooDecision->getDateFrom()?->format(DateTime::RFC3339),
            'dossierDateTo' => $wooDecision->getDateTo()?->format(DateTime::RFC3339),
            'decision' => $wooDecision->getDecision()?->value,
            'reason' => $wooDecision->getPublicationReason()?->value,
            'previewDate' => $wooDecision->getPreviewDate()?->format(DateTime::RFC3339),
            'documents' => [
                [
                    'caseNumbers' => [],
                    'date' => $wooDecisionDocument1->getDocumentDate()?->format(DateTime::RFC3339),
                    'documentId' => $wooDecisionDocument1->getDocumentId(),
                    'documentNr' => $wooDecisionDocument1->getDocumentNr(),
                    'externalId' => $wooDecisionDocument1->getExternalId()?->__toString(),
                    'familyId' => $wooDecisionDocument1->getFamilyId(),
                    'grounds' => $wooDecisionDocument1->getGrounds(),
                    'isSuspended' => $wooDecisionDocument1->isSuspended(),
                    'isUploaded' => $wooDecisionDocument1->isUploaded(),
                    'isWithdrawn' => $wooDecisionDocument1->isWithdrawn(),
                    'judgement' => $wooDecisionDocument1->getJudgement()?->value,
                    'links' => $wooDecisionDocument1->getLinks(),
                    'period' => $wooDecisionDocument1->getPeriod(),
                    'refersTo' => [],
                    'remark' => $wooDecisionDocument1->getRemark(),
                    'threadId' => $wooDecisionDocument1->getThreadId(),
                ],
                [
                    'caseNumbers' => [],
                    'date' => $wooDecisionDocument2->getDocumentDate()?->format(DateTime::RFC3339),
                    'documentId' => $wooDecisionDocument2->getDocumentId(),
                    'documentNr' => $wooDecisionDocument2->getDocumentNr(),
                    'externalId' => $wooDecisionDocument2->getExternalId()?->__toString(),
                    'familyId' => $wooDecisionDocument2->getFamilyId(),
                    'grounds' => $wooDecisionDocument2->getGrounds(),
                    'isSuspended' => $wooDecisionDocument2->isSuspended(),
                    'isUploaded' => $wooDecisionDocument2->isUploaded(),
                    'isWithdrawn' => $wooDecisionDocument2->isWithdrawn(),
                    'judgement' => $wooDecisionDocument2->getJudgement()?->value,
                    'links' => $wooDecisionDocument2->getLinks(),
                    'period' => $wooDecisionDocument2->getPeriod(),
                    'refersTo' => [
                        [
                            'documentId' => $wooDecisionDocument1->getDocumentId(),
                            'externalId' => $wooDecisionDocument1->getExternalId(),
                        ],
                    ],
                    'remark' => $wooDecisionDocument2->getRemark(),
                    'threadId' => $wooDecisionDocument2->getThreadId(),
                ],
            ],
        ];

        self::assertEquals($expectedResponse, $response->toArray());
        self::assertMatchesResourceItemJsonSchema(WooDecisionDto::class);
    }

    public function testGetFromIncorrectOrganisation(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $wooDecision = WooDecisionFactory::createOne([
            'departments' => [$department],
            'externalId' => $this->getFaker()->slug(1),
        ]);

        self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $wooDecision));
        self::assertResponseStatusCodeSame(404);
    }

    public function testGetWithUnknownExternalId(): void
    {
        $organisation = OrganisationFactory::createOne();

        self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $this->getFaker()->word()));

        self::assertResponseStatusCodeSame(404);
    }

    public function testCreateWooDecision(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();

        self::assertDatabaseCount(WooDecision::class, 0);

        $data = $this->createValidWooDecisionDataPayload($department, $subject);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(WooDecisionDto::class);

        self::assertDatabaseCount(WooDecision::class, 1);
    }

    public function testCreateWooDecisionWithRelatedDocuments(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();

        $putData = $this->createValidWooDecisionDataPayload($department, $subject, 0, 0);

        $documentExternalId1 = $this->getFaker()->uuid();
        $documentExternalId2 = $this->getFaker()->uuid();

        $documentData1 = $this->createDocumentDataPayload();
        $documentData1['externalId'] = $documentExternalId1;
        $documentData1['refersTo'] = [$documentExternalId2];
        $documentData2 = $this->createDocumentDataPayload();
        $documentData2['externalId'] = $documentExternalId2;

        $putData['documents'] = [
            $documentData1,
            $documentData2,
        ];

        self::createPublicationApiRequest(
            Request::METHOD_PUT,
            $this->buildUrl($organisation, $this->getFaker()->slug(1)),
            ['json' => $putData],
        );
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(WooDecisionDto::class);

        $document1 = self::getEntity(Document::class, ['externalId' => ExternalId::create($documentExternalId1)]);
        self::assertInstanceOf(Document::class, $document1);
        self::assertCount(1, $document1->getRefersTo());
        $relatedDocument = $document1->getRefersTo()->first();
        self::assertInstanceOf(Document::class, $relatedDocument);
        self::assertEquals($documentExternalId2, $relatedDocument->getExternalId());

        $document2 = self::getEntity(Document::class, ['externalId' => ExternalId::create($documentExternalId2)]);
        self::assertInstanceOf(Document::class, $document2);
        self::assertCount(0, $document2->getRefersTo());
    }

    public function testCreateWooDecisionWithoutSubject(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        self::assertDatabaseCount(WooDecision::class, 0);

        $data = $this->createValidWooDecisionDataPayload($department);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(WooDecisionDto::class);
        self::assertDatabaseCount(WooDecision::class, 1);
    }

    public function testCreateWooDecisionWithoutMainDocument(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        self::assertDatabaseCount(WooDecision::class, 0);

        $data = $this->createValidWooDecisionDataPayload($department, $subject);
        unset($data['mainDocument']);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [[
            'code' => Type::INVALID_TYPE_ERROR,
            'propertyPath' => 'mainDocument',
        ]]]);
        self::assertDatabaseCount(WooDecision::class, 0);
    }

    public function testCreateWooDecisionWithoutAttachments(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        self::assertDatabaseCount(WooDecision::class, 0);

        $data = $this->createValidWooDecisionDataPayload($department, $subject);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(WooDecisionDto::class);
        self::assertDatabaseCount(WooDecision::class, 1);
    }

    /**
     * @param array<string,array<array-key,mixed>> $dataOverrides
     * @param array<string,array<array-key,mixed>> $violations
     */
    #[DataProvider('createWooDecisionValidationDataProvider')]
    public function testCreateWooDecisionWithValidationError(array $dataOverrides, array $violations): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        self::assertDatabaseCount(WooDecision::class, 0);

        $data = array_merge($this->createValidWooDecisionDataPayload($department, $subject, 1, 1), $dataOverrides);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [$violations]]);
        self::assertDatabaseCount(WooDecision::class, 0);
    }

    /**
     * @return array<string,array<array-key,mixed>>
     */
    public static function createWooDecisionValidationDataProvider(): array
    {
        return [
            'dossierDateFrom in the future' => [
                [
                    'dossierDateFrom' => CarbonImmutable::now()->addDay()->format(DateTime::RFC3339),
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
                        'filename' => 'file.pdf',
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
                            'fileName' => 'file.pdf',
                            'formalDate' => CarbonImmutable::now()->addDay()->format(DateTime::RFC3339),
                            'type' => 'invalid',
                            'language' => AttachmentLanguage::ENGLISH,
                            'externalId' => 'externalId',
                        ],
                    ],
                ],
                [
                    'code' => Type::INVALID_TYPE_ERROR,
                    'propertyPath' => 'attachments[0].type',
                ],
            ],
            'missing attachment type' => [
                [
                    'attachments' => [
                        [
                            'fileName' => 'file.pdf',
                            'formalDate' => CarbonImmutable::now()->addDay()->format(DateTime::RFC3339),
                            'language' => AttachmentLanguage::ENGLISH,
                            'externalId' => 'externalId',
                        ],
                    ],
                ],
                [
                    'code' => Type::INVALID_TYPE_ERROR,
                    'propertyPath' => 'attachments[0].type',
                ],
            ],
            'missing attachment external_id' => [
                [
                    'attachments' => [
                        [
                            'fileName' => 'file.pdf',
                            'formalDate' => CarbonImmutable::now()->addDay()->format(DateTime::RFC3339),
                            'language' => AttachmentLanguage::ENGLISH,
                            'type' => AttachmentType::ACCOUNTABILITY_REPORT,
                        ],
                    ],
                ],
                [
                    'code' => Type::INVALID_TYPE_ERROR,
                    'propertyPath' => 'attachments[0].externalId',
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

    public function testUpdateWooDecision(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $wooDecision = WooDecisionFactory::createOne([
            'departments' => [$department],
            'externalId' => $this->getFaker()->slug(1),
            'organisation' => $organisation,
            'previewDate' => $this->getFaker()->dateTime(),
            'status' => DossierStatus::CONCEPT,
        ]);
        WooDecisionMainDocumentFactory::createOne(['dossier' => $wooDecision]);
        WooDecisionAttachmentFactory::createOne(['dossier' => $wooDecision]);
        DocumentFactory::createOne(['dossiers' => [$wooDecision]]);

        self::assertDatabaseHas(WooDecision::class, [
            'title' => $wooDecision->getTitle(),
            'summary' => $wooDecision->getSummary(),
        ]);

        $data = $this->createValidWooDecisionDataPayload($department);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $wooDecision), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(WooDecisionDto::class);

        self::assertDatabaseHas(WooDecision::class, [
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
    #[DataProvider('updateWooDecisionValidationDataProvider')]
    public function testUpdateWooDecisionWithValidationErrors(array $dataOverrides, array $violations): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $wooDecision = WooDecisionFactory::createOne([
            'departments' => [$department],
            'externalId' => $this->getFaker()->slug(1),
            'organisation' => $organisation,
            'previewDate' => $this->getFaker()->dateTime(),
            'status' => DossierStatus::CONCEPT,
        ]);
        WooDecisionMainDocumentFactory::createOne(['dossier' => $wooDecision]);
        WooDecisionAttachmentFactory::createOne(['dossier' => $wooDecision]);

        self::assertDatabaseHas(WooDecision::class, [
            'title' => $wooDecision->getTitle(),
            'summary' => $wooDecision->getSummary(),
        ]);

        $data = array_merge($this->createValidWooDecisionDataPayload($department), $dataOverrides);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $wooDecision), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [$violations]]);

        self::assertDatabaseHas(WooDecision::class, [
            'title' => $wooDecision->getTitle(),
            'summary' => $wooDecision->getSummary(),
        ]);
    }

    /**
     * @return array<string,array<array-key,mixed>>
     */
    public static function updateWooDecisionValidationDataProvider(): array
    {
        return [
            'dossierDate in the future' => [
                [
                    'dossierDateFrom' => CarbonImmutable::now()->addDay()->format(DateTime::RFC3339),
                ],
                [
                    'code' => LessThanOrEqual::TOO_HIGH_ERROR,
                    'propertyPath' => 'dateFrom',
                ],
            ],
        ];
    }

    public function testUpdateWooDecisionWithNonConceptState(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $wooDecision = WooDecisionFactory::createOne([
            'departments' => [$department],
            'externalId' => $this->getFaker()->slug(1),
            'organisation' => $organisation,
            'previewDate' => $this->getFaker()->dateTime(),
            'status' => $this->getFaker()->randomElement(DossierStatus::nonConceptCases()),
        ]);
        WooDecisionMainDocumentFactory::createOne(['dossier' => $wooDecision]);
        WooDecisionAttachmentFactory::createOne(['dossier' => $wooDecision]);

        self::assertDatabaseHas(WooDecision::class, [
            'title' => $wooDecision->getTitle(),
            'summary' => $wooDecision->getSummary(),
        ]);

        $data = $this->createValidWooDecisionDataPayload($department);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $wooDecision), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        self::assertDatabaseHas(WooDecision::class, [
            'title' => $wooDecision->getTitle(),
            'summary' => $wooDecision->getSummary(),
        ]);
    }

    public function testUpdateWooDecisionWithExistingDocumentsExternalId(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $wooDecision = WooDecisionFactory::createOne([
            'departments' => [$department],
            'externalId' => $this->getFaker()->slug(1),
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
        ]);
        WooDecisionMainDocumentFactory::createOne(['dossier' => $wooDecision]);
        $wooDecisionDocument = DocumentFactory::createOne([
            'documentNr' => 'A',
            'dossiers' => [$wooDecision],
            'externalId' => ExternalId::create($this->getFaker()->uuid()),
        ]);

        $newDocumentId = $this->getFaker()->uuid();

        $putData = $this->createValidWooDecisionDataPayload($department, null, 0, 0);

        $documentData = $this->createDocumentDataPayload();
        $documentData['documentId'] = $newDocumentId;
        $documentData['externalId'] = (string) $wooDecisionDocument->getExternalId();
        $putData['documents'] = [$documentData];

        $res = self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $wooDecision), ['json' => $putData]);
        self::assertResponseIsSuccessful();

        self::assertDatabaseHas(Document::class, [
            'documentId' => $newDocumentId,
            'externalId' => $wooDecisionDocument->getExternalId(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function createValidWooDecisionDataPayload(
        Department $department,
        ?Subject $subject = null,
        ?int $attachmentCount = null,
        ?int $documentCount = null,
    ): array {
        return [
            'title' => $this->getFaker()->sentence(),
            'dossierNumber' => $this->getFaker()->slug(2),
            'internalReference' => $this->getFaker()->optional(default: '')->uuid(),
            'prefix' => $this->getFaker()->slug(2),
            'dossierDateFrom' => $this->getFaker()->dateTimeBetween('-3 weeks', '-2 week')->format(DateTime::RFC3339),
            'dossierDateTo' => $this->getFaker()->dateTimeBetween('-1 week', 'now')->format(DateTime::RFC3339),
            'decision' => $this->getFaker()->randomElement(DecisionType::cases()),
            'reason' => $this->getFaker()->randomElement(PublicationReason::cases()),
            'previewDate' => $this->getFaker()->dateTimeBetween('1 week', '2 weeks')->format(DateTime::RFC3339),
            'publicationDate' => $this->getFaker()->dateTimeBetween('2 weeks', '3 weeks')->format(DateTime::RFC3339),
            'summary' => $this->getFaker()->sentence(),
            'departmentId' => $department->getId(),
            'subjectId' => $subject?->getId(),
            'mainDocument' => [
                'filename' => $this->getFaker()->word(),
                'formalDate' => $this->getFaker()->date(DateTime::RFC3339),
                'type' => $this->getFaker()->randomElement(AttachmentType::cases()),
                'language' => $this->getFaker()->randomElement(AttachmentLanguage::cases()),
            ],
            'attachments' => $this->createAttachments($attachmentCount ?? $this->getFaker()->numberBetween(0, 3)),
            'documents' => $this->createDocuments($documentCount ?? $this->getFaker()->numberBetween(0, 3)),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function createDocuments(int $documentCount): array
    {
        $documents = [];
        for ($i = 0; $i < $documentCount; $i++) {
            $documents[] = $this->createDocumentDataPayload();
        }

        return $documents;
    }

    /**
     * @return array<string, mixed>
     */
    private function createDocumentDataPayload(): array
    {
        return [
            'caseNumbers' => [],
            'date' => $this->getFaker()->date(DateTime::RFC3339),
            'documentId' => $this->getFaker()->uuid(),
            'externalId' => $this->getFaker()->uuid(),
            'familyId' => $this->getFaker()->numberBetween(1, 1000),
            'fileName' => $this->getFaker()->word(),
            'grounds' => [$this->getFaker()->word()],
            'isSuspended' => $this->getFaker()->boolean(),
            'judgement' => $this->getFaker()->randomElement(Judgement::cases()),
            'links' => [],
            'matter' => $this->getFaker()->slug(1),
            'period' => null,
            'refersTo' => [],
            'remark' => $this->getFaker()->sentence(),
            'sourceType' => $this->getFaker()->randomElement(SourceType::cases()),
            'threadId' => $this->getFaker()->numberBetween(1, 1000),
        ];
    }
}
