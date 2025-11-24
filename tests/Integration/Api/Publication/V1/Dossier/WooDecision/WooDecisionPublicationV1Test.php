<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Api\Publication\V1\Dossier\WooDecision;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Api\Publication\V1\Dossier\WooDecision\WooDecisionDto;
use Shared\Domain\Department\Department;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Decision\DecisionType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\PublicationReason;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionAttachmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\WooDecision\WooDecisionMainDocumentFactory;
use Shared\Tests\Factory\Publication\Subject\SubjectFactory;
use Shared\Tests\Integration\Api\Publication\V1\Dossier\ApiPublicationV1DossierTestCase;
use Shared\Validator\EntityExists;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\Type;

final class WooDecisionPublicationV1Test extends ApiPublicationV1DossierTestCase
{
    public function getDossierApiUriSegment(): string
    {
        return 'woo-decision';
    }

    public function testGet(): void
    {
        $organisation = OrganisationFactory::createOne()->_real();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create()->_real();
        $wooDecision = WooDecisionFactory::createOne([
            'organisation' => $organisation,
            'previewDate' => $this->getFaker()->dateTime(),
            'departments' => [$department],
        ])->_real();
        $wooDecisionMainDocument = WooDecisionMainDocumentFactory::createOne(['dossier' => $wooDecision])->_real();
        $wooDecisionAttachment = WooDecisionAttachmentFactory::createOne(['dossier' => $wooDecision])->_real();

        $response = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $wooDecision));

        self::assertResponseIsSuccessful();

        $expectedResponse = [
            'id' => (string) $wooDecision->getId(),
            'organisation' => [
                'id' => (string) $wooDecision->getOrganisation()->getId(),
                'name' => $wooDecision->getOrganisation()->getName(),
            ],
            'prefix' => $wooDecision->getDocumentPrefix(),
            'dossierNumber' => $wooDecision->getDossierNr(),
            'internalReference' => '',
            'title' => $wooDecision->getTitle(),
            'summary' => $wooDecision->getSummary(),
            'department' => [
                'id' => (string) $department->getId(),
                'name' => $department->getName(),
            ],
            'publicationDate' => $wooDecision->getPublicationDate()?->format(\DateTime::RFC3339),
            'status' => $wooDecision->getStatus()->value,
            'mainDocument' => [
                'id' => (string) $wooDecisionMainDocument->getId(),
                'type' => $wooDecisionMainDocument->getType()->value,
                'language' => $wooDecisionMainDocument->getLanguage()->value,
                'formalDate' => $wooDecisionMainDocument->getFormalDate()->format(\DateTime::RFC3339),
                'internalReference' => $wooDecisionMainDocument->getInternalReference(),
                'grounds' => $wooDecisionMainDocument->getGrounds(),
            ],
            'attachments' => [
                [
                    'id' => (string) $wooDecisionAttachment->getId(),
                    'type' => $wooDecisionAttachment->getType()->value,
                    'language' => $wooDecisionAttachment->getLanguage()->value,
                    'formalDate' => $wooDecisionAttachment->getFormalDate()->format(\DateTime::RFC3339),
                    'internalReference' => $wooDecisionAttachment->getInternalReference(),
                    'grounds' => $wooDecisionAttachment->getGrounds(),
                ],
            ],
            'dossierDateFrom' => $wooDecision->getDateFrom()?->format(\DateTime::RFC3339),
            'dossierDateTo' => $wooDecision->getDateTo()?->format(\DateTime::RFC3339),
            'decision' => $wooDecision->getDecision()?->value,
            'reason' => $wooDecision->getPublicationReason()?->value,
            'previewDate' => $wooDecision->getPreviewDate()?->format(\DateTime::RFC3339),
        ];

        self::assertSame($expectedResponse, $response->toArray());
        self::assertMatchesResourceItemJsonSchema(WooDecisionDto::class);
    }

    public function testGetFromIncorrectOrganisation(): void
    {
        $organisation = OrganisationFactory::createOne()->_real();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create()->_real();
        $wooDecision = WooDecisionFactory::createOne([
            'departments' => [$department],
        ])->_real();

        self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $wooDecision));
        self::assertResponseStatusCodeSame(404);
    }

    public function testGetWithUknownUuid(): void
    {
        $organisation = OrganisationFactory::createOne()->_real();

        self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, Uuid::fromString($this->getFaker()->uuid())));

        self::assertResponseStatusCodeSame(404);
    }

    public function testCreateWooDecision(): void
    {
        $organisation = OrganisationFactory::createOne()->_real();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create()->_real();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create()->_real();

        $response = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation));
        self::assertCount(0, $response->toArray());

        $data = $this->createValidWooDecisionDataPayload($department, $subject, $this->getFaker()->numberBetween(1, 3));
        self::createPublicationApiRequest(Request::METHOD_POST, $this->buildUrl($organisation), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertMatchesResourceItemJsonSchema(WooDecisionDto::class);

        $response = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation));
        self::assertCount(1, $response->toArray());
    }

    public function testCreateWooDecisionWithoutSubject(): void
    {
        $organisation = OrganisationFactory::createOne()->_real();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create()->_real();

        $response = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation));
        self::assertCount(0, $response->toArray());

        $data = $this->createValidWooDecisionDataPayload($department, null, 1);
        self::createPublicationApiRequest(Request::METHOD_POST, $this->buildUrl($organisation), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertMatchesResourceItemJsonSchema(WooDecisionDto::class);

        $response = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation));
        self::assertCount(1, $response->toArray());
    }

    public function testCreateWooDecisionWithoutMainDocument(): void
    {
        $organisation = OrganisationFactory::createOne()->_real();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create()->_real();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create()->_real();

        $response = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation));
        self::assertCount(0, $response->toArray());

        $data = $this->createValidWooDecisionDataPayload($department, $subject, 0);
        unset($data['mainDocument']);
        self::createPublicationApiRequest(Request::METHOD_POST, $this->buildUrl($organisation), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [[
            'code' => Type::INVALID_TYPE_ERROR,
            'propertyPath' => 'mainDocument',
        ], ]]);
    }

    public function testCreateWooDecisionWithoutAttachments(): void
    {
        $organisation = OrganisationFactory::createOne()->_real();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create()->_real();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create()->_real();

        $response = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation));
        self::assertCount(0, $response->toArray());

        $data = $this->createValidWooDecisionDataPayload($department, $subject, 0);
        self::createPublicationApiRequest(Request::METHOD_POST, $this->buildUrl($organisation), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertMatchesResourceItemJsonSchema(WooDecisionDto::class);

        $response = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation));
        self::assertCount(1, $response->toArray());
    }

    /**
     * @param array<string, array<mixed>> $dataOverrides
     * @param array<string, array<mixed>> $violations
     */
    #[DataProvider('createWooDecisionValidationDataProvider')]
    public function testCreateWooDecisionWithValidationError(array $dataOverrides, array $violations): void
    {
        $organisation = OrganisationFactory::createOne()->_real();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create()->_real();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create()->_real();

        $response = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation));
        self::assertCount(0, $response->toArray());

        $data = \array_merge($this->createValidWooDecisionDataPayload($department, $subject, 1), $dataOverrides);
        self::createPublicationApiRequest(Request::METHOD_POST, $this->buildUrl($organisation), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [$violations]]);
    }

    /**
     * @return array<string, array<mixed>>
     */
    public static function createWooDecisionValidationDataProvider(): array
    {
        return [
            'dossierDateFrom in the future' => [
                [
                    'dossierDateFrom' => CarbonImmutable::now()->addDay()->format(\DateTime::RFC3339),
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
                        'formalDate' => CarbonImmutable::now()->addDay()->format(\DateTime::RFC3339),
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
                            'formalDate' => CarbonImmutable::now()->addDay()->format(\DateTime::RFC3339),
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

    public function testUpdateWooDecision(): void
    {
        $organisation = OrganisationFactory::createOne()->_real();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create()->_real();
        $wooDecision = WooDecisionFactory::createOne([
            'organisation' => $organisation,
            'previewDate' => $this->getFaker()->dateTime(),
            'departments' => [$department],
            'status' => DossierStatus::CONCEPT,
        ])->_real();
        WooDecisionMainDocumentFactory::createOne(['dossier' => $wooDecision])->_real();
        WooDecisionAttachmentFactory::createOne(['dossier' => $wooDecision])->_real();

        $response = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $wooDecision));
        self::assertArraySubset([
            'title' => $wooDecision->getTitle(),
            'summary' => $wooDecision->getSummary(),
        ], $response->toArray());

        $data = $this->createValidWooDecisionDataPayload($department, null, 0);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $wooDecision), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(WooDecisionDto::class);

        $response = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $wooDecision));
        self::assertArraySubset([
            'dossierNumber' => $data['dossierNumber'],
            'internalReference' => $data['internalReference'],
            'prefix' => $data['prefix'],
            'summary' => $data['summary'],
            'title' => $data['title'],
        ], $response->toArray());
    }

    /**
     * @param array<string, array<mixed>> $dataOverrides
     * @param array<string, array<mixed>> $violations
     */
    #[DataProvider('updateWooDecisionValidationDataProvider')]
    public function testUpdateWooDecisionWithValidationErrors(array $dataOverrides, array $violations): void
    {
        $organisation = OrganisationFactory::createOne()->_real();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create()->_real();
        $wooDecision = WooDecisionFactory::createOne([
            'organisation' => $organisation,
            'previewDate' => $this->getFaker()->dateTime(),
            'departments' => [$department],
            'status' => DossierStatus::CONCEPT,
        ])->_real();
        WooDecisionMainDocumentFactory::createOne(['dossier' => $wooDecision])->_real();
        WooDecisionAttachmentFactory::createOne(['dossier' => $wooDecision])->_real();

        $response = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $wooDecision));
        self::assertArraySubset([
            'title' => $wooDecision->getTitle(),
            'summary' => $wooDecision->getSummary(),
        ], $response->toArray());

        $data = \array_merge($this->createValidWooDecisionDataPayload($department, null, 1), $dataOverrides);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $wooDecision), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [$violations]]);

        $response = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $wooDecision));
        self::assertArraySubset([
            'title' => $wooDecision->getTitle(),
            'summary' => $wooDecision->getSummary(),
        ], $response->toArray());
    }

    /**
     * @return array<string, array<mixed>>
     */
    public static function updateWooDecisionValidationDataProvider(): array
    {
        return [
            'dossierDate in the future' => [
                [
                    'dossierDateFrom' => CarbonImmutable::now()->addDay()->format(\DateTime::RFC3339),
                ],
                [
                    'code' => LessThanOrEqual::TOO_HIGH_ERROR,
                    'propertyPath' => 'dateFrom',
                ],
            ],
        ];
    }

    public function testUpdateDispositionWithNonConceptState(): void
    {
        $organisation = OrganisationFactory::createOne()->_real();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create()->_real();
        $wooDecision = WooDecisionFactory::createOne([
            'previewDate' => $this->getFaker()->dateTime(),
            'departments' => [$department],
            'organisation' => $organisation,
            'status' => $this->getFaker()->randomElement(DossierStatus::nonConceptCases()),
        ])->_real();
        WooDecisionMainDocumentFactory::createOne(['dossier' => $wooDecision])->_real();
        WooDecisionAttachmentFactory::createOne(['dossier' => $wooDecision])->_real();

        $response = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $wooDecision));
        self::assertArraySubset([
            'title' => $wooDecision->getTitle(),
            'summary' => $wooDecision->getSummary(),
        ], $response->toArray());

        $data = $this->createValidWooDecisionDataPayload($department, null, 0);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $wooDecision), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $response = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $wooDecision));
        self::assertArraySubset([
            'title' => $wooDecision->getTitle(),
            'summary' => $wooDecision->getSummary(),
        ], $response->toArray());
    }

    /**
     * @return array<string, mixed>
     */
    private function createValidWooDecisionDataPayload(Department $department, ?Subject $subject, int $attachmentCount): array
    {
        $attachments = [];
        for ($i = 0; $i < $attachmentCount; $i++) {
            $attachments[] = [
                'formalDate' => $this->getFaker()->date(\DateTime::RFC3339),
                'type' => $this->getFaker()->randomElement(AttachmentType::cases()),
                'language' => $this->getFaker()->randomElement(AttachmentLanguage::cases()),
            ];
        }

        return [
            'title' => $this->getFaker()->sentence(),
            'dossierNumber' => $this->getFaker()->slug(2),
            'internalReference' => $this->getFaker()->optional(default: '')->uuid(),
            'prefix' => $this->getFaker()->slug(2),
            'dossierDateFrom' => $this->getFaker()->dateTimeBetween('-3 weeks', '-2 week')->format(\DateTime::RFC3339),
            'dossierDateTo' => $this->getFaker()->dateTimeBetween('-1 week', 'now')->format(\DateTime::RFC3339),
            'decision' => $this->getFaker()->randomElement(DecisionType::cases()),
            'reason' => $this->getFaker()->randomElement(PublicationReason::cases()),
            'previewDate' => $this->getFaker()->dateTimeBetween('1 week', '2 weeks')->format(\DateTime::RFC3339),
            'publicationDate' => $this->getFaker()->dateTimeBetween('2 weeks', '3 weeks')->format(\DateTime::RFC3339),
            'summary' => $this->getFaker()->sentence(),
            'departmentId' => $department->getId(),
            'subjectId' => $subject?->getId(),
            'mainDocument' => [
                'formalDate' => $this->getFaker()->date(\DateTime::RFC3339),
                'type' => $this->getFaker()->randomElement(AttachmentType::cases()),
                'language' => $this->getFaker()->randomElement(AttachmentLanguage::cases()),
            ],
            'attachments' => $attachments,
        ];
    }
}
