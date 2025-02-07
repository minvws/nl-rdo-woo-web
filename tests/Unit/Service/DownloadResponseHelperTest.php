<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Domain\Publication\Dossier\Type\WooDecision\Entity\Document;
use App\Service\ArchiveService;
use App\Service\DownloadFilenameGenerator;
use App\Service\DownloadResponseHelper;
use App\Service\Storage\EntityStorageService;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DownloadResponseHelperTest extends UnitTestCase
{
    private DownloadResponseHelper $responseHelper;
    private ArchiveService&MockInterface $archiveService;
    private DownloadFilenameGenerator&MockInterface $filenameGenerator;
    private EntityStorageService&MockInterface $entityStorageService;

    public function setUp(): void
    {
        $this->entityStorageService = \Mockery::mock(EntityStorageService::class);
        $this->archiveService = \Mockery::mock(ArchiveService::class);
        $this->filenameGenerator = \Mockery::mock(DownloadFilenameGenerator::class);

        $this->responseHelper = new DownloadResponseHelper(
            $this->entityStorageService,
            $this->archiveService,
            $this->filenameGenerator,
        );

        parent::setUp();
    }

    public function testGetResponseForEntityWithFileInfoThrowsExceptionWhenEntityIsMissing(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $this->responseHelper->getResponseForEntityWithFileInfo(null);
    }

    public function testGetResponseForEntityWithFileInfoThrowsExceptionWhenEntityHasNoUpload(): void
    {
        $entity = \Mockery::mock(Document::class);
        $entity->shouldReceive('getFileInfo->isUploaded')->andReturnFalse();

        $this->expectException(NotFoundHttpException::class);

        $this->responseHelper->getResponseForEntityWithFileInfo($entity);
    }

    public function testGetResponseForEntityWithFileInfoThrowsExceptionWhenUploadCannotBeRetrievedFromStorage(): void
    {
        $entity = \Mockery::mock(Document::class);
        $entity->shouldReceive('getFileInfo->isUploaded')->andReturnTrue();

        $this->entityStorageService->expects('retrieveResourceEntity')->with($entity)->andReturnNull();

        $this->expectException(NotFoundHttpException::class);

        $this->responseHelper->getResponseForEntityWithFileInfo($entity);
    }

    #[DataProvider('getResponseProvider')]
    public function testGetResponseForEntityWithPdf(string $type, string $mimetype): void
    {
        $entity = \Mockery::mock(Document::class);
        $entity->shouldReceive('getFileInfo->isUploaded')->andReturnTrue();
        $entity->shouldReceive('getFileInfo->getType')->andReturn($type);
        $entity->shouldReceive('getFileInfo->getMimeType')->andReturn($mimetype);
        $entity->shouldReceive('getFileInfo->getSize')->andReturn(456);
        $entity->shouldReceive('getUpdatedAt->format')->andReturn('Wed, 27 Nov 2024 11:56:18 GMT');

        $stream = fopen('php://memory', 'r+');
        if ($stream === false) {
            $this->fail('cannot open mock stream');
        }
        fwrite($stream, $expectedOutput = 'foo bar');
        rewind($stream);

        $this->entityStorageService->expects('retrieveResourceEntity')->with($entity)->andReturn($stream);

        $this->filenameGenerator->expects('getFileName')->with($entity)->andReturn('123.' . $type);

        $response = $this->responseHelper->getResponseForEntityWithFileInfo($entity);

        ob_start();
        $response->sendContent();
        $output = ob_get_clean();

        self::assertEquals(
            $expectedOutput,
            $output,
        );

        $headers = $response->headers->all();
        unset($headers['date']);
        $this->assertMatchesSnapshot($headers);
    }

    /**
     * @return array<string, array{type: string, mimetype: string}>
     */
    public static function getResponseProvider(): array
    {
        return [
            'pdf' => [
                'type' => 'pdf',
                'mimetype' => 'application/pdf',
            ],
            'doc' => [
                'type' => 'doc',
                'mimetype' => 'application/msword',
            ],
        ];
    }
}
