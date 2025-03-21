<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Uploader;

use App\Domain\Publication\Dossier\Type\Covenant\CovenantMainDocument;
use App\Domain\Publication\FileInfo;
use App\Domain\Upload\FileType\FileType;
use App\Exception\UploaderServiceException;
use App\Service\Storage\EntityStorageService;
use App\Service\Uploader\UploaderService;
use App\Service\Uploader\UploadGroupId;
use App\SourceType;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Oneup\UploaderBundle\Uploader\Storage\FilesystemOrphanageStorage;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Uid\Uuid;

#[Group('uploader')]
final class UploaderServiceTest extends UnitTestCase
{
    private RequestStack&MockInterface $requestStack;
    private FilesystemOrphanageStorage&MockInterface $orphanageStorage;
    private EntityStorageService&MockInterface $entityStorageService;
    private Session&MockInterface $session;
    private vfsStreamDirectory $fileSystem;
    private UploaderService $uploaderService;

    protected function setUp(): void
    {
        $this->fileSystem = vfsStream::setup();

        $this->orphanageStorage = \Mockery::mock(FilesystemOrphanageStorage::class);
        $this->entityStorageService = \Mockery::mock(EntityStorageService::class);
        $this->session = \Mockery::mock(Session::class);

        $this->requestStack = \Mockery::mock(RequestStack::class);
        $this->requestStack
            ->shouldReceive('getSession')
            ->andReturn($this->session);

        $this->uploaderService = new UploaderService(
            $this->requestStack,
            $this->orphanageStorage,
            $this->entityStorageService,
        );
    }

    public function testRegisterUploadWithAnEmptySession(): void
    {
        $sessionKey = 'uploads_attachments';
        $uploadUuid = 'my-uuid';
        $pathname = 'my-pathname';

        $this->session
            ->shouldReceive('get')
            ->once()
            ->with($sessionKey, [])
            ->andReturn([]);
        $this->session
            ->shouldReceive('set')
            ->once()
            ->with($sessionKey, [$uploadUuid => [$pathname]]);

        $this->uploaderService->registerUpload($uploadUuid, $pathname, UploadGroupId::ATTACHMENTS);
    }

    public function testRegisterUploadWithANonEmptySession(): void
    {
        $sessionKey = 'uploads_attachments';
        $uploadUuid = 'my-uuid';
        $pathname = 'my-pathname';

        $this->session
            ->shouldReceive('get')
            ->once()
            ->with($sessionKey, [])
            ->andReturn($existingPaths = [
                'existing-uuid-one' => ['existing-pathname-one', 'existing-pathname-two'],
                'existing-uuid-two' => ['existing-pathname'],
            ]);

        $capturedUploads = [$uploadUuid => null];
        $this->session
            ->shouldReceive('set')
            ->once()
            ->with($sessionKey, \Mockery::capture($capturedUploads));

        $this->uploaderService->registerUpload($uploadUuid, $pathname, UploadGroupId::ATTACHMENTS);

        $this->assertMatchesJsonSnapshot($capturedUploads);
    }

    public function testConfirmUploadOnEmptyUploadsSession(): void
    {
        $sessionKey = 'uploads_main-documents';
        $myUuid = 'my-uuid';

        $this->session
            ->shouldReceive('get')
            ->once()
            ->with($sessionKey, [])
            ->andReturn([]);

        $this->uploaderService->confirmUpload($myUuid, UploadGroupId::MAIN_DOCUMENTS);
    }

    public function testConfirmUploadForNonExistingUploadUuid(): void
    {
        $sessionKey = 'uploads_attachments';
        $myUuid = 'my-uuid';

        $this->session
            ->shouldReceive('get')
            ->once()
            ->with($sessionKey, [])
            ->andReturn([
                'existing-uuid-one' => ['existing-pathname-one'],
                'existing-uuid-two' => ['existing-pathname'],
            ]);

        $this->uploaderService->confirmUpload($myUuid, UploadGroupId::ATTACHMENTS);
    }

    public function testConfirmUploadForExistingUploadUuid(): void
    {
        $sessionKey = 'uploads_attachments';
        $myUuid = 'my-uuid';

        $this->session
            ->shouldReceive('get')
            ->once()
            ->with($sessionKey, [])
            ->andReturn([
                $myUuid => ['/uploads/file1.txt', '/uploads/file2.pdf'],
            ]);

        $this->session
            ->shouldReceive('remove')
            ->once()
            ->with($sessionKey);

        $iteratorResult = new \ArrayIterator($arrayResult = [new \stdClass()]);

        $subFinder = \Mockery::mock(Finder::class);
        $subFinder
            ->shouldReceive('hasResults')
            ->once()
            ->andReturnTrue();
        $subFinder
            ->shouldReceive('getIterator')
            ->once()
            ->andReturn($iteratorResult);

        $finder = \Mockery::mock(Finder::class);
        $finder
            ->shouldReceive('path')
            ->once()
            ->with(['attachments/file1.txt', 'attachments/file2.pdf'])
            ->andReturn($subFinder);

        $this->orphanageStorage
            ->shouldReceive('getFiles')
            ->once()
            ->andReturn($finder);
        $this->orphanageStorage
            ->shouldReceive('uploadFiles')
            ->once()
            ->with($arrayResult)
            ->andReturn([]);

        $this->uploaderService->confirmUpload($myUuid, UploadGroupId::ATTACHMENTS);
    }

    public function testConfirmSingleUploadThrowsExceptionForZeroUploads(): void
    {
        $sessionKey = 'uploads_attachments';
        $myUuid = 'my-uuid';

        $this->session
            ->shouldReceive('get')
            ->once()
            ->with($sessionKey, [])
            ->andReturn([]);

        $this->expectExceptionObject(UploaderServiceException::forNoFilesUploaded($myUuid));
        $this->uploaderService->confirmSingleUpload($myUuid, UploadGroupId::ATTACHMENTS);
    }

    public function testConfirmSingleUploadThrowsExceptionForMultipleUploads(): void
    {
        $sessionKey = 'uploads_main-documents';
        $myUuid = 'my-uuid';

        $this->session
            ->shouldReceive('get')
            ->once()
            ->with($sessionKey, [])
            ->andReturn([
                $myUuid => ['/uploads/file1.txt', '/uploads/file2.pdf'],
            ]);

        $this->session
            ->shouldReceive('remove')
            ->once()
            ->with($sessionKey);

        $iteratorResult = new \ArrayIterator($arrayResult = [new \stdClass()]);

        $subFinder = \Mockery::mock(Finder::class);
        $subFinder
            ->shouldReceive('hasResults')
            ->once()
            ->andReturnTrue();
        $subFinder
            ->shouldReceive('getIterator')
            ->once()
            ->andReturn($iteratorResult);

        $finder = \Mockery::mock(Finder::class);
        $finder
            ->shouldReceive('path')
            ->once()
            ->with(['main-documents/file1.txt', 'main-documents/file2.pdf'])
            ->andReturn($subFinder);

        $this->orphanageStorage
            ->shouldReceive('getFiles')
            ->once()
            ->andReturn($finder);
        $this->orphanageStorage
            ->shouldReceive('uploadFiles')
            ->once()
            ->with($arrayResult)
            ->andReturn(['/foo/file1.txt', '/bar/file2.pdf']);

        $this->expectExceptionObject(UploaderServiceException::forMultipleFilesUploaded($myUuid));
        $this->uploaderService->confirmSingleUpload($myUuid, UploadGroupId::MAIN_DOCUMENTS);
    }

    public function testAttachFileToEntityReplacesExistingUpload(): void
    {
        $sessionKey = 'uploads_main-documents';
        $myUuid = 'my-uuid';

        $this->session
            ->shouldReceive('get')
            ->once()
            ->with($sessionKey, [])
            ->andReturn([
                $myUuid => ['/main-documents/file1.txt'],
            ]);

        $this->session
            ->shouldReceive('remove')
            ->once()
            ->with($sessionKey);

        $iteratorResult = new \ArrayIterator($arrayResult = [new \stdClass()]);

        $subFinder = \Mockery::mock(Finder::class);
        $subFinder
            ->shouldReceive('hasResults')
            ->once()
            ->andReturnTrue();
        $subFinder
            ->shouldReceive('getIterator')
            ->once()
            ->andReturn($iteratorResult);

        $mockFilePath = $this->fileSystem->url() . '/dummy.pdf';
        file_put_contents($mockFilePath, 'content');

        $storedFile = \Mockery::mock(File::class);
        $storedFile->expects('getPathname')->andReturn($mockFilePath);
        $storedFile->shouldReceive('getMimeType')->andReturn(FileType::DOC->getMimeTypes()[0]);
        $storedFile->shouldReceive('getFilename')->andReturn('prefix_dummy.pdf');

        $finder = \Mockery::mock(Finder::class);
        $finder
            ->shouldReceive('path')
            ->once()
            ->with(['main-documents/file1.txt'])
            ->andReturn($subFinder);

        $this->orphanageStorage
            ->shouldReceive('getFiles')
            ->once()
            ->andReturn($finder);
        $this->orphanageStorage
            ->shouldReceive('uploadFiles')
            ->once()
            ->with($arrayResult)
            ->andReturn([$storedFile]);

        $entityId = Uuid::v6();

        $fileInfo = \Mockery::mock(FileInfo::class);
        $fileInfo->shouldReceive('isUploaded')->andReturnTrue();
        $fileInfo->shouldReceive('getName')->andReturn('prefix_dummy.pdf');

        $entity = \Mockery::mock(CovenantMainDocument::class);
        $entity->shouldReceive('getFileInfo')->andReturn($fileInfo);
        $entity->shouldReceive('getId')->andReturn($entityId);

        $this->entityStorageService->expects('deleteAllFilesForEntity')->with($entity);
        $fileInfo->expects('removeFileProperties');
        $fileInfo->expects('setSourceType')->with(SourceType::DOC);
        $fileInfo->expects('setType')->with('doc');
        $fileInfo->expects('setName')->with('dummy.pdf');

        $this->entityStorageService->expects('storeEntity')->with($storedFile, $entity)->andReturnTrue();

        self::assertFalse($this->fileSystem->hasChild($mockFilePath));

        $this->uploaderService->attachFileToEntity($myUuid, $entity, UploadGroupId::MAIN_DOCUMENTS);
    }
}
