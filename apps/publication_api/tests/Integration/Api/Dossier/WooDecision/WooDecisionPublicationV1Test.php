<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Dossier\WooDecision;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PublicationApi\Api\Dossier\WooDecision\Uploads\Attachment\WooDecisionUploadAttachmentResource;
use PublicationApi\Api\Dossier\WooDecision\Uploads\Document\WooDecisionUploadDocumentResource;
use PublicationApi\Api\Dossier\WooDecision\Uploads\MainDocument\WooDecisionUploadMainDocumentResource;
use PublicationApi\Api\Dossier\WooDecision\WooDecisionResource;
use PublicationApi\Domain\OpenApi\Links\ApiUrlGenerator;
use PublicationApi\Domain\Upload\UploadStatus;
use PublicationApi\Tests\Integration\Api\Dossier\ApiPublicationV1DossierTestCase;
use Shared\Controller\Public\Dossier\DossierFileController;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Citation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Attachment\WooDecisionAttachment;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Decision\DecisionType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Validator\UniqueDocumentNumber;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Domain\Publication\Dossier\Type\WooDecision\MainDocument\WooDecisionMainDocument;
use Shared\Domain\Publication\Dossier\Type\WooDecision\PublicationReason;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Validator\Immutable;
use Shared\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use Shared\Domain\Publication\PublicUrlGenerator;
use Shared\Domain\Publication\SourceType;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\DocumentFactory;
use Shared\Tests\Factory\FileInfoFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\DocumentPrefixFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionAttachmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionMainDocumentFactory;
use Shared\Tests\Factory\Publication\Subject\SubjectFactory;
use Shared\Validator\AllowedFileExtension;
use Shared\Validator\PlainDate\PlainDateAfterOrEqual;
use Shared\Validator\PlainDate\PlainDateBeforeOrEqual;
use Shared\Validator\Violation\ConstraintViolationBuilder;
use Shared\ValueObject\DocumentId;
use Shared\ValueObject\ExternalId;
use Shared\ValueObject\PlainDate;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Constraints\Unique;
use Webmozart\Assert\Assert;

use function array_map;
use function array_merge;
use function range;
use function sprintf;
use function str_repeat;

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
            'previewDate' => $this->getFaker()->plainDate(),
            'externalId' => $this->getFaker()->externalId(),
        ]);
        WooDecisionMainDocumentFactory::createOne(['dossier' => $wooDecision]);
        WooDecisionAttachmentFactory::createOne([
            'dossier' => $wooDecision,
            'externalId' => $this->getFaker()->externalId(),
        ]);
        DocumentFactory::createOne([
            'dossiers' => [$wooDecision],
            'externalId' => $this->getFaker()->externalId(),
        ]);

        $result = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation));
        self::assertResponseIsSuccessful();
        $data = $result->toArray();
        self::assertArrayHasKey('items', $data);
        self::assertArrayHasKey('hasNextPage', $data);
        /** @var array<array-key, mixed> $items */
        $items = $data['items'];
        self::assertCount(1, $items);
        self::assertJsonContains(['items' => [['externalId' => $wooDecision->getExternalId()?->toString()]]]);
    }

    public function testGetWooDecisionCollectionDoesNotContainWooDecisionWithoutExternalId(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();

        $wooDecision1 = WooDecisionFactory::createOne([
            'departments' => [$department],
            'organisation' => $organisation,
            'previewDate' => $this->getFaker()->plainDate(),
            'externalId' => $this->getFaker()->externalId(),
        ]);
        WooDecisionMainDocumentFactory::createOne(['dossier' => $wooDecision1]);
        WooDecisionAttachmentFactory::createOne([
            'dossier' => $wooDecision1,
            'externalId' => $this->getFaker()->externalId(),
        ]);
        DocumentFactory::createOne([
            'dossiers' => [$wooDecision1],
            'externalId' => $this->getFaker()->externalId(),
        ]);

        $wooDecision2 = WooDecisionFactory::createOne([
            'departments' => [$department],
            'organisation' => $organisation,
            'previewDate' => $this->getFaker()->plainDate(),
            'externalId' => null,
        ]);
        WooDecisionMainDocumentFactory::createOne(['dossier' => $wooDecision2]);

        $result = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation));
        self::assertResponseIsSuccessful();
        $data = $result->toArray();
        self::assertArrayHasKey('items', $data);
        self::assertArrayHasKey('hasNextPage', $data);
        /** @var array<array-key, mixed> $items */
        $items = $data['items'];
        self::assertCount(1, $items);
        self::assertJsonContains(['items' => [['externalId' => $wooDecision1->getExternalId()?->toString()]]]);
    }

    public function testGetWooDecision(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $subject = SubjectFactory::createOne();
        $wooDecision = WooDecisionFactory::createOne(
            [
                'departments' => [$department],
                'externalId' => $this->getFaker()->externalId(),
                'organisation' => $organisation,
                'previewDate' => $this->getFaker()->plainDate(),
                'subject' => $subject,
            ],
        );
        $wooDecisionMainDocument = WooDecisionMainDocumentFactory::createOne(['dossier' => $wooDecision]);
        $wooDecisionAttachment = WooDecisionAttachmentFactory::createOne([
            'dossier' => $wooDecision,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        // watch it: documents are sorted by documentNumber
        $wooDecisionDocument1 = DocumentFactory::createOne(
            [
                'documentNumber' => 'A',
                'judgement' => Judgement::PUBLIC,
                'dossiers' => [$wooDecision],
                'externalId' => $this->getFaker()->externalId(),
                'fileInfo' => FileInfoFactory::createOne([
                    'uploaded' => true,
                ]),
            ],
        );
        $wooDecisionDocument2 = DocumentFactory::createOne(
            [
                'documentNumber' => 'B',
                'judgement' => Judgement::PUBLIC,
                'dossiers' => [$wooDecision],
                'externalId' => $this->getFaker()->externalId(),
                'fileInfo' => FileInfoFactory::createOne([
                    'uploaded' => true,
                ]),
                'refersTo' => [$wooDecisionDocument1],
            ],
        );

        $response = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $wooDecision));
        self::assertResponseIsSuccessful();

        $apiUrlGenerator = $this->fromContainer(ApiUrlGenerator::class);
        $dossierPathHelper = $this->fromContainer(DossierPathHelper::class);
        $publicUrlGenerator = $this->fromContainer(PublicUrlGenerator::class);
        $expectedResponse = [
            'id' => (string) $wooDecision->getId(),
            'externalId' => $wooDecision->getExternalId()?->toString(),
            'organisation' => [
                'id' => $organisation->getId()->toString(),
                'name' => $organisation->getName(),
            ],
            'dossierNumber' => $wooDecision->getDossierNumber(),
            'title' => (string) $wooDecision->getTitle(),
            'summary' => $wooDecision->getSummary(),
            'subject' => [
                'id' => $subject->getId()->toString(),
                'name' => $subject->getName(),
            ],
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
                '_links' => [
                    'upload' => [
                        'href' => $apiUrlGenerator->buildUrlFromRoute(
                            WooDecisionUploadMainDocumentResource::ROUTE_NAME_UPLOAD,
                            [
                                'organisationId' => $wooDecision->getOrganisation()->getId(),
                                'dossierExternalId' => $wooDecision->getExternalId()?->toString(),
                            ],
                        )->toString(),
                    ],
                    'public' => ['href' => $dossierPathHelper->getAbsoluteDetailsPath($wooDecision)],
                    'file' => [
                        'href' => $publicUrlGenerator->buildUrlFromRoute(
                            DossierFileController::ROUTE_NAME_DOSSIER_FILE_DOWNLOAD,
                            [
                                'documentPrefix' => $wooDecision->getDocumentPrefix(),
                                'dossierNumber' => $wooDecision->getDossierNumber(),
                                'type' => DossierFileType::MAIN_DOCUMENT->value,
                                'id' => $wooDecisionMainDocument->getId(),
                            ],
                        )->toString(),
                    ],
                ],
            ],
            'attachments' => [
                [
                    'id' => (string) $wooDecisionAttachment->getId(),
                    'type' => $wooDecisionAttachment->getType()->value,
                    'language' => $wooDecisionAttachment->getLanguage()->value,
                    'formalDate' => $wooDecisionAttachment->getFormalDate()->format('Y-m-d'),
                    'grounds' => $wooDecisionAttachment->getGrounds(),
                    'fileName' => $wooDecisionAttachment->getFileInfo()->getName(),
                    'externalId' => $wooDecisionAttachment->getExternalId()?->toString(),
                    'uploadStatus' => UploadStatus::PROCESSED->value,
                    '_links' => [
                        'upload' => [
                            'href' => $apiUrlGenerator->buildUrlFromRoute(
                                WooDecisionUploadAttachmentResource::ROUTE_NAME_UPLOAD,
                                [
                                    'organisationId' => $wooDecision->getOrganisation()->getId(),
                                    'dossierExternalId' => $wooDecision->getExternalId()?->toString(),
                                    'attachmentExternalId' => $wooDecisionAttachment->getExternalId(),
                                ],
                            )->toString(),
                        ],
                        'public' => ['href' => $dossierPathHelper->getAbsoluteDetailsPath($wooDecision)],
                        'file' => [
                            'href' => $publicUrlGenerator->buildUrlFromRoute(
                                DossierFileController::ROUTE_NAME_DOSSIER_FILE_DOWNLOAD,
                                [
                                    'documentPrefix' => $wooDecision->getDocumentPrefix(),
                                    'dossierNumber' => $wooDecision->getDossierNumber(),
                                    'type' => DossierFileType::ATTACHMENT->value,
                                    'id' => $wooDecisionAttachment->getId(),
                                ],
                            )->toString(),
                        ],
                    ],
                ],
            ],
            'dateFrom' => $wooDecision->getDateFrom()?->format('Y-m-d'),
            'dateTo' => $wooDecision->getDateTo()?->format('Y-m-d'),
            'decision' => $wooDecision->getDecision()?->value,
            'reason' => $wooDecision->getPublicationReason()?->value,
            'previewDate' => $wooDecision->getPreviewDate()?->format('Y-m-d'),
            'documents' => [
                [
                    'inquiryNumbers' => [],
                    'documentDate' => $wooDecisionDocument1->getDocumentDate()?->format('Y-m-d'),
                    'documentId' => $wooDecisionDocument1->getDocumentId()?->toString(),
                    'documentNumber' => $wooDecisionDocument1->getDocumentNumber(),
                    'externalId' => $wooDecisionDocument1->getExternalId()?->toString(),
                    'familyId' => $wooDecisionDocument1->getFamilyId(),
                    'fileName' => $wooDecisionDocument1->getFileInfo()->getName(),
                    'sourceType' => $wooDecisionDocument1->getFileInfo()->getSourceType()?->value,
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
                    '_links' => [
                        'upload' => [
                            'href' => $apiUrlGenerator->buildUrlFromRoute(
                                WooDecisionUploadDocumentResource::ROUTE_NAME_UPLOAD,
                                [
                                    'organisationId' => $wooDecision->getOrganisation()->getId(),
                                    'dossierExternalId' => $wooDecision->getExternalId()?->toString(),
                                    'documentExternalId' => $wooDecisionDocument1->getExternalId(),
                                ],
                            )->toString(),
                        ],
                        'public' => ['href' => $dossierPathHelper->getAbsoluteDetailsPath($wooDecision)],
                        'file' => [
                            'href' => $publicUrlGenerator->buildUrlFromRoute(
                                DossierFileController::ROUTE_NAME_DOSSIER_FILE_DOWNLOAD,
                                [
                                    'documentPrefix' => $wooDecision->getDocumentPrefix(),
                                    'dossierNumber' => $wooDecision->getDossierNumber(),
                                    'type' => DossierFileType::DOCUMENT->value,
                                    'id' => $wooDecisionDocument1->getId(),
                                ],
                            )->toString(),
                        ],
                    ],
                ],
                [
                    'inquiryNumbers' => [],
                    'documentDate' => $wooDecisionDocument2->getDocumentDate()?->format('Y-m-d'),
                    'documentId' => $wooDecisionDocument2->getDocumentId()?->toString(),
                    'documentNumber' => $wooDecisionDocument2->getDocumentNumber(),
                    'externalId' => $wooDecisionDocument2->getExternalId()?->toString(),
                    'familyId' => $wooDecisionDocument2->getFamilyId(),
                    'fileName' => $wooDecisionDocument2->getFileInfo()->getName(),
                    'sourceType' => $wooDecisionDocument2->getFileInfo()->getSourceType()?->value,
                    'grounds' => $wooDecisionDocument2->getGrounds(),
                    'isSuspended' => $wooDecisionDocument2->isSuspended(),
                    'isUploaded' => $wooDecisionDocument2->isUploaded(),
                    'isWithdrawn' => $wooDecisionDocument2->isWithdrawn(),
                    'judgement' => $wooDecisionDocument2->getJudgement()?->value,
                    'links' => $wooDecisionDocument2->getLinks(),
                    'refersTo' => [
                        [
                            'documentId' => $wooDecisionDocument1->getDocumentId()?->toString(),
                            'externalId' => $wooDecisionDocument1->getExternalId()?->toString(),
                        ],
                    ],
                    'remark' => $wooDecisionDocument2->getRemark(),
                    'threadId' => $wooDecisionDocument2->getThreadId(),
                    'uploadStatus' => UploadStatus::PROCESSED->value,
                    '_links' => [
                        'upload' => [
                            'href' => $apiUrlGenerator->buildUrlFromRoute(
                                WooDecisionUploadDocumentResource::ROUTE_NAME_UPLOAD,
                                [
                                    'organisationId' => $wooDecision->getOrganisation()->getId(),
                                    'dossierExternalId' => $wooDecision->getExternalId()?->toString(),
                                    'documentExternalId' => $wooDecisionDocument2->getExternalId(),
                                ],
                            )->toString(),
                        ],
                        'public' => ['href' => $dossierPathHelper->getAbsoluteDetailsPath($wooDecision)],
                        'file' => [
                            'href' => $publicUrlGenerator->buildUrlFromRoute(
                                DossierFileController::ROUTE_NAME_DOSSIER_FILE_DOWNLOAD,
                                [
                                    'documentPrefix' => $wooDecision->getDocumentPrefix(),
                                    'dossierNumber' => $wooDecision->getDossierNumber(),
                                    'type' => DossierFileType::DOCUMENT->value,
                                    'id' => $wooDecisionDocument2->getId(),
                                ],
                            )->toString(),
                        ],
                    ],
                ],
            ],
            '_links' => [
                'self' => ['href' => $this->buildApiUrl($organisation, $wooDecision)],
                'public' => ['href' => $dossierPathHelper->getAbsoluteDetailsPath($wooDecision)],
            ],
        ];

        self::assertSame($expectedResponse, $response->toArray());
        self::assertMatchesResourceItemJsonSchema(WooDecisionResource::class);
    }

    public function testGetWooDecisionWithPublicLink(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $wooDecision = WooDecisionFactory::new()->published()->create(
            [
                'departments' => [$department],
                'externalId' => $this->getFaker()->externalId(),
                'organisation' => $organisation,
            ],
        );
        WooDecisionMainDocumentFactory::createOne(['dossier' => $wooDecision]);
        WooDecisionAttachmentFactory::createOne([
            'dossier' => $wooDecision,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        $result = self::createPublicationApiRequest(
            Request::METHOD_GET,
            $this->buildUrl($organisation, $wooDecision),
        );
        self::assertResponseIsSuccessful();

        $dossierPathHelper = $this->fromContainer(DossierPathHelper::class);
        self::assertEquals([
            'self' => ['href' => $this->buildApiUrl($organisation, $wooDecision)],
            'public' => ['href' => $dossierPathHelper->getAbsoluteDetailsPath($wooDecision)],
        ], $result->toArray()['_links']);
    }

    public function testGetWooDecisionWithoutPublicLinkIfNotPublished(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $wooDecision = WooDecisionFactory::new()->concept()->create(
            [
                'departments' => [$department],
                'externalId' => $this->getFaker()->externalId(),
                'organisation' => $organisation,
            ],
        );
        WooDecisionMainDocumentFactory::createOne(['dossier' => $wooDecision]);
        WooDecisionAttachmentFactory::createOne([
            'dossier' => $wooDecision,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        $result = self::createPublicationApiRequest(
            Request::METHOD_GET,
            $this->buildUrl($organisation, $wooDecision),
        );
        self::assertResponseIsSuccessful();

        self::assertEquals([
            'self' => ['href' => $this->buildApiUrl($organisation, $wooDecision)],
        ], $result->toArray()['_links']);
    }

    public function testGetFromIncorrectOrganisation(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $wooDecision = WooDecisionFactory::createOne(
            [
                'departments' => [$department],
                'externalId' => $this->getFaker()->externalId(),
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
        self::assertMatchesResourceItemJsonSchema(WooDecisionResource::class);

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
        self::assertMatchesResourceItemJsonSchema(WooDecisionResource::class);

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
        self::assertMatchesResourceItemJsonSchema(WooDecisionResource::class);

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

    public function testCreateWooDecisionWithExternalIdAlreadyUsedByComplaintJudgementReturnsConflict(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        $data = $this->createValidWooDecisionDataPayload($department, $subject);
        $externalId = $this->getFaker()->externalId();
        ComplaintJudgementFactory::createOne([
            'externalId' => $externalId,
            'organisation' => $organisation,
            'departments' => [$department],
            'subject' => $subject,
        ]);

        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $externalId), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
        self::assertJsonContains(['detail' => 'ExternalId already in use by type complaint-judgement']);
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
        self::assertMatchesResourceItemJsonSchema(WooDecisionResource::class);
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
        self::assertMatchesResourceItemJsonSchema(WooDecisionResource::class);
        self::assertDatabaseCount(WooDecision::class, 1);
    }

    public function testCreateWooDecisionWithNonUniqueDossierNumberAndDocumentPrefix(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $documentPrefix = DocumentPrefixFactory::createOne(['organisation' => $organisation]);
        $wooDecision = WooDecisionFactory::createOne(
            [
                'departments' => [$department],
                'externalId' => $this->getFaker()->externalId(),
                'organisation' => $organisation,
                'previewDate' => $this->getFaker()->plainDate(),
                'documentPrefix' => $documentPrefix->getPrefix(),
            ],
        );

        $data = $this->createValidWooDecisionDataPayload($department, $subject);
        $data['dossierNumber'] = $wooDecision->getDossierNumber();

        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->externalId()), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testCreateWooDecisionWithTooLongExternalId(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();

        $data = $this->createValidWooDecisionDataPayload($department, $subject);

        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, str_repeat('x', 129)), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
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
                    'code' => ConstraintViolationBuilder::ENTITY_MISSING_ERROR,
                    'propertyPath' => 'subjectId',
                ],
            ],
            'invalid departmentId format' => [
                [
                    'departmentId' => [],
                ],
                [
                    'code' => Type::INVALID_TYPE_ERROR,
                    'propertyPath' => 'departmentId',
                    'message' => 'Invalid string format',
                ],
            ],
            'invalid departmentId uuid format' => [
                [
                    'departmentId' => 'invalid-uuid',
                ],
                [
                    'code' => Type::INVALID_TYPE_ERROR,
                    'propertyPath' => 'departmentId',
                    'message' => 'Invalid uuid format',
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
            'document grounds not an array' => [
                [
                    'documents' => [
                        [
                            'inquiryNumbers' => [],
                            'documentDate' => '2025-09-17',
                            'documentId' => '7d54bd0f',
                            'externalId' => 'd3147b92-f6a3-3c78-91bc-627f252fc07e',
                            'familyId' => 838,
                            'fileName' => 'quos',
                            'grounds' => 'string-instead-of-array',
                            'isSuspended' => true,
                            'judgement' => Judgement::PUBLIC->value,
                            'links' => [],
                            'publicationContext' => 'sint',
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
                            'inquiryNumbers' => [],
                            'documentDate' => '2025-09-17',
                            'documentId' => '7d54bd0f',
                            'externalId' => 'd3147b92-f6a3-3c78-91bc-627f252fc07e',
                            'familyId' => 838,
                            'fileName' => 'document.pdf',
                            'grounds' => ['invalid'],
                            'isSuspended' => true,
                            'judgement' => Judgement::PUBLIC->value,
                            'links' => [],
                            'publicationContext' => 'sint',
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
                            'inquiryNumbers' => [],
                            'documentDate' => '2025-09-17',
                            'documentId' => '7d54bd0f',
                            'externalId' => 'd3147b92-f6a3-3c78-91bc-627f252fc07e',
                            'familyId' => 838,
                            'fileName' => 'document.pdf',
                            'grounds' => [Citation::GROUND_WOO_511A, Citation::GROUND_WOB_102B, 'invalid'],
                            'isSuspended' => true,
                            'judgement' => Judgement::PUBLIC->value,
                            'links' => [],
                            'publicationContext' => 'sint',
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
            'document publicationContext with invalid character' => [
                [
                    'documents' => [
                        [
                            'inquiryNumbers' => [],
                            'documentDate' => '2025-09-17',
                            'documentId' => '7d54bd0f',
                            'externalId' => 'd3147b92-f6a3-3c78-91bc-627f252fc07e',
                            'familyId' => 838,
                            'fileName' => 'document.pdf',
                            'grounds' => [],
                            'isSuspended' => true,
                            'judgement' => Judgement::PUBLIC->value,
                            'links' => [],
                            'publicationContext' => 'invalid-character-$',
                            'refersTo' => [],
                            'remark' => 'Consequatur perferendis facere omnis.',
                            'sourceType' => SourceType::VIDEO->value,
                            'threadId' => 341,
                        ],
                    ],
                ],
                [
                    'code' => Type::INVALID_TYPE_ERROR,
                    'propertyPath' => 'documents[0].publicationContext',
                    'hint' => 'Invalid publication context format',
                ],
            ],
            'documentId is an empty string' => [
                [
                    'documents' => [
                        [
                            'inquiryNumbers' => [],
                            'documentDate' => '2025-09-17',
                            'documentId' => '',
                            'externalId' => 'd3147b92-f6a3-3c78-91bc-627f252fc07e',
                            'familyId' => 838,
                            'fileName' => 'document.pdf',
                            'grounds' => [],
                            'isSuspended' => true,
                            'judgement' => Judgement::PUBLIC->value,
                            'links' => [],
                            'publicationContext' => 'publicationContext',
                            'refersTo' => [],
                            'remark' => 'Consequatur perferendis facere omnis.',
                            'sourceType' => SourceType::VIDEO->value,
                            'threadId' => 341,
                        ],
                    ],
                ],
                [
                    'code' => Type::INVALID_TYPE_ERROR,
                    'propertyPath' => 'documents[0].documentId',
                    'hint' => 'Document ID cannot be empty',
                ],
            ],
            'mainDocument grounds contains both valid & invalid values' => [
                [
                    'mainDocument' => [
                        'fileName' => 'mainDocument.pdf',
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
            'invalid mainDocument fileName' => [
                [
                    'mainDocument' => [
                        'fileName' => '../secret.txt',
                        'formalDate' => CarbonImmutable::now()->addDay()->format('Y-m-d'),
                        'type' => AttachmentType::ACCOUNTABILITY_REPORT,
                        'language' => AttachmentLanguage::NLD,
                    ],
                ],
                [
                    'code' => Type::INVALID_TYPE_ERROR,
                    'propertyPath' => 'mainDocument.fileName',
                ],
            ],
            'invalid document fileName' => [
                [
                    'documents' => [
                        [
                            'inquiryNumbers' => [],
                            'documentDate' => '2025-09-17',
                            'documentId' => '7d54bd0f',
                            'externalId' => 'd3147b92-f6a3-3c78-91bc-627f252fc07e',
                            'familyId' => 838,
                            'fileName' => '\secret.txt',
                            'grounds' => [],
                            'isSuspended' => false,
                            'judgement' => Judgement::PUBLIC->value,
                            'links' => [],
                            'publicationContext' => 'sint',
                            'refersTo' => [],
                            'remark' => null,
                            'sourceType' => SourceType::VIDEO->value,
                            'threadId' => null,
                        ],
                    ],
                ],
                [
                    'code' => Type::INVALID_TYPE_ERROR,
                    'propertyPath' => 'documents[0].fileName',
                ],
            ],
            'attachment grounds contains both valid & invalid values' => [
                [
                    'attachments' => [
                        [
                            'externalId' => 'foo',
                            'fileName' => 'attachment.pdf',
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
            'disallowed mainDocument fileName extension' => [
                [
                    'mainDocument' => [
                        'fileName' => 'document.exe',
                        'formalDate' => CarbonImmutable::now()->addDay()->format('Y-m-d'),
                        'type' => AttachmentType::ACCOUNTABILITY_REPORT,
                        'language' => AttachmentLanguage::NLD,
                    ],
                ],
                [
                    'code' => AllowedFileExtension::INVALID_EXTENSION_ERROR,
                    'propertyPath' => 'mainDocument.fileName',
                ],
            ],
            'attachment externalId too short' => [
                [
                    'attachments' => [
                        [
                            'externalId' => '',
                            'fileName' => 'document.pdf',
                            'formalDate' => CarbonImmutable::now()->addDay()->format('Y-m-d'),
                            'type' => AttachmentType::ACCOUNTABILITY_REPORT,
                            'language' => AttachmentLanguage::NLD,
                        ],
                    ],
                ],
                [
                    'code' => Type::INVALID_TYPE_ERROR,
                    'propertyPath' => 'attachments[0].externalId',
                ],
            ],
            'disallowed attachment fileName extension' => [
                [
                    'attachments' => [
                        [
                            'externalId' => 'externalId',
                            'fileName' => 'document.exe',
                            'formalDate' => CarbonImmutable::now()->addDay()->format('Y-m-d'),
                            'type' => AttachmentType::ACCOUNTABILITY_REPORT,
                            'language' => AttachmentLanguage::ENG,
                        ],
                    ],
                ],
                [
                    'code' => AllowedFileExtension::INVALID_EXTENSION_ERROR,
                    'propertyPath' => 'attachments[0].fileName',
                ],
            ],
            'disallowed attachment language' => [
                [
                    'attachments' => [
                        [
                            'externalId' => 'externalId',
                            'fileName' => 'document.exe',
                            'formalDate' => CarbonImmutable::now()->addDay()->format('Y-m-d'),
                            'type' => AttachmentType::ACCOUNTABILITY_REPORT,
                            'language' => 'non-enum-value',
                        ],
                    ],
                ],
                [
                    'code' => Type::INVALID_TYPE_ERROR,
                    'propertyPath' => 'attachments[0].language',
                ],
            ],
            'disallowed document fileName extension' => [
                [
                    'documents' => [
                        [
                            'inquiryNumbers' => [],
                            'documentDate' => '2025-09-17',
                            'documentId' => '7d54bd0f',
                            'externalId' => 'd3147b92-f6a3-3c78-91bc-627f252fc07e',
                            'familyId' => 838,
                            'fileName' => 'document.exe',
                            'grounds' => [],
                            'isSuspended' => false,
                            'judgement' => Judgement::PUBLIC->value,
                            'links' => [],
                            'publicationContext' => 'sint',
                            'refersTo' => [],
                            'remark' => null,
                            'sourceType' => SourceType::VIDEO->value,
                            'threadId' => null,
                        ],
                    ],
                ],
                [
                    'code' => AllowedFileExtension::INVALID_EXTENSION_ERROR,
                    'propertyPath' => 'documents[0].fileName',
                ],
            ],
            'exceeds max attachments per dossier' => [
                [
                    'attachments' => array_map(
                        static fn ($i) => [
                            'fileName' => sprintf('file%s.pdf', $i),
                            'formalDate' => CarbonImmutable::now()->format('Y-m-d'),
                            'type' => AttachmentType::ACCOUNTABILITY_REPORT->value,
                            'language' => AttachmentLanguage::ENG->value,
                            'externalId' => sprintf('external-id-%s', $i),
                        ],
                        range(1, AbstractAttachment::MAX_ATTACHMENTS_PER_DOSSIER + 1),
                    ),
                ],
                [
                    'code' => Count::TOO_MANY_ERROR,
                    'propertyPath' => 'attachments',
                ],
            ],
            'externalId too short' => [
                [
                    'attachments' => [
                        [
                            'fileName' => 'foo.pdf',
                            'formalDate' => '2000-01-01',
                            'language' => 'NLD',
                            'type' => 'c_d506b718',
                            'externalId' => '',
                        ],
                    ],
                ],
                [
                    'code' => Type::INVALID_TYPE_ERROR,
                    'hint' => 'Invalid external id length',
                    'propertyPath' => 'attachments[0].externalId',
                ],
            ],
            'externalId too long (129 chars exceeds 128 max)' => [
                [
                    'attachments' => [
                        [
                            'fileName' => 'foo.pdf',
                            'formalDate' => '2000-01-01',
                            'language' => 'NLD',
                            'type' => 'c_d506b718',
                            'externalId' => str_repeat('x', 129),
                        ],
                    ],
                ],
                [
                    'code' => Type::INVALID_TYPE_ERROR,
                    'hint' => 'Invalid external id length',
                    'propertyPath' => 'attachments[0].externalId',
                ],
            ],
            'duplicate document external_ids' => [
                [
                    'documents' => [
                        [
                            'inquiryNumbers' => [],
                            'documentDate' => '2025-09-17',
                            'documentId' => '7d54bd0f',
                            'externalId' => 'ext-1',
                            'familyId' => 838,
                            'fileName' => 'document1.pdf',
                            'grounds' => [],
                            'isSuspended' => false,
                            'judgement' => Judgement::PUBLIC->value,
                            'links' => [],
                            'publicationContext' => 'sint',
                            'refersTo' => [],
                            'remark' => null,
                            'sourceType' => SourceType::VIDEO->value,
                            'threadId' => null,
                        ],
                        [
                            'inquiryNumbers' => [],
                            'documentDate' => '2025-09-17',
                            'documentId' => 'a1b2c3d4',
                            'externalId' => 'ext-1',
                            'familyId' => 839,
                            'fileName' => 'document2.pdf',
                            'grounds' => [],
                            'isSuspended' => false,
                            'judgement' => Judgement::PUBLIC->value,
                            'links' => [],
                            'publicationContext' => 'sint',
                            'refersTo' => [],
                            'remark' => null,
                            'sourceType' => SourceType::VIDEO->value,
                            'threadId' => null,
                        ],
                    ],
                ],
                [
                    'code' => Unique::IS_NOT_UNIQUE,
                    'propertyPath' => 'documents',
                ],
            ],
            'duplicate document document_ids' => [
                [
                    'documents' => [
                        [
                            'inquiryNumbers' => [],
                            'documentDate' => '2025-09-17',
                            'documentId' => 'doc1',
                            'externalId' => 'd3147b92-f6a3-3c78-91bc-627f252fc07e',
                            'familyId' => 838,
                            'fileName' => 'document1.pdf',
                            'grounds' => [],
                            'isSuspended' => false,
                            'judgement' => Judgement::PUBLIC->value,
                            'links' => [],
                            'publicationContext' => 'sint',
                            'refersTo' => [],
                            'remark' => null,
                            'sourceType' => SourceType::VIDEO->value,
                            'threadId' => null,
                        ],
                        [
                            'inquiryNumbers' => [],
                            'documentDate' => '2025-09-17',
                            'documentId' => 'doc1',
                            'externalId' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
                            'familyId' => 839,
                            'fileName' => 'document2.pdf',
                            'grounds' => [],
                            'isSuspended' => false,
                            'judgement' => Judgement::PUBLIC->value,
                            'links' => [],
                            'publicationContext' => 'sint',
                            'refersTo' => [],
                            'remark' => null,
                            'sourceType' => SourceType::VIDEO->value,
                            'threadId' => null,
                        ],
                    ],
                ],
                [
                    'code' => Unique::IS_NOT_UNIQUE,
                    'propertyPath' => 'documents',
                ],
            ],
            'empty document externalId' => [
                [
                    'documents' => [
                        [
                            'inquiryNumbers' => [],
                            'documentDate' => '2025-09-17',
                            'documentId' => 'documentId',
                            'externalId' => '',
                            'familyId' => 838,
                            'fileName' => 'document1.pdf',
                            'grounds' => [],
                            'isSuspended' => false,
                            'judgement' => Judgement::PUBLIC->value,
                            'links' => [],
                            'publicationContext' => 'sint',
                            'refersTo' => [],
                            'remark' => null,
                            'sourceType' => SourceType::VIDEO->value,
                            'threadId' => null,
                        ],
                    ],
                ],
                [
                    'code' => Type::INVALID_TYPE_ERROR,
                    'propertyPath' => 'documents[0].externalId',
                    'message' => 'Invalid external id length',
                ],
            ],
            'invalid documentId format' => [
                [
                    'documents' => [
                        [
                            'inquiryNumbers' => [],
                            'documentDate' => '2025-09-17',
                            'documentId' => 'invalid-format-$',
                            'externalId' => 'd3147b92-f6a3-3c78-91bc-627f252fc07e',
                            'familyId' => 838,
                            'fileName' => 'document1.pdf',
                            'grounds' => [],
                            'isSuspended' => false,
                            'judgement' => Judgement::PUBLIC->value,
                            'links' => [],
                            'publicationContext' => 'sint',
                            'refersTo' => [],
                            'remark' => null,
                            'sourceType' => SourceType::VIDEO->value,
                            'threadId' => null,
                        ],
                    ],
                ],
                [
                    'code' => Type::INVALID_TYPE_ERROR,
                    'propertyPath' => 'documents[0].documentId',
                    'message' => 'Invalid document ID format',
                ],
            ],
            'invalid document fileName format' => [
                [
                    'documents' => [
                        [
                            'inquiryNumbers' => [],
                            'documentDate' => '2025-09-17',
                            'documentId' => 'my-document-id',
                            'externalId' => 'd3147b92-f6a3-3c78-91bc-627f252fc07e',
                            'familyId' => 838,
                            'fileName' => 'invalid-format-$',
                            'grounds' => [],
                            'isSuspended' => false,
                            'judgement' => Judgement::PUBLIC->value,
                            'links' => [],
                            'publicationContext' => 'sint',
                            'refersTo' => [],
                            'remark' => null,
                            'sourceType' => SourceType::VIDEO->value,
                            'threadId' => null,
                        ],
                    ],
                ],
                [
                    'code' => Type::INVALID_TYPE_ERROR,
                    'propertyPath' => 'documents[0].fileName',
                    'message' => 'Filename contains invalid characters',
                ],
            ],
            'invalid previewDate format' => [
                [
                    'previewDate' => 'invalid',
                ],
                [
                    'code' => Type::INVALID_TYPE_ERROR,
                    'propertyPath' => 'previewDate',
                    'message' => 'This value should be of type date.',
                ],
            ],
        ];
    }

    public function testCreateWooDecisionWithInvalidDocumentIdFormatReturnsOpenApiValidationError(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);
        self::assertDatabaseCount(WooDecision::class, 0);

        $data = $this->createValidWooDecisionDataPayload($department, $subject, 0, 0);
        $data['documents'] = [array_merge($this->createDocumentDataPayload(), ['documentId' => 'INVALID/DOC/ID'])];

        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains([
            'type' => sprintf('/validation_errors/%s', Type::INVALID_TYPE_ERROR),
            'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
            'title' => 'An error occurred',
        ]);
        self::assertDatabaseCount(WooDecision::class, 0);
    }

    public function testCreateWooDecisionWithDocumentExternalIdAlreadyExistsInAnotherDossier(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        $existingWooDecision = WooDecisionFactory::createOne(['departments' => [$department], 'organisation' => $organisation]);
        $existingExternalId = $this->getFaker()->externalId();
        DocumentFactory::createOne(['dossiers' => [$existingWooDecision], 'externalId' => $existingExternalId]);

        $data = $this->createValidWooDecisionDataPayload($department, $subject, 0, 0);
        $data['documents'] = [array_merge($this->createDocumentDataPayload(), ['externalId' => $existingExternalId->toString()])];

        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [[
            'code' => UniqueEntity::NOT_UNIQUE_ERROR,
            'propertyPath' => 'documents.[0].externalId',
        ]]]);
    }

    public function testCreateWooDecisionWithDocumentNumberAlreadyExistsInAnotherDossier(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        $documentId = $this->getFaker()->documentId();
        $publicationContext = $this->getFaker()->publicationContext();

        $existingWooDecision = WooDecisionFactory::createOne(['departments' => [$department], 'organisation' => $organisation]);
        DocumentFactory::createOne([
            'dossiers' => [$existingWooDecision],
            'documentNumber' => sprintf('%s-%s', $publicationContext, $documentId),
        ]);

        $data = $this->createValidWooDecisionDataPayload($department, $subject, 0, 0);
        $data['documents'] = [array_merge($this->createDocumentDataPayload(), [
            'documentId' => $documentId->toString(),
            'publicationContext' => $publicationContext->toString(),
        ])];

        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [[
            'code' => UniqueDocumentNumber::NOT_UNIQUE_ERROR,
            'propertyPath' => 'documents.[0].documentNumber',
        ]]]);
    }

    public function testUpdateWooDecisionWithDocumentExternalIdAlreadyExistsInAnotherDossier(): void
    {
        $existingDocumentExternalId = $this->getFaker()->externalId();

        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        $existingWooDecision = WooDecisionFactory::createOne(['departments' => [$department], 'organisation' => $organisation]);
        DocumentFactory::createOne(['dossiers' => [$existingWooDecision], 'externalId' => $existingDocumentExternalId]);

        $wooDecisionToUpdate = WooDecisionFactory::createOne([
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
        ]);
        WooDecisionMainDocumentFactory::createOne(['dossier' => $wooDecisionToUpdate]);

        $data = $this->createValidWooDecisionDataPayload($department, null, 0, 0);
        $data['documents'] = [array_merge($this->createDocumentDataPayload(), ['externalId' => $existingDocumentExternalId->toString()])];

        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $wooDecisionToUpdate), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [[
            'code' => UniqueEntity::NOT_UNIQUE_ERROR,
            'propertyPath' => 'documents.[0].externalId',
        ]]]);
    }

    public function testUpdateWooDecisionWithDocumentNumberAlreadyExistsInAnotherDossier(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $documentPrefix = DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        $documentId = 'testdocid';

        $existingWooDecision = WooDecisionFactory::createOne(['departments' => [$department], 'organisation' => $organisation]);
        DocumentFactory::createOne([
            'dossiers' => [$existingWooDecision],
            'documentId' => $documentId,
            'documentNumber' => sprintf('%s-%s-%s', 'prefix', 'matter', $documentId),
        ]);

        $wooDecisionToUpdate = WooDecisionFactory::createOne([
            'departments' => [$department],
            'documentPrefix' => $documentPrefix->getPrefix(),
            'externalId' => ExternalId::create($this->getFaker()->slug(1)),
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
        ]);
        WooDecisionMainDocumentFactory::createOne(['dossier' => $wooDecisionToUpdate]);

        $data = $this->createValidWooDecisionDataPayload($department, null, 0, 0);
        $data['documents'] = [array_merge($this->createDocumentDataPayload(), [
            'documentId' => $documentId,
            'publicationContext' => 'prefix-matter',
        ])];

        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $wooDecisionToUpdate), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [[
            'code' => UniqueDocumentNumber::NOT_UNIQUE_ERROR,
            'propertyPath' => 'documents.[0].documentNumber',
        ]]]);
    }

    public function testUpdateWooDecision(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $wooDecision = WooDecisionFactory::createOne(
            [
                'departments' => [$department],
                'externalId' => $this->getFaker()->externalId(),
                'organisation' => $organisation,
                'previewDate' => $this->getFaker()->plainDate(),
                'status' => DossierStatus::CONCEPT,
            ],
        );
        WooDecisionMainDocumentFactory::createOne(['dossier' => $wooDecision]);
        WooDecisionAttachmentFactory::createOne(['dossier' => $wooDecision]);
        DocumentFactory::new()->withExternalId()->create(['dossiers' => [$wooDecision]]);

        self::assertDatabaseHas(
            WooDecision::class,
            [
                'title' => (string) $wooDecision->getTitle(),
                'summary' => $wooDecision->getSummary(),
            ],
        );

        $data = $this->createValidWooDecisionDataPayload($department);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $wooDecision), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(WooDecisionResource::class);

        self::assertDatabaseHas(
            WooDecision::class,
            [
                'dossierNumber' => $data['dossierNumber'],
                'documentPrefix' => $wooDecision->getDocumentPrefix(),
                'summary' => $data['summary'],
                'title' => $data['title'],
            ],
        );
    }

    public function testUpdateWooDecisionWithGetResponse(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $wooDecision = WooDecisionFactory::createOne([
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'previewDate' => $this->getFaker()->plainDate(),
        ]);
        WooDecisionMainDocumentFactory::createOne(['dossier' => $wooDecision]);
        WooDecisionAttachmentFactory::createOne([
            'dossier' => $wooDecision,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        $response = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $wooDecision))->toArray();

        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $wooDecision), ['json' => $response]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [[
            'propertyPath' => 'departmentId',
            'message' => 'This value should be of type Uuid.',
            'code' => Type::INVALID_TYPE_ERROR,
        ]]]);
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
                'externalId' => $this->getFaker()->externalId(),
                'organisation' => $organisation,
                'previewDate' => $this->getFaker()->plainDate(),
                'status' => DossierStatus::CONCEPT,
            ],
        );
        WooDecisionMainDocumentFactory::createOne(['dossier' => $wooDecision]);
        WooDecisionAttachmentFactory::createOne(['dossier' => $wooDecision]);

        self::assertDatabaseHas(
            WooDecision::class,
            [
                'title' => (string) $wooDecision->getTitle(),
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
                'title' => (string) $wooDecision->getTitle(),
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
            'exceeds max attachments per dossier' => [
                [
                    'attachments' => array_map(
                        static fn ($i) => [
                            'fileName' => sprintf('file%s.pdf', $i),
                            'formalDate' => CarbonImmutable::now()->format('Y-m-d'),
                            'type' => AttachmentType::ACCOUNTABILITY_REPORT->value,
                            'language' => AttachmentLanguage::ENG->value,
                            'externalId' => sprintf('external-id-%s', $i),
                        ],
                        range(1, AbstractAttachment::MAX_ATTACHMENTS_PER_DOSSIER + 1),
                    ),
                ],
                [
                    'code' => Count::TOO_MANY_ERROR,
                    'propertyPath' => 'attachments',
                ],
            ],
            'invalid document link format' => [
                [
                    'documents' => [
                        [
                            'inquiryNumbers' => [],
                            'documentDate' => '2025-01-01',
                            'documentId' => 'document-id-1',
                            'externalId' => 'document-external-id-1',
                            'familyId' => 333,
                            'fileName' => 'document.pdf',
                            'grounds' => [],
                            'isSuspended' => false,
                            'judgement' => Judgement::PUBLIC->value,
                            'links' => ['asasdfasdf'],
                            'publicationContext' => '2025-01',
                            'refersTo' => [],
                            'remark' => 'None',
                            'sourceType' => SourceType::PDF->value,
                            'threadId' => 12345,
                        ],
                    ],
                ],
                [
                    'propertyPath' => 'documents[0].links[0]',
                ],
            ],
        ];
    }

    public function testUpdateWooDecisionWithNonUniqueDossierNumberAndDocumentPrefix(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $documentPrefix = DocumentPrefixFactory::createOne(['organisation' => $organisation]);
        $existingWooDecision = WooDecisionFactory::createOne(
            [
                'departments' => [$department],
                'externalId' => $this->getFaker()->externalId(),
                'organisation' => $organisation,
                'previewDate' => $this->getFaker()->plainDate(),
                'documentPrefix' => $documentPrefix->getPrefix(),
            ],
        );

        $wooDecisionToUpdate = WooDecisionFactory::createOne(
            [
                'departments' => [$department],
                'externalId' => $this->getFaker()->externalId(),
                'organisation' => $organisation,
                'previewDate' => $this->getFaker()->plainDate(),
                'documentPrefix' => $documentPrefix->getPrefix(),
            ],
        );

        $data = $this->createValidWooDecisionDataPayload($department, $subject);
        $data['dossierNumber'] = $existingWooDecision->getDossierNumber();

        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $wooDecisionToUpdate), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @param array<string,mixed> $dossierPayloadOverride
     * @param array<string,mixed> $mainDocumentPayloadOverride
     * @param array<array-key,mixed> $attachmentPayloadOverride
     * @param array<array-key,mixed> $documentsPayloadOverride
     * @param array<string,mixed> $violations
     */
    #[DataProvider('updateWooDecisionNonConceptStateValidationDataProvider')]
    public function testUpdateWooDecisionWithNonConceptState(
        array $dossierPayloadOverride,
        array $mainDocumentPayloadOverride,
        array $attachmentPayloadOverride,
        array $documentsPayloadOverride,
        int $expectedStatusCode,
        array $violations,
    ): void {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);
        $wooDecision = WooDecisionFactory::createOne([
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'decision' => DecisionType::PUBLIC,
            'previewDate' => PlainDate::create('2025-01-01'),
            'publicationDate' => PlainDate::create('2025-01-01'),
            'status' => DossierStatus::SCHEDULED,
        ]);
        $mainDocument = WooDecisionMainDocumentFactory::createOne([
            'dossier' => $wooDecision,
            'grounds' => $this->getFaker()->groundsBetween(0, 3),
            'language' => AttachmentLanguage::NLD,
        ]);
        $attachment = WooDecisionAttachmentFactory::createOne([
            'dossier' => $wooDecision,
            'externalId' => $this->getFaker()->externalId(),
            'grounds' => $this->getFaker()->groundsBetween(0, 3),
            'language' => AttachmentLanguage::NLD,
        ]);
        $documentId = $this->getFaker()->documentId();
        $publicationContext = $this->getFaker()->publicationContext();
        $document = DocumentFactory::createOne([
            'dossiers' => [$wooDecision],
            'externalId' => $this->getFaker()->externalId(),
            'documentId' => $documentId,
            'documentNumber' => sprintf('%s-%s', $publicationContext, $documentId),
            'publicationContext' => $publicationContext,
            'judgement' => Judgement::PUBLIC,
        ]);

        $payload = array_merge([
            'title' => $wooDecision->getTitle()->toString(),
            'dossierNumber' => $wooDecision->getDossierNumber(),
            'dateFrom' => $wooDecision->getDateFrom()?->format('Y-m-d'),
            'dateTo' => $wooDecision->getDateTo()?->format('Y-m-d'),
            'decision' => $wooDecision->getDecision(),
            'reason' => $wooDecision->getPublicationReason(),
            'previewDate' => $wooDecision->getPreviewDate()?->format('Y-m-d'),
            'publicationDate' => $wooDecision->getPublicationDate()?->format('Y-m-d'),
            'summary' => $wooDecision->getSummary(),
            'departmentId' => $department->getId(),
            'subjectId' => $subject->getId(),
        ], $dossierPayloadOverride);

        $payload['mainDocument'] = array_merge([
            'fileName' => $mainDocument->getFileInfo()->getName(),
            'formalDate' => $mainDocument->getFormalDate()->format('Y-m-d'),
            'type' => $mainDocument->getType(),
            'language' => $mainDocument->getLanguage(),
            'grounds' => $mainDocument->getGrounds(),
        ], $mainDocumentPayloadOverride);
        $payload['attachments'] = [
            array_merge([
                'fileName' => $attachment->getFileInfo()->getName(),
                'formalDate' => $attachment->getFormalDate()->format('Y-m-d'),
                'type' => $attachment->getType(),
                'language' => $attachment->getLanguage(),
                'grounds' => $attachment->getGrounds(),
                'externalId' => $attachment->getExternalId()?->toString(),
            ], $attachmentPayloadOverride),
        ];
        $payload['documents'] = [
            array_merge([
                'inquiryNumbers' => [],
                'documentDate' => $this->getFaker()->date(),
                'documentId' => $document->getDocumentId()?->toString(),
                'externalId' => $document->getExternalId()?->toString(),
                'familyId' => $this->getFaker()->numberBetween(1, 1000),
                'fileName' => $document->getFileInfo()->getName(),
                'grounds' => $this->getFaker()->groundsBetween(0, 3),
                'isSuspended' => false,
                'judgement' => Judgement::PUBLIC,
                'links' => [],
                'publicationContext' => $document->getPublicationContext()?->toString(),
                'refersTo' => [],
                'remark' => $this->getFaker()->sentence(),
                'sourceType' => $this->getFaker()->randomElement(SourceType::cases()),
                'threadId' => $this->getFaker()->numberBetween(1, 1000),
            ], $documentsPayloadOverride),
        ];

        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $wooDecision), ['json' => $payload]);
        self::assertResponseStatusCodeSame($expectedStatusCode);

        if ($violations !== []) {
            self::assertJsonContains(['violations' => [$violations]]);
        }
    }

    /**
     * @return array<string,array<array-key,mixed>>
     */
    public static function updateWooDecisionNonConceptStateValidationDataProvider(): array
    {
        return [
            'no changes' => [[], [], [], [], Response::HTTP_OK, []],
            'changed title' => [['title' => 'my new title'], [], [], [], Response::HTTP_OK, []],
            'mainDocument language allowed' => [[], ['language' => AttachmentLanguage::BUL->value], [], [], Response::HTTP_OK, []],
            'attachment language allowed' => [[], [], ['language' => AttachmentLanguage::BUL->value], [], Response::HTTP_OK, []],

            'decision not allowed' => [
                ['decision' => DecisionType::ALREADY_PUBLIC],
                [],
                [],
                [],
                Response::HTTP_UNPROCESSABLE_ENTITY,
                [
                    'code' => Immutable::ERROR_CODE,
                    'propertyPath' => 'decision',
                ],
            ],
            'dossierNumber not allowed' => [
                ['dossierNumber' => 'new-dossier-number'],
                [],
                [],
                [],
                Response::HTTP_UNPROCESSABLE_ENTITY,
                [
                    'code' => Immutable::ERROR_CODE,
                    'propertyPath' => 'dossierNumber',
                ],
            ],
            'previewDate not allowed' => [
                ['previewDate' => '2020-01-01'],
                [],
                [],
                [],
                Response::HTTP_UNPROCESSABLE_ENTITY,
                [
                    'code' => Immutable::ERROR_CODE,
                    'propertyPath' => 'previewDate',
                ],
            ],
            'publicationDate not allowed' => [
                ['publicationDate' => '2020-01-01'],
                [],
                [],
                [],
                Response::HTTP_UNPROCESSABLE_ENTITY,
                [
                    'code' => Immutable::ERROR_CODE,
                    'propertyPath' => 'publicationDate',
                ],
            ],
            'document.documentId not allowed' => [
                [],
                [],
                [],
                ['documentId' => 'new-document-id'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
                [
                    'code' => Immutable::ERROR_CODE,
                    'propertyPath' => 'documents.[0].documentId',
                ],
            ],
            'document.publicationContext not allowed' => [
                [],
                [],
                [],
                ['publicationContext' => 'new-publication-context'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
                [
                    'code' => Immutable::ERROR_CODE,
                    'propertyPath' => 'documents.[0].publicationContext',
                ],
            ],
        ];
    }

    public function testUpdateWooDecisionWithNonConceptStateContainsEmptyDocuments(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);
        $wooDecision = WooDecisionFactory::createOne([
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'decision' => DecisionType::PUBLIC,
            'previewDate' => PlainDate::create('2025-01-01'),
            'publicationDate' => PlainDate::create('2025-01-01'),
            'status' => $this->getFaker()->randomElement(DossierStatus::nonConceptCases()),
        ]);
        $mainDocument = WooDecisionMainDocumentFactory::createOne([
            'dossier' => $wooDecision,
            'grounds' => $this->getFaker()->groundsBetween(0, 3),
            'language' => AttachmentLanguage::NLD,
        ]);
        $attachment = WooDecisionAttachmentFactory::createOne([
            'dossier' => $wooDecision,
            'externalId' => $this->getFaker()->externalId(),
            'grounds' => $this->getFaker()->groundsBetween(0, 3),
            'language' => AttachmentLanguage::NLD,
        ]);
        $documentId = $this->getFaker()->documentId();
        $publicationContext = $this->getFaker()->publicationContext();
        DocumentFactory::createOne([
            'dossiers' => [$wooDecision],
            'externalId' => $this->getFaker()->externalId(),
            'documentId' => $documentId,
            'documentNumber' => sprintf('%s-%s', $publicationContext, $documentId),
            'publicationContext' => $publicationContext,
            'judgement' => Judgement::PUBLIC,
        ]);

        $payload = [
            'title' => $wooDecision->getTitle()->toString(),
            'dossierNumber' => $wooDecision->getDossierNumber(),
            'dateFrom' => $wooDecision->getDateFrom()?->format('Y-m-d'),
            'dateTo' => $wooDecision->getDateTo()?->format('Y-m-d'),
            'decision' => $wooDecision->getDecision(),
            'reason' => $wooDecision->getPublicationReason(),
            'previewDate' => $wooDecision->getPreviewDate()?->format('Y-m-d'),
            'publicationDate' => $wooDecision->getPublicationDate()?->format('Y-m-d'),
            'summary' => $wooDecision->getSummary(),
            'departmentId' => $department->getId(),
            'subjectId' => $subject->getId(),
        ];

        $payload['mainDocument'] = [
            'fileName' => $mainDocument->getFileInfo()->getName(),
            'formalDate' => $mainDocument->getFormalDate()->format('Y-m-d'),
            'type' => $mainDocument->getType(),
            'language' => $mainDocument->getLanguage(),
            'grounds' => $mainDocument->getGrounds(),
        ];
        $payload['attachments'] = [
            [
                'fileName' => $attachment->getFileInfo()->getName(),
                'formalDate' => $attachment->getFormalDate()->format('Y-m-d'),
                'type' => $attachment->getType(),
                'language' => $attachment->getLanguage(),
                'grounds' => $attachment->getGrounds(),
                'externalId' => $attachment->getExternalId()?->toString(),
            ],
        ];
        $payload['documents'] = [];

        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $wooDecision), ['json' => $payload]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => []]);
    }

    public function testUpdateWooDecisionCannotDeleteAttachmentWhenNonConcept(): void
    {
        [$organisation, $wooDecision, $payload] = $this->createNonConceptWooDecisionWithValidPayload();
        $payload['attachments'] = [];

        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $wooDecision), ['json' => $payload]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [[
            'code' => ConstraintViolationBuilder::MODIFIED_SUB_ENTITY_ERROR,
            'propertyPath' => 'attachments',
        ]]]);
    }

    public function testUpdateWooDecisionCanAddAttachmentWhenNonConcept(): void
    {
        [$organisation, $wooDecision, $payload] = $this->createNonConceptWooDecisionWithValidPayload();
        $existingAttachments = $payload['attachments'];
        Assert::isArray($existingAttachments);
        $newAttachment = $this->createValidAttachmentsPayload(1, WooDecisionAttachment::getAllowedTypes())[0];
        $payload['attachments'] = [
            ...$existingAttachments,
            $newAttachment,
        ];

        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $wooDecision), ['json' => $payload]);
        self::assertResponseIsSuccessful();

        self::assertDatabaseHas(WooDecisionAttachment::class, [
            'dossier' => ['id' => $wooDecision->getId()],
            'externalId' => $newAttachment['externalId'],
        ]);
    }

    public function testUpdateWooDecisionCanAddDocumentWhenNonConcept(): void
    {
        [$organisation, $wooDecision, $payload] = $this->createNonConceptWooDecisionWithValidPayload();
        $existingDocuments = $payload['documents'];
        Assert::isArray($existingDocuments);
        $newDocument = $this->createDocumentDataPayload();
        $payload['documents'] = [
            ...$existingDocuments,
            $newDocument,
        ];

        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $wooDecision), ['json' => $payload]);
        self::assertResponseIsSuccessful();

        self::assertDatabaseHas(Document::class, [
            'externalId' => $newDocument['externalId'],
        ]);
    }

    public function testUpdateWooDecisionCannotDeleteDocumentWhenNonConcept(): void
    {
        [$organisation, $wooDecision, $payload] = $this->createNonConceptWooDecisionWithValidPayload();
        $documentId = $this->getFaker()->documentId();
        $publicationContext = $this->getFaker()->publicationContext();
        DocumentFactory::createOne([
            'dossiers' => [$wooDecision],
            'externalId' => $this->getFaker()->externalId(),
            'documentId' => $documentId,
            'documentNumber' => sprintf('%s-%s', $publicationContext, $documentId),
            'publicationContext' => $publicationContext,
            'judgement' => Judgement::PUBLIC,
        ]);
        $payload['documents'] = [];

        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $wooDecision), ['json' => $payload]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [[
            'code' => ConstraintViolationBuilder::MODIFIED_SUB_ENTITY_ERROR,
            'propertyPath' => 'documents',
        ]]]);
    }

    public function testUpdateWooDecisionWithExistingDocumentsExternalId(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $wooDecision = WooDecisionFactory::createOne(
            [
                'departments' => [$department],
                'externalId' => $this->getFaker()->externalId(),
                'organisation' => $organisation,
                'status' => DossierStatus::CONCEPT,
            ],
        );
        WooDecisionMainDocumentFactory::createOne(['dossier' => $wooDecision]);
        $wooDecisionDocument = DocumentFactory::createOne(
            [
                'documentNumber' => 'A',
                'dossiers' => [$wooDecision],
                'externalId' => $this->getFaker()->externalId(),
            ],
        );

        $newDocumentId = $this->getFaker()->unique()->documentId()->toString();

        $putData = $this->createValidWooDecisionDataPayload($department, null, 0, 0);

        $documentData = $this->createDocumentDataPayload();
        $documentData['documentId'] = $newDocumentId;
        $documentData['externalId'] = $wooDecisionDocument->getExternalId()?->toString();
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

    public function testUpdateWooDecisionWithConflictingDocumentNumberReturnsUnprocessableEntity(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        $wooDecision = WooDecisionFactory::createOne([
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
        ]);
        WooDecisionMainDocumentFactory::createOne(['dossier' => $wooDecision]);

        $publicationContext = '2025-01';

        DocumentFactory::createOne([
            'documentId' => DocumentId::create('123'),
            'documentNumber' => '2025-01-123',
        ]);

        $putData = $this->createValidWooDecisionDataPayload($department, $subject, 0, 0);
        $putData['documents'] = [array_merge($this->createDocumentDataPayload(), [
            'documentId' => '123',
            'publicationContext' => $publicationContext,
        ])];

        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $wooDecision), ['json' => $putData]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [[
            'code' => UniqueDocumentNumber::NOT_UNIQUE_ERROR,
            'propertyPath' => 'documents.[0].documentNumber',
        ]]]);
    }

    public function testUpdateWooDecisionWithSameDocumentDataMakesNoDocumentChanges(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $wooDecision = WooDecisionFactory::createOne(
            [
                'departments' => [$department],
                'externalId' => $this->getFaker()->externalId(),
                'organisation' => $organisation,
                'status' => DossierStatus::CONCEPT,
            ],
        );
        WooDecisionMainDocumentFactory::createOne(['dossier' => $wooDecision]);

        $documentId = $this->getFaker()->documentId();
        $publicationContext = $this->getFaker()->publicationContext();

        $wooDecisionDocument = DocumentFactory::createOne(
            [
                'dossiers' => [$wooDecision],
                'documentId' => $documentId,
                'documentNumber' => sprintf('%s-%s', $publicationContext, $documentId),
                'externalId' => $this->getFaker()->externalId(),
                'judgement' => Judgement::PUBLIC, // force isUploaded = true
                'publicationContext' => $publicationContext,
            ],
        );

        $putData = $this->createValidWooDecisionDataPayload($department, null, 0, 0);
        $putData['documents'] = [
            [
                'inquiryNumbers' => [],
                'documentDate' => $wooDecisionDocument->getDocumentDate()?->format('Y-m-d'),
                'documentId' => $wooDecisionDocument->getDocumentId()?->toString(),
                'externalId' => $wooDecisionDocument->getExternalId()?->toString(),
                'familyId' => $wooDecisionDocument->getFamilyId(),
                'fileName' => $wooDecisionDocument->getFileInfo()->getName(),
                'grounds' => $wooDecisionDocument->getGrounds(),
                'isSuspended' => $wooDecisionDocument->isSuspended(),
                'judgement' => $wooDecisionDocument->getJudgement()?->value,
                'links' => $wooDecisionDocument->getLinks(),
                'publicationContext' => $wooDecisionDocument->getPublicationContext()?->toString(),
                'refersTo' => $wooDecisionDocument->getRefersTo()->toArray(),
                'remark' => $wooDecisionDocument->getRemark(),
                'sourceType' => $wooDecisionDocument->getFileInfo()->getSourceType(),
                'threadId' => $wooDecisionDocument->getThreadId(),
            ],
        ];

        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $wooDecision), ['json' => $putData]);
        self::assertResponseIsSuccessful();

        self::assertDatabaseHas(WooDecision::class, [
            'id' => $wooDecision->getId(),
            'externalId' => $wooDecision->getExternalId(),
            'dossierNumber' => $putData['dossierNumber'],
        ]);
        self::assertDatabaseHas(Document::class, [
            'id' => $wooDecisionDocument->getId(),
            'documentId' => $wooDecisionDocument->getDocumentId(),
            'externalId' => $wooDecisionDocument->getExternalId(),
            'fileInfo.uploaded' => true,
        ]);
    }

    public function testUpdateWooDecisionDocumentForcesReUpload(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $wooDecision = WooDecisionFactory::createOne(
            [
                'departments' => [$department],
                'externalId' => $this->getFaker()->externalId(),
                'organisation' => $organisation,
                'status' => DossierStatus::CONCEPT,
            ],
        );
        WooDecisionMainDocumentFactory::createOne(['dossier' => $wooDecision]);
        $wooDecisionDocument = DocumentFactory::createOne(
            [
                'dossiers' => [$wooDecision],
                'externalId' => $this->getFaker()->externalId(),
                'judgement' => Judgement::PUBLIC, // force isUploaded = true
                'publicationContext' => $this->getFaker()->publicationContext(),
            ],
        );

        $putData = $this->createValidWooDecisionDataPayload($department, null, 0, 0);
        $putData['documents'] = [
            [
                'inquiryNumbers' => [],
                'documentDate' => $wooDecisionDocument->getDocumentDate()?->addDays(1)->format('Y-m-d'),
                'documentId' => $wooDecisionDocument->getDocumentId()?->toString(),
                'externalId' => $wooDecisionDocument->getExternalId()?->toString(),
                'familyId' => $wooDecisionDocument->getFamilyId(),
                'fileName' => $wooDecisionDocument->getFileInfo()->getName(),
                'grounds' => $wooDecisionDocument->getGrounds(),
                'isSuspended' => $wooDecisionDocument->isSuspended(),
                'judgement' => $wooDecisionDocument->getJudgement()?->value,
                'links' => $wooDecisionDocument->getLinks(),
                'publicationContext' => $wooDecisionDocument->getPublicationContext()?->toString(),
                'refersTo' => $wooDecisionDocument->getRefersTo()->toArray(),
                'remark' => $wooDecisionDocument->getRemark(),
                'sourceType' => $wooDecisionDocument->getFileInfo()->getSourceType(),
                'threadId' => $wooDecisionDocument->getThreadId(),
            ],
        ];
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $wooDecision), ['json' => $putData]);

        // assert upload-status
        self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation));
        self::assertJsonContains(['items' => [['documents' => [0 => ['uploadStatus' => UploadStatus::UPLOAD_REQUIRED->value]]]]]);
    }

    public function testUpdateWooDecisionWithDifferentDocumentDataSetsUploadedToFalse(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $wooDecision = WooDecisionFactory::createOne([
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
        ]);
        WooDecisionMainDocumentFactory::createOne(['dossier' => $wooDecision]);
        $wooDecisionDocument = DocumentFactory::createOne([
            'documentNumber' => 'A',
            'dossiers' => [$wooDecision],
            'externalId' => $this->getFaker()->externalId(),
            'judgement' => Judgement::PUBLIC, // force isUploaded = true
            'publicationContext' => $this->getFaker()->publicationContext(),
        ]);

        $newDocumentId = 'this.document.id.update.should.require.new.file.upload';

        $putData = $this->createValidWooDecisionDataPayload($department, null, 0, 0);
        $putData['documents'] = [
            [
                'inquiryNumbers' => [],
                'documentDate' => $wooDecisionDocument->getDocumentDate()?->format('Y-m-d'),
                'documentId' => $newDocumentId,
                'externalId' => $wooDecisionDocument->getExternalId()?->toString(),
                'familyId' => $wooDecisionDocument->getFamilyId(),
                'fileName' => $wooDecisionDocument->getFileInfo()->getName(),
                'grounds' => $wooDecisionDocument->getGrounds(),
                'isSuspended' => $wooDecisionDocument->isSuspended(),
                'judgement' => $wooDecisionDocument->getJudgement()?->value,
                'links' => $wooDecisionDocument->getLinks(),
                'publicationContext' => $wooDecisionDocument->getPublicationContext()?->toString(),
                'refersTo' => $wooDecisionDocument->getRefersTo()->toArray(),
                'remark' => $wooDecisionDocument->getRemark(),
                'sourceType' => SourceType::VIDEO->value,
                'threadId' => $wooDecisionDocument->getThreadId(),
            ],
        ];

        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $wooDecision), ['json' => $putData]);
        self::assertResponseIsSuccessful();

        self::assertDatabaseHas(WooDecision::class, [
            'id' => $wooDecision->getId(),
            'externalId' => $wooDecision->getExternalId(),
            'dossierNumber' => $putData['dossierNumber'],
        ]);
        self::assertDatabaseHas(Document::class, [
            'id' => $wooDecisionDocument->getId(),
            'documentId' => $newDocumentId,
            'externalId' => $wooDecisionDocument->getExternalId(),
            'fileInfo.uploaded' => false,
        ]);
    }

    public function testUpdateWooDecisionWithSameAttachmentsMetadataIsIgnored(): void
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
            'externalId' => $this->getFaker()->externalId(),
        ]);

        $data = [
            'title' => (string) $wooDecision->getTitle(),
            'dossierNumber' => $wooDecision->getDossierNumber(),
            'dateFrom' => $wooDecision->getDateFrom()?->format('Y-m-d'),
            'dateTo' => $wooDecision->getDateFrom()?->format('Y-m-d'),
            'decision' => $wooDecision->getDecision()?->value,
            'reason' => $wooDecision->getPublicationReason(),
            'previewDate' => $this->getFaker()->plainDate()->format('Y-m-d'),
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
                    'externalId' => $attachment->getExternalId()?->toString(),
                ],
            ],
        ];
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $wooDecision), ['json' => $data]);
        self::assertResponseIsSuccessful();

        self::assertDatabaseCount(WooDecisionAttachment::class, 1);
        self::assertDatabaseHas(WooDecisionAttachment::class, [
            'id' => $attachment->getId(),
            'dossier' => ['id' => $wooDecision->getId()],
        ]);
    }

    public function testUpdateWooDecisionWithChangedAttachmentsMetadataIsUpdated(): void
    {
        $changedFileName = 'new-file.pdf';

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
            'externalId' => $this->getFaker()->externalId(),
        ]);

        $data = [
            'title' => (string) $wooDecision->getTitle(),
            'dossierNumber' => $wooDecision->getDossierNumber(),
            'dateFrom' => $wooDecision->getDateFrom()?->format('Y-m-d'),
            'dateTo' => $wooDecision->getDateFrom()?->format('Y-m-d'),
            'decision' => $wooDecision->getDecision()?->value,
            'reason' => $wooDecision->getPublicationReason(),
            'previewDate' => $this->getFaker()->plainDate()->format('Y-m-d'),
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
                    'fileName' => $changedFileName,
                    'formalDate' => $attachment->getFormalDate()->format('Y-m-d'),
                    'language' => $attachment->getLanguage(),
                    'type' => $attachment->getType(),
                    'externalId' => $attachment->getExternalId()?->toString(),
                ],
            ],
        ];
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $wooDecision), ['json' => $data]);
        self::assertResponseIsSuccessful();

        self::assertDatabaseCount(WooDecisionAttachment::class, 1);
        self::assertDatabaseHas(WooDecisionAttachment::class, [
            'id' => $attachment->getId(),
            'dossier' => ['id' => $wooDecision->getId()],
            'fileInfo.name' => $changedFileName,
        ]);
    }

    public function testUpdateWooDecisionWithOneNewAttachmentMetadataAndOneExistingMetadataIsPartiallyUpdated(): void
    {
        $changedFileName = 'new-file.pdf';
        $newAttachmentExternalId = $this->getFaker()->externalId();

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
        $attachment1 = WooDecisionAttachmentFactory::createOne([
            'dossier' => $wooDecision,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        $data = [
            'title' => (string) $wooDecision->getTitle(),
            'dossierNumber' => $wooDecision->getDossierNumber(),
            'dateFrom' => $wooDecision->getDateFrom()?->format('Y-m-d'),
            'dateTo' => $wooDecision->getDateFrom()?->format('Y-m-d'),
            'decision' => $wooDecision->getDecision()?->value,
            'reason' => $wooDecision->getPublicationReason(),
            'previewDate' => $this->getFaker()->plainDate()->format('Y-m-d'),
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
                    'fileName' => $attachment1->getFileInfo()->getName(),
                    'formalDate' => $attachment1->getFormalDate()->format('Y-m-d'),
                    'language' => $attachment1->getLanguage(),
                    'type' => $attachment1->getType(),
                    'externalId' => $attachment1->getExternalId()?->toString(),
                ],
                [
                    'fileName' => $changedFileName,
                    'formalDate' => $attachment1->getFormalDate()->format('Y-m-d'),
                    'language' => $attachment1->getLanguage(),
                    'type' => $attachment1->getType(),
                    'externalId' => $newAttachmentExternalId->toString(),
                ],
            ],
        ];

        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $wooDecision), ['json' => $data]);
        self::assertResponseIsSuccessful();

        self::assertDatabaseCount(WooDecisionAttachment::class, 2);

        self::assertDatabaseHas(WooDecisionAttachment::class, [
            'id' => $attachment1->getId(),
            'dossier' => ['id' => $wooDecision->getId()],
            'fileInfo.name' => $attachment1->getFileInfo()->getName(),
        ]);

        self::assertDatabaseHas(WooDecisionAttachment::class, [
            'externalId' => $newAttachmentExternalId,
            'dossier' => ['id' => $wooDecision->getId()],
            'fileInfo.name' => $changedFileName,
        ]);
    }

    public function testUpdateWooDecisionWithLessAttachmentMetadataAndOneExistingMetadataIsPartiallyDeleted(): void
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
        $attachment1 = WooDecisionAttachmentFactory::createOne([
            'dossier' => $wooDecision,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        $attachment2 = WooDecisionAttachmentFactory::createOne([
            'dossier' => $wooDecision,
            'externalId' => $this->getFaker()->externalId(),
        ]);

        $data = [
            'title' => (string) $wooDecision->getTitle(),
            'dossierNumber' => $wooDecision->getDossierNumber(),
            'dateFrom' => $wooDecision->getDateFrom()?->format('Y-m-d'),
            'dateTo' => $wooDecision->getDateFrom()?->format('Y-m-d'),
            'decision' => $wooDecision->getDecision()?->value,
            'reason' => $wooDecision->getPublicationReason(),
            'previewDate' => $this->getFaker()->plainDate()->format('Y-m-d'),
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
                    'fileName' => $attachment1->getFileInfo()->getName(),
                    'formalDate' => $attachment1->getFormalDate()->format('Y-m-d'),
                    'language' => $attachment1->getLanguage(),
                    'type' => $attachment1->getType(),
                    'externalId' => $attachment1->getExternalId()?->toString(),
                ],
            ],
        ];

        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $wooDecision), ['json' => $data]);
        self::assertResponseIsSuccessful();

        self::assertDatabaseCount(WooDecisionAttachment::class, 1);

        self::assertDatabaseHas(WooDecisionAttachment::class, [
            'id' => $attachment1->getId(),
            'dossier' => ['id' => $wooDecision->getId()],
            'fileInfo.name' => $attachment1->getFileInfo()->getName(),
        ]);

        self::assertDatabaseMissing(WooDecisionAttachment::class, [
            'id' => $attachment2->getId(),
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
        $payload = [
            'title' => $this->getFaker()->sentence(),
            'dossierNumber' => $this->getFaker()->slug(2),
            'dateFrom' => $this->getFaker()->dateTimeBetween('-3 weeks', '-2 week')->format('Y-m-d'),
            'dateTo' => $this->getFaker()->dateTimeBetween('-1 week', 'now')->format('Y-m-d'),
            'decision' => $this->getFaker()->randomElement(DecisionType::cases()),
            'reason' => $this->getFaker()->randomElement(PublicationReason::cases()),
            'previewDate' => $this->getFaker()->plainDateBetween('1 week', '2 weeks')->format('Y-m-d'),
            'publicationDate' => $this->getFaker()->plainDateBetween('2 weeks', '3 weeks')->format('Y-m-d'),
            'summary' => $this->getFaker()->sentence(),
            'departmentId' => $department->getId(),
            'subjectId' => $subject?->getId(),
            'mainDocument' => [
                'fileName' => $this->getFaker()->fileNameForGroup(UploadGroupId::MAIN_DOCUMENTS)->toString(),
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
            'inquiryNumbers' => [],
            'documentDate' => $this->getFaker()->date(),
            'documentId' => $this->getFaker()->unique()->documentId()->toString(),
            'externalId' => $this->getFaker()->externalId()->toString(),
            'familyId' => $this->getFaker()->numberBetween(1, 1000),
            'fileName' => $this->getFaker()->fileNameForGroup(UploadGroupId::API_WOO_DECISION_DOCUMENTS)->toString(),
            'grounds' => $this->getFaker()->groundsBetween(0, 3),
            'isSuspended' => false,
            'judgement' => Judgement::PUBLIC,
            'links' => [],
            'publicationContext' => $this->getFaker()->publicationContext()->toString(),
            'refersTo' => [],
            'remark' => $this->getFaker()->sentence(),
            'sourceType' => $this->getFaker()->randomElement(SourceType::cases()),
            'threadId' => $this->getFaker()->numberBetween(1, 1000),
        ];
    }

    /**
     * @param array<string, array<array-key, mixed>> $dataOverrides
     * @param array<string, mixed> $expectedSubset
     *
     * Pattern A fix (500→422): https://github.com/minvws/nl-rdo-woo-web-private/issues/6923
     * Pattern B (data persists despite 422): https://github.com/minvws/nl-rdo-woo-web-private/issues/7043
     */
    #[DataProvider('createWooDecisionRefersToValidationDataProvider')]
    public function testCreateWooDecisionWithInvalidRefersTo(array $dataOverrides, array $expectedSubset): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        $data = array_merge($this->createValidWooDecisionDataPayload($department, $subject, 1, 1), $dataOverrides);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains($expectedSubset);

        // Make sure no data inconsistency entity persists despite 422
        self::assertDatabaseCount(WooDecision::class, 0);
        self::assertDatabaseCount(WooDecisionMainDocument::class, 0);
        self::assertDatabaseCount(WooDecisionAttachment::class, 0);
        self::assertDatabaseCount(Document::class, 0);
    }

    /**
     * @return array<string, array{array<string, array<array-key, mixed>>, array<string, mixed>}>
     */
    public static function createWooDecisionRefersToValidationDataProvider(): array
    {
        return [
            'refersTo with empty externalId' => [
                [
                    'documents' => [
                        [
                            'inquiryNumbers' => [],
                            'documentDate' => '2025-09-17',
                            'documentId' => '7d54bd0f',
                            'externalId' => 'd3147b92-f6a3-3c78-91bc-627f252fc07e',
                            'familyId' => 838,
                            'fileName' => 'document.pdf',
                            'grounds' => [],
                            'isSuspended' => false,
                            'judgement' => Judgement::PUBLIC->value,
                            'links' => [],
                            'publicationContext' => 'sint',
                            'refersTo' => [''],
                            'remark' => null,
                            'sourceType' => SourceType::VIDEO->value,
                            'threadId' => null,
                        ],
                    ],
                ],
                [
                    'violations' => [
                        [
                            'propertyPath' => 'documents[0].refersTo[0]',
                            'message' => 'Invalid external id length',
                        ],
                    ],
                ],
            ],
            'refersTo with too long externalId (129 chars)' => [
                [
                    'documents' => [
                        [
                            'inquiryNumbers' => [],
                            'documentDate' => '2025-09-17',
                            'documentId' => '7d54bd0f',
                            'externalId' => 'd3147b92-f6a3-3c78-91bc-627f252fc07e',
                            'familyId' => 838,
                            'fileName' => 'document.pdf',
                            'grounds' => [],
                            'isSuspended' => false,
                            'judgement' => Judgement::PUBLIC->value,
                            'links' => [],
                            'publicationContext' => 'sint',
                            'refersTo' => [str_repeat('x', 129)],
                            'remark' => null,
                            'sourceType' => SourceType::VIDEO->value,
                            'threadId' => null,
                        ],
                    ],
                ],
                [
                    'violations' => [
                        [
                            'propertyPath' => 'documents[0].refersTo[0]',
                            'message' => 'Invalid external id length',
                        ],
                    ],
                ],
            ],
            // Bug replicator: https://github.com/minvws/nl-rdo-woo-web-private/issues/7053
            'refersTo with non-existent externalId' => [
                [
                    'documents' => [
                        [
                            'inquiryNumbers' => [],
                            'documentDate' => '2025-09-17',
                            'documentId' => '7d54bd0f',
                            'externalId' => 'd3147b92-f6a3-3c78-91bc-627f252fc07e',
                            'familyId' => 838,
                            'fileName' => 'document.pdf',
                            'grounds' => [],
                            'isSuspended' => false,
                            'judgement' => Judgement::PUBLIC->value,
                            'links' => [],
                            'publicationContext' => 'sint',
                            'refersTo' => ['non-existent-document-id'],
                            'remark' => null,
                            'sourceType' => SourceType::VIDEO->value,
                            'threadId' => null,
                        ],
                    ],
                ],
                [
                    'violations' => [
                        [
                            'propertyPath' => 'documents[0].refersTo[0]',
                            'message' => 'The referenced document could not be found',
                        ],
                    ],
                ],
            ],
            'refersTo with two non-existent externalIds' => [
                [
                    'documents' => [
                        [
                            'inquiryNumbers' => [],
                            'documentDate' => '2025-09-17',
                            'documentId' => '7d54bd0f',
                            'externalId' => 'd3147b92-f6a3-3c78-91bc-627f252fc07e',
                            'familyId' => 838,
                            'fileName' => 'document.pdf',
                            'grounds' => [],
                            'isSuspended' => false,
                            'judgement' => Judgement::PUBLIC->value,
                            'links' => [],
                            'publicationContext' => 'sint',
                            'refersTo' => ['non-existent-document-id-1', 'non-existent-document-id-2'],
                            'remark' => null,
                            'sourceType' => SourceType::VIDEO->value,
                            'threadId' => null,
                        ],
                    ],
                ],
                [
                    'violations' => [
                        [
                            'propertyPath' => 'documents[0].refersTo[0]',
                            'message' => 'The referenced document could not be found',
                        ],
                        [
                            'propertyPath' => 'documents[0].refersTo[1]',
                            'message' => 'The referenced document could not be found',
                        ],
                    ],
                ],
            ],
        ];
    }

    public function testCreateWooDecisionWithAlreadyPublicDocument(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);

        $putData = $this->createValidWooDecisionDataPayload($department, $subject, 0, 0);

        $documentData = $this->createDocumentDataPayload();
        $documentData['judgement'] = Judgement::ALREADY_PUBLIC->value;
        $documentData['isSuspended'] = false;
        $documentData['links'] = ['http://dummy.domain.com/path'];

        $putData['documents'] = [$documentData];

        $response = $this->createPublicationApiRequest(
            Request::METHOD_PUT,
            $this->buildUrl($organisation, $this->getFaker()->slug(1)),
            ['json' => $putData],
        );
        self::assertResponseIsSuccessful();

        Assert::string($documentData['externalId']);
        $documentEntity = $this->getEntity(
            Document::class,
            ['externalId' => ExternalId::create($documentData['externalId'])],
        );
        Assert::notNull($documentEntity);

        $expectedDocumentResponseData = [
            'inquiryNumbers' => [],
            'documentDate' => $documentData['documentDate'],
            'documentId' => $documentData['documentId'],
            'externalId' => $documentData['externalId'],
            'familyId' => $documentData['familyId'],
            'grounds' => $documentData['grounds'],
            'isSuspended' => $documentData['isSuspended'],
            'judgement' => $documentData['judgement'],
            'links' => $documentData['links'],
            'refersTo' => $documentData['refersTo'],
            'remark' => $documentData['remark'],
            'threadId' => $documentData['threadId'],
            'uploadStatus' => UploadStatus::NO_UPLOAD_REQUIRED->value,
            'isUploaded' => false,
            'isWithdrawn' => false,
            'documentNumber' => $documentEntity->getDocumentNumber(),
            'fileName' => $documentData['fileName'],
            'sourceType' => $documentEntity->getFileInfo()?->getSourceType()?->value,
            '_links' => [],
        ];

        $responseData = $response->toArray();
        Assert::isArray($responseData);
        Assert::keyExists($responseData, 'documents');
        Assert::isArray($responseData['documents']);
        Assert::keyExists($responseData['documents'], 0);

        self::assertEquals(
            $expectedDocumentResponseData,
            $responseData['documents'][0],
        );
    }

    /**
     * @return array{0: Organisation, 1: WooDecision, 2: array<string, mixed>}
     */
    private function createNonConceptWooDecisionWithValidPayload(?DossierStatus $status = null): array
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        DocumentPrefixFactory::createOne(['organisation' => $organisation]);
        $wooDecision = WooDecisionFactory::createOne([
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'decision' => DecisionType::PUBLIC,
            'previewDate' => PlainDate::create('2025-01-01'),
            'publicationDate' => PlainDate::create('2025-01-01'),
            'status' => $status ?? $this->getFaker()->randomElement(DossierStatus::nonConceptCases()),
        ]);
        $mainDocument = WooDecisionMainDocumentFactory::createOne([
            'dossier' => $wooDecision,
            'grounds' => $this->getFaker()->groundsBetween(0, 3),
            'language' => AttachmentLanguage::NLD,
        ]);
        $attachment = WooDecisionAttachmentFactory::createOne([
            'dossier' => $wooDecision,
            'externalId' => $this->getFaker()->externalId(),
            'grounds' => $this->getFaker()->groundsBetween(0, 3),
            'language' => AttachmentLanguage::NLD,
        ]);

        $payload = [
            'title' => $wooDecision->getTitle()->toString(),
            'dossierNumber' => $wooDecision->getDossierNumber(),
            'dateFrom' => $wooDecision->getDateFrom()?->format('Y-m-d'),
            'dateTo' => $wooDecision->getDateTo()?->format('Y-m-d'),
            'decision' => $wooDecision->getDecision(),
            'reason' => $wooDecision->getPublicationReason(),
            'previewDate' => $wooDecision->getPreviewDate()?->format('Y-m-d'),
            'publicationDate' => $wooDecision->getPublicationDate()?->format('Y-m-d'),
            'summary' => $wooDecision->getSummary(),
            'departmentId' => $department->getId(),
            'subjectId' => $subject->getId(),
            'mainDocument' => [
                'fileName' => $mainDocument->getFileInfo()->getName(),
                'formalDate' => $mainDocument->getFormalDate()->format('Y-m-d'),
                'type' => $mainDocument->getType(),
                'language' => $mainDocument->getLanguage(),
                'grounds' => $mainDocument->getGrounds(),
            ],
            'attachments' => [
                [
                    'fileName' => $attachment->getFileInfo()->getName(),
                    'formalDate' => $attachment->getFormalDate()->format('Y-m-d'),
                    'type' => $attachment->getType(),
                    'language' => $attachment->getLanguage(),
                    'grounds' => $attachment->getGrounds(),
                    'externalId' => $attachment->getExternalId()?->toString(),
                ],
            ],
            'documents' => [],
        ];

        return [$organisation, $wooDecision, $payload];
    }

    /**
     * Regression test: when a WooDecision is exactly published, incoming publicationDate and
     * previewDate changes must be silently ignored by the mapper. Other fields (e.g. title) are
     * still applied.
     */
    public function testUpdatePublishedWooDecisionIgnoresPublicationDateAndPreviewDateChanges(): void
    {
        [$organisation, $wooDecision, $payload] = $this->createNonConceptWooDecisionWithValidPayload(DossierStatus::PUBLISHED);

        $originalPublicationDate = $wooDecision->getPublicationDate()?->format('Y-m-d');
        $originalPreviewDate = $wooDecision->getPreviewDate()?->format('Y-m-d');
        $newTitle = 'Updated title for published dossier';

        // Send different dates and a changed title
        $payload['publicationDate'] = '2026-06-01';
        $payload['previewDate'] = '2026-06-01';
        $payload['title'] = $newTitle;

        $response = self::createPublicationApiRequest(
            Request::METHOD_PUT,
            $this->buildUrl($organisation, $wooDecision),
            ['json' => $payload],
        );

        // Expect success: published dossier ignores date changes instead of returning 422
        self::assertResponseIsSuccessful();

        $data = $response->toArray();

        // Dates must remain at original values (mapper must not apply the incoming changes)
        self::assertSame($originalPublicationDate, $data['publicationDate']);
        self::assertSame($originalPreviewDate, $data['previewDate']);

        // Other editable fields should change
        self::assertSame($newTitle, $data['title']);
    }
}
