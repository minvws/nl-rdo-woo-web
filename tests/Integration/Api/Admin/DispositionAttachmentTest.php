<?php

declare(strict_types=1);

namespace App\Tests\Integration\Api\Admin;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Api\Admin\DispositionAttachment\DispositionAttachmentDto;
use App\Domain\Publication\Attachment\AttachmentLanguage;
use App\Domain\Publication\Attachment\AttachmentType;
use App\Domain\Publication\Dossier\DossierStatus;
use App\Service\Uploader\UploadGroupId;
use App\Tests\Factory\FileInfoFactory;
use App\Tests\Factory\Publication\Dossier\Type\Disposition\DispositionAttachmentFactory;
use App\Tests\Factory\Publication\Dossier\Type\Disposition\DispositionFactory;
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

final class DispositionAttachmentTest extends ApiTestCase
{
    use IntegrationTestTrait;

    private vfsStreamDirectory $root;

    protected function setUp(): void
    {
        $this->root = vfsStream::setup();

        self::bootKernel();
    }

    public function testGetAllDispositionAttachments(): void
    {
        $user = UserFactory::new()
            ->asAdmin()
            ->isEnabled()
            ->create();

        $dossier = DispositionFactory::createOne([
            'organisation' => $user->getOrganisation(),
        ]);

        DispositionAttachmentFactory::createMany(5, [
            'dossier' => $dossier,
        ]);

        $response = static::createClient()
            ->loginUser($user->object(), 'balie')
            ->request(
                Request::METHOD_GET,
                sprintf('/balie/api/dossiers/%s/disposition-attachments', $dossier->getId()),
                [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                ],
            );

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceCollectionJsonSchema(DispositionAttachmentDto::class);
        self::assertCount(5, $response->toArray(), 'Expected 5 Disposition attachments');
    }

    public function testGetSingleDispositionAttachment(): void
    {
        $user = UserFactory::new()
            ->asAdmin()
            ->isEnabled()
            ->create();

        $attachment = DispositionAttachmentFactory::createOne([
            'dossier' => DispositionFactory::createOne([
                'organisation' => $user->getOrganisation(),
            ]),
        ]);

        static::createClient()
            ->loginUser($user->object(), 'balie')
            ->request(
                Request::METHOD_GET,
                sprintf('/balie/api/dossiers/%s/disposition-attachments/%s', $attachment->getDossier()->getId(), $attachment->getId()),
                [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                ],
            );

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(DispositionAttachmentDto::class);
    }

    public function testGetSingleNonExistingDispositionAttachment(): void
    {
        $user = UserFactory::new()
            ->asAdmin()
            ->isEnabled()
            ->create();

        $dossier = DispositionFactory::createOne([
            'organisation' => $user->getOrganisation(),
        ]);

        static::createClient()
            ->loginUser($user->object(), 'balie')
            ->request(
                Request::METHOD_GET,
                sprintf('/balie/api/dossiers/%s/disposition-attachments/%s', $dossier->getId(), $this->getFaker()->uuid()),
                [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                ],
            );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testCreateDispositionAttachment(): void
    {
        $user = UserFactory::new()
            ->asAdmin()
            ->isEnabled()
            ->create();

        $dossier = DispositionFactory::createOne([
            'organisation' => $user->getOrganisation(),
        ]);

        $client = static::createClient()->loginUser($user->object(), 'balie');

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
                        'groupId' => UploadGroupId::DISPOSITION_ATTACHMENTS->value,
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
            sprintf('/balie/api/dossiers/%s/disposition-attachments', $dossier->getId()),
            [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'uploadUuid' => $uploadUuid,
                    'name' => 'foobar',
                    'type' => AttachmentType::COVENANT->value,
                    'formalDate' => CarbonImmutable::now()->format('Y-m-d'),
                    'language' => AttachmentLanguage::DUTCH->value,
                ],
            ],
        );

        self::assertResponseIsSuccessful();

        $client->request(
            Request::METHOD_GET,
            sprintf('/balie/api/dossiers/%s/disposition-attachments', $dossier->getId()),
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
    public function testCreateDispositionAttachmentWithInvalidRequestData(array $input, array $expectedViolations): void
    {
        $user = UserFactory::new()
            ->asAdmin()
            ->isEnabled()
            ->create();

        $dossier = DispositionFactory::createOne([
            'organisation' => $user->getOrganisation(),
        ]);

        $client = static::createClient()->loginUser($user->object(), 'balie');

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
                        'groupId' => UploadGroupId::DISPOSITION_ATTACHMENTS->value,
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
            sprintf('/balie/api/dossiers/%s/disposition-attachments', $dossier->getId()),
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
                    'name' => '',
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
                    'name' => '',
                    'formalDate' => 'foobar',
                    'grounds' => [1, 2, 3],
                ],
                'expectedViolations' => [
                    [
                        'propertyPath' => 'name',
                        'code' => NotBlank::IS_BLANK_ERROR,
                    ],
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
                    'name' => '',
                    'formalDate' => '',
                ],
                'expectedViolations' => [
                    [
                        'propertyPath' => 'name',
                        'code' => NotBlank::IS_BLANK_ERROR,
                    ],
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
                    'name' => ' ',
                    'formalDate' => ' ',
                ],
                'expectedViolations' => [
                    [
                        'propertyPath' => 'name',
                        'code' => NotBlank::IS_BLANK_ERROR,
                    ],
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
                    'name' => 'foobar',
                    'type' => AttachmentType::COVENANT->value,
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
            'missing name field' => [
                'input' => [
                    'uploadUuid' => '55ae5de9-55f4-3420-b50b-5cde6e07fc5a',
                    'formalDate' => CarbonImmutable::now()->subYear()->format('Y-m-d'),
                    'type' => AttachmentType::OBJECTION->value,
                ],
                'expectedViolations' => [
                    [
                        'propertyPath' => 'name',
                        'code' => NotBlank::IS_BLANK_ERROR,
                    ],
                ],
            ],
            'invalid type field PERMIT' => [
                'input' => [
                    'uploadUuid' => '55ae5de9-55f4-3420-b50b-5cde6e07fc5a',
                    'name' => 'foobar',
                    'type' => AttachmentType::PERMIT->value,
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
            'invalid type field DECISION_TO_IMPOSE_A_FINE' => [
                'input' => [
                    'uploadUuid' => '55ae5de9-55f4-3420-b50b-5cde6e07fc5a',
                    'name' => 'foobar',
                    'type' => AttachmentType::DECISION_TO_IMPOSE_A_FINE->value,
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

    public function testDeleteDispositionAttachment(): void
    {
        $user = UserFactory::new()
            ->asAdmin()
            ->isEnabled()
            ->create();

        $attachment = DispositionAttachmentFactory::createOne([
            'dossier' => DispositionFactory::createOne([
                'organisation' => $user->getOrganisation(),
                'status' => DossierStatus::CONCEPT,
            ]),
        ]);

        static::createClient()
            ->loginUser($user->object(), 'balie')
            ->request(
                Request::METHOD_DELETE,
                sprintf('/balie/api/dossiers/%s/disposition-attachments/%s', $attachment->getDossier()->getId(), $attachment->getId()),
                [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                ],
            );

        self::assertResponseIsSuccessful();
    }

    public function testDeleteNonExistingDispositionAttachment(): void
    {
        $user = UserFactory::new()
            ->asAdmin()
            ->isEnabled()
            ->create();

        $dossier = DispositionFactory::createOne([
            'organisation' => $user->getOrganisation(),
        ]);

        static::createClient()
            ->loginUser($user->object(), 'balie')
            ->request(
                Request::METHOD_DELETE,
                sprintf('/balie/api/dossiers/%s/disposition-attachments/%s', $dossier->getId(), $this->getFaker()->uuid()),
                [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                ],
            );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testUpdateDispositionAttachment(): void
    {
        $user = UserFactory::new()
            ->asAdmin()
            ->isEnabled()
            ->create();

        $attachment = DispositionAttachmentFactory::createOne([
            'fileInfo' => FileInfoFactory::createOne([
                'name' => 'test_file.pdf',
            ]),
            'dossier' => DispositionFactory::createOne([
                'organisation' => $user->getOrganisation(),
            ]),
        ]);

        $response = static::createClient()
            ->loginUser($user->object(), 'balie')
            ->request(
                Request::METHOD_PUT,
                sprintf('/balie/api/dossiers/%s/disposition-attachments/%s', $attachment->getDossier()->getId(), $attachment->getId()),
                [
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'name' => 'foobar.pdf',
                        'formalDate' => $attachment->getFormalDate()->format('Y-m-d'),
                        'type' => $attachment->getType()->value,
                        'language' => $attachment->getLanguage()->value,
                        'internalReference' => $attachment->getInternalReference(),
                        'grounds' => $attachment->getGrounds(),
                    ],
                ],
            );

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(DispositionAttachmentDto::class);

        $response2 = static::createClient()
            ->loginUser($user->object(), 'balie')
            ->request(
                Request::METHOD_GET,
                sprintf('/balie/api/dossiers/%s/disposition-attachments/%s', $attachment->getDossier()->getId(), $attachment->getId()),
                [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                ],
            );

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(DispositionAttachmentDto::class);

        $this->assertSame($response->toArray(), $response2->toArray());
    }

    public function testUpdateNonExistingDispositionAttachment(): void
    {
        $user = UserFactory::new()
            ->asAdmin()
            ->isEnabled()
            ->create();

        $dossier = DispositionFactory::createOne([
            'organisation' => $user->getOrganisation(),
        ]);

        static::createClient()
            ->loginUser($user->object(), 'balie')
            ->request(
                Request::METHOD_GET,
                sprintf('/balie/api/dossiers/%s/disposition-attachments/%s', $dossier->getId(), $this->getFaker()->uuid()),
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
    public function testUpdateDispositionAttachmentWithInvalidRequestData(array $input, array $expectedViolations): void
    {
        $user = UserFactory::new()
            ->asAdmin()
            ->isEnabled()
            ->create();

        $attachment = DispositionAttachmentFactory::createOne([
            'fileInfo' => FileInfoFactory::createOne([
                'name' => 'test_file.pdf',
            ]),
            'dossier' => DispositionFactory::createOne([
                'organisation' => $user->getOrganisation(),
            ]),
        ]);

        static::createClient()
            ->loginUser($user->object(), 'balie')
            ->request(
                Request::METHOD_PUT,
                sprintf('/balie/api/dossiers/%s/disposition-attachments/%s', $attachment->getDossier()->getId(), $attachment->getId()),
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
            'invalid name input data' => [
                'input' => [
                    'name' => '',
                ],
                'expectedViolations' => [
                    [
                        'propertyPath' => 'name',
                        'code' => NotBlank::IS_BLANK_ERROR,
                    ],
                ],
            ],
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
            'invalid type field EXEMPTION_DECISION' => [
                'input' => [
                    'type' => AttachmentType::EXEMPTION_DECISION->value,
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
