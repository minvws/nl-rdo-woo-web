<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Document\ViewModel;

use Mockery;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document as DocumentEntity;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\ViewModel\DocumentViewFactory;
use Shared\Service\Search\SearchService;
use Shared\Tests\Unit\UnitTestCase;

final class DocumentViewFactoryTest extends UnitTestCase
{
    public function testMake(): void
    {
        $searchService = Mockery::mock(SearchService::class);
        $searchService->shouldReceive('isIngested')->andReturn($expectedIngested = true);

        $documentEntity = Mockery::mock(DocumentEntity::class);

        $result = (new DocumentViewFactory($searchService))->make($documentEntity);

        $this->assertSame($expectedIngested, $result->ingested);
        $this->assertSame($documentEntity, $result->entity);
    }
}
