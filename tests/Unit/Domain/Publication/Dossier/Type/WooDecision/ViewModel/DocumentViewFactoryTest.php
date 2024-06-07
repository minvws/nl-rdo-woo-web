<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\ViewModel;

use App\Domain\Publication\Dossier\Type\WooDecision\ViewModel\DocumentViewFactory;
use App\Entity\Document as DocumentEntity;
use App\Service\Search\SearchService;
use App\Tests\Unit\UnitTestCase;

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
