<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Integration\Api\Dossier\Uploads;

use Mockery;
use PublicationApi\Tests\Integration\Api\ApiPublicationV1TestCase;
use Shared\Domain\Upload\StreamUpload;
use Shared\Domain\Upload\UploadEntity;
use Shared\Domain\Upload\UploadEntityRepository;
use Shared\Domain\Upload\UploadEntityStatus;
use Shared\Domain\Upload\UploadService;
use Shared\Service\Uploader\UploadGroupId;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\UuidV6;

abstract class ApiPublicationV1UploadTestCase extends ApiPublicationV1TestCase
{
    protected function assertUpload(
        string $url,
        string $dossierId,
        string $entityId,
        ?string $entityFileName,
        UploadGroupId $uploadGroupId,
        string $entityParameterKey,
    ): void {
        $client = self::createPublicationApiClient();
        $fileContent = $this->getTestFileContent('1008.pdf');

        $mockUploadEntity = Mockery::mock(UploadEntity::class);
        $mockUploadEntity->expects('getFilename')->twice()->andReturn('1008.pdf');
        $mockUploadEntity->expects('getMimeType')->twice()->andReturn('application/pdf');
        $mockUploadEntity->expects('getSize')->andReturn(1000);
        $mockUploadEntity->expects('getStatus')->andReturn(UploadEntityStatus::VALIDATION_PASSED);

        $uploadEntityRepository = Mockery::mock(UploadEntityRepository::class);
        $uploadEntityRepository->expects('findOneBy')->twice()->andReturn($mockUploadEntity);
        self::getContainer()->set(UploadEntityRepository::class, $uploadEntityRepository);

        $uploadService = Mockery::mock(UploadService::class);
        self::getContainer()->set(UploadService::class, $uploadService);

        $uploadService->expects('handleUpload')->with(
            Mockery::on(function (StreamUpload $streamUpload) use (
                $dossierId,
                $entityId,
                $entityFileName,
                $uploadGroupId,
                $entityParameterKey,
                $fileContent,
            ): bool {
                if ($streamUpload->fileName !== $entityFileName) {
                    return false;
                }

                if ($streamUpload->stream->getContents() !== $fileContent) {
                    return false;
                }

                if ($streamUpload->groupId !== $uploadGroupId) {
                    return false;
                }

                if ($streamUpload->additionalParameters->get('dossierId') !== $dossierId) {
                    return false;
                }

                if ($streamUpload->additionalParameters->get($entityParameterKey) !== $entityId) {
                    return false;
                }

                if (! UuidV6::isValid($streamUpload->uploadId)) {
                    return false;
                }

                return true;
            }),
        );
        $uploadService->expects('moveUploadToStorage');

        $client->request(Request::METHOD_PUT, $url, [
            'headers' => [
                'Content-Type' => 'application/octet-stream',
            ],
            'body' => $fileContent,
        ]);

        $this->assertResponseIsSuccessful();
    }

    protected function assertUploadWithoutFile(string $url): void
    {
        $client = self::createPublicationApiClient();

        $client->request(Request::METHOD_PUT, $url, [
            'headers' => [
                'Content-Type' => 'application/octet-stream',
            ],
            'body' => '',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
