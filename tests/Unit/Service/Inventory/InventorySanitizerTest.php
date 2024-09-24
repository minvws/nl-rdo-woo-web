<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Inventory;

use App\Entity\Document;
use App\Entity\Dossier;
use App\Entity\FileInfo;
use App\Entity\Inventory;
use App\Entity\Judgement;
use App\Service\Inventory\Sanitizer\InventoryDataProviderInterface;
use App\Service\Inventory\Sanitizer\InventorySanitizer;
use App\Service\Inventory\Sanitizer\InventoryWriterInterface;
use App\Service\Storage\EntityStorageService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class InventorySanitizerTest extends MockeryTestCase
{
    private EntityManagerInterface&MockInterface $entityManager;
    private EntityStorageService&MockInterface $entityStorageService;
    private TranslatorInterface&MockInterface $translator;
    private InventoryWriterInterface&MockInterface $writer;
    private UrlGeneratorInterface&MockInterface $urlGenerator;
    private InventorySanitizer $sanitizer;
    private InventoryDataProviderInterface&MockInterface $dataProvider;

    public function setUp(): void
    {
        $this->entityManager = \Mockery::mock(EntityManagerInterface::class);
        $this->entityStorageService = \Mockery::mock(EntityStorageService::class);
        $this->translator = \Mockery::mock(TranslatorInterface::class);
        $this->writer = \Mockery::mock(InventoryWriterInterface::class);
        $this->urlGenerator = \Mockery::mock(UrlGeneratorInterface::class);

        $this->dataProvider = \Mockery::mock(InventoryDataProviderInterface::class);

        $this->sanitizer = new InventorySanitizer(
            $this->entityManager,
            $this->entityStorageService,
            $this->translator,
            $this->writer,
            $this->urlGenerator,
            ''
        );

        parent::setUp();
    }

    public function testFileIsWrittenAndInventoryPersisted(): void
    {
        $urls = ['http://dummy.url', 'https://x.y.z'];

        $this->writer->expects('open');
        $this->writer->expects('addHeaders');
        $this->writer->expects('addRow')->with(
            '123',
            'test-doc-nr',
            'test-doc-name',
            'deels openbaar',
            ['a', 'b'],
            '',
            "http://dummy.url\nhttps://x.y.z",
            'test-url',
            'ja',
            ''
        );
        $this->writer->expects('close');
        $this->writer->expects('getFileExtension')->twice()->andReturn('csv');

        $this->translator->expects('trans')->with('public.documents.judgment.short.' . Judgement::PARTIAL_PUBLIC->value)->andReturn('deels openbaar');

        $dossier = \Mockery::mock(Dossier::class);
        $dossier->expects('getDossierNr')->andReturn('tst-123');
        $dossier->expects('getDocumentPrefix')->andReturn('PREFIX');

        $inventory = \Mockery::mock(Inventory::class);
        $inventory->expects('setFileInfo')->with(\Mockery::on(
            function (FileInfo $fileInfo) {
                self::assertEquals('foo-bar.csv', $fileInfo->getName());

                return true;
            }
        ));

        $document = \Mockery::mock(Document::class);
        $document->expects('getDocumentId')->andReturn(123);
        $document->expects('getDocumentNr')->twice()->andReturn('test-doc-nr');
        $document->expects('getFileInfo->getName')->andReturn('test-doc-name');
        $document->expects('getJudgement')->twice()->andReturn(Judgement::PARTIAL_PUBLIC);
        $document->expects('getGrounds')->andReturn(['a', 'b']);
        $document->expects('getRemark')->andReturnNull();
        $document->expects('isSuspended')->andReturnTrue();
        $document->expects('getDossiers->first')->andReturn($dossier);
        $document->expects('getLinks')->andReturn($urls);

        $this->urlGenerator
            ->expects('generate')
            ->with('app_document_detail', ['prefix' => 'PREFIX', 'dossierId' => 'tst-123', 'documentId' => 'test-doc-nr'])
            ->andReturn('test-url');

        $this->dataProvider->expects('getDocuments')->andReturn(new ArrayCollection([$document]));
        $this->dataProvider->expects('getInventoryEntity')->andReturn($inventory);
        $this->dataProvider->expects('getFilename')->andReturn('foo-bar');
        $this->entityManager->expects('persist')->with($inventory);

        $this->entityStorageService->expects('storeEntity')->andReturnTrue();

        $this->sanitizer->generateSanitizedInventory($this->dataProvider);
    }
}
