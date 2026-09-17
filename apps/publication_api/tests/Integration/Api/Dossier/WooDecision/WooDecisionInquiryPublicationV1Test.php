<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Dossier\WooDecision;

use PublicationApi\Api\Dossier\WooDecision\WooDecisionResource;
use PublicationApi\Tests\Integration\Api\Dossier\ApiPublicationV1DossierTestCase;
use Shared\Controller\Public\Dossier\WooDecision\InquiryController;
use Shared\Domain\Department\Department;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Attachment\WooDecisionAttachment;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Decision\DecisionType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Domain\Publication\Dossier\Type\WooDecision\MainDocument\WooDecisionMainDocument;
use Shared\Domain\Publication\Dossier\Type\WooDecision\PublicationReason;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\PublicUrlGenerator;
use Shared\Domain\Publication\SourceType;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\DocumentFactory;
use Shared\Tests\Factory\InquiryFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionAttachmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionMainDocumentFactory;
use Shared\Tests\Factory\Publication\Subject\SubjectFactory;
use Shared\ValueObject\ExternalId;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Webmozart\Assert\Assert;

use function array_column;
use function array_filter;
use function array_map;
use function array_shift;
use function array_values;
use function range;
use function sprintf;

final class WooDecisionInquiryPublicationV1Test extends ApiPublicationV1DossierTestCase
{
    public function getDossierApiUriSegment(): string
    {
        return 'woo-decision';
    }

    public function testCreateWooDecisionWithDocumentsAttachedToInquiries(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();

        self::assertDatabaseCount(WooDecision::class, 0);

        $documents = $this->createValidDocumentsPayload(7, [
            ['C-1', 'C-2'], // external-document-id-0
            ['C-3'], // external-document-id-1
            [], // external-document-id-2
            ['C-1', 'C-2', 'C-3'], // external-document-id-3
            ['C-1', 'C-4', 'C-5', 'C-6'], // external-document-id-4
        ]);

        $data = $this->createValidWooDecisionDataPayload($department, $subject, $documents);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(WooDecisionResource::class);

        self::assertDatabaseCount(WooDecision::class, 1);
        self::assertDatabaseCount(Inquiry::class, 6);

        $inquiryC1 = $this->getEntity(Inquiry::class, ['inquiryNumber' => 'C-1']);
        self::assertNotNull($inquiryC1);
        self::assertEqualsCanonicalizing(
            [
                'external-document-id-0',
                'external-document-id-3',
                'external-document-id-4',
            ],
            $this->getDocumentExternalIds($inquiryC1->getDocuments()->toArray()),
        );

        $inquiryC2 = $this->getEntity(Inquiry::class, ['inquiryNumber' => 'C-2']);
        self::assertNotNull($inquiryC2);
        self::assertEqualsCanonicalizing(
            [
                'external-document-id-0',
                'external-document-id-3',
            ],
            $this->getDocumentExternalIds($inquiryC2->getDocuments()->toArray()),
        );

        $inquiryC3 = $this->getEntity(Inquiry::class, ['inquiryNumber' => 'C-3']);
        self::assertNotNull($inquiryC3);
        self::assertEqualsCanonicalizing(
            [
                'external-document-id-1',
                'external-document-id-3',
            ],
            $this->getDocumentExternalIds($inquiryC3->getDocuments()->toArray()),
        );

        $inquiryC4 = $this->getEntity(Inquiry::class, ['inquiryNumber' => 'C-4']);
        self::assertNotNull($inquiryC4);
        self::assertEqualsCanonicalizing(
            [
                'external-document-id-4',
            ],
            $this->getDocumentExternalIds($inquiryC4->getDocuments()->toArray()),
        );

        $inquiryC5 = $this->getEntity(Inquiry::class, ['inquiryNumber' => 'C-5']);
        self::assertNotNull($inquiryC5);
        self::assertEqualsCanonicalizing(
            [
                'external-document-id-4',
            ],
            $this->getDocumentExternalIds($inquiryC5->getDocuments()->toArray()),
        );

        $inquiryC6 = $this->getEntity(Inquiry::class, ['inquiryNumber' => 'C-6']);
        self::assertNotNull($inquiryC6);
        self::assertEqualsCanonicalizing(
            [
                'external-document-id-4',
            ],
            $this->getDocumentExternalIds($inquiryC6->getDocuments()->toArray()),
        );
    }

    public function testUpdatingWooDecisionWithDocumentsAttachedToInquiries(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $wooDecision = WooDecisionFactory::createOne([
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'previewDate' => $this->getFaker()->plainDate(),
            'status' => DossierStatus::CONCEPT,
        ]);
        WooDecisionMainDocumentFactory::createOne(['dossier' => $wooDecision]);
        WooDecisionAttachmentFactory::createOne(['dossier' => $wooDecision]);

        $documents = DocumentFactory::new()
            ->sequence(static function () {
                foreach (range(0, 2) as $i) {
                    yield ['externalId' => ExternalId::create(sprintf('external-document-id-%d', $i))];
                }
            })
            ->create([
                'dossiers' => [$wooDecision],
            ]);

        $inquiry = InquiryFactory::createOne([
            'inquiryNumber' => 'C-1',
            'organisation' => $organisation,
            'documents' => $documents,
            'dossiers' => [],
        ]);

        self::assertDatabaseCount(WooDecision::class, 1);
        self::assertDatabaseCount(Inquiry::class, 1);
        self::assertCount(3, $inquiry->getDocuments());

        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $documents = $this->createValidDocumentsPayload(7, [
            ['C-1', 'C-2'], // external-document-id-0
            ['C-2', 'C-3'], // external-document-id-1
            [], // external-document-id-2
        ]);

        $data = $this->createValidWooDecisionDataPayload($department, $subject, $documents);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $wooDecision), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(WooDecisionResource::class);

        self::assertDatabaseCount(WooDecision::class, 1);
        self::assertDatabaseCount(Inquiry::class, 3);

        $inquiry = $this->getEntity(Inquiry::class, ['inquiryNumber' => 'C-1']);
        self::assertNotNull($inquiry);

        $inquiryC1 = $this->getEntity(Inquiry::class, ['inquiryNumber' => 'C-1']);
        self::assertNotNull($inquiryC1);
        self::assertEqualsCanonicalizing(
            ['external-document-id-0'],
            $this->getDocumentExternalIds($inquiryC1->getDocuments()->toArray()),
            'Case C1: Mismatched document external IDs',
        );
        self::assertSame($inquiry->getId(), $inquiryC1->getId());

        $inquiryC2 = $this->getEntity(Inquiry::class, ['inquiryNumber' => 'C-2']);
        self::assertNotNull($inquiryC2);
        self::assertEqualsCanonicalizing(
            [
                'external-document-id-0',
                'external-document-id-1',
            ],
            $this->getDocumentExternalIds($inquiryC2->getDocuments()->toArray()),
            'Case C2: Mismatched document external IDs',
        );
        $inquiryC3 = $this->getEntity(Inquiry::class, ['inquiryNumber' => 'C-3']);
        self::assertNotNull($inquiryC3);
        self::assertEqualsCanonicalizing(
            ['external-document-id-1'],
            $this->getDocumentExternalIds($inquiryC3->getDocuments()->toArray()),
            'Case C3: Mismatched document external IDs',
        );
    }

    public function testUpdatingWooDecisionWithDocumentsAttachedToInquiriesDoesNotInfluenceOtherDossierDocuments(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();

        // Dossier A
        $wooDecisionA = WooDecisionFactory::createOne([
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'previewDate' => $this->getFaker()->plainDate(),
            'status' => DossierStatus::CONCEPT,
        ]);
        WooDecisionMainDocumentFactory::createOne(['dossier' => $wooDecisionA]);
        WooDecisionAttachmentFactory::createOne(['dossier' => $wooDecisionA]);

        $documentsA = DocumentFactory::new()
            ->sequence(static function () {
                foreach (range(0, 1) as $i) {
                    yield ['externalId' => ExternalId::create(sprintf('external-document-id-A-%d', $i))];
                }
            })
            ->create([
                'dossiers' => [$wooDecisionA],
            ]);

        // Dossier B
        $wooDecisionB = WooDecisionFactory::createOne([
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'previewDate' => $this->getFaker()->plainDate(),
            'status' => DossierStatus::CONCEPT,
        ]);
        WooDecisionMainDocumentFactory::createOne(['dossier' => $wooDecisionB]);
        WooDecisionAttachmentFactory::createOne(['dossier' => $wooDecisionB]);

        $documentsB = DocumentFactory::new()
            ->sequence(static function () {
                foreach (range(0, 1) as $i) {
                    yield ['externalId' => ExternalId::create(sprintf('external-document-id-B-%d', $i))];
                }
            })
            ->create([
                'dossiers' => [$wooDecisionB],
            ]);

        // Inquiries
        $inquiryOne = InquiryFactory::createOne([
            'inquiryNumber' => 'C-1',
            'organisation' => $organisation,
            'documents' => [
                $documentsA[0],
                $documentsA[1],
                $documentsB[0],
            ],
            'dossiers' => [],
        ]);

        $inquiryTwo = InquiryFactory::createOne([
            'inquiryNumber' => 'C-2',
            'organisation' => $organisation,
            'documents' => [
                $documentsA[1],
                $documentsB[0],
                $documentsB[1],
            ],
            'dossiers' => [],
        ]);

        self::assertDatabaseCount(WooDecision::class, 2);
        self::assertDatabaseCount(Inquiry::class, 2);
        self::assertCount(3, $inquiryOne->getDocuments());
        self::assertCount(3, $inquiryTwo->getDocuments());

        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $documents = $this->createValidDocumentsPayload(2, [
            ['C-2'], // external-document-id-B-0
            ['C-1', 'C-2'], // external-document-id-B-1
        ], 'external-document-id-B');

        $data = $this->createValidWooDecisionDataPayload($department, $subject, $documents);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $wooDecisionB), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(WooDecisionResource::class);

        self::assertDatabaseCount(WooDecision::class, 2);
        self::assertDatabaseCount(Inquiry::class, 2);

        $inquiryC1 = $this->getEntity(Inquiry::class, ['inquiryNumber' => 'C-1']);
        self::assertNotNull($inquiryC1);
        self::assertEqualsCanonicalizing(
            [
                'external-document-id-A-0',
                'external-document-id-A-1',
                'external-document-id-B-1',
            ],
            $this->getDocumentExternalIds($inquiryC1->getDocuments()->toArray()),
            'Case C1: Mismatched document external IDs',
        );

        $inquiryC2 = $this->getEntity(Inquiry::class, ['inquiryNumber' => 'C-2']);
        self::assertNotNull($inquiryC2);
        self::assertEqualsCanonicalizing(
            [
                'external-document-id-A-1',
                'external-document-id-B-0',
                'external-document-id-B-1',
            ],
            $this->getDocumentExternalIds($inquiryC2->getDocuments()->toArray()),
            'Case C2: Mismatched document external IDs',
        );
    }

    public function testUpdatingWooDecisionDocumentsAttachedToInquiriesDoesNotInfluenceOtherDossierDocumentsWithNewDocuments(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();

        // Dossier A
        $wooDecisionA = WooDecisionFactory::createOne([
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'previewDate' => $this->getFaker()->plainDate(),
            'status' => DossierStatus::CONCEPT,
        ]);
        WooDecisionMainDocumentFactory::createOne(['dossier' => $wooDecisionA]);
        WooDecisionAttachmentFactory::createOne(['dossier' => $wooDecisionA]);

        $documentsA = DocumentFactory::new()
            ->sequence(static function () {
                foreach (range(0, 1) as $i) {
                    yield ['externalId' => ExternalId::create(sprintf('external-document-id-A-%d', $i))];
                }
            })
            ->create([
                'dossiers' => [$wooDecisionA],
            ]);

        // Dossier B
        $wooDecisionB = WooDecisionFactory::createOne([
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'previewDate' => $this->getFaker()->plainDate(),
            'status' => DossierStatus::CONCEPT,
        ]);
        WooDecisionMainDocumentFactory::createOne(['dossier' => $wooDecisionB]);
        WooDecisionAttachmentFactory::createOne(['dossier' => $wooDecisionB]);

        $documentsB = DocumentFactory::new()
            ->sequence(static function () {
                foreach (range(0, 1) as $i) {
                    yield ['externalId' => ExternalId::create(sprintf('external-document-id-B-%d', $i))];
                }
            })
            ->create([
                'dossiers' => [$wooDecisionB],
            ]);

        // Inquiries
        $inquiryOne = InquiryFactory::createOne([
            'inquiryNumber' => 'C-1',
            'organisation' => $organisation,
            'documents' => [
                $documentsA[0],
                $documentsA[1],
                $documentsB[0],
            ],
            'dossiers' => [],
        ]);

        $inquiryTwo = InquiryFactory::createOne([
            'inquiryNumber' => 'C-2',
            'organisation' => $organisation,
            'documents' => [
                $documentsA[1],
                $documentsB[1],
            ],
            'dossiers' => [],
        ]);

        self::assertDatabaseCount(WooDecision::class, 2);
        self::assertDatabaseCount(Inquiry::class, 2);
        self::assertCount(3, $inquiryOne->getDocuments());
        self::assertCount(2, $inquiryTwo->getDocuments());

        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $documents = $this->createValidDocumentsPayload(2, [
            ['C-2'], // external-document-id-R-0
            ['C-1', 'C-2'], // external-document-id-R-1
        ], 'external-document-id-R');

        $data = $this->createValidWooDecisionDataPayload($department, $subject, $documents);

        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $wooDecisionB), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(WooDecisionResource::class);

        self::assertDatabaseCount(WooDecision::class, 2);
        self::assertDatabaseCount(Inquiry::class, 2);

        $inquiryC1 = $this->getEntity(Inquiry::class, ['inquiryNumber' => 'C-1']);
        self::assertNotNull($inquiryC1);
        self::assertEqualsCanonicalizing(
            [
                'external-document-id-A-0',
                'external-document-id-A-1',
                'external-document-id-R-1',
            ],
            $this->getDocumentExternalIds($inquiryC1->getDocuments()->toArray()),
            'Case C1: Mismatched document external IDs',
        );

        $inquiryC2 = $this->getEntity(Inquiry::class, ['inquiryNumber' => 'C-2']);
        self::assertNotNull($inquiryC2);
        self::assertEqualsCanonicalizing(
            [
                'external-document-id-A-1',
                'external-document-id-R-0',
                'external-document-id-R-1',
            ],
            $this->getDocumentExternalIds($inquiryC2->getDocuments()->toArray()),
            'Case C2: Mismatched document external IDs',
        );
    }

    public function testUpdatingWooDecisionDocumentsWithoutExternalIdReturnsAValidationError(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();

        $wooDecision = WooDecisionFactory::createOne([
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
            'previewDate' => $this->getFaker()->plainDate(),
            'status' => DossierStatus::CONCEPT,
        ]);
        WooDecisionMainDocumentFactory::createOne(['dossier' => $wooDecision]);
        WooDecisionAttachmentFactory::createOne(['dossier' => $wooDecision]);

        DocumentFactory::createOne([
            'dossiers' => [$wooDecision],
            'externalId' => null,
        ]);

        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $documents = $this->createValidDocumentsPayload(1);

        $data = $this->createValidWooDecisionDataPayload($department, $subject, $documents);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $wooDecision), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        self::assertJsonContains(['violations' => [[
            'message' => 'Dossier has Document(s) without external ID(s). This is likely because this Dossier was updated through the UI.',
        ], ]]);
    }

    /**
     * This test was added because numeric case numbers were causing issues in the past. InquiryNumbers are being used as
     * the keys in arrays which where auto casted to integers by PHP. That caused type issues.
     */
    public function testCreateWooDecisionWithDocumentsAttachedToInquiresUsingNumericInquiryNumbers(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();

        self::assertDatabaseCount(WooDecision::class, 0);

        $documents = $this->createValidDocumentsPayload(7, [
            ['1', '2'], // external-document-id-0
            ['3'], // external-document-id-1
        ]);

        $data = $this->createValidWooDecisionDataPayload($department, $subject, $documents);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->slug(1)), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(WooDecisionResource::class);

        self::assertDatabaseCount(WooDecision::class, 1);
        self::assertDatabaseCount(Inquiry::class, 3);

        $inquiryC1 = $this->getEntity(Inquiry::class, ['inquiryNumber' => '1']);
        self::assertNotNull($inquiryC1);
        self::assertEqualsCanonicalizing(
            [
                'external-document-id-0',
            ],
            $this->getDocumentExternalIds($inquiryC1->getDocuments()->toArray()),
        );

        $inquiryC2 = $this->getEntity(Inquiry::class, ['inquiryNumber' => '2']);
        self::assertNotNull($inquiryC2);
        self::assertEqualsCanonicalizing(
            [
                'external-document-id-0',
            ],
            $this->getDocumentExternalIds($inquiryC2->getDocuments()->toArray()),
        );

        $inquiryC3 = $this->getEntity(Inquiry::class, ['inquiryNumber' => '3']);
        self::assertNotNull($inquiryC3);
        self::assertEqualsCanonicalizing(
            [
                'external-document-id-1',
            ],
            $this->getDocumentExternalIds($inquiryC3->getDocuments()->toArray()),
        );
    }

    public function testWooDecisionDocumentMissingExternalIdInRefersTo(): void
    {
        $organisation = OrganisationFactory::createOne();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();

        $documents = $this->createValidDocumentsPayload(1);
        $documents[0]['externalId'] = null;

        $data = $this->createValidWooDecisionDataPayload($department, $subject, $documents);

        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $this->getFaker()->externalId()), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testGetWooDecisionReturnsInquiryLinksForTheDossierAndItsDocuments(): void
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

        $externalIdWithInquiries = 'document-with-inquiries';
        $externalIdWithoutInquiries = 'document-without-inquiries';

        $documentWithInquiries = DocumentFactory::new()
            ->withPublicJudgement()
            ->create([
                'dossiers' => [$wooDecision],
                'externalId' => ExternalId::create($externalIdWithInquiries),
            ]);

        DocumentFactory::new()
            ->withPublicJudgement()
            ->create([
                'dossiers' => [$wooDecision],
                'externalId' => ExternalId::create($externalIdWithoutInquiries),
            ]);

        $inquiryC1 = InquiryFactory::createOne([
            'documents' => [$documentWithInquiries],
            'dossiers' => [$wooDecision],
            'inquiryNumber' => 'C-1',
            'organisation' => $organisation,
        ]);
        $inquiryC2 = InquiryFactory::createOne([
            'documents' => [$documentWithInquiries],
            'dossiers' => [$wooDecision],
            'inquiryNumber' => 'C-2',
            'organisation' => $organisation,
        ]);

        $result = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $wooDecision));
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(WooDecisionResource::class);

        $publicUrlGenerator = $this->fromContainer(PublicUrlGenerator::class);
        $expectedInquiryLinks = [
            [
                'href' => $publicUrlGenerator
                    ->buildUrlFromRoute(InquiryController::ROUTE_NAME_INQUIRY_DETAIL, ['token' => $inquiryC1->getToken()])
                    ->toString(),
                'name' => 'C-1',
            ],
            [
                'href' => $publicUrlGenerator
                    ->buildUrlFromRoute(InquiryController::ROUTE_NAME_INQUIRY_DETAIL, ['token' => $inquiryC2->getToken()])
                    ->toString(),
                'name' => 'C-2',
            ],
        ];

        $response = $result->toArray();

        $links = $response['_links'];
        Assert::isArray($links);

        self::assertSame(['href' => $this->buildApiUrl($organisation, $wooDecision)], $links['self']);
        self::assertEqualsCanonicalizing($expectedInquiryLinks, $links['inquiries']);

        $documents = $response['documents'];
        Assert::isArray($documents);
        $linksByExternalId = array_column($documents, '_links', 'externalId');

        $linksWithInquiries = $linksByExternalId[$externalIdWithInquiries];
        Assert::isArray($linksWithInquiries);
        self::assertEqualsCanonicalizing($expectedInquiryLinks, $linksWithInquiries['inquiries']);

        $linksWithoutInquiries = $linksByExternalId[$externalIdWithoutInquiries];
        Assert::isArray($linksWithoutInquiries);
        self::assertArrayNotHasKey('inquiries', $linksWithoutInquiries);
    }

    public function testGetWooDecisionWithoutInquiriesReturnsNoInquiryLinks(): void
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

        $externalId = 'document-without-inquiries';

        DocumentFactory::new()
            ->withAlreadyPublicJudgement()
            ->create([
                'dossiers' => [$wooDecision],
                'externalId' => ExternalId::create($externalId),
            ]);

        self::assertDatabaseCount(Inquiry::class, 0);

        $result = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $wooDecision));
        self::assertResponseIsSuccessful();

        $response = $result->toArray();

        self::assertSame([
            'self' => ['href' => $this->buildApiUrl($organisation, $wooDecision)],
        ], $response['_links']);

        $documents = $response['documents'];
        Assert::isArray($documents);

        $documentLinks = array_column($documents, '_links', 'externalId')[$externalId];
        Assert::isArray($documentLinks);
        self::assertArrayNotHasKey('inquiries', $documentLinks);

        self::assertStringContainsString('"_links":{}', $result->getContent());
    }

    public function testInquiryLinksOfAnotherOrganisationAreNotVisible(): void
    {
        $organisation = OrganisationFactory::createOne();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create();
        $wooDecision = WooDecisionFactory::new()->published()->create([
            'departments' => [$department],
            'externalId' => $this->getFaker()->externalId(),
            'organisation' => $organisation,
        ]);
        WooDecisionMainDocumentFactory::createOne(['dossier' => $wooDecision]);

        $inquiry = InquiryFactory::createOne([
            'documents' => [],
            'dossiers' => [$wooDecision],
            'inquiryNumber' => 'C-1',
            'organisation' => $organisation,
        ]);

        $otherOrganisation = OrganisationFactory::createOne();

        $result = self::createPublicationApiRequest(
            Request::METHOD_GET,
            $this->buildUrl($otherOrganisation, $wooDecision),
        );
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        self::assertStringNotContainsString($inquiry->getToken(), $result->getContent(false));
    }

    /**
     * @param list<array<string,mixed>> $documents
     *
     * @return array<string, mixed>
     */
    private function createValidWooDecisionDataPayload(Department $department, ?Subject $subject, array $documents): array
    {
        return [
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
            'attachments' => $this->createValidAttachmentsPayload($this->getFaker()->numberBetween(0, 3), WooDecisionAttachment::getAllowedTypes()),
            'documents' => $documents,
        ];
    }

    /**
     * In the context of these testcases a 'valid' document has judgement 'public' and is not `suspended`, so they
     * support uploads.
     *
     * @param array<array-key,array<array-key,string>> $inquiryNumberProvider
     *
     * @return list<array<string,mixed>>
     */
    private function createValidDocumentsPayload(
        int $documentCount,
        array $inquiryNumberProvider = [],
        string $externalIdPrefix = 'external-document-id',
    ): array {
        $documents = [];
        for ($i = 0; $i < $documentCount; $i++) {
            $inquiryNumbers = array_shift($inquiryNumberProvider) ?? [];

            $documents[] = [
                'inquiryNumbers' => $inquiryNumbers,
                'documentDate' => $this->getFaker()->date(),
                'documentId' => $this->getFaker()->unique()->documentId()->toString(),
                'externalId' => sprintf('%s-%d', $externalIdPrefix, $i),
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

        return $documents;
    }

    /**
     * @param array<array-key,Document> $documents
     *
     * @return list<string>
     */
    private function getDocumentExternalIds(array $documents): array
    {
        $externalIds = array_map(
            static fn (Document $document): ?string => $document->getExternalId()?->toString(),
            $documents,
        );

        return array_values(array_filter($externalIds));
    }
}
