<?php

declare(strict_types=1);

namespace App\Tests\Integration\Api\Admin;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Api\Admin\DispositionMainDocument\DispositionMainDocumentDto;
use App\Domain\Publication\Attachment\AttachmentLanguage;
use App\Domain\Publication\Attachment\AttachmentType;
use App\Domain\Publication\Dossier\DossierStatus;
use App\Service\Uploader\UploadGroupId;
use App\Tests\Factory\FileInfoFactory;
use App\Tests\Factory\Publication\Dossier\Type\Disposition\DispositionFactory;
use App\Tests\Factory\Publication\Dossier\Type\Disposition\DispositionMainDocumentFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\Integration\IntegrationTestTrait;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Choice;

final class DispositionMainDocumentTest extends ApiTestCase
{
    use IntegrationTestTrait;

    private vfsStreamDirectory $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = vfsStream::setup();

        self::bootKernel();
    }

    public function testGetDispositionDocumentReturns404UntilCreated(): void
    {
        $user = UserFactory::new()
            ->asSuperAdmin()
            ->isEnabled()
            ->create();

        $dossier = DispositionFactory::createOne([
            'organisation' => $user->getOrganisation(),
        ]);

        $client = static::createClient()->loginUser($user->_real(), 'balie');

        // There should be no Disposition document yet, so 404
        $client->request(
            Request::METHOD_GET,
            sprintf('/balie/api/dossiers/%s/disposition-document', $dossier->getId()),
            [
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ],
        );
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $uploadUuid = $this->uploadDocument($client);

        // Now create the DispositionDocument
        $data = [
            'formalDate' => (new \DateTimeImmutable('yesterday'))->format('Y-m-d'),
            'internalReference' => 'foo bar',
            'type' => AttachmentType::APPOINTMENT_DECISION->value,
            'language' => AttachmentLanguage::DUTCH->value,
            'grounds' => ['foo', 'bar'],
            'uploadUuid' => $uploadUuid,
        ];
        $client->request(
            Request::METHOD_POST,
            sprintf('/balie/api/dossiers/%s/disposition-document', $dossier->getId()),
            [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => $data,
            ],
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        // Now it should be possible to fetch the data
        $client->request(
            Request::METHOD_GET,
            sprintf('/balie/api/dossiers/%s/disposition-document', $dossier->getId()),
            [
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ],
        );
        self::assertResponseIsSuccessful();

        unset($data['uploadUuid']); // This is only used for processing and not returned in the response
        self::assertJsonContains($data);
        self::assertMatchesResourceItemJsonSchema(DispositionMainDocumentDto::class);
    }

    public function testUpdateDispositionDocument(): void
    {
        $user = UserFactory::new()
            ->asSuperAdmin()
            ->isEnabled()
            ->create();

        $document = DispositionMainDocumentFactory::createOne([
            'fileInfo' => FileInfoFactory::createOne([
                'name' => 'test_file.pdf',
            ]),
            'dossier' => DispositionFactory::createOne([
                'organisation' => $user->getOrganisation(),
            ]),
        ]);

        $client = static::createClient()->loginUser($user->_real(), 'balie');

        $response = $client->request(
            Request::METHOD_PUT,
            sprintf('/balie/api/dossiers/%s/disposition-document', $document->getDossier()->getId()),
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
        self::assertMatchesResourceItemJsonSchema(DispositionMainDocumentDto::class);

        $response2 = $client->request(
            Request::METHOD_GET,
            sprintf('/balie/api/dossiers/%s/disposition-document', $document->getDossier()->getId()),
            [
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ],
        );

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(DispositionMainDocumentDto::class);

        $this->assertSame($response->toArray(), $response2->toArray());
    }

    public function testDispositionDocumentCanBeDeletedAfterCreation(): void
    {
        $user = UserFactory::new()
            ->asSuperAdmin()
            ->isEnabled()
            ->create();

        $dossier = DispositionFactory::createOne([
            'organisation' => $user->getOrganisation(),
            'status' => DossierStatus::CONCEPT,
        ]);

        $client = static::createClient()->loginUser($user->_real(), 'balie');

        // There should be no Disposition document yet, so 404
        $client->request(
            Request::METHOD_DELETE,
            sprintf('/balie/api/dossiers/%s/disposition-document', $dossier->getId()),
            [
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ],
        );
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $uploadUuid = $this->uploadDocument($client);

        // Now create the DispositionDocument
        $data = [
            'formalDate' => (new \DateTimeImmutable('yesterday'))->format('Y-m-d'),
            'internalReference' => 'foo bar',
            'type' => AttachmentType::APPOINTMENT_DECISION->value,
            'language' => AttachmentLanguage::DUTCH->value,
            'grounds' => ['foo', 'bar'],
            'uploadUuid' => $uploadUuid,
        ];
        $client->request(
            Request::METHOD_POST,
            sprintf('/balie/api/dossiers/%s/disposition-document', $dossier->getId()),
            [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => $data,
            ],
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertMatchesResourceItemJsonSchema(DispositionMainDocumentDto::class);

        // Now it should be possible to delete it
        $client->request(
            Request::METHOD_DELETE,
            sprintf('/balie/api/dossiers/%s/disposition-document', $dossier->getId()),
            [
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ],
        );
        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testDispositionDocumentCannotBeDeletedForAPublishedDossier(): void
    {
        $user = UserFactory::new()
            ->asSuperAdmin()
            ->isEnabled()
            ->create();

        $dossier = DispositionFactory::createOne([
            'organisation' => $user->getOrganisation(),
            'status' => DossierStatus::PUBLISHED,
        ]);

        $client = static::createClient()->loginUser($user->_real(), 'balie');

        $uploadUuid = $this->uploadDocument($client);

        // Now create the DispositionDocument
        $data = [
            'formalDate' => (new \DateTimeImmutable('yesterday'))->format('Y-m-d'),
            'internalReference' => 'foo bar',
            'type' => AttachmentType::APPOINTMENT_DECISION->value,
            'language' => AttachmentLanguage::DUTCH->value,
            'grounds' => ['foo', 'bar'],
            'uploadUuid' => $uploadUuid,
        ];
        $client->request(
            Request::METHOD_POST,
            sprintf('/balie/api/dossiers/%s/disposition-document', $dossier->getId()),
            [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => $data,
            ],
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertMatchesResourceItemJsonSchema(DispositionMainDocumentDto::class);

        // It should not be possible to delete it
        $client->request(
            Request::METHOD_DELETE,
            sprintf('/balie/api/dossiers/%s/disposition-document', $dossier->getId()),
            [
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ],
        );
        self::assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }

    public function testCreateDispositionDocumentOnlyAcceptsValidTypeValues(): void
    {
        $user = UserFactory::new()
            ->asSuperAdmin()
            ->isEnabled()
            ->create();

        $dossier = DispositionFactory::createOne([
            'organisation' => $user->getOrganisation(),
        ]);

        $client = static::createClient()->loginUser($user->_real(), 'balie');

        $uploadUuid = $this->uploadDocument($client);

        $data = [
            'formalDate' => (new \DateTimeImmutable('yesterday'))->format('Y-m-d'),
            'internalReference' => 'foo bar',
            'type' => AttachmentType::COVENANT->value,
            'language' => AttachmentLanguage::DUTCH->value,
            'grounds' => ['foo', 'bar'],
            'uploadUuid' => $uploadUuid,
        ];

        $client->request(
            Request::METHOD_POST,
            sprintf('/balie/api/dossiers/%s/disposition-document', $dossier->getId()),
            [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => $data,
            ],
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertMatchesResourceItemJsonSchema(DispositionMainDocumentDto::class);
        self::assertJsonContains(['violations' => [
            ['propertyPath' => 'type', 'code' => Choice::NO_SUCH_CHOICE_ERROR],
        ]]);
    }

    public function testUpdateDispositionDocumentOnlyAcceptsValidTypeValues(): void
    {
        $user = UserFactory::new()
            ->asSuperAdmin()
            ->isEnabled()
            ->create();

        $document = DispositionMainDocumentFactory::createOne([
            'fileInfo' => FileInfoFactory::createOne([
                'name' => 'test_file.pdf',
            ]),
            'dossier' => DispositionFactory::createOne([
                'organisation' => $user->getOrganisation(),
            ]),
        ]);

        $client = static::createClient()->loginUser($user->_real(), 'balie');

        $client->request(
            Request::METHOD_PUT,
            sprintf('/balie/api/dossiers/%s/disposition-document', $document->getDossier()->getId()),
            [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'name' => 'foobar.pdf',
                    'type' => AttachmentType::COVENANT->value,
                ],
            ],
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertMatchesResourceItemJsonSchema(DispositionMainDocumentDto::class);
        self::assertJsonContains(['violations' => [
            ['propertyPath' => 'type', 'code' => Choice::NO_SUCH_CHOICE_ERROR],
        ]]);
    }

    private function uploadDocument(Client $client): string
    {
        vfsStream::newFile('test_file.pdf')
            ->withContent('This is a test file.')
            ->at($this->root);

        $uploadFile = new UploadedFile(
            path: $this->root->url() . '/test_file.pdf',
            originalName: 'test_file.pdf    ',
        );

        $uploadUuid = 'file-' . $this->getFaker()->uuid();

        // Upload the document first
        $client->request(
            Request::METHOD_POST,
            '/balie/uploader',
            [
                'headers' => ['Content-Type' => 'multipart/form-data'],
                'extra' => [
                    'parameters' => [
                        'chunkindex' => '0',
                        'totalchunkcount' => '1',
                        'groupId' => UploadGroupId::MAIN_DOCUMENTS->value,
                        'uuid' => $uploadUuid,
                    ],
                    'files' => [
                        'file' => $uploadFile,
                    ],
                ],
            ],
        );

        self::assertResponseIsSuccessful();

        return $uploadUuid;
    }
}