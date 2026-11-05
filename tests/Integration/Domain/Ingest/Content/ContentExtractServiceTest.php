<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Ingest\Content;

use Doctrine\ORM\EntityManagerInterface;
use Mockery;
use Psr\Log\LoggerInterface;
use Shared\Domain\Ingest\Content\ContentExtractOptions;
use Shared\Domain\Ingest\Content\ContentExtractService;
use Shared\Domain\Ingest\Content\Extractor\ContentExtractorInterface;
use Shared\Domain\Ingest\Content\Extractor\ContentExtractorKey;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Service\Storage\EntityStorageService;
use Shared\Tests\Factory\DocumentFactory;
use Shared\Tests\Factory\FileInfoFactory;
use Shared\Tests\Integration\SharedWebTestCase;

final class ContentExtractServiceTest extends SharedWebTestCase
{
    public function testGetExtractsLeavesMissingHashUntouched(): void
    {
        self::bootKernel();

        $document = DocumentFactory::createOne([
            'fileInfo' => FileInfoFactory::new([
                'uploaded' => true,
                'hash' => null,
            ]),
        ]);

        $extractor = Mockery::mock(ContentExtractorInterface::class);
        $extractor->expects('getKey')->times(2)->andReturn(ContentExtractorKey::TIKA);
        $extractor->expects('supports')->andReturnTrue();
        $extractor->expects('getContent')->andReturn($this->getFaker()->sentence());

        $contentExtractService = new ContentExtractService(
            self::fromContainer(EntityStorageService::class),
            self::fromContainer(LoggerInterface::class),
            [$extractor],
        );

        $contentExtractService->getExtracts(
            $document,
            ContentExtractOptions::create()
                ->withAllExtractors()
                ->withFileContents($this->getFaker()->sentence()),
        );

        $entityManager = self::fromContainer(EntityManagerInterface::class);
        $entityManager->clear();

        $reloadedDocument = $entityManager->find(Document::class, $document->getId());

        self::assertNotNull($reloadedDocument);
        self::assertNull($reloadedDocument->getFileInfo()->getHash());
    }
}
