<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Publication\Dossier\WooDecision;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PublicationApi\Api\Publication\Dossier\WooDecision\WooDecisionResponseDto;
use PublicationApi\Api\Publication\UploadStatus;
use PublicationApi\Tests\Integration\Api\Publication\Dossier\ApiPublicationV1DossierTestCase;
use Shared\Domain\Department\Department;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Citation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Attachment\WooDecisionAttachment;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Decision\DecisionType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Domain\Publication\Dossier\Type\WooDecision\MainDocument\WooDecisionMainDocument;
use Shared\Domain\Publication\Dossier\Type\WooDecision\PublicationReason;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\SourceType;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\DocumentFactory;
use Shared\Tests\Factory\FileInfoFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\DocumentPrefixFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionAttachmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionMainDocumentFactory;
use Shared\Tests\Factory\Publication\Subject\SubjectFactory;
use Shared\Validator\EntityExists;
use Shared\Validator\PlainDate\PlainDateAfterOrEqual;
use Shared\Validator\PlainDate\PlainDateBeforeOrEqual;
use Shared\ValueObject\ExternalId;
use Shared\ValueObject\PlainDate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\Unique;

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
            'externalId' => $this->getFaker()->externalId(),
        ]);
        WooDecisionMainDocumentFactory::createOne(['dossier' => $wooDecision]);
        WooDecisionAttachmentFactory::createOne(['dossier' => $wooDecision]);
        DocumentFactory::createOne(['dossiers' => [$wooDecision]]);

        $result = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation));
        self::assertResponseIsSuccessful();
        self::assertCount(1, $result->toArray());

        self::assertJsonContains([['externalId' => $wooDecision->getExternalId()?->__toString()]]);
    }

    public function testGetWooDecisionCollectionDoesNotContainWooDecisionWithoutExternalId(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();

        $wooDecision1 = WooDecisionFactory::createOne([
            'departments' => [$department],
            'organisation' => $organisation,
            'previewDate' => $this->getFaker()->dateTime(),
            'externalId' => $this->getFaker()->externalId(),
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
        self::assertJsonContains([['externalId' => $wooDecision1->getExternalId()?->__toString()]]);
    }

    public function testGetWooDecision(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $wooDecision = WooDecisionFactory::createOne(
            [
                'departments' => [$department],
                'externalId' => ExternalId::create($this->getFaker()->slug(1)),
                'organisation' => $organisation,
                'previewDate' => $this->getFaker()->dateTime(),
            ],
        );
        $wooDecisionMainDocument = WooDecisionMainDocumentFactory::createOne(['dossier' => $wooDecision]);
        $wooDecisionAttachment = WooDecisionAttachmentFactory::createOne(['dossier' => $wooDecision]);

        // watch it: documents are sorted by documentNr
        $wooDecisionDocument1 = DocumentFactory::createOne(
            [
                'documentNr' => 'A',
                'dossiers' => [$wooDecision],
                'fileInfo' => FileInfoFactory::createOne([
                    'uploaded' => true,
                ]),
            ],
        );
        $wooDecisionDocument2 = DocumentFactory::createOne(
            [
                'documentNr' => 'B',
                'dossiers' => [$wooDecision],
                'fileInfo' => FileInfoFactory::createOne([
                    'uploaded' => true,
                ]),
                'refersTo' => [$wooDecisionDocument1],
            ],
        );

        $response = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $wooDecision));

        self::assertResponseIsSuccessful();

        $expectedResponse = [
            'id' => (string) $wooDecision->getId(),
            'externalId' => $wooDecision->getExternalId(),
            'organisation' => [
                'id' => (string) $wooDecision->getOrganisation()->getId(),
                'name' => $wooDecision->getOrganisation()->getName(),
            ],
            'dossierNumber' => $wooDecision->getDossierNr(),
            'title' => $wooDecision->getTitle(),
            'summary' => $wooDecision->getSummary(),
            'subject' => $wooDecision->getSubject()?->getName(),
            'department' => [
                'id' => (string) $department->getId(),
                'name' => $department->getName(),
            ],
            'publicationDate' => $wooDecision->getPublicationDate()?->format('Y-m-d'),
            'status' => $wooDecision->getStatus()->value,
            'mainDocument' => [
                'id' => (string) $wooDecisionMainDocument->getId(),
                'type' => $wooDecisionMainDocument->getType()->value,
                'language' => $wooDecisionMainDocument->getLanguage()->value,
                'formalDate' => $wooDecisionMainDocument->getFormalDate()->format('Y-m-d'),
                'grounds' => $wooDecisionMainDocument->getGrounds(),
                'fileName' => $wooDecisionMainDocument->getFileInfo()->getName(),
                'uploadStatus' => UploadStatus::PROCESSED->value,
            ],
            'attachments' => [
                [
                    'id' => (string) $wooDecisionAttachment->getId(),
                    'type' => $wooDecisionAttachment->getType()->value,
                    'language' => $wooDecisionAttachment->getLanguage()->value,
                    'formalDate' => $wooDecisionAttachment->getFormalDate()->format('Y-m-d'),
                    'grounds' => $wooDecisionAttachment->getGrounds(),
                    'fileName' => $wooDecisionAttachment->getFileInfo()->getName(),
                    'externalId' => $wooDecisionAttachment->getExternalId()?->__toString(),
                    'uploadStatus' => UploadStatus::PROCESSED->value,
                ],
            ],
            'dateFrom' => $wooDecision->getDateFrom()?->format('Y-m-d'),
            'dateTo' => $wooDecision->getDateTo()?->format('Y-m-d'),
            'decision' => $wooDecision->getDecision()?->value,
            'reason' => $wooDecision->getPublicationReason()?->value,
            'previewDate' => $wooDecision->getPreviewDate()?->format('Y-m-d'),
            'documents' => [
                [
                    'caseNumbers' => [],
                    'date' => $wooDecisionDocument1->getDocumentDate()?->format('Y-m-d'),
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
                    'refersTo' => [],
                    'remark' => $wooDecisionDocument1->getRemark(),
                    'threadId' => $wooDecisionDocument1->getThreadId(),
                    'uploadStatus' => UploadStatus::PROCESSED->value,
                ],
                [
                    'caseNumbers' => [],
                    'date' => $wooDecisionDocument2->getDocumentDate()?->format('Y-m-d'),
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
                    'refersTo' => [
                        [
                            'documentId' => $wooDecisionDocument1->getDocumentId(),
                            'externalId' => $wooDecisionDocument1->getExternalId(),
                        ],
                    ],
                    'remark' => $wooDecisionDocument2->getRemark(),
                    'threadId' => $wooDecisionDocument2->getThreadId(),
                    'uploadStatus' => UploadStatus::PROCESSED->value,
                ],
            ],
        ];

        self::assertEquals($expectedResponse, $response->toArray());
        self::assertMatchesResourceItemJsonSchema(WooDecisionResponseDto::class);
    }

    public function testGetFromIncorrectOrganisation(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $wooDecision = WooDecisionFactory::createOne(
            [
                'departments' => [$department],
                'externalId' => ExternalId::create($this->getFaker()->slug(1)),
            ],
        );

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
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        self::assertDatabaseCount(WooDecision::class, 0);

        $data = $this->createValidWooDecisionDataPayload($department, $subject);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(WooDecisionResponseDto::class);

        self::assertDatabaseCount(WooDecision::class, 1);
    }

    public function testCreateWooDecisionWithPrefixShouldIgnorePostData(): void
    {
        $organisation = OrganisationFactory::createOne();
        $documentPrefix = DocumentPrefixFactory::createOne(['organisation' => $organisation]);
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();

        self::assertDatabaseCount(WooDecision::class, 0);

        $data = $this->createValidWooDecisionDataPayload($department, $subject);
        $data['prefix'] = 'ignored';

        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(WooDecisionResponseDto::class);

        self::assertDatabaseCount(WooDecision::class, 1);
        self::assertDatabaseHas(WooDecision::class, [
            'documentPrefix' => $documentPrefix->getPrefix(),
        ]);
    }

    public function testCreateWooDecisionWithRelatedDocuments(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

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
        self::assertMatchesResourceItemJsonSchema(WooDecisionResponseDto::class);

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
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);
        self::assertDatabaseCount(WooDecision::class, 0);

        $data = $this->createValidWooDecisionDataPayload($department);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(WooDecisionResponseDto::class);
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
        self::assertJsonContains(
            [
                'violations' => [
                    [
                        'code' => Type::INVALID_TYPE_ERROR,
                        'propertyPath' => 'mainDocument',
                    ],
                ],
            ],
        );
        self::assertDatabaseCount(WooDecision::class, 0);
    }

    public function testCreateWooDecisionWithoutAttachments(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);
        self::assertDatabaseCount(WooDecision::class, 0);

        $data = $this->createValidWooDecisionDataPayload($department, $subject);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(WooDecisionResponseDto::class);
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
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);
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
            'dateTo foo far in the future' => [
                [
                    'dateTo' => CarbonImmutable::now()->addYears(10)->format('Y-m-d'),
                ],
                [
                    'code' => PlainDateBeforeOrEqual::PLAIN_DATE_BEFORE_OR_EQUAL_ERROR,
                    'propertyPath' => 'dateTo',
                ],
            ],
            'invalid mainDocument language' => [
                [
                    'mainDocument' => [
                        'fileName' => 'file.pdf',
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
                            'fileName' => 'file.pdf',
                            'formalDate' => CarbonImmutable::now()->addDay()->format('Y-m-d'),
                            'type' => 'invalid',
                            'language' => AttachmentLanguage::ENG,
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
                            'formalDate' => CarbonImmutable::now()->addDay()->format('Y-m-d'),
                            'language' => AttachmentLanguage::ENG,
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
                            'formalDate' => CarbonImmutable::now()->addDay()->format('Y-m-d'),
                            'language' => AttachmentLanguage::ENG,
                            'type' => AttachmentType::ACCOUNTABILITY_REPORT,
                        ],
                    ],
                ],
                [
                    'code' => Type::INVALID_TYPE_ERROR,
                    'propertyPath' => 'attachments[0].externalId',
                ],
            ],
            'duplicate attachment external_ids' => [
                [
                    'attachments' => [
                        [
                            'fileName' => 'file1.pdf',
                            'formalDate' => CarbonImmutable::now()->addDay()->format('Y-m-d'),
                            'language' => AttachmentLanguage::ENG,
                            'type' => AttachmentType::ACCOUNTABILITY_REPORT,
                            'externalId' => 'externalId',
                        ],
                        [
                            'fileName' => 'file2.pdf',
                            'formalDate' => CarbonImmutable::now()->addDay()->format('Y-m-d'),
                            'language' => AttachmentLanguage::ENG,
                            'type' => AttachmentType::ACCOUNTABILITY_REPORT,
                            'externalId' => 'externalId',
                        ],
                    ],
                ],
                [
                    'code' => Unique::IS_NOT_UNIQUE,
                    'propertyPath' => 'attachments',
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
            'document grounds not an array' => [
                [
                    'documents' => [
                        [
                            'caseNumbers' => [],
                            'date' => '2025-09-17',
                            'documentId' => '7d54bd0f-96be-309c-a541-290efacef319',
                            'externalId' => 'd3147b92-f6a3-3c78-91bc-627f252fc07e',
                            'familyId' => 838,
                            'fileName' => 'quos',
                            'grounds' => 'string-instead-of-array',
                            'isSuspended' => true,
                            'judgement' => Judgement::PUBLIC->value,
                            'links' => [],
                            'matter' => 'sint',
                            'refersTo' => [],
                            'remark' => 'Consequatur perferendis facere omnis.',
                            'sourceType' => SourceType::VIDEO->value,
                            'threadId' => 341,
                        ],
                    ],
                ],
                [
                    'code' => Type::INVALID_TYPE_ERROR,
                    'propertyPath' => 'documents[0].grounds',
                ],
            ],
            'document grounds contains only invalid value' => [
                [
                    'documents' => [
                        [
                            'caseNumbers' => [],
                            'date' => '2025-09-17',
                            'documentId' => '7d54bd0f-96be-309c-a541-290efacef319',
                            'externalId' => 'd3147b92-f6a3-3c78-91bc-627f252fc07e',
                            'familyId' => 838,
                            'fileName' => 'quos',
                            'grounds' => ['invalid'],
                            'isSuspended' => true,
                            'judgement' => Judgement::PUBLIC->value,
                            'links' => [],
                            'matter' => 'sint',
                            'refersTo' => [],
                            'remark' => 'Consequatur perferendis facere omnis.',
                            'sourceType' => SourceType::VIDEO->value,
                            'threadId' => 341,
                        ],
                    ],
                ],
                [
                    'code' => Choice::NO_SUCH_CHOICE_ERROR,
                    'propertyPath' => 'documents[0].grounds[0]',
                ],
            ],
            'document grounds contains both valid & invalid values' => [
                [
                    'documents' => [
                        [
                            'caseNumbers' => [],
                            'date' => '2025-09-17',
                            'documentId' => '7d54bd0f-96be-309c-a541-290efacef319',
                            'externalId' => 'd3147b92-f6a3-3c78-91bc-627f252fc07e',
                            'familyId' => 838,
                            'fileName' => 'quos',
                            'grounds' => [Citation::GROUND_WOO_511A, Citation::GROUND_WOB_102B, 'invalid'],
                            'isSuspended' => true,
                            'judgement' => Judgement::PUBLIC->value,
                            'links' => [],
                            'matter' => 'sint',
                            'refersTo' => [],
                            'remark' => 'Consequatur perferendis facere omnis.',
                            'sourceType' => SourceType::VIDEO->value,
                            'threadId' => 341,
                        ],
                    ],
                ],
                [
                    'code' => Choice::NO_SUCH_CHOICE_ERROR,
                    'propertyPath' => 'documents[0].grounds[2]',
                ],
            ],
            'mainDocument grounds contains both valid & invalid values' => [
                [
                    'mainDocument' => [
                        'fileName' => 'qux',
                        'formalDate' => '2024-11-04',
                        'type' => AttachmentType::JUDGEMENT_ON_WOB_WOO_REQUEST->value,
                        'language' => AttachmentLanguage::NLD->value,
                        'grounds' => [Citation::GROUND_WOO_511A, Citation::GROUND_WOB_102B, 'invalid'],
                    ],
                ],
                [
                    'code' => Choice::NO_SUCH_CHOICE_ERROR,
                    'propertyPath' => 'mainDocument.grounds[2]',
                ],
            ],
            'attachment grounds contains both valid & invalid values' => [
                [
                    'attachments' => [
                        [
                            'externalId' => 'foo',
                            'fileName' => 'baz',
                            'formalDate' => '2024-11-04',
                            'type' => AttachmentType::AGENDA->value,
                            'language' => AttachmentLanguage::NLD->value,
                            'grounds' => [Citation::GROUND_WOO_511A, Citation::GROUND_WOB_102B, 'invalid'],
                        ],
                    ],
                ],
                [
                    'code' => Choice::NO_SUCH_CHOICE_ERROR,
                    'propertyPath' => 'attachments[0].grounds[2]',
                ],
            ],
        ];
    }

    public function testUpdateWooDecision(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $wooDecision = WooDecisionFactory::createOne(
            [
                'departments' => [$department],
                'externalId' => ExternalId::create($this->getFaker()->slug(1)),
                'organisation' => $organisation,
                'previewDate' => $this->getFaker()->dateTime(),
                'status' => DossierStatus::CONCEPT,
            ],
        );
        WooDecisionMainDocumentFactory::createOne(['dossier' => $wooDecision]);
        WooDecisionAttachmentFactory::createOne(['dossier' => $wooDecision]);
        DocumentFactory::new()->withExternalId()->create(['dossiers' => [$wooDecision]]);

        self::assertDatabaseHas(
            WooDecision::class,
            [
                'title' => $wooDecision->getTitle(),
                'summary' => $wooDecision->getSummary(),
            ],
        );

        $data = $this->createValidWooDecisionDataPayload($department);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $wooDecision), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(WooDecisionResponseDto::class);

        self::assertDatabaseHas(
            WooDecision::class,
            [
                'dossierNr' => $data['dossierNumber'],
                'documentPrefix' => $wooDecision->getDocumentPrefix(),
                'summary' => $data['summary'],
                'title' => $data['title'],
            ],
        );
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
        $wooDecision = WooDecisionFactory::createOne(
            [
                'departments' => [$department],
                'externalId' => ExternalId::create($this->getFaker()->slug(1)),
                'organisation' => $organisation,
                'previewDate' => $this->getFaker()->dateTime(),
                'status' => DossierStatus::CONCEPT,
            ],
        );
        WooDecisionMainDocumentFactory::createOne(['dossier' => $wooDecision]);
        WooDecisionAttachmentFactory::createOne(['dossier' => $wooDecision]);

        self::assertDatabaseHas(
            WooDecision::class,
            [
                'title' => $wooDecision->getTitle(),
                'summary' => $wooDecision->getSummary(),
            ],
        );

        $data = array_merge($this->createValidWooDecisionDataPayload($department), $dataOverrides);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $wooDecision), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [$violations]]);

        self::assertDatabaseHas(
            WooDecision::class,
            [
                'title' => $wooDecision->getTitle(),
                'summary' => $wooDecision->getSummary(),
            ],
        );
    }

    /**
     * @return array<string,array<array-key,mixed>>
     */
    public static function updateWooDecisionValidationDataProvider(): array
    {
        return [
            'dateFrom must be before dateTo' => [
                [
                    'dateFrom' => CarbonImmutable::now()->addDay()->format('Y-m-d'),
                    'dateTo' => CarbonImmutable::now()->subDay()->format('Y-m-d'),
                ],
                [
                    'code' => PlainDateAfterOrEqual::PLAIN_DATE_AFTER_OR_EQUAL_ERROR,
                    'propertyPath' => 'dateTo',
                ],
            ],
            'dateTo must not be too far in the future' => [
                [
                    'dateFrom' => CarbonImmutable::now()->addDay()->format('Y-m-d'),
                    'dateTo' => CarbonImmutable::now()->addYears(10)->format('Y-m-d'),
                ],
                [
                    'code' => PlainDateBeforeOrEqual::PLAIN_DATE_BEFORE_OR_EQUAL_ERROR,
                    'propertyPath' => 'dateTo',
                ],
            ],
        ];
    }

    public function testUpdateWooDecisionWithNonConceptState(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $wooDecision = WooDecisionFactory::createOne(
            [
                'departments' => [$department],
                'externalId' => ExternalId::create($this->getFaker()->slug(1)),
                'organisation' => $organisation,
                'previewDate' => $this->getFaker()->dateTime(),
                'status' => $this->getFaker()->randomElement(DossierStatus::nonConceptCases()),
            ],
        );
        WooDecisionMainDocumentFactory::createOne(['dossier' => $wooDecision]);
        WooDecisionAttachmentFactory::createOne(['dossier' => $wooDecision]);

        self::assertDatabaseHas(
            WooDecision::class,
            [
                'title' => $wooDecision->getTitle(),
                'summary' => $wooDecision->getSummary(),
            ],
        );

        $data = $this->createValidWooDecisionDataPayload($department);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $wooDecision), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        self::assertDatabaseHas(
            WooDecision::class,
            [
                'title' => $wooDecision->getTitle(),
                'summary' => $wooDecision->getSummary(),
            ],
        );
    }

    public function testUpdateWooDecisionWithExistingDocumentsExternalId(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $wooDecision = WooDecisionFactory::createOne(
            [
                'departments' => [$department],
                'externalId' => ExternalId::create($this->getFaker()->slug(1)),
                'organisation' => $organisation,
                'status' => DossierStatus::CONCEPT,
            ],
        );
        WooDecisionMainDocumentFactory::createOne(['dossier' => $wooDecision]);
        $wooDecisionDocument = DocumentFactory::createOne(
            [
                'documentNr' => 'A',
                'dossiers' => [$wooDecision],
                'externalId' => ExternalId::create($this->getFaker()->uuid()),
            ],
        );

        $newDocumentId = $this->getFaker()->uuid();

        $putData = $this->createValidWooDecisionDataPayload($department, null, 0, 0);

        $documentData = $this->createDocumentDataPayload();
        $documentData['documentId'] = $newDocumentId;
        $documentData['externalId'] = $wooDecisionDocument->getExternalId()?->__toString();
        $putData['documents'] = [$documentData];

        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $wooDecision), ['json' => $putData]);
        self::assertResponseIsSuccessful();

        self::assertDatabaseHas(
            Document::class,
            [
                'documentId' => $newDocumentId,
                'externalId' => $wooDecisionDocument->getExternalId(),
            ],
        );
    }

    public function testUpdateWooDecisionWithSameAttachmentsReplacesThem(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $wooDecision = WooDecisionFactory::new()->concept()->createOne([
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'dateFrom' => PlainDate::create('2022-01-01'),
            'dateTo' => PlainDate::create('2022-01-02'),
            'previewDate' => null,
            'publicationDate' => null,
            'status' => DossierStatus::CONCEPT,
        ]);
        $mainDocument = WooDecisionMainDocumentFactory::createOne([
            'dossier' => $wooDecision,
            'fileInfo' => FileInfoFactory::createOne([
                'uploaded' => false,
            ]),
        ]);
        $attachment = WooDecisionAttachmentFactory::createOne([
            'dossier' => $wooDecision,
            'externalId' => ExternalId::create($this->getFaker()->uuid()),
        ]);

        self::assertDatabaseCount(WooDecisionAttachment::class, 1);
        self::assertDatabaseHas(WooDecisionAttachment::class, [
            'id' => $attachment->getId(),
            'dossier' => ['id' => $wooDecision->getId()],
        ]);

        $data = [
            'title' => $wooDecision->getTitle(),
            'dossierNumber' => $wooDecision->getDossierNr(),
            'dateFrom' => $wooDecision->getDateFrom()?->format('Y-m-d'),
            'dateTo' => $wooDecision->getDateFrom()?->format('Y-m-d'),
            'decision' => $wooDecision->getDecision()?->value,
            'reason' => $wooDecision->getPublicationReason(),
            'previewDate' => $this->getFaker()->dateTime()->format('Y-m-d'),
            'publicationDate' => $this->getFaker()->plainDate()->format('Y-m-d'),
            'summary' => $wooDecision->getSummary(),
            'departmentId' => $department->getId(),
            'subjectId' => $wooDecision->getSubject()?->getId(),
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
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $wooDecision), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR, 'test fails now, but should be fixed in WOO-6225');

        // after fixing the issue in WOO-6225, the 500-error-assertion can be removed and these lines below should be uncommented
        // self::assertResponseIsSuccessful();

        // self::assertDatabaseCount(DispositionAttachment::class, 1);
        // self::assertDatabaseMissing(DispositionAttachment::class, [
        //     'id' => $attachment->getId(),
        //     'dossier' => ['id' => $disposition->getId()],
        // ]);
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
        $payload = [
            'title' => $this->getFaker()->sentence(),
            'dossierNumber' => $this->getFaker()->slug(2),
            'dateFrom' => $this->getFaker()->dateTimeBetween('-3 weeks', '-2 week')->format('Y-m-d'),
            'dateTo' => $this->getFaker()->dateTimeBetween('-1 week', 'now')->format('Y-m-d'),
            'decision' => $this->getFaker()->randomElement(DecisionType::cases()),
            'reason' => $this->getFaker()->randomElement(PublicationReason::cases()),
            'previewDate' => $this->getFaker()->dateTimeBetween('1 week', '2 weeks')->format('Y-m-d'),
            'publicationDate' => $this->getFaker()->plainDateBetween('2 weeks', '3 weeks')->format('Y-m-d'),
            'summary' => $this->getFaker()->sentence(),
            'departmentId' => $department->getId(),
            'subjectId' => $subject?->getId(),
            'mainDocument' => [
                'fileName' => $this->getFaker()->word(),
                'formalDate' => $this->getFaker()->date(),
                'type' => $this->getFaker()->randomElement(WooDecisionMainDocument::getAllowedTypes()),
                'language' => $this->getFaker()->randomElement(AttachmentLanguage::cases()),
            ],
            'attachments' => $this->createValidAttachmentsPayload(
                $attachmentCount ?? $this->getFaker()->numberBetween(0, 3),
                WooDecisionAttachment::getAllowedTypes(),
            ),
            'documents' => $this->createDocuments($documentCount ?? $this->getFaker()->numberBetween(0, 3)),
        ];

        if ($this->getFaker()->boolean()) {
            $payload['mainDocument']['grounds'] = $this->getFaker()->groundsBetween(0, 3);
        }

        return $payload;
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
            'date' => $this->getFaker()->date(),
            'documentId' => $this->getFaker()->uuid(),
            'externalId' => $this->getFaker()->externalId()->__toString(),
            'familyId' => $this->getFaker()->numberBetween(1, 1000),
            'fileName' => $this->getFaker()->word(),
            'grounds' => $this->getFaker()->groundsBetween(0, 3),
            'isSuspended' => $this->getFaker()->boolean(),
            'judgement' => $this->getFaker()->randomElement(Judgement::cases()),
            'links' => [],
            'matter' => $this->getFaker()->slug(1),
            'refersTo' => [],
            'remark' => $this->getFaker()->sentence(),
            'sourceType' => $this->getFaker()->randomElement(SourceType::cases()),
            'threadId' => $this->getFaker()->numberBetween(1, 1000),
        ];
    }
}
