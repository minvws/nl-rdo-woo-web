<?php

declare(strict_types=1);

namespace App\Tests\Integration\Api\Admin;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Api\Admin\AnnualReportAttachment\AnnualReportAttachmentDto;
use App\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use App\Domain\Publication\Attachment\Enum\AttachmentType;
use App\Domain\Publication\Dossier\DossierStatus;
use App\Service\Uploader\UploadGroupId;
use App\Tests\Factory\FileInfoFactory;
use App\Tests\Factory\Publication\Dossier\Type\AnnualReport\AnnualReportAttachmentFactory;
use App\Tests\Factory\Publication\Dossier\Type\AnnualReport\AnnualReportFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\Integration\IntegrationTestTrait;
use Carbon\CarbonImmutable;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Date;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

final class AnnualReportAttachmentTest extends ApiTestCase
{
    use IntegrationTestTrait;
    use TestFileTrait;

    private vfsStreamDirectory $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = vfsStream::setup();

        self::bootKernel();
    }

    public function testGetAllAnnualReportAttachments(): void
    {
        $user = UserFactory::new()
            ->asSuperAdmin()
            ->isEnabled()
            ->create();

        $dossier = AnnualReportFactory::createOne([
            'organisation' => $user->getOrganisation(),
        ])->_real();

        AnnualReportAttachmentFactory::createMany(5, [
            'dossier' => $dossier,
        ]);

        $response = static::createClient()
            ->loginUser($user->_real(), 'balie')
            ->request(
                Request::METHOD_GET,
                sprintf('/balie/api/dossiers/%s/annual-report-attachments', $dossier->getId()),
                [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                ],
            );

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceCollectionJsonSchema(AnnualReportAttachmentDto::class);
        self::assertCount(5, $response->toArray(), 'Expected 5 annualReport attachments');
    }

    public function testGetSingleAnnualReportAttachment(): void
    {
        $user = UserFactory::new()
            ->asSuperAdmin()
            ->isEnabled()
            ->create();

        $attachment = AnnualReportAttachmentFactory::createOne([
            'dossier' => AnnualReportFactory::createOne([
                'organisation' => $user->getOrganisation(),
            ]),
        ])->_real();

        static::createClient()
            ->loginUser($user->_real(), 'balie')
            ->request(
                Request::METHOD_GET,
                sprintf('/balie/api/dossiers/%s/annual-report-attachments/%s', $attachment->getDossier()->getId(), $attachment->getId()),
                [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                ],
            );

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(AnnualReportAttachmentDto::class);
    }

    public function testGetSingleNonExistingAnnualReportAttachment(): void
    {
        $user = UserFactory::new()
            ->asSuperAdmin()
            ->isEnabled()
            ->create();

        $dossier = AnnualReportFactory::createOne([
            'organisation' => $user->getOrganisation(),
        ]);

        static::createClient()
            ->loginUser($user->_real(), 'balie')
            ->request(
                Request::METHOD_GET,
                sprintf('/balie/api/dossiers/%s/annual-report-attachments/%s', $dossier->getId(), $this->getFaker()->uuid()),
                [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                ],
            );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testCreateAnnualReportAttachment(): void
    {
        $user = UserFactory::new()
            ->asSuperAdmin()
            ->isEnabled()
            ->create();

        $dossier = AnnualReportFactory::createOne([
            'organisation' => $user->getOrganisation(),
        ])->_real();

        $client = static::createClient()->loginUser($user->_real(), 'balie');

        $this->createPdfTestFile();

        $uploadFile = new UploadedFile(
            path: $this->root->url() . '/test_file.pdf',
            originalName: 'test_file.pdf',
        );

        $uploadUuid = $this->getFaker()->uuid();

        $client->request(
            Request::METHOD_POST,
            '/balie/uploader',
            [
                'headers' => ['Content-Type' => 'multipart/form-data'],
                'extra' => [
                    'parameters' => [
                        'chunkindex' => '0',
                        'totalchunkcount' => '1',
                        'groupId' => UploadGroupId::ATTACHMENTS->value,
                        'uuid' => $uploadUuid,
                        'grounds' => ['ground one', 'ground two'],
                    ],
                    'files' => [
                        'file' => $uploadFile,
                    ],
                ],
            ],
        );

        self::assertResponseIsSuccessful();

        $client->request(
            Request::METHOD_POST,
            sprintf('/balie/api/dossiers/%s/annual-report-attachments', $dossier->getId()),
            [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'uploadUuid' => $uploadUuid,
                    'type' => AttachmentType::PROGRESS_REPORT->value,
                    'formalDate' => CarbonImmutable::now()->format('Y-m-d'),
                    'language' => AttachmentLanguage::DUTCH->value,
                ],
            ],
        );

        self::assertResponseIsSuccessful();

        $client->request(
            Request::METHOD_GET,
            sprintf('/balie/api/dossiers/%s/annual-report-attachments', $dossier->getId()),
            [
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ],
        );

        self::assertResponseIsSuccessful();
    }

    /**
     * @param array<string,string|list<mixed>>      $input
     * @param array<array-key,array<string,string>> $expectedViolations
     */
    #[DataProvider('getInvalidCreateRequestData')]
    public function testCreateAnnualReportAttachmentWithInvalidRequestData(array $input, array $expectedViolations): void
    {
        $user = UserFactory::new()
            ->asSuperAdmin()
            ->isEnabled()
            ->create();

        $dossier = AnnualReportFactory::createOne([
            'organisation' => $user->getOrganisation(),
        ])->_real();

        $client = static::createClient()->loginUser($user->_real(), 'balie');

        $this->createPdfTestFile();

        $uploadFile = new UploadedFile(
            path: $this->root->url() . '/test_file.pdf',
            originalName: 'test_file.pdf',
        );

        $uploadUuid = ! isset($input['uploadUuid']) || (is_string($input['uploadUuid']) && trim($input['uploadUuid']) === '')
            ? $this->getFaker()->uuid()
            : $input['uploadUuid'];

        $client->request(
            Request::METHOD_POST,
            '/balie/uploader',
            [
                'headers' => ['Content-Type' => 'multipart/form-data'],
                'extra' => [
                    'parameters' => [
                        'chunkindex' => '0',
                        'totalchunkcount' => '1',
                        'groupId' => UploadGroupId::ATTACHMENTS->value,
                        'uuid' => $uploadUuid,
                    ],
                    'files' => [
                        'file' => $uploadFile,
                    ],
                ],
            ],
        );

        self::assertResponseIsSuccessful();

        $client->request(
            Request::METHOD_POST,
            sprintf('/balie/api/dossiers/%s/annual-report-attachments', $dossier->getId()),
            [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => $input,
            ],
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => $expectedViolations]);
    }

    /**
     * @return array<string,array{input:array<string,string|list<mixed>>,expectedViolations:array<array-key,array<string,string>>}>
     */
    public static function getInvalidCreateRequestData(): array
    {
        return [
            'invalid type error' => [
                'input' => [
                    'type' => '',
                    'language' => '',
                    'grounds' => '',
                ],
                'expectedViolations' => [
                    [
                        'propertyPath' => 'type',
                        'code' => Type::INVALID_TYPE_ERROR,
                    ],
                    [
                        'propertyPath' => 'language',
                        'code' => Type::INVALID_TYPE_ERROR,
                    ],
                    [
                        'propertyPath' => 'grounds',
                        'code' => Type::INVALID_TYPE_ERROR,
                    ],
                ],
            ],
            'invalid input data' => [
                'input' => [
                    'uploadUuid' => '55ae5de9-55f4-3420-b50b-5cde6e07fc5a',
                    'formalDate' => 'foobar',
                    'grounds' => [1, 2, 3],
                ],
                'expectedViolations' => [
                    [
                        'propertyPath' => 'formalDate',
                        'code' => Date::INVALID_FORMAT_ERROR,
                    ],
                    [
                        'propertyPath' => 'type',
                        'code' => NotBlank::IS_BLANK_ERROR,
                    ],
                    [
                        'propertyPath' => 'language',
                        'code' => NotBlank::IS_BLANK_ERROR,
                    ],
                    [
                        'propertyPath' => 'grounds[0]',
                        'code' => Type::INVALID_TYPE_ERROR,
                    ],
                ],
            ],
            'empty string input data' => [
                'input' => [
                    'uploadUuid' => '',
                    'formalDate' => '',
                ],
                'expectedViolations' => [
                    [
                        'propertyPath' => 'formalDate',
                        'code' => NotBlank::IS_BLANK_ERROR,
                    ],
                    [
                        'propertyPath' => 'uploadUuid',
                        'code' => NotBlank::IS_BLANK_ERROR,
                    ],
                    [
                        'propertyPath' => 'type',
                        'code' => NotBlank::IS_BLANK_ERROR,
                    ],
                ],
            ],
            'single space input data' => [
                'input' => [
                    'uploadUuid' => ' ',
                    'formalDate' => ' ',
                ],
                'expectedViolations' => [
                    [
                        'propertyPath' => 'formalDate',
                        'code' => NotBlank::IS_BLANK_ERROR,
                    ],
                    [
                        'propertyPath' => 'formalDate',
                        'code' => Date::INVALID_FORMAT_ERROR,
                    ],
                    [
                        'propertyPath' => 'uploadUuid',
                        'code' => NotBlank::IS_BLANK_ERROR,
                    ],
                    [
                        'propertyPath' => 'type',
                        'code' => NotBlank::IS_BLANK_ERROR,
                    ],
                    [
                        'propertyPath' => 'language',
                        'code' => NotBlank::IS_BLANK_ERROR,
                    ],
                ],
            ],
            'invalid business logic data' => [
                'input' => [
                    'uploadUuid' => '55ae5de9-55f4-3420-b50b-5cde6e07fc5a',
                    'type' => AttachmentType::PERMIT->value,
                    'formalDate' => CarbonImmutable::now()->addYear()->format('Y-m-d'),
                    'internalReference' => str_repeat('a', 256),
                    'language' => AttachmentLanguage::DUTCH->value,
                ],
                'expectedViolations' => [
                    [
                        'propertyPath' => 'formalDate',
                        'code' => LessThanOrEqual::TOO_HIGH_ERROR,
                    ],
                    [
                        'propertyPath' => 'internalReference',
                        'code' => Length::TOO_LONG_ERROR,
                    ],
                ],
            ],
            'invalid type field ANNUAL_REPORT' => [
                'input' => [
                    'uploadUuid' => '55ae5de9-55f4-3420-b50b-5cde6e07fc5a',
                    'type' => AttachmentType::ANNUAL_REPORT->value,
                    'formalDate' => CarbonImmutable::now()->format('Y-m-d'),
                    'language' => AttachmentLanguage::DUTCH->value,
                ],
                'expectedViolations' => [
                    [
                        'propertyPath' => 'type',
                        'code' => Choice::NO_SUCH_CHOICE_ERROR,
                    ],
                ],
            ],
            'invalid type field ANNUAL_PLAN' => [
                'input' => [
                    'uploadUuid' => '55ae5de9-55f4-3420-b50b-5cde6e07fc5a',
                    'type' => AttachmentType::ANNUAL_PLAN->value,
                    'formalDate' => CarbonImmutable::now()->format('Y-m-d'),
                    'language' => AttachmentLanguage::DUTCH->value,
                ],
                'expectedViolations' => [
                    [
                        'propertyPath' => 'type',
                        'code' => Choice::NO_SUCH_CHOICE_ERROR,
                    ],
                ],
            ],
        ];
    }

    public function testDeleteAnnualReportAttachment(): void
    {
        $user = UserFactory::new()
            ->asSuperAdmin()
            ->isEnabled()
            ->create();

        $attachment = AnnualReportAttachmentFactory::createOne([
            'dossier' => AnnualReportFactory::createOne([
                'organisation' => $user->getOrganisation(),
                'status' => DossierStatus::CONCEPT,
            ]),
        ])->_real();

        static::createClient()
            ->loginUser($user->_real(), 'balie')
            ->request(
                Request::METHOD_DELETE,
                sprintf('/balie/api/dossiers/%s/annual-report-attachments/%s', $attachment->getDossier()->getId(), $attachment->getId()),
                [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                ],
            );

        self::assertResponseIsSuccessful();
    }

    public function testDeleteNonExistingAnnualReportAttachment(): void
    {
        $user = UserFactory::new()
            ->asSuperAdmin()
            ->isEnabled()
            ->create();

        $dossier = AnnualReportFactory::createOne([
            'organisation' => $user->getOrganisation(),
        ])->_real();

        static::createClient()
            ->loginUser($user->_real(), 'balie')
            ->request(
                Request::METHOD_DELETE,
                sprintf('/balie/api/dossiers/%s/annual-report-attachments/%s', $dossier->getId(), $this->getFaker()->uuid()),
                [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                ],
            );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testUpdateAnnualReportAttachment(): void
    {
        $user = UserFactory::new()
            ->asSuperAdmin()
            ->isEnabled()
            ->create();

        $attachment = AnnualReportAttachmentFactory::createOne([
            'fileInfo' => FileInfoFactory::createOne([
                'name' => 'test_file.pdf',
            ]),
            'dossier' => AnnualReportFactory::createOne([
                'organisation' => $user->getOrganisation(),
            ]),
        ])->_real();

        $response = static::createClient()
            ->loginUser($user->_real(), 'balie')
            ->request(
                Request::METHOD_PUT,
                sprintf('/balie/api/dossiers/%s/annual-report-attachments/%s', $attachment->getDossier()->getId(), $attachment->getId()),
                [
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'formalDate' => $attachment->getFormalDate()->format('Y-m-d'),
                        'type' => $attachment->getType()->value,
                        'language' => $attachment->getLanguage()->value,
                        'internalReference' => $attachment->getInternalReference(),
                        'grounds' => $attachment->getGrounds(),
                    ],
                ],
            );

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(AnnualReportAttachmentDto::class);

        $response2 = static::createClient()
            ->loginUser($user->_real(), 'balie')
            ->request(
                Request::METHOD_GET,
                sprintf('/balie/api/dossiers/%s/annual-report-attachments/%s', $attachment->getDossier()->getId(), $attachment->getId()),
                [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                ],
            );

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(AnnualReportAttachmentDto::class);

        $this->assertSame($response->toArray(), $response2->toArray());
    }

    public function testUpdateNonExistingAnnualReportAttachment(): void
    {
        $user = UserFactory::new()
            ->asSuperAdmin()
            ->isEnabled()
            ->create();

        $dossier = AnnualReportFactory::createOne([
            'organisation' => $user->getOrganisation(),
        ])->_real();

        static::createClient()
            ->loginUser($user->_real(), 'balie')
            ->request(
                Request::METHOD_GET,
                sprintf('/balie/api/dossiers/%s/annual-report-attachments/%s', $dossier->getId(), $this->getFaker()->uuid()),
                [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                ],
            );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * @param array<string,string|list<mixed>>      $input
     * @param array<array-key,array<string,string>> $expectedViolations
     */
    #[DataProvider('getInvalidUpdateRequestData')]
    public function testUpdateAnnualReportAttachmentWithInvalidRequestData(array $input, array $expectedViolations): void
    {
        $user = UserFactory::new()
            ->asSuperAdmin()
            ->isEnabled()
            ->create();

        $attachment = AnnualReportAttachmentFactory::createOne([
            'fileInfo' => FileInfoFactory::createOne([
                'name' => 'test_file.pdf',
            ]),
            'dossier' => AnnualReportFactory::createOne([
                'organisation' => $user->getOrganisation(),
            ]),
        ])->_real();

        static::createClient()
            ->loginUser($user->_real(), 'balie')
            ->request(
                Request::METHOD_PUT,
                sprintf('/balie/api/dossiers/%s/annual-report-attachments/%s', $attachment->getDossier()->getId(), $attachment->getId()),
                [
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $input,
                ],
            );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertJsonContains(['violations' => $expectedViolations]);
    }

    /**
     * @return array<string,array{input:array<string,string|list<mixed>>,expectedViolations:array<array-key,array<string,string>>}>
     */
    public static function getInvalidUpdateRequestData(): array
    {
        return [
            'invalid empty string for formalDate input data' => [
                'input' => [
                    'formalDate' => '',
                ],
                'expectedViolations' => [
                    [
                        'propertyPath' => 'formalDate',
                        'code' => NotBlank::IS_BLANK_ERROR,
                    ],
                ],
            ],
            'invalid single space for formalDate input data' => [
                'input' => [
                    'formalDate' => ' ',
                ],
                'expectedViolations' => [
                    [
                        'propertyPath' => 'formalDate',
                        'code' => NotBlank::IS_BLANK_ERROR,
                    ],
                ],
            ],
            'invalid date format for formalDate input data' => [
                'input' => [
                    'formalDate' => 'foobar',
                ],
                'expectedViolations' => [
                    [
                        'propertyPath' => 'formalDate',
                        'code' => Date::INVALID_FORMAT_ERROR,
                    ],
                ],
            ],
            'invalid grounds format for grounds input date' => [
                'input' => [
                    'grounds' => [1, 2, 3],
                ],
                'expectedViolations' => [
                    [
                        'propertyPath' => 'grounds[0]',
                        'code' => Type::INVALID_TYPE_ERROR,
                    ],
                ],
            ],
            'invalid type input data' => [
                'input' => [
                    'type' => 'foobar',
                    'language' => 'foobar',
                    'grounds' => 'foobar',
                ],
                'expectedViolations' => [
                    [
                        'propertyPath' => 'type',
                        'code' => Type::INVALID_TYPE_ERROR,
                    ],
                    [
                        'propertyPath' => 'language',
                        'code' => Type::INVALID_TYPE_ERROR,
                    ],
                    [
                        'propertyPath' => 'grounds',
                        'code' => Type::INVALID_TYPE_ERROR,
                    ],
                ],
            ],
            'invalid business logic data' => [
                'input' => [
                    'formalDate' => CarbonImmutable::now()->addYear()->format('Y-m-d'),
                    'type' => AttachmentType::POINT_OF_VIEW->value,
                    'internalReference' => str_repeat('a', 256),
                ],
                'expectedViolations' => [
                    [
                        'propertyPath' => 'formalDate',
                        'code' => LessThanOrEqual::TOO_HIGH_ERROR,
                    ],
                    [
                        'propertyPath' => 'internalReference',
                        'code' => Length::TOO_LONG_ERROR,
                    ],
                ],
            ],
            'invalid type field ANNUAL_REPORT' => [
                'input' => [
                    'type' => AttachmentType::ANNUAL_REPORT->value,
                ],
                'expectedViolations' => [
                    [
                        'propertyPath' => 'type',
                        'code' => Choice::NO_SUCH_CHOICE_ERROR,
                    ],
                ],
            ],
            'invalid type field ANNUAL_PLAN' => [
                'input' => [
                    'type' => AttachmentType::ANNUAL_PLAN->value,
                ],
                'expectedViolations' => [
                    [
                        'propertyPath' => 'type',
                        'code' => Choice::NO_SUCH_CHOICE_ERROR,
                    ],
                ],
            ],
        ];
    }
}
