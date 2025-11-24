<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Api\Admin;

use Carbon\CarbonImmutable;
use League\Flysystem\FilesystemOperator;
use Mockery\MockInterface;
use Shared\Api\Admin\AnnualReportMainDocument\AnnualReportMainDocumentDto;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Upload\Handler\UploadHandlerInterface;
use Shared\Domain\Upload\UploadEntity;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Factory\FileInfoFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\AnnualReport\AnnualReportFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\AnnualReport\AnnualReportMainDocumentFactory;
use Shared\Tests\Factory\UploadEntityFactory;
use Shared\Tests\Factory\UserFactory;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints\Choice;

final class AnnualReportMainDocumentTest extends AdminApiTestCase
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

    public function testGetAnnualReportDocumentReturnsEmptySetUntilCreated(): void
    {
        $user = UserFactory::new()->asDossierAdmin()->isEnabled()->create()->_real();

        $dossier = AnnualReportFactory::createOne(['organisation' => $user->getOrganisation()])->_real();

        $response = self::createAdminApiClient($user)
            ->request(
                Request::METHOD_GET,
                sprintf('/balie/api/dossiers/%s/annual-report-document', $dossier->getId()),
            );
        $this->assertCount(0, $response->toArray(), 'Expected no main documents yet');

        $upload = UploadEntityFactory::createOne([
            'uploadGroupId' => UploadGroupId::MAIN_DOCUMENTS,
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

        $data = [
            'formalDate' => CarbonImmutable::yesterday()->format('Y-m-d'),
            'internalReference' => 'foo bar',
            'type' => AttachmentType::ANNUAL_REPORT->value,
            'language' => AttachmentLanguage::DUTCH->value,
            'grounds' => ['foo', 'bar'],
            'uploadUuid' => $upload->getUploadId(),
        ];
        self::createAdminApiClient($user)
            ->request(
                Request::METHOD_POST,
                sprintf('/balie/api/dossiers/%s/annual-report-document', $dossier->getId()),
                ['json' => $data],
            );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $response = self::createAdminApiClient($user)
            ->request(
                Request::METHOD_GET,
                sprintf('/balie/api/dossiers/%s/annual-report-document', $dossier->getId()),
            );
        self::assertResponseIsSuccessful();
        $this->assertCount(1, $response->toArray(), 'Expected one main document');

        unset($data['uploadUuid']); // This is only used for processing and not returned in the response
        self::assertJsonContains([$data]);
    }

    public function testUpdateAnnualReportDocument(): void
    {
        $user = UserFactory::new()->asDossierAdmin()->isEnabled()->create()->_real();

        $document = AnnualReportMainDocumentFactory::createOne([
            'fileInfo' => FileInfoFactory::createOne([
                'name' => 'test_file.pdf',
            ]),
            'dossier' => AnnualReportFactory::createOne([
                'organisation' => $user->getOrganisation(),
            ]),
        ])->_real();

        $updateResponse = self::createAdminApiClient($user)
            ->request(
                Request::METHOD_PUT,
                sprintf(
                    '/balie/api/dossiers/%s/annual-report-document/%s',
                    $document->getDossier()->getId(),
                    $document->getId(),
                ),
                [
                    'json' => [
                        'name' => 'foobar.pdf',
                    ],
                ],
            );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertMatchesResourceItemJsonSchema(AnnualReportMainDocumentDto::class);

        $getResponse = self::createAdminApiClient($user)
            ->request(
                Request::METHOD_GET,
                sprintf(
                    '/balie/api/dossiers/%s/annual-report-document/%s',
                    $document->getDossier()->getId(),
                    $document->getId(),
                ),
            );

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(AnnualReportMainDocumentDto::class);

        $this->assertSame($updateResponse->toArray(), $getResponse->toArray());
    }

    public function testAnnualReportDocumentCanBeDeletedAfterCreation(): void
    {
        $user = UserFactory::new()->asDossierAdmin()->isEnabled()->create()->_real();

        $dossier = AnnualReportFactory::createOne([
            'organisation' => $user->getOrganisation(),
            'status' => DossierStatus::CONCEPT,
        ]);

        $adviceMainDocument = AnnualReportMainDocumentFactory::createOne([
            'dossier' => $dossier,
        ])->_real();

        self::createAdminApiClient($user)
            ->request(
                Request::METHOD_DELETE,
                sprintf(
                    '/balie/api/dossiers/%s/annual-report-document/%s',
                    $dossier->getId(),
                    $adviceMainDocument->getId(),
                ),
            );
        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testAnnualReportDocumentCannotBeDeletedForAPublishedDossier(): void
    {
        $user = UserFactory::new()->asDossierAdmin()->isEnabled()->create()->_real();

        $dossier = AnnualReportFactory::createOne([
            'organisation' => $user->getOrganisation(),
            'status' => DossierStatus::PUBLISHED,
        ]);

        AnnualReportMainDocumentFactory::createOne([
            'dossier' => $dossier,
        ])->_real();

        self::createAdminApiClient($user)
            ->request(
                Request::METHOD_DELETE,
                sprintf('/balie/api/dossiers/%s/annual-report-document', $dossier->getId()),
            );
        self::assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }

    public function testCreateAnnualReportDocumentOnlyAcceptsValidTypeValues(): void
    {
        $user = UserFactory::new()->asDossierAdmin()->isEnabled()->create()->_real();

        $dossier = AnnualReportFactory::createOne(['organisation' => $user->getOrganisation()]);

        $data = [
            'formalDate' => CarbonImmutable::yesterday()->format('Y-m-d'),
            'internalReference' => 'foo bar',
            'type' => AttachmentType::PROGRESS_REPORT->value,
            'language' => AttachmentLanguage::DUTCH->value,
            'grounds' => ['foo', 'bar'],
            'uploadUuid' => Uuid::v6(),
        ];
        self::createAdminApiClient($user)
            ->request(
                Request::METHOD_POST,
                sprintf('/balie/api/dossiers/%s/annual-report-document', $dossier->getId()),
                ['json' => $data],
            );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertMatchesResourceItemJsonSchema(AnnualReportMainDocumentDto::class);
        self::assertJsonContains(['violations' => [
            ['propertyPath' => 'type', 'code' => Choice::NO_SUCH_CHOICE_ERROR],
        ]]);
    }

    public function testUpdateAnnualReportDocumentOnlyAcceptsValidTypeValues(): void
    {
        $user = UserFactory::new()->asDossierAdmin()->isEnabled()->create()->_real();

        $document = AnnualReportMainDocumentFactory::createOne([
            'fileInfo' => FileInfoFactory::createOne([
                'name' => 'test_file.pdf',
            ]),
            'dossier' => AnnualReportFactory::createOne([
                'organisation' => $user->getOrganisation(),
            ]),
        ])->_real();

        self::createAdminApiClient($user)
            ->request(
                Request::METHOD_PUT,
                sprintf(
                    '/balie/api/dossiers/%s/annual-report-document/%s',
                    $document->getDossier()->getId(),
                    $document->getId(),
                ),
                [
                    'json' => [
                        'name' => 'foobar.pdf',
                        'type' => AttachmentType::PROGRESS_REPORT->value,
                    ],
                ],
            );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertMatchesResourceItemJsonSchema(AnnualReportMainDocumentDto::class);
        self::assertJsonContains(['violations' => [
            ['propertyPath' => 'type', 'code' => Choice::NO_SUCH_CHOICE_ERROR],
        ]]);
    }
}
