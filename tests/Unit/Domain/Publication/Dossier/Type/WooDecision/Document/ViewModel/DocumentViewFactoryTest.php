<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Document\ViewModel;

use Mockery;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document as DocumentEntity;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\ViewModel\DocumentViewFactory;
use Shared\Domain\Publication\FileInfo;
use Shared\Service\Search\SearchService;
use Shared\Tests\Unit\UnitTestCase;

final class DocumentViewFactoryTest extends UnitTestCase
{
    public function testMake(): void
    {
        $expectedIngested = true;
        $expectedIsDownloadable = true;

        $searchService = Mockery::mock(SearchService::class);
        $searchService->expects('isIngested')->andReturn($expectedIngested);

        $fileInfo = Mockery::mock(FileInfo::class);
        $fileInfo->expects('getSize')->andReturn(1337);

        $documentEntity = Mockery::mock(DocumentEntity::class);
        $documentEntity->expects('shouldBeUploaded')->andReturnTrue();
        $documentEntity->expects('isUploaded')->andReturnTrue();
        $documentEntity->expects('getFileInfo')->andReturn($fileInfo);

        $result = new DocumentViewFactory($searchService)->make($documentEntity);

        $this->assertSame($expectedIngested, $result->ingested);
        $this->assertSame($documentEntity, $result->entity);
        $this->assertSame($expectedIsDownloadable, $result->isDownloadable);
    }
}
