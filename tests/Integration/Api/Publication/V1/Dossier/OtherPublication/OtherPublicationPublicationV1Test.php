<?php

declare(strict_types=1);

namespace Integration\Api\Publication\V1\Dossier\OtherPublication;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Api\Publication\V1\Dossier\OtherPublication\OtherPublicationDto;
use Shared\Domain\Department\Department;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Tests\Factory\DepartmentFactory;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\OtherPublication\OtherPublicationAttachmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\OtherPublication\OtherPublicationFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\OtherPublication\OtherPublicationMainDocumentFactory;
use Shared\Tests\Factory\Publication\Subject\SubjectFactory;
use Shared\Tests\Integration\Api\Publication\V1\Dossier\ApiPublicationV1DossierTestCase;
use Shared\Validator\EntityExists;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\Type;

final class OtherPublicationPublicationV1Test extends ApiPublicationV1DossierTestCase
{
    public function getDossierApiUriSegment(): string
    {
        return 'other-publication';
    }

    public function testGet(): void
    {
        $organisation = OrganisationFactory::createOne()->_real();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create()->_real();
        $otherPublication = OtherPublicationFactory::createOne([
            'date_from' => $this->getFaker()->dateTime(),
            'organisation' => $organisation,
            'departments' => [$department],
        ])->_real();
        $otherPublicationMainDocument = OtherPublicationMainDocumentFactory::createOne(['dossier' => $otherPublication])->_real();
        $otherPublicationAttachment = OtherPublicationAttachmentFactory::createOne(['dossier' => $otherPublication])->_real();

        $response = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $otherPublication));

        self::assertResponseIsSuccessful();

        $expectedResponse = [
            'id' => (string) $otherPublication->getId(),
            'organisation' => [
                'id' => (string) $otherPublication->getOrganisation()->getId(),
                'name' => $otherPublication->getOrganisation()->getName(),
            ],
            'prefix' => $otherPublication->getDocumentPrefix(),
            'dossierNumber' => $otherPublication->getDossierNr(),
            'internalReference' => '',
            'title' => $otherPublication->getTitle(),
            'summary' => $otherPublication->getSummary(),
            'department' => [
                'id' => (string) $department->getId(),
                'name' => $department->getName(),
            ],
            'publicationDate' => $otherPublication->getPublicationDate()?->format(\DateTime::RFC3339),
            'status' => $otherPublication->getStatus()->value,
            'mainDocument' => [
                'id' => (string) $otherPublicationMainDocument->getId(),
                'type' => $otherPublicationMainDocument->getType()->value,
                'language' => $otherPublicationMainDocument->getLanguage()->value,
                'formalDate' => $otherPublicationMainDocument->getFormalDate()->format(\DateTime::RFC3339),
                'internalReference' => $otherPublicationMainDocument->getInternalReference(),
                'grounds' => $otherPublicationMainDocument->getGrounds(),
            ],
            'attachments' => [
                [
                    'id' => (string) $otherPublicationAttachment->getId(),
                    'type' => $otherPublicationAttachment->getType()->value,
                    'language' => $otherPublicationAttachment->getLanguage()->value,
                    'formalDate' => $otherPublicationAttachment->getFormalDate()->format(\DateTime::RFC3339),
                    'internalReference' => $otherPublicationAttachment->getInternalReference(),
                    'grounds' => $otherPublicationAttachment->getGrounds(),
                ],
            ],
            'dossierDate' => $otherPublication->getDateFrom()?->format(\DateTime::RFC3339),
        ];

        self::assertSame($expectedResponse, $response->toArray());
        self::assertMatchesResourceItemJsonSchema(OtherPublicationDto::class);
    }

    public function testGetFromIncorrectOrganisation(): void
    {
        $organisation = OrganisationFactory::createOne()->_real();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create()->_real();
        $otherPublication = OtherPublicationFactory::createOne([
            'departments' => [$department],
        ])->_real();

        self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $otherPublication));
        self::assertResponseStatusCodeSame(404);
    }

    public function testGetWithUknownUuid(): void
    {
        $organisation = OrganisationFactory::createOne()->_real();

        self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, Uuid::fromString($this->getFaker()->uuid())));

        self::assertResponseStatusCodeSame(404);
    }

    public function testCreateOtherPublication(): void
    {
        $organisation = OrganisationFactory::createOne()->_real();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create()->_real();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create()->_real();

        $response = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation));
        self::assertCount(0, $response->toArray());

        $data = $this->createValidOtherPublicationDataPayload($department, $subject, $this->getFaker()->numberBetween(1, 3));
        self::createPublicationApiRequest(Request::METHOD_POST, $this->buildUrl($organisation), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertMatchesResourceItemJsonSchema(OtherPublicationDto::class);

        $response = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation));
        self::assertCount(1, $response->toArray());
    }

    public function testCreateOtherPublicationWithoutSubject(): void
    {
        $organisation = OrganisationFactory::createOne()->_real();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create()->_real();

        $response = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation));
        self::assertCount(0, $response->toArray());

        $data = $this->createValidOtherPublicationDataPayload($department, null, 1);
        self::createPublicationApiRequest(Request::METHOD_POST, $this->buildUrl($organisation), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertMatchesResourceItemJsonSchema(OtherPublicationDto::class);

        $response = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation));
        self::assertCount(1, $response->toArray());
    }

    public function testCreateOtherPublicationWithoutMainDocument(): void
    {
        $organisation = OrganisationFactory::createOne()->_real();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create()->_real();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create()->_real();

        $response = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation));
        self::assertCount(0, $response->toArray());

        $data = $this->createValidOtherPublicationDataPayload($department, $subject, 0);
        unset($data['mainDocument']);
        self::createPublicationApiRequest(Request::METHOD_POST, $this->buildUrl($organisation), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [[
            'code' => Type::INVALID_TYPE_ERROR,
            'propertyPath' => 'mainDocument',
        ], ]]);
    }

    public function testCreateOtherPublicationWithoutAttachments(): void
    {
        $organisation = OrganisationFactory::createOne()->_real();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create()->_real();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create()->_real();

        $response = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation));
        self::assertCount(0, $response->toArray());

        $data = $this->createValidOtherPublicationDataPayload($department, $subject, 0);
        self::createPublicationApiRequest(Request::METHOD_POST, $this->buildUrl($organisation), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertMatchesResourceItemJsonSchema(OtherPublicationDto::class);

        $response = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation));
        self::assertCount(1, $response->toArray());
    }

    /**
     * @param array<string, array<mixed>> $dataOverrides
     * @param array<string, array<mixed>> $violations
     */
    #[DataProvider('createOtherPublicationValidationDataProvider')]
    public function testCreateOtherPublicationWithValidationError(array $dataOverrides, array $violations): void
    {
        $organisation = OrganisationFactory::createOne()->_real();
        $subject = SubjectFactory::new(['organisation' => $organisation])->create()->_real();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create()->_real();

        $response = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation));
        self::assertCount(0, $response->toArray());

        $data = \array_merge($this->createValidOtherPublicationDataPayload($department, $subject, 1), $dataOverrides);
        self::createPublicationApiRequest(Request::METHOD_POST, $this->buildUrl($organisation), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [$violations]]);
    }

    /**
     * @return array<string, array<mixed>>
     */
    public static function createOtherPublicationValidationDataProvider(): array
    {
        return [
            'dossierDate in the future' => [
                [
                    'dossierDate' => CarbonImmutable::now()->addDay()->format(\DateTime::RFC3339),
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

    public function testUpdateOtherPublication(): void
    {
        $organisation = OrganisationFactory::createOne()->_real();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create()->_real();
        $otherPublication = OtherPublicationFactory::createOne([
            'date_from' => $this->getFaker()->dateTime(),
            'departments' => [$department],
            'organisation' => $organisation,
            'status' => DossierStatus::CONCEPT,
        ])->_real();
        OtherPublicationMainDocumentFactory::createOne(['dossier' => $otherPublication])->_real();
        OtherPublicationAttachmentFactory::createOne(['dossier' => $otherPublication])->_real();

        $response = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $otherPublication));
        self::assertArraySubset([
            'title' => $otherPublication->getTitle(),
            'summary' => $otherPublication->getSummary(),
        ], $response->toArray());

        $data = $this->createValidOtherPublicationDataPayload($department, null, 0);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $otherPublication), ['json' => $data]);
        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(OtherPublicationDto::class);

        $response = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $otherPublication));
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
    #[DataProvider('updateOtherPublicationValidationDataProvider')]
    public function testUpdateOtherPublicationWithValidationErrors(array $dataOverrides, array $violations): void
    {
        $organisation = OrganisationFactory::createOne()->_real();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create()->_real();
        $otherPublication = OtherPublicationFactory::createOne([
            'date_from' => $this->getFaker()->dateTime(),
            'organisation' => $organisation,
            'departments' => [$department],
            'status' => DossierStatus::CONCEPT,
        ])->_real();
        OtherPublicationMainDocumentFactory::createOne(['dossier' => $otherPublication])->_real();
        OtherPublicationAttachmentFactory::createOne(['dossier' => $otherPublication])->_real();

        $response = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $otherPublication));
        self::assertArraySubset([
            'title' => $otherPublication->getTitle(),
            'summary' => $otherPublication->getSummary(),
        ], $response->toArray());

        $data = \array_merge($this->createValidOtherPublicationDataPayload($department, null, 1), $dataOverrides);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $otherPublication), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => [$violations]]);

        $response = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $otherPublication));
        self::assertArraySubset([
            'title' => $otherPublication->getTitle(),
            'summary' => $otherPublication->getSummary(),
        ], $response->toArray());
    }

    /**
     * @return array<string, array<mixed>>
     */
    public static function updateOtherPublicationValidationDataProvider(): array
    {
        return [
            'dossierDate in the future' => [
                [
                    'dossierDate' => CarbonImmutable::now()->addDay()->format(\DateTime::RFC3339),
                ],
                [
                    'code' => LessThanOrEqual::TOO_HIGH_ERROR,
                    'propertyPath' => 'dateFrom',
                ],
            ],
        ];
    }

    public function testUpdateOtherPublicationWithNonConceptState(): void
    {
        $organisation = OrganisationFactory::createOne()->_real();
        $department = DepartmentFactory::new(['organisations' => [$organisation]])->create()->_real();
        $otherPublication = OtherPublicationFactory::createOne([
            'date_from' => $this->getFaker()->dateTime(),
            'departments' => [$department],
            'organisation' => $organisation,
            'status' => $this->getFaker()->randomElement(DossierStatus::nonConceptCases()),
        ])->_real();
        OtherPublicationMainDocumentFactory::createOne(['dossier' => $otherPublication])->_real();
        OtherPublicationAttachmentFactory::createOne(['dossier' => $otherPublication])->_real();

        $response = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $otherPublication));
        self::assertArraySubset([
            'title' => $otherPublication->getTitle(),
            'summary' => $otherPublication->getSummary(),
        ], $response->toArray());

        $data = $this->createValidOtherPublicationDataPayload($department, null, 0);
        self::createPublicationApiRequest(Request::METHOD_PUT, $this->buildUrl($organisation, $otherPublication), ['json' => $data]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $response = self::createPublicationApiRequest(Request::METHOD_GET, $this->buildUrl($organisation, $otherPublication));
        self::assertArraySubset([
            'title' => $otherPublication->getTitle(),
            'summary' => $otherPublication->getSummary(),
        ], $response->toArray());
    }

    /**
     * @return array<string, mixed>
     */
    private function createValidOtherPublicationDataPayload(Department $department, ?Subject $subject, int $attachmentCount): array
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
            'dossierDate' => $this->getFaker()->dateTimeBetween('-3 weeks', '-2 week')->format(\DateTime::RFC3339),
            'publicationDate' => $this->getFaker()->dateTimeBetween('-2 weeks', '-1 week')->format(\DateTime::RFC3339),
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
