<?php

declare(strict_types=1);

namespace App\Tests\Unit\ViewModel\Factory;

use App\Entity\Document as DocumentEntity;
use App\Service\Search\SearchService;
use App\Tests\Unit\UnitTestCase;
use App\ViewModel\Factory\DocumentViewFactory;

final class DocumentViewFactoryTest extends UnitTestCase
{
    public function testMake(): void
    {
        $searchService = \Mockery::mock(SearchService::class);
        $searchService->shouldReceive('isIngested')->andReturn($expectedIngested = true);

        $documentEntity = \Mockery::mock(DocumentEntity::class);

        $result = (new DocumentViewFactory($searchService))->make($documentEntity);

        $this->assertSame($expectedIngested, $result->ingested);
        $this->assertSame($documentEntity, $result->entity);
    }
}
