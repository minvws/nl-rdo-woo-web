<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Search\Index;

use Mockery;
use Shared\Domain\Search\Index\DeleteElasticDocumentCommand;
use Shared\Domain\Search\Index\DeleteElasticDocumentHandler;
use Shared\Service\Elastic\ElasticService;
use Shared\Tests\Unit\UnitTestCase;

class DeleteElasticDocumentHandlerTest extends UnitTestCase
{
    public function testInvoke(): void
    {
        $id = 'foo-123';

        $elasticService = Mockery::mock(ElasticService::class);
        $elasticService->expects('removeDocument')->with($id);

        $handler = new DeleteElasticDocumentHandler($elasticService);

        $handler->__invoke(new DeleteElasticDocumentCommand($id));
    }
}
