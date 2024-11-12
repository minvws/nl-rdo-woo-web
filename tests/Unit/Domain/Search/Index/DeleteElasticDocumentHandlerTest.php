<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Index;

use App\Domain\Search\Index\DeleteElasticDocumentCommand;
use App\Domain\Search\Index\DeleteElasticDocumentHandler;
use App\Service\Elastic\ElasticService;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class DeleteElasticDocumentHandlerTest extends MockeryTestCase
{
    public function testInvoke(): void
    {
        $id = 'foo-123';

        $elasticService = \Mockery::mock(ElasticService::class);
        $elasticService->expects('removeDocument')->with($id);

        $handler = new DeleteElasticDocumentHandler($elasticService);

        $handler->__invoke(new DeleteElasticDocumentCommand($id));
    }
}
