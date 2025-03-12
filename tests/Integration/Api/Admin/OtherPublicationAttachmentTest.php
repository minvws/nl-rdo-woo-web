<?php

declare(strict_types=1);

namespace App\Tests\Integration\Api\Admin;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Api\Admin\OtherPublicationAttachment\OtherPublicationAttachmentDto;
use App\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use App\Domain\Publication\Attachment\Enum\AttachmentType;
use App\Domain\Publication\Dossier\DossierStatus;
use App\Service\Uploader\UploadGroupId;
use App\Tests\Factory\FileInfoFactory;
use App\Tests\Factory\Publication\Dossier\Type\OtherPublication\OtherPublicationAttachmentFactory;
use App\Tests\Factory\Publication\Dossier\Type\OtherPublication\OtherPublicationFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\Integration\IntegrationTestTrait;
use Carbon\CarbonImmutable;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Date;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

final class OtherPublicationAttachmentTest extends ApiTestCase
{
    use IntegrationTestTrait;

    private vfsStreamDirectory $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = vfsStream::setup();

        self::bootKernel();
    }

    public function testGetAllOtherPublicationAttachments(): void
    {
        $user = UserFactory::new()
            ->asSuperAdmin()
            ->isEnabled()
            ->create();

        $dossier = OtherPublicationFactory::createOne([
            'organisation' => $user->getOrganisation(),
        ]);

        OtherPublicationAttachmentFactory::createMany(5, [
            'dossier' => $dossier,
        ]);

        $response = static::createClient()
            ->loginUser($user->_real(), 'balie')
            ->request(
                Request::METHOD_GET,
                sprintf('/balie/api/dossiers/%s/other-publication-attachments', $dossier->getId()),
                [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                ],
            );

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceCollectionJsonSchema(OtherPublicationAttachmentDto::class);
        self::assertCount(5, $response->toArray(), 'Expected 5 OtherPublication attachments');
    }

    public function testGetSingleOtherPublicationAttachment(): void
    {
        $user = UserFactory::new()
            ->asSuperAdmin()
            ->isEnabled()
            ->create();

        $attachment = OtherPublicationAttachmentFactory::createOne([
            'dossier' => OtherPublicationFactory::createOne([
                'organisation' => $user->getOrganisation(),
            ]),
        ]);

        static::createClient()
            ->loginUser($user->_real(), 'balie')
            ->request(
                Request::METHOD_GET,
                sprintf('/balie/api/dossiers/%s/other-publication-attachments/%s', $attachment->getDossier()->getId(), $attachment->getId()),
                [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                ],
            );

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(OtherPublicationAttachmentDto::class);
    }

    public function testGetSingleNonExistingOtherPublicationAttachment(): void
    {
        $user = UserFactory::new()
            ->asSuperAdmin()
            ->isEnabled()
            ->create();

        $dossier = OtherPublicationFactory::createOne([
            'organisation' => $user->getOrganisation(),
        ]);

        static::createClient()
            ->loginUser($user->_real(), 'balie')
            ->request(
                Request::METHOD_GET,
                sprintf('/balie/api/dossiers/%s/other-publication-attachments/%s', $dossier->getId(), $this->getFaker()->uuid()),
                [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                ],
            );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testCreateOtherPublicationAttachment(): void
    {
        $user = UserFactory::new()
            ->asSuperAdmin()
            ->isEnabled()
            ->create();

        $dossier = OtherPublicationFactory::createOne([
            'organisation' => $user->getOrganisation(),
        ]);

        $client = static::createClient()->loginUser($user->_real(), 'balie');

        vfsStream::newFile('test_file.pdf')
            ->withContent('This is a test file.')
            ->at($this->root);

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
            sprintf('/balie/api/dossiers/%s/other-publication-attachments', $dossier->getId()),
            [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'uploadUuid' => $uploadUuid,
                    'type' => AttachmentType::COVENANT->value,
                    'formalDate' => CarbonImmutable::now()->format('Y-m-d'),
                    'language' => AttachmentLanguage::DUTCH->value,
                ],
            ],
        );

        self::assertResponseIsSuccessful();

        $client->request(
            Request::METHOD_GET,
            sprintf('/balie/api/dossiers/%s/other-publication-attachments', $dossier->getId()),
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
    public function testCreateOtherPublicationAttachmentWithInvalidRequestData(array $input, array $expectedViolations): void
    {
        $user = UserFactory::new()
            ->asSuperAdmin()
            ->isEnabled()
            ->create();

        $dossier = OtherPublicationFactory::createOne([
            'organisation' => $user->getOrganisation(),
        ]);

        $client = static::createClient()->loginUser($user->_real(), 'balie');

        vfsStream::newFile('test_file.pdf')
            ->withContent('This is a test file.')
            ->at($this->root);

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
            sprintf('/balie/api/dossiers/%s/other-publication-attachments', $dossier->getId()),
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
        ];
    }

    public function testDeleteOtherPublicationAttachment(): void
    {
        $user = UserFactory::new()
            ->asSuperAdmin()
            ->isEnabled()
            ->create();

        $attachment = OtherPublicationAttachmentFactory::createOne([
            'dossier' => OtherPublicationFactory::createOne([
                'organisation' => $user->getOrganisation(),
                'status' => DossierStatus::CONCEPT,
            ]),
        ]);

        static::createClient()
            ->loginUser($user->_real(), 'balie')
            ->request(
                Request::METHOD_DELETE,
                sprintf('/balie/api/dossiers/%s/other-publication-attachments/%s', $attachment->getDossier()->getId(), $attachment->getId()),
                [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                ],
            );

        self::assertResponseIsSuccessful();
    }

    public function testDeleteNonExistingOtherPublicationAttachment(): void
    {
        $user = UserFactory::new()
            ->asSuperAdmin()
            ->isEnabled()
            ->create();

        $dossier = OtherPublicationFactory::createOne([
            'organisation' => $user->getOrganisation(),
        ]);

        static::createClient()
            ->loginUser($user->_real(), 'balie')
            ->request(
                Request::METHOD_DELETE,
                sprintf('/balie/api/dossiers/%s/other-publication-attachments/%s', $dossier->getId(), $this->getFaker()->uuid()),
                [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                ],
            );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testUpdateOtherPublicationAttachment(): void
    {
        $user = UserFactory::new()
            ->asSuperAdmin()
            ->isEnabled()
            ->create();

        $attachment = OtherPublicationAttachmentFactory::createOne([
            'fileInfo' => FileInfoFactory::createOne([
                'name' => 'test_file.pdf',
            ]),
            'dossier' => OtherPublicationFactory::createOne([
                'organisation' => $user->getOrganisation(),
            ]),
        ]);

        $response = static::createClient()
            ->loginUser($user->_real(), 'balie')
            ->request(
                Request::METHOD_PUT,
                sprintf('/balie/api/dossiers/%s/other-publication-attachments/%s', $attachment->getDossier()->getId(), $attachment->getId()),
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
        self::assertMatchesResourceItemJsonSchema(OtherPublicationAttachmentDto::class);

        $response2 = static::createClient()
            ->loginUser($user->_real(), 'balie')
            ->request(
                Request::METHOD_GET,
                sprintf('/balie/api/dossiers/%s/other-publication-attachments/%s', $attachment->getDossier()->getId(), $attachment->getId()),
                [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                ],
            );

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(OtherPublicationAttachmentDto::class);

        $this->assertSame($response->toArray(), $response2->toArray());
    }

    public function testUpdateNonExistingOtherPublicationAttachment(): void
    {
        $user = UserFactory::new()
            ->asSuperAdmin()
            ->isEnabled()
            ->create();

        $dossier = OtherPublicationFactory::createOne([
            'organisation' => $user->getOrganisation(),
        ]);

        static::createClient()
            ->loginUser($user->_real(), 'balie')
            ->request(
                Request::METHOD_GET,
                sprintf('/balie/api/dossiers/%s/other-publication-attachments/%s', $dossier->getId(), $this->getFaker()->uuid()),
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
    public function testUpdateOtherPublicationAttachmentWithInvalidRequestData(array $input, array $expectedViolations): void
    {
        $user = UserFactory::new()
            ->asSuperAdmin()
            ->isEnabled()
            ->create();

        $attachment = OtherPublicationAttachmentFactory::createOne([
            'fileInfo' => FileInfoFactory::createOne([
                'name' => 'test_file.pdf',
            ]),
            'dossier' => OtherPublicationFactory::createOne([
                'organisation' => $user->getOrganisation(),
            ]),
        ]);

        static::createClient()
            ->loginUser($user->_real(), 'balie')
            ->request(
                Request::METHOD_PUT,
                sprintf('/balie/api/dossiers/%s/other-publication-attachments/%s', $attachment->getDossier()->getId(), $attachment->getId()),
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
        ];
    }
}
