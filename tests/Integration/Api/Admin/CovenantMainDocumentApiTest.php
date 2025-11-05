<?php

declare(strict_types=1);

namespace App\Tests\Integration\Api\Admin;

use App\Api\Admin\CovenantMainDocument\CovenantMainDocumentDto;
use App\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use App\Domain\Publication\Attachment\Enum\AttachmentType;
use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Upload\Handler\UploadHandlerInterface;
use App\Domain\Upload\UploadEntity;
use App\Service\Uploader\UploadGroupId;
use App\Tests\Factory\FileInfoFactory;
use App\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantFactory;
use App\Tests\Factory\Publication\Dossier\Type\Covenant\CovenantMainDocumentFactory;
use App\Tests\Factory\UploadEntityFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\Integration\IntegrationTestTrait;
use Carbon\CarbonImmutable;
use League\Flysystem\FilesystemOperator;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CovenantMainDocumentApiTest extends AdminApiTestCase
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

    public function testGetCovenantDocumentReturnsEmptySetUntilCreated(): void
    {
        $user = UserFactory::new()->asDossierAdmin()->isEnabled()->create()->_real();

        $dossier = CovenantFactory::createOne([
            'organisation' => $user->getOrganisation(),
        ])->_real();

        $response = self::createAdminApiClient($user)
            ->request(
                Request::METHOD_GET,
                sprintf('/balie/api/dossiers/%s/covenant-document', $dossier->getId()),
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
            'type' => AttachmentType::COVENANT->value,
            'language' => AttachmentLanguage::DUTCH->value,
            'grounds' => ['foo', 'bar'],
            'uploadUuid' => $upload->getUploadId(),
        ];
        self::createAdminApiClient($user)
            ->request(
                Request::METHOD_POST,
                sprintf('/balie/api/dossiers/%s/covenant-document', $dossier->getId()),
                ['json' => $data],
            );

        self::assertResponseStatusCodeSame(201);

        $response = self::createAdminApiClient($user)
            ->request(
                Request::METHOD_GET,
                sprintf('/balie/api/dossiers/%s/covenant-document', $dossier->getId()),
            );
        self::assertResponseIsSuccessful();
        $this->assertCount(1, $response->toArray(), 'Expected one main document');

        unset($data['uploadUuid']); // This is only used for processing and not returned in the response
        self::assertJsonContains([$data]);
    }

    public function testUpdateAnnualReportDocument(): void
    {
        $user = UserFactory::new()->asDossierAdmin()->isEnabled()->create()->_real();

        $document = CovenantMainDocumentFactory::createOne([
            'fileInfo' => FileInfoFactory::createOne([
                'name' => 'test_file.pdf',
            ]),
            'dossier' => CovenantFactory::createOne([
                'organisation' => $user->getOrganisation(),
            ]),
        ])->_real();

        $updateResponse = self::createAdminApiClient($user)
            ->request(
                Request::METHOD_PUT,
                sprintf(
                    '/balie/api/dossiers/%s/covenant-document/%s',
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
        self::assertMatchesResourceItemJsonSchema(CovenantMainDocumentDto::class);

        $getUpdate = self::createAdminApiClient($user)
            ->request(
                Request::METHOD_GET,
                sprintf(
                    '/balie/api/dossiers/%s/covenant-document/%s',
                    $document->getDossier()->getId(),
                    $document->getId(),
                ),
            );

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(CovenantMainDocumentDto::class);

        $this->assertSame($updateResponse->toArray(), $getUpdate->toArray());
    }

    public function testCovenantDocumentCanBeDeletedAfterCreation(): void
    {
        $user = UserFactory::new()->asDossierAdmin()->isEnabled()->create()->_real();

        $dossier = CovenantFactory::createOne([
            'organisation' => $user->getOrganisation(),
            'status' => DossierStatus::CONCEPT,
        ])->_real();

        $covenantMainDocument = CovenantMainDocumentFactory::createOne(['dossier' => $dossier])->_real();

        self::createAdminApiClient($user)
            ->request(
                Request::METHOD_DELETE,
                sprintf(
                    '/balie/api/dossiers/%s/covenant-document/%s',
                    $dossier->getId(),
                    $covenantMainDocument->getId(),
                ),
            );
        self::assertResponseStatusCodeSame(204);
    }

    public function testCovenantDocumentCannotBeDeletedForAPublishedDossier(): void
    {
        $user = UserFactory::new()->asDossierAdmin()->isEnabled()->create()->_real();

        $dossier = CovenantFactory::createOne([
            'organisation' => $user->getOrganisation(),
            'status' => DossierStatus::PUBLISHED,
        ])->_real();

        CovenantMainDocumentFactory::createOne(['dossier' => $dossier]);

        self::createAdminApiClient($user)
            ->request(
                Request::METHOD_DELETE,
                sprintf('/balie/api/dossiers/%s/covenant-document', $dossier->getId()),
            );
        self::assertResponseStatusCodeSame(405);
    }
}
