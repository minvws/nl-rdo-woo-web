<?php

declare(strict_types=1);

namespace App\Tests\Integration\Api\Admin;

use App\Api\Admin\InvestigationReportAttachment\InvestigationReportAttachmentDto;
use App\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use App\Domain\Publication\Attachment\Enum\AttachmentType;
use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Upload\Handler\UploadHandlerInterface;
use App\Domain\Upload\UploadEntity;
use App\Service\Uploader\UploadGroupId;
use App\Tests\Factory\FileInfoFactory;
use App\Tests\Factory\OrganisationFactory;
use App\Tests\Factory\Publication\Dossier\Type\InvestigationReport\InvestigationReportAttachmentFactory;
use App\Tests\Factory\Publication\Dossier\Type\InvestigationReport\InvestigationReportFactory;
use App\Tests\Factory\UploadEntityFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\Integration\IntegrationTestTrait;
use Carbon\CarbonImmutable;
use League\Flysystem\FilesystemOperator;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Date;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

final class InvestigationReportAttachmentTest extends AdminApiTestCase
{
    use IntegrationTestTrait;

    protected static ?bool $alwaysBootKernel = false;

    private UploadHandlerInterface&MockInterface $uploadHandler;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->uploadHandler = \Mockery::mock(UploadHandlerInterface::class);
        self::getContainer()->set(UploadHandlerInterface::class, $this->uploadHandler);
    }

    public function testGetAllInvestigationReportAttachments(): void
    {
        $user = UserFactory::new()->asSuperAdmin()->isEnabled()->create()->_real();

        $dossier = InvestigationReportFactory::createOne(['organisation' => $user->getOrganisation()])->_real();

        InvestigationReportAttachmentFactory::createMany(5, ['dossier' => $dossier]);

        $response = self::createAdminApiClient($user)
            ->request(
                Request::METHOD_GET,
                \sprintf('/balie/api/dossiers/%s/investigation-report-attachments', $dossier->getId()),
            );

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceCollectionJsonSchema(InvestigationReportAttachmentDto::class);
        self::assertCount(5, $response->toArray(), 'Expected 5 investigationReport attachments');
    }

    public function testGetSingleInvestigationReportAttachment(): void
    {
        $user = UserFactory::new()->asSuperAdmin()->isEnabled()->create()->_real();

        $attachment = InvestigationReportAttachmentFactory::createOne([
            'dossier' => InvestigationReportFactory::createOne([
                'organisation' => $user->getOrganisation(),
            ]),
        ])->_real();

        self::createAdminApiClient($user)
            ->request(
                Request::METHOD_GET,
                \sprintf('/balie/api/dossiers/%s/investigation-report-attachments/%s', $attachment->getDossier()->getId(), $attachment->getId()),
            );

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(InvestigationReportAttachmentDto::class);
    }

    public function testGetSingleNonExistingInvestigationReportAttachment(): void
    {
        $user = UserFactory::new()->asSuperAdmin()->isEnabled()->create()->_real();

        $dossier = InvestigationReportFactory::createOne(['organisation' => $user->getOrganisation()])->_real();

        self::createAdminApiClient($user)
            ->request(
                Request::METHOD_GET,
                \sprintf('/balie/api/dossiers/%s/investigation-report-attachments/%s', $dossier->getId(), $this->getFaker()->uuid()),
            );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testCreateInvestigationReportAttachmentFoo(): void
    {
        $user = UserFactory::new()->asDossierAdmin()->isEnabled()->create()->_real();

        $dossier = InvestigationReportFactory::createOne(['organisation' => $user->getOrganisation()])->_real();

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

        self::createAdminApiClient($user)
            ->request(
                Request::METHOD_POST,
                \sprintf('/balie/api/dossiers/%s/investigation-report-attachments', $dossier->getId()),
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
                \sprintf('/balie/api/dossiers/%s/investigation-report-attachments', $dossier->getId()),
            );

        self::assertResponseIsSuccessful();
    }

    /**
     * @param array<string,string|list<mixed>>      $input
     * @param array<array-key,array<string,string>> $expectedViolations
     */
    #[DataProvider('getInvalidCreateRequestData')]
    public function testCreateInvestigationReportAttachmentWithInvalidRequestData(array $input, array $expectedViolations): void
    {
        $user = UserFactory::new()->asSuperAdmin()->isEnabled()->create()->_real();

        $dossier = InvestigationReportFactory::createOne(['organisation' => $user->getOrganisation()])->_real();

        self::createAdminApiClient($user)
            ->request(
                Request::METHOD_POST,
                \sprintf('/balie/api/dossiers/%s/investigation-report-attachments', $dossier->getId()),
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
            'invalid type field ANNUAL_PLAN' => [
                'input' => [
                    'uploadUuid' => '55ae5de9-55f4-3420-b50b-5cde6e07fc5a',
                    'type' => AttachmentType::RESEARCH_REPORT->value,
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

    public function testDeleteInvestigationReportAttachment(): void
    {
        $user = UserFactory::new()->asSuperAdmin()->isEnabled()->create()->_real();

        $attachment = InvestigationReportAttachmentFactory::createOne([
            'dossier' => InvestigationReportFactory::createOne([
                'organisation' => $user->getOrganisation(),
                'status' => DossierStatus::CONCEPT,
            ]),
        ])->_real();

        self::createAdminApiClient($user)
            ->request(
                Request::METHOD_DELETE,
                \sprintf('/balie/api/dossiers/%s/investigation-report-attachments/%s', $attachment->getDossier()->getId(), $attachment->getId()),
            );

        self::assertResponseIsSuccessful();
    }

    public function testDeleteNonExistingInvestigationReportAttachment(): void
    {
        $user = UserFactory::new()->asSuperAdmin()->isEnabled()->create()->_real();

        $dossier = InvestigationReportFactory::createOne(['organisation' => $user->getOrganisation()])->_real();

        self::createAdminApiClient($user)
            ->request(
                Request::METHOD_DELETE,
                \sprintf('/balie/api/dossiers/%s/investigation-report-attachments/%s', $dossier->getId(), $this->getFaker()->uuid()),
            );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testUpdateInvestigationReportAttachment(): void
    {
        $user = UserFactory::new()->asSuperAdmin()->isEnabled()->create()->_real();

        $attachment = InvestigationReportAttachmentFactory::createOne([
            'fileInfo' => FileInfoFactory::createOne([
                'name' => 'test_file.pdf',
            ]),
            'dossier' => InvestigationReportFactory::createOne([
                'organisation' => $user->getOrganisation(),
            ]),
        ])->_real();

        $response = self::createAdminApiClient($user)
            ->request(
                Request::METHOD_PUT,
                \sprintf('/balie/api/dossiers/%s/investigation-report-attachments/%s', $attachment->getDossier()->getId(), $attachment->getId()),
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
        self::assertMatchesResourceItemJsonSchema(InvestigationReportAttachmentDto::class);

        $response2 = self::createAdminApiClient($user)
            ->request(
                Request::METHOD_GET,
                \sprintf('/balie/api/dossiers/%s/investigation-report-attachments/%s', $attachment->getDossier()->getId(), $attachment->getId()),
            );

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(InvestigationReportAttachmentDto::class);

        $this->assertSame($response->toArray(), $response2->toArray());
    }

    public function testUpdateNonExistingInvestigationReportAttachment(): void
    {
        $user = UserFactory::new()->asSuperAdmin()->isEnabled()->create()->_real();

        $dossier = InvestigationReportFactory::createOne(['organisation' => $user->getOrganisation()])->_real();

        self::createAdminApiClient($user)
            ->request(
                Request::METHOD_GET,
                \sprintf('/balie/api/dossiers/%s/investigation-report-attachments/%s', $dossier->getId(), $this->getFaker()->uuid()),
            );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * @param array<string,string|list<mixed>>      $input
     * @param array<array-key,array<string,string>> $expectedViolations
     */
    #[DataProvider('getInvalidUpdateRequestData')]
    public function testUpdateInvestigationReportAttachmentWithInvalidRequestData(array $input, array $expectedViolations): void
    {
        $user = UserFactory::new()->asSuperAdmin()->isEnabled()->create()->_real();

        $attachment = InvestigationReportAttachmentFactory::createOne([
            'fileInfo' => FileInfoFactory::createOne([
                'name' => 'test_file.pdf',
            ]),
            'dossier' => InvestigationReportFactory::createOne([
                'organisation' => $user->getOrganisation(),
            ]),
        ])->_real();

        self::createAdminApiClient($user)
            ->request(
                Request::METHOD_PUT,
                \sprintf('/balie/api/dossiers/%s/investigation-report-attachments/%s', $attachment->getDossier()->getId(), $attachment->getId()),
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
            'invalid type field RESEARCH_REPORT' => [
                'input' => [
                    'type' => AttachmentType::RESEARCH_REPORT->value,
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

    public function testUpdateInvestigationReportAttachmentAllowedWhenDossierFromOtherOrganisationWithPermission(): void
    {
        $user = UserFactory::new()->asSuperAdmin()->isEnabled()->create()->_real();
        $otherOrganisation = OrganisationFactory::new()->create()->_real();
        $attachment = InvestigationReportAttachmentFactory::createOne([
            'dossier' => InvestigationReportFactory::createOne([
                'organisation' => $otherOrganisation,
            ]),
        ])->_real();

        self::setActiveOrganisation($otherOrganisation);

        self::createAdminApiClient($user)
            ->request(
                Request::METHOD_PUT,
                \sprintf('/balie/api/dossiers/%s/investigation-report-attachments/%s', $attachment->getDossier()->getId(), $attachment->getId()),
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

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testUpdateInvestigationReportAttachmentNotAllowedWhenDossierFromOtherOrganisationWithoutPermission(): void
    {
        $user = UserFactory::new()->asOrganisationAdmin()->isEnabled()->create()->_real();
        $otherOrganisation = OrganisationFactory::new()->create()->_real();

        $attachment = InvestigationReportAttachmentFactory::createOne([
            'fileInfo' => FileInfoFactory::createOne([
                'name' => 'test_file.pdf',
            ]),
            'dossier' => InvestigationReportFactory::createOne([
                'organisation' => $otherOrganisation,
                'status' => DossierStatus::PUBLISHED,
            ]),
        ])->_real();

        self::createAdminApiClient($user)
            ->request(
                Request::METHOD_PUT,
                \sprintf('/balie/api/dossiers/%s/investigation-report-attachments/%s', $attachment->getDossier()->getId(), $attachment->getId()),
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

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
