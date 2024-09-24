<?php

declare(strict_types=1);

namespace App\Tests\Integration\Api\Admin;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Api\Admin\AnnualReportDocument\AnnualReportDocumentDto;
use App\Domain\Publication\Attachment\AttachmentLanguage;
use App\Domain\Publication\Attachment\AttachmentType;
use App\Domain\Publication\Dossier\DossierStatus;
use App\Service\Uploader\UploadGroupId;
use App\Tests\Factory\FileInfoFactory;
use App\Tests\Factory\Publication\Dossier\Type\AnnualReport\AnnualReportDocumentFactory;
use App\Tests\Factory\Publication\Dossier\Type\AnnualReport\AnnualReportFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\Integration\IntegrationTestTrait;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Choice;

final class AnnualReportDocumentTest extends ApiTestCase
{
    use IntegrationTestTrait;

    private vfsStreamDirectory $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = vfsStream::setup();

        self::bootKernel();
    }

    public function testGetAnnualReportDocumentReturns404UntilCreated(): void
    {
        $user = UserFactory::new()
            ->asSuperAdmin()
            ->isEnabled()
            ->create();

        $dossier = AnnualReportFactory::createOne([
            'organisation' => $user->getOrganisation(),
        ]);

        $client = static::createClient()->loginUser($user->_real(), 'balie');

        // There should be no annualReport document yet, so 404
        $client->request(
            Request::METHOD_GET,
            sprintf('/balie/api/dossiers/%s/annual-report-document', $dossier->getId()),
            [
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ],
        );
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        ['uploadUuid' => $uploadUuid, 'uploadName' => $uploadName] = $this->uploadDocument($client);

        // Now create the AnnualReportDocument
        $data = [
            'formalDate' => (new \DateTimeImmutable('yesterday'))->format('Y-m-d'),
            'internalReference' => 'foo bar',
            'type' => AttachmentType::ANNUAL_REPORT->value,
            'language' => AttachmentLanguage::DUTCH->value,
            'grounds' => ['foo', 'bar'],
            'uploadUuid' => $uploadUuid,
            'name' => $uploadName,
        ];
        $client->request(
            Request::METHOD_POST,
            sprintf('/balie/api/dossiers/%s/annual-report-document', $dossier->getId()),
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
            sprintf('/balie/api/dossiers/%s/annual-report-document', $dossier->getId()),
            [
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ],
        );
        self::assertResponseIsSuccessful();

        unset($data['uploadUuid']); // This is only used for processing and not returned in the response
        self::assertJsonContains($data);
        self::assertMatchesResourceItemJsonSchema(AnnualReportDocumentDto::class);
    }

    public function testUpdateAnnualReportDocument(): void
    {
        $user = UserFactory::new()
            ->asSuperAdmin()
            ->isEnabled()
            ->create();

        $document = AnnualReportDocumentFactory::createOne([
            'fileInfo' => FileInfoFactory::createOne([
                'name' => 'test_file.pdf',
            ]),
            'dossier' => AnnualReportFactory::createOne([
                'organisation' => $user->getOrganisation(),
            ]),
        ]);

        $client = static::createClient()->loginUser($user->_real(), 'balie');

        $response = $client->request(
            Request::METHOD_PUT,
            sprintf('/balie/api/dossiers/%s/annual-report-document', $document->getDossier()->getId()),
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
        self::assertMatchesResourceItemJsonSchema(AnnualReportDocumentDto::class);

        $response2 = $client->request(
            Request::METHOD_GET,
            sprintf('/balie/api/dossiers/%s/annual-report-document', $document->getDossier()->getId()),
            [
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ],
        );

        self::assertResponseIsSuccessful();
        self::assertMatchesResourceItemJsonSchema(AnnualReportDocumentDto::class);

        $this->assertSame($response->toArray(), $response2->toArray());
    }

    public function testAnnualReportDocumentCanBeDeletedAfterCreation(): void
    {
        $user = UserFactory::new()
            ->asSuperAdmin()
            ->isEnabled()
            ->create();

        $dossier = AnnualReportFactory::createOne([
            'organisation' => $user->getOrganisation(),
            'status' => DossierStatus::CONCEPT,
        ]);

        $client = static::createClient()->loginUser($user->_real(), 'balie');

        // There should be no annualReport document yet, so 404
        $client->request(
            Request::METHOD_DELETE,
            sprintf('/balie/api/dossiers/%s/annual-report-document', $dossier->getId()),
            [
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ],
        );
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        ['uploadUuid' => $uploadUuid, 'uploadName' => $uploadName] = $this->uploadDocument($client);

        // Now create the AnnualReportDocument
        $data = [
            'formalDate' => (new \DateTimeImmutable('yesterday'))->format('Y-m-d'),
            'internalReference' => 'foo bar',
            'type' => AttachmentType::ANNUAL_PLAN->value,
            'language' => AttachmentLanguage::DUTCH->value,
            'grounds' => ['foo', 'bar'],
            'uploadUuid' => $uploadUuid,
            'name' => $uploadName,
        ];
        $client->request(
            Request::METHOD_POST,
            sprintf('/balie/api/dossiers/%s/annual-report-document', $dossier->getId()),
            [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => $data,
            ],
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertMatchesResourceItemJsonSchema(AnnualReportDocumentDto::class);

        // Now it should be possible to delete it
        $client->request(
            Request::METHOD_DELETE,
            sprintf('/balie/api/dossiers/%s/annual-report-document', $dossier->getId()),
            [
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ],
        );
        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testAnnualReportDocumentCannotBeDeletedForAPublishedDossier(): void
    {
        $user = UserFactory::new()
            ->asSuperAdmin()
            ->isEnabled()
            ->create();

        $dossier = AnnualReportFactory::createOne([
            'organisation' => $user->getOrganisation(),
            'status' => DossierStatus::PUBLISHED,
        ]);

        $client = static::createClient()->loginUser($user->_real(), 'balie');

        ['uploadUuid' => $uploadUuid, 'uploadName' => $uploadName] = $this->uploadDocument($client);

        // Now create the AnnualReportDocument
        $data = [
            'formalDate' => (new \DateTimeImmutable('yesterday'))->format('Y-m-d'),
            'internalReference' => 'foo bar',
            'type' => AttachmentType::ANNUAL_REPORT->value,
            'language' => AttachmentLanguage::DUTCH->value,
            'grounds' => ['foo', 'bar'],
            'uploadUuid' => $uploadUuid,
            'name' => $uploadName,
        ];
        $client->request(
            Request::METHOD_POST,
            sprintf('/balie/api/dossiers/%s/annual-report-document', $dossier->getId()),
            [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => $data,
            ],
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertMatchesResourceItemJsonSchema(AnnualReportDocumentDto::class);

        // It should not be possible to delete it
        $client->request(
            Request::METHOD_DELETE,
            sprintf('/balie/api/dossiers/%s/annual-report-document', $dossier->getId()),
            [
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ],
        );
        self::assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }

    public function testCreateAnnualReportDocumentOnlyAcceptsValidTypeValues(): void
    {
        $user = UserFactory::new()
            ->asSuperAdmin()
            ->isEnabled()
            ->create();

        $dossier = AnnualReportFactory::createOne([
            'organisation' => $user->getOrganisation(),
        ]);

        $client = static::createClient()->loginUser($user->_real(), 'balie');

        ['uploadUuid' => $uploadUuid, 'uploadName' => $uploadName] = $this->uploadDocument($client);

        $data = [
            'formalDate' => (new \DateTimeImmutable('yesterday'))->format('Y-m-d'),
            'internalReference' => 'foo bar',
            'type' => AttachmentType::PROGRESS_REPORT->value,
            'language' => AttachmentLanguage::DUTCH->value,
            'grounds' => ['foo', 'bar'],
            'uploadUuid' => $uploadUuid,
            'name' => $uploadName,
        ];

        $client->request(
            Request::METHOD_POST,
            sprintf('/balie/api/dossiers/%s/annual-report-document', $dossier->getId()),
            [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => $data,
            ],
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertMatchesResourceItemJsonSchema(AnnualReportDocumentDto::class);
        self::assertJsonContains(['violations' => [
            ['propertyPath' => 'type', 'code' => Choice::NO_SUCH_CHOICE_ERROR],
        ]]);
    }

    public function testUpdateAnnualReportDocumentOnlyAcceptsValidTypeValues(): void
    {
        $user = UserFactory::new()
            ->asSuperAdmin()
            ->isEnabled()
            ->create();

        $document = AnnualReportDocumentFactory::createOne([
            'fileInfo' => FileInfoFactory::createOne([
                'name' => 'test_file.pdf',
            ]),
            'dossier' => AnnualReportFactory::createOne([
                'organisation' => $user->getOrganisation(),
            ]),
        ]);

        $client = static::createClient()->loginUser($user->_real(), 'balie');

        $client->request(
            Request::METHOD_PUT,
            sprintf('/balie/api/dossiers/%s/annual-report-document', $document->getDossier()->getId()),
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
        self::assertMatchesResourceItemJsonSchema(AnnualReportDocumentDto::class);
        self::assertJsonContains(['violations' => [
            ['propertyPath' => 'type', 'code' => Choice::NO_SUCH_CHOICE_ERROR],
        ]]);
    }

    /**
     * @return array{uploadUuid:string,uploadName:string}
     */
    private function uploadDocument(Client $client): array
    {
        vfsStream::newFile('test_file.pdf')
            ->withContent('This is a test file.')
            ->at($this->root);

        $uploadFile = new UploadedFile(
            path: $this->root->url() . '/test_file.pdf',
            originalName: 'test_file.pdf    ',
        );

        $uploadUuid = 'file-' . $this->getFaker()->uuid();
        $uploadName = 'test-123.pdf';

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
                        'groupId' => UploadGroupId::ANNUAL_REPORT_DOCUMENTS->value,
                        'uuid' => $uploadUuid,
                    ],
                    'files' => [
                        'file' => $uploadFile,
                    ],
                ],
            ],
        );

        self::assertResponseIsSuccessful();

        return ['uploadUuid' => $uploadUuid, 'uploadName' => $uploadName];
    }
}
