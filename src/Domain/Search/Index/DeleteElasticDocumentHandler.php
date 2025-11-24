<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Index;

use Shared\Service\Elastic\ElasticService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class DeleteElasticDocumentHandler
{
    public function __construct(
        private ElasticService $elasticService,
    ) {
    }

    public function __invoke(DeleteElasticDocumentCommand $command): void
    {
        $this->elasticService->removeDocument($command->id);
    }
}
