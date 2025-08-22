<?php

declare(strict_types=1);

namespace App\Tests\Integration\Api\Admin;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Api\Admin\ComplaintJudgementMainDocument\ComplaintJudgementMainDocumentDto;
use App\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use App\Domain\Publication\Attachment\Enum\AttachmentType;
use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Upload\Handler\UploadHandlerInterface;
use App\Domain\Upload\UploadEntity;
use App\Service\Uploader\UploadGroupId;
use App\Tests\Factory\FileInfoFactory;
use App\Tests\Factory\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementFactory;
use App\Tests\Factory\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementMainDocumentFactory;
use App\Tests\Factory\UploadEntityFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\Integration\IntegrationTestTrait;
use Carbon\CarbonImmutable;
use League\Flysystem\FilesystemOperator;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints\Choice;

final class ComplaintJudgementMainDocumentTest extends ApiTestCase
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

    public function testGetComplaintJudgementDocumentReturnsEmptySetUntilCreated(): void
    {
        $user = UserFactory::new()->asSuperAdmin()->isEnabled()->create()->_real();

        $dossier = ComplaintJudgementFactory::createOne(['organisation' => $user->getOrganisation()])->_real();

        $response = static::createClient()
            ->loginUser($user, 'balie')
            ->request(
                Request::METHOD_GET,
                sprintf('/balie/api/dossiers/%s/complaint-judgement-document', $dossier->getId()),
                [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                ],
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
            'type' => AttachmentType::COMPLAINT_JUDGEMENT->value,
            'language' => AttachmentLanguage::DUTCH->value,
            'grounds' => ['foo', 'bar'],
            'uploadUuid' => $upload->getUploadId(),
        ];
        static::createClient()
            ->loginUser($user, 'balie')
            ->request(
                Request::METHOD_POST,
                sprintf('/balie/api/dossiers/%s/complaint-judgement-document', $dossier->getId()),
                [
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $data,
                ],
            );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $response = static::createClient()
            ->loginUser($user, 'balie')
            ->request(
                Request::METHOD_GET,
                sprintf('/balie/api/dossiers/%s/complaint-judgement-document', $dossier->getId()),
                [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                ],
            );
        self::assertResponseIsSuccessful();
        $this->assertCount(1, $response->toArray(), 'Expected one main document');

        unset($data['uploadUuid']); // This is only used for processing and not returned in the response
        self::assertJsonContains([$data]);
    }

    public function testUpdateComplaintJudgementDocument(): void
    {
        $user = UserFactory::new()->asSuperAdmin()->isEnabled()->create()->_real();

        $document = ComplaintJudgementMainDocumentFactory::createOne([
            'fileInfo' => FileInfoFactory::createOne([
                'name' => 'test_file.pdf',
            ]),
            'dossier' => ComplaintJudgementFactory::createOne([
                'organisation' => $user->getOrganisation(),
            ]),
        ])->_real();

        $updateResponse = static::createClient()
            ->loginUser($user, 'balie')
            ->request(
                Request::METHOD_PUT,
                sprintf(
                    '/balie/api/dossiers/%s/complaint-judgement-document/%s',
                    $document->getDossier()->getId(),
                    $document->getId(),
                ),
                [
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'name' => 'foobar.pdf',
                    ],
                ],
            );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);
        self::assertMatchesResourceItemJsonSchema(ComplaintJudgementMainDocumentDto::class);

        $getResponse = static::createClient()
            ->loginUser($user, 'balie')
            ->request(
                Request::METHOD_GET,
                sprintf(
                    '/balie/api/dossiers/%s/complaint-judgement-document/%s',
                    $document->getDossier()->getId(),
                    $document->getId(),
                ),
                [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                ],
            );

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(ComplaintJudgementMainDocumentDto::class);

        $this->assertSame($updateResponse->toArray(), $getResponse->toArray());
    }

    public function testComplaintJudgementDocumentCanBeDeletedAfterCreation(): void
    {
        $user = UserFactory::new()->asSuperAdmin()->isEnabled()->create()->_real();

        $dossier = ComplaintJudgementFactory::createOne([
            'organisation' => $user->getOrganisation(),
            'status' => DossierStatus::CONCEPT,
        ])->_real();

        $complaintJudgementMainDocument = ComplaintJudgementMainDocumentFactory::createOne(['dossier' => $dossier]);

        static::createClient()
            ->loginUser($user, 'balie')
            ->request(
                Request::METHOD_DELETE,
                sprintf(
                    '/balie/api/dossiers/%s/complaint-judgement-document/%s',
                    $dossier->getId(),
                    $complaintJudgementMainDocument->getId(),
                ),
                [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                ],
            );
        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testComplaintJudgementDocumentCannotBeDeletedForAPublishedDossier(): void
    {
        $user = UserFactory::new()->asSuperAdmin()->isEnabled()->create()->_real();

        $dossier = ComplaintJudgementFactory::createOne([
            'organisation' => $user->getOrganisation(),
            'status' => DossierStatus::PUBLISHED,
        ])->_real();

        ComplaintJudgementMainDocumentFactory::createOne([
            'dossier' => $dossier,
        ]);

        static::createClient()
            ->loginUser($user, 'balie')
            ->request(
                Request::METHOD_DELETE,
                sprintf('/balie/api/dossiers/%s/complaint-judgement-document', $dossier->getId()),
                [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                ],
            );
        self::assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }

    public function testCreateComplaintJudgementDocumentOnlyAcceptsValidTypeValues(): void
    {
        $user = UserFactory::new()->asSuperAdmin()->isEnabled()->create()->_real();

        $dossier = ComplaintJudgementFactory::createOne([
            'organisation' => $user->getOrganisation(),
        ])->_real();

        $data = [
            'formalDate' => CarbonImmutable::yesterday()->format('Y-m-d'),
            'internalReference' => 'foo bar',
            'type' => AttachmentType::PROGRESS_REPORT->value,
            'language' => AttachmentLanguage::DUTCH->value,
            'grounds' => ['foo', 'bar'],
            'uploadUuid' => Uuid::v6(),
        ];

        static::createClient()
            ->loginUser($user, 'balie')
            ->request(
                Request::METHOD_POST,
                sprintf('/balie/api/dossiers/%s/complaint-judgement-document', $dossier->getId()),
                [
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $data,
                ],
            );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertMatchesResourceItemJsonSchema(ComplaintJudgementMainDocumentDto::class);
        self::assertJsonContains(['violations' => [
            ['propertyPath' => 'type', 'code' => Choice::NO_SUCH_CHOICE_ERROR],
        ]]);
    }

    public function testUpdateComplaintJudgementDocumentOnlyAcceptsValidTypeValues(): void
    {
        $user = UserFactory::new()->asSuperAdmin()->isEnabled()->create()->_real();

        $document = ComplaintJudgementMainDocumentFactory::createOne([
            'fileInfo' => FileInfoFactory::createOne([
                'name' => 'test_file.pdf',
            ]),
            'dossier' => ComplaintJudgementFactory::createOne([
                'organisation' => $user->getOrganisation(),
            ]),
        ])->_real();

        static::createClient()
            ->loginUser($user, 'balie')
            ->request(
                Request::METHOD_PUT,
                sprintf(
                    '/balie/api/dossiers/%s/complaint-judgement-document/%s',
                    $document->getDossier()->getId(),
                    $document->getId(),
                ),
                [
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'name' => 'foobar.pdf',
                        'type' => AttachmentType::PROGRESS_REPORT->value,
                    ],
                ],
            );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertMatchesResourceItemJsonSchema(ComplaintJudgementMainDocumentDto::class);
        self::assertJsonContains(['violations' => [
            ['propertyPath' => 'type', 'code' => Choice::NO_SUCH_CHOICE_ERROR],
        ]]);
    }
}
