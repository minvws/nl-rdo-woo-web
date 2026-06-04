<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Department\ViewModel;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileType;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use Shared\Domain\Publication\Dossier\ViewModel\DossierFileViewFactory;
use Shared\Service\Storage\ThumbnailStorageService;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Uuid;

final class DossierFileViewFactoryTest extends UnitTestCase
{
    private ThumbnailStorageService&MockInterface $thumbnailStorage;
    private UrlGeneratorInterface&MockInterface $urlGenerator;
    private DossierFileViewFactory $factory;

    protected function setUp(): void
    {
        $this->thumbnailStorage = Mockery::mock(ThumbnailStorageService::class);
        $this->urlGenerator = Mockery::mock(UrlGeneratorInterface::class);

        $this->factory = new DossierFileViewFactory(
            $this->thumbnailStorage,
            $this->urlGenerator,
        );
    }

    public function testMakeReturnsEarlyWhenEntityHasNoPages(): void
    {
        $dossier = Mockery::mock(Covenant::class);
        $dossier->expects('getDocumentPrefix')->andReturn($prefix = 'foo');
        $dossier->expects('getDossierNr')->andReturn($dossierNr = 'bar-123');

        $fileEntity = Mockery::mock(CovenantAttachment::class);
        $fileEntity->expects('getId')->andReturn($entityId = Uuid::v6());
        $fileEntity->expects('getFileInfo->getType')->andReturn('pdf');
        $fileEntity->expects('getFileInfo->getSize')->andReturn(456);
        $fileEntity->expects('getFileInfo->hasPages')->andReturnFalse();

        $fileType = DossierFileType::ATTACHMENT;

        $this->urlGenerator
            ->expects('generate')
            ->with(
                'app_dossier_file_download',
                [
                    'prefix' => $prefix,
                    'dossierId' => $dossierNr,
                    'type' => $fileType->value,
                    'id' => $entityId,
                ],
            )
            ->andReturn($expectedUrl = 'my-url');

        $fileView = $this->factory->make($dossier, $fileEntity, $fileType);

        self::assertEquals($expectedUrl, $fileView->downloadUrl);
        self::assertFalse($fileView->hasPages);
        self::assertCount(0, $fileView->pages);
    }

    public function testMakeWithPages(): void
    {
        $dossier = Mockery::mock(Covenant::class);
        $dossier->expects('getDocumentPrefix')->times(2)->andReturn($prefix = 'foo');
        $dossier->expects('getDossierNr')->times(2)->andReturn($dossierNr = 'bar-123');

        $fileEntity = Mockery::mock(CovenantAttachment::class);
        $fileEntity->expects('getId')->times(2)->andReturn($entityId = Uuid::v6());
        $fileEntity->expects('getFileInfo->hasPages')->andReturnTrue();
        $fileEntity->expects('getFileInfo->getPageCount')->andReturn(2);
        $fileEntity->expects('getFileInfo->getType')->andReturn('pdf');
        $fileEntity->expects('getFileInfo->getSize')->andReturn(456);
        $fileEntity->expects('getFileInfo->getHash')->andReturn($fileHash = 'fooBar');

        $fileType = DossierFileType::ATTACHMENT;

        $this->urlGenerator
            ->expects('generate')
            ->with(
                'app_dossier_file_download',
                [
                    'prefix' => $prefix,
                    'dossierId' => $dossierNr,
                    'type' => $fileType->value,
                    'id' => $entityId,
                ],
            )
            ->andReturn('my-url');

        $this->thumbnailStorage->expects('exists')->with($fileEntity, 1)->andReturnFalse();
        $this->thumbnailStorage->expects('exists')->with($fileEntity, 2)->andReturnTrue();

        $this->urlGenerator
            ->expects('generate')
            ->with(
                'app_dossier_file_thumbnail',
                [
                    'prefix' => $prefix,
                    'dossierId' => $dossierNr,
                    'type' => $fileType->value,
                    'id' => $entityId,
                    'pageNr' => 2,
                    'hash' => $fileHash,
                ],
            )
            ->andReturn('thumb-url');

        $fileView = $this->factory->make($dossier, $fileEntity, $fileType);

        $this->assertMatchesObjectSnapshot($fileView);
    }
}
