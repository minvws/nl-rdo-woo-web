<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Uploader;

use App\Domain\Publication\Dossier\Type\Covenant\CovenantDocument;
use App\Domain\Upload\FileType\FileType;
use App\Domain\Upload\FileType\FileTypeHelper;
use App\Entity\FileInfo;
use App\Exception\UploaderServiceException;
use App\Service\Storage\EntityStorageService;
use App\Service\Uploader\UploaderService;
use App\Service\Uploader\UploadGroupId;
use App\SourceType;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Oneup\UploaderBundle\Event\PostUploadEvent;
use Oneup\UploaderBundle\Uploader\File\FileInterface;
use Oneup\UploaderBundle\Uploader\Storage\FilesystemOrphanageStorage;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
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
    private FileTypeHelper&MockInterface $fileTypeHelper;
    private UploaderService $uploaderService;

    protected function setUp(): void
    {
        $this->fileSystem = vfsStream::setup();

        $this->orphanageStorage = \Mockery::mock(FilesystemOrphanageStorage::class);
        $this->entityStorageService = \Mockery::mock(EntityStorageService::class);
        $this->session = \Mockery::mock(Session::class);
        $this->fileTypeHelper = \Mockery::mock(FileTypeHelper::class);

        $this->requestStack = \Mockery::mock(RequestStack::class);
        $this->requestStack
            ->shouldReceive('getSession')
            ->andReturn($this->session);

        $this->uploaderService = new UploaderService(
            $this->requestStack,
            $this->orphanageStorage,
            $this->entityStorageService,
            $this->fileTypeHelper,
        );
    }

    public function testRegisterUploadWithAnEmptySession(): void
    {
        $sessionKey = 'uploads_woo-decision-attachments';
        $this->session
            ->shouldReceive('get')
            ->once()
            ->with($sessionKey, [])
            ->andReturn([]);

        $request = \Mockery::mock(Request::class);
        $request
            ->shouldReceive('get')
            ->once()
            ->with('uuid')
            ->andReturn($myUuid = 'my-uuid');

        $file = \Mockery::mock(FileInterface::class);
        $file
            ->shouldReceive('getPathname')
            ->once()
            ->andReturn($myPathname = 'my-pathname');
        $file
            ->shouldReceive('getMimeType')
            ->andReturn('application/msword');

        $postUploadEvent = \Mockery::mock(PostUploadEvent::class);
        $postUploadEvent
            ->shouldReceive('getRequest')
            ->once()
            ->andReturn($request);
        $postUploadEvent
            ->shouldReceive('getFile')
            ->once()
            ->andReturn($file);

        $this->session
            ->shouldReceive('set')
            ->once()
            ->with($sessionKey, [$myUuid => [$myPathname]]);

        $this->uploaderService->registerUpload($postUploadEvent, UploadGroupId::WOO_DECISION_ATTACHMENTS);
    }

    public function testRegisterUploadWithANonEmptySession(): void
    {
        $sessionKey = 'uploads_default';
        $myUploadUuid = 'my-uuid';
        $myPathname = 'my-pathname';

        $this->session
            ->shouldReceive('get')
            ->once()
            ->with($sessionKey, [])
            ->andReturn($existingPaths = [
                'existing-uuid-one' => ['existing-pathname-one', 'existing-pathname-two'],
                'existing-uuid-two' => ['existing-pathname'],
            ]);

        $request = \Mockery::mock(Request::class);
        $request
            ->shouldReceive('get')
            ->once()
            ->with('uuid')
            ->andReturn($myUploadUuid);

        $file = \Mockery::mock(FileInterface::class);
        $file
            ->shouldReceive('getPathname')
            ->once()
            ->andReturn($myPathname);
        $file
            ->shouldReceive('getMimeType')
            ->andReturn('application/msword');

        $postUploadEvent = \Mockery::mock(PostUploadEvent::class);
        $postUploadEvent
            ->shouldReceive('getRequest')
            ->once()
            ->andReturn($request);
        $postUploadEvent
            ->shouldReceive('getFile')
            ->once()
            ->andReturn($file);

        $capturedUploads = [];
        $this->session
            ->shouldReceive('set')
            ->once()
            ->with($sessionKey, \Mockery::capture($capturedUploads));

        $this->uploaderService->registerUpload($postUploadEvent);

        $this->assertArrayHasKey($myUploadUuid, $capturedUploads);
        $this->assertSame([$myPathname], $capturedUploads[$myUploadUuid] ?? null);

        $this->assertArrayHasKey('existing-uuid-one', $capturedUploads);
        $this->assertSame($existingPaths['existing-uuid-one'], $capturedUploads['existing-uuid-one']);

        $this->assertArrayHasKey('existing-uuid-two', $capturedUploads);
        $this->assertSame($existingPaths['existing-uuid-two'], $capturedUploads['existing-uuid-two']);
    }

    public function testConfirmUploadOnEmptyUploadsSession(): void
    {
        $sessionKey = 'uploads_default';
        $myUuid = 'my-uuid';

        $this->session
            ->shouldReceive('get')
            ->once()
            ->with($sessionKey, [])
            ->andReturn([]);

        $this->uploaderService->confirmUpload($myUuid, UploadGroupId::DEFAULT);
    }

    public function testConfirmUploadForNonExistingUploadUuid(): void
    {
        $sessionKey = 'uploads_default';
        $myUuid = 'my-uuid';

        $this->session
            ->shouldReceive('get')
            ->once()
            ->with($sessionKey, [])
            ->andReturn([
                'existing-uuid-one' => ['existing-pathname-one'],
                'existing-uuid-two' => ['existing-pathname'],
            ]);

        $this->uploaderService->confirmUpload($myUuid);
    }

    public function testConfirmUploadForExistingUploadUuid(): void
    {
        $sessionKey = 'uploads_woo-decision-attachments';
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
            ->with(['woo-decision-attachments/file1.txt', 'woo-decision-attachments/file2.pdf'])
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

        $this->uploaderService->confirmUpload($myUuid, UploadGroupId::WOO_DECISION_ATTACHMENTS);
    }

    public function testConfirmSingleUploadThrowsExceptionForZeroUploads(): void
    {
        $sessionKey = 'uploads_default';
        $myUuid = 'my-uuid';

        $this->session
            ->shouldReceive('get')
            ->once()
            ->with($sessionKey, [])
            ->andReturn([]);

        $this->expectExceptionObject(UploaderServiceException::forNoFilesUploaded($myUuid));
        $this->uploaderService->confirmSingleUpload($myUuid);
    }

    public function testConfirmSingleUploadThrowsExceptionForMultipleUploads(): void
    {
        $sessionKey = 'uploads_default';
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
            ->with(['default/file1.txt', 'default/file2.pdf'])
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
        $this->uploaderService->confirmSingleUpload($myUuid);
    }

    public function testAttachFileToEntityReplacesExistingUpload(): void
    {
        $sessionKey = 'uploads_covenant-documents';
        $myUuid = 'my-uuid';

        $this->session
            ->shouldReceive('get')
            ->once()
            ->with($sessionKey, [])
            ->andReturn([
                $myUuid => ['/covenant-documents/file1.txt'],
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
        $storedFile->shouldReceive('getMimeType')->andReturn('application/msword');
        $storedFile->shouldReceive('getFilename')->andReturn('prefix_dummy.pdf');

        $finder = \Mockery::mock(Finder::class);
        $finder
            ->shouldReceive('path')
            ->once()
            ->with(['covenant-documents/file1.txt'])
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

        $entity = \Mockery::mock(CovenantDocument::class);
        $entity->shouldReceive('getFileInfo')->andReturn($fileInfo);
        $entity->shouldReceive('getId')->andReturn($entityId);

        $this->entityStorageService->expects('removeFileForEntity')->with($entity);
        $fileInfo->expects('removeFileProperties');
        $fileInfo->expects('setSourceType')->with(SourceType::DOC);
        $fileInfo->expects('setType')->with('doc');
        $fileInfo->expects('setName')->with('dummy.pdf');

        $this->entityStorageService->expects('storeEntity')->with($storedFile, $entity)->andReturnTrue();

        $this->fileTypeHelper->expects('getFileType')->with('application/msword')->andReturn(FileType::DOC);

        self::assertFalse($this->fileSystem->hasChild($mockFilePath));

        $this->uploaderService->attachFileToEntity($myUuid, $entity, UploadGroupId::COVENANT_DOCUMENTS);
    }
}
