<?php

declare(strict_types=1);

namespace Admin\Tests\Integration\Api\Admin;

use Admin\Api\Admin\DraftDecisionMainDocument\DraftDecisionMainDocumentDto;
use Carbon\CarbonImmutable;
use League\Flysystem\FilesystemOperator;
use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Upload\Handler\UploadHandlerInterface;
use Shared\Domain\Upload\UploadEntity;
use Shared\Service\Uploader\UploadGroupId;
use Shared\Tests\Factory\FileInfoFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\DraftDecision\DraftDecisionFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\DraftDecision\DraftDecisionMainDocumentFactory;
use Shared\Tests\Factory\UploadEntityFactory;
use Shared\Tests\Factory\UserFactory;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function sprintf;
use function Zenstruck\Foundry\Persistence\save;

final class DraftDecisionMainDocumentTest extends AdminApiTestCase
{
    protected static ?bool $alwaysBootKernel = false;

    private UploadHandlerInterface&MockInterface $uploadHandler;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        $this->uploadHandler = Mockery::mock(UploadHandlerInterface::class);
        self::getContainer()->set(UploadHandlerInterface::class, $this->uploadHandler);
    }

    public function testGetDraftDecisionDocumentReturnsEmptySetUntilCreated(): void
    {
        $user = UserFactory::new()->asDossierAdmin()->isEnabled()->create();

        $dossier = DraftDecisionFactory::createOne(['organisation' => $user->getOrganisation()]);

        $response = self::createAdminApiClient($user)
            ->request(
                Request::METHOD_GET,
                sprintf('/balie/api/dossiers/%s/draft-decision-document', $dossier->getId()),
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
        save($upload);

        $upload->passValidation(mimeType: 'application/pdf');
        save($upload);

        $this->uploadHandler
            ->expects('moveUploadedFileToStorage')
            ->with(
                Mockery::on(static fn (UploadEntity $uploadEntity) => $uploadEntity->getId() == $upload->getId()),
                Mockery::type(FilesystemOperator::class),
                Mockery::type('string'),
            );

        $data = [
            'formalDate' => CarbonImmutable::yesterday()->format('Y-m-d'),
            'internalReference' => 'foo bar',
            'type' => AttachmentType::LEGISLATIVE_PROPOSAL->value,
            'language' => AttachmentLanguage::NLD->value,
            'grounds' => ['foo', 'bar'],
            'uploadUuid' => $upload->getUploadId(),
        ];
        self::createAdminApiClient($user)
            ->request(
                Request::METHOD_POST,
                sprintf('/balie/api/dossiers/%s/draft-decision-document', $dossier->getId()),
                ['json' => $data],
            );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $response = self::createAdminApiClient($user)
            ->request(
                Request::METHOD_GET,
                sprintf('/balie/api/dossiers/%s/draft-decision-document', $dossier->getId()),
            );
        self::assertResponseIsSuccessful();
        $this->assertCount(1, $response->toArray(), 'Expected one main document');

        unset($data['uploadUuid']); // This is only used for processing and not returned in the response
        self::assertJsonContains([$data]);
    }

    public function testUpdateDraftDecisionDocument(): void
    {
        $user = UserFactory::new()->asDossierAdmin()->isEnabled()->create();

        $document = DraftDecisionMainDocumentFactory::createOne([
            'fileInfo' => FileInfoFactory::createOne([
                'name' => 'test_file.pdf',
            ]),
            'dossier' => DraftDecisionFactory::createOne([
                'organisation' => $user->getOrganisation(),
            ]),
        ]);

        $updateResponse = self::createAdminApiClient($user)
            ->request(
                Request::METHOD_PUT,
                sprintf(
                    '/balie/api/dossiers/%s/draft-decision-document/%s',
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
        self::assertMatchesResourceItemJsonSchema(DraftDecisionMainDocumentDto::class);

        $getResponse = self::createAdminApiClient($user)
            ->request(
                Request::METHOD_GET,
                sprintf(
                    '/balie/api/dossiers/%s/draft-decision-document/%s',
                    $document->getDossier()->getId(),
                    $document->getId(),
                ),
            );

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(DraftDecisionMainDocumentDto::class);

        $this->assertSame($updateResponse->toArray(), $getResponse->toArray());
    }

    public function testDraftDecisionDocumentCanBeDeletedAfterCreation(): void
    {
        $user = UserFactory::new()->asDossierAdmin()->isEnabled()->create();

        $dossier = DraftDecisionFactory::createOne([
            'organisation' => $user->getOrganisation(),
            'status' => DossierStatus::CONCEPT,
        ]);

        $mainDocument = DraftDecisionMainDocumentFactory::createOne([
            'dossier' => $dossier,
        ]);

        self::createAdminApiClient($user)
            ->request(
                Request::METHOD_DELETE,
                sprintf(
                    '/balie/api/dossiers/%s/draft-decision-document/%s',
                    $dossier->getId(),
                    $mainDocument->getId(),
                ),
            );
        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testDraftDecisionDocumentCannotBeDeletedForAPublishedDossier(): void
    {
        $user = UserFactory::new()->asDossierAdmin()->isEnabled()->create();

        $dossier = DraftDecisionFactory::createOne([
            'organisation' => $user->getOrganisation(),
            'status' => DossierStatus::PUBLISHED,
        ]);

        DraftDecisionMainDocumentFactory::createOne(['dossier' => $dossier]);

        self::createAdminApiClient($user)
            ->request(
                Request::METHOD_DELETE,
                sprintf('/balie/api/dossiers/%s/draft-decision-document', $dossier->getId()),
            );
        self::assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }
}
