<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Api\Admin;

use Carbon\CarbonImmutable;
use League\Flysystem\FilesystemOperator;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Shared\Api\Admin\OtherPublicationAttachment\OtherPublicationAttachmentDto;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Upload\Handler\UploadHandlerInterface;
use Shared\Domain\Upload\UploadEntity;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Factory\FileInfoFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\OtherPublication\OtherPublicationAttachmentFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\OtherPublication\OtherPublicationFactory;
use Shared\Tests\Factory\UploadEntityFactory;
use Shared\Tests\Factory\UserFactory;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Date;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

final class OtherPublicationAttachmentTest extends AdminApiTestCase
{
    protected static ?bool $alwaysBootKernel = false;

    private UploadHandlerInterface&MockInterface $uploadHandler;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->uploadHandler = \Mockery::mock(UploadHandlerInterface::class);
        self::getContainer()->set(UploadHandlerInterface::class, $this->uploadHandler);
    }

    public function testGetAllOtherPublicationAttachments(): void
    {
        $user = UserFactory::new()->asSuperAdmin()->isEnabled()->create()->_real();

        $dossier = OtherPublicationFactory::createOne(['organisation' => $user->getOrganisation()])->_real();

        OtherPublicationAttachmentFactory::createMany(5, ['dossier' => $dossier]);

        $response = self::createAdminApiClient($user)
            ->request(
                Request::METHOD_GET,
                sprintf('/balie/api/dossiers/%s/other-publication-attachments', $dossier->getId()),
            );

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceCollectionJsonSchema(OtherPublicationAttachmentDto::class);
        self::assertCount(5, $response->toArray(), 'Expected 5 OtherPublication attachments');
    }

    public function testGetSingleOtherPublicationAttachment(): void
    {
        $user = UserFactory::new()->asSuperAdmin()->isEnabled()->create()->_real();

        $attachment = OtherPublicationAttachmentFactory::createOne([
            'dossier' => OtherPublicationFactory::createOne([
                'organisation' => $user->getOrganisation(),
            ]),
        ])->_real();

        self::createAdminApiClient($user)
            ->request(
                Request::METHOD_GET,
                sprintf('/balie/api/dossiers/%s/other-publication-attachments/%s', $attachment->getDossier()->getId(), $attachment->getId()),
            );

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(OtherPublicationAttachmentDto::class);
    }

    public function testGetSingleNonExistingOtherPublicationAttachment(): void
    {
        $user = UserFactory::new()->asSuperAdmin()->isEnabled()->create()->_real();

        $dossier = OtherPublicationFactory::createOne(['organisation' => $user->getOrganisation()])->_real();

        self::createAdminApiClient($user)
            ->request(
                Request::METHOD_GET,
                sprintf('/balie/api/dossiers/%s/other-publication-attachments/%s', $dossier->getId(), $this->getFaker()->uuid()),
            );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testCreateOtherPublicationAttachment(): void
    {
        $user = UserFactory::new()->asSuperAdmin()->isEnabled()->create()->_real();
        $organisation = $user->getOrganisation();
        $dossier = OtherPublicationFactory::createOne(['organisation' => $organisation])->_real();

        $upload = UploadEntityFactory::createOne([
            'uploadGroupId' => UploadGroupId::ATTACHMENTS,
            'context' => new InputBag([
                'dossierId' => $dossier->getId()->toRfc4122(),
            ]),
        ]);

        $upload->finishUploading(
            filename: 'filename.pdf',
            size: 3547981,
        );
        $upload->_save();

        $upload->passValidation(mimeType: 'application/pdf');
        $upload->_save();
        $upload = $upload->_real();

        $this->uploadHandler
            ->shouldReceive('moveUploadedFileToStorage')
            ->once()
            ->with(
                \Mockery::on(fn (UploadEntity $uploadEntity) => $uploadEntity->getId() == $upload->getId()),
                \Mockery::type(FilesystemOperator::class),
                \Mockery::type('string'),
            );

        self::setActiveOrganisation($organisation);

        self::createAdminApiClient($user)
            ->request(
                Request::METHOD_POST,
                sprintf('/balie/api/dossiers/%s/other-publication-attachments', $dossier->getId()),
                [
                    'json' => [
                        'uploadUuid' => $upload->getUploadId(),
                        'type' => AttachmentType::COVENANT->value,
                        'formalDate' => CarbonImmutable::now()->format('Y-m-d'),
                        'language' => AttachmentLanguage::DUTCH->value,
                    ],
                ],
            );

        self::assertResponseIsSuccessful();

        self::createAdminApiClient($user)
            ->request(
                Request::METHOD_GET,
                sprintf('/balie/api/dossiers/%s/other-publication-attachments', $dossier->getId()),
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
        $user = UserFactory::new()->asSuperAdmin()->isEnabled()->create()->_real();

        $dossier = OtherPublicationFactory::createOne(['organisation' => $user->getOrganisation()])->_real();

        self::createAdminApiClient($user)
            ->request(
                Request::METHOD_POST,
                sprintf('/balie/api/dossiers/%s/other-publication-attachments', $dossier->getId()),
                ['json' => $input],
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
        $user = UserFactory::new()->asSuperAdmin()->isEnabled()->create()->_real();

        $attachment = OtherPublicationAttachmentFactory::createOne([
            'dossier' => OtherPublicationFactory::createOne([
                'organisation' => $user->getOrganisation(),
                'status' => DossierStatus::CONCEPT,
            ]),
        ])->_real();

        self::createAdminApiClient($user)
            ->request(
                Request::METHOD_DELETE,
                sprintf('/balie/api/dossiers/%s/other-publication-attachments/%s', $attachment->getDossier()->getId(), $attachment->getId()),
            );

        self::assertResponseIsSuccessful();
    }

    public function testDeleteNonExistingOtherPublicationAttachment(): void
    {
        $user = UserFactory::new()->asSuperAdmin()->isEnabled()->create()->_real();

        $dossier = OtherPublicationFactory::createOne(['organisation' => $user->getOrganisation()])->_real();

        self::createAdminApiClient($user)
            ->request(
                Request::METHOD_DELETE,
                sprintf('/balie/api/dossiers/%s/other-publication-attachments/%s', $dossier->getId(), $this->getFaker()->uuid()),
            );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testUpdateOtherPublicationAttachment(): void
    {
        $user = UserFactory::new()->asSuperAdmin()->isEnabled()->create()->_real();

        $attachment = OtherPublicationAttachmentFactory::createOne([
            'fileInfo' => FileInfoFactory::createOne([
                'name' => 'test_file.pdf',
            ]),
            'dossier' => OtherPublicationFactory::createOne([
                'organisation' => $user->getOrganisation(),
            ]),
        ])->_real();

        $updateResponse = self::createAdminApiClient($user)
            ->request(
                Request::METHOD_PUT,
                sprintf('/balie/api/dossiers/%s/other-publication-attachments/%s', $attachment->getDossier()->getId(), $attachment->getId()),
                [
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

        $getResponse = self::createAdminApiClient($user)
            ->request(
                Request::METHOD_GET,
                sprintf('/balie/api/dossiers/%s/other-publication-attachments/%s', $attachment->getDossier()->getId(), $attachment->getId()),
            );

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(OtherPublicationAttachmentDto::class);

        $this->assertSame($updateResponse->toArray(), $getResponse->toArray());
    }

    public function testUpdateNonExistingOtherPublicationAttachment(): void
    {
        $user = UserFactory::new()->asSuperAdmin()->isEnabled()->create()->_real();

        $dossier = OtherPublicationFactory::createOne(['organisation' => $user->getOrganisation()])->_real();

        self::createAdminApiClient($user)
            ->request(
                Request::METHOD_GET,
                sprintf('/balie/api/dossiers/%s/other-publication-attachments/%s', $dossier->getId(), $this->getFaker()->uuid()),
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
        $user = UserFactory::new()->asSuperAdmin()->isEnabled()->create()->_real();

        $attachment = OtherPublicationAttachmentFactory::createOne([
            'fileInfo' => FileInfoFactory::createOne([
                'name' => 'test_file.pdf',
            ]),
            'dossier' => OtherPublicationFactory::createOne([
                'organisation' => $user->getOrganisation(),
            ]),
        ])->_real();

        self::createAdminApiClient($user)
            ->request(
                Request::METHOD_PUT,
                sprintf('/balie/api/dossiers/%s/other-publication-attachments/%s', $attachment->getDossier()->getId(), $attachment->getId()),
                ['json' => $input],
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
