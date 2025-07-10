<?php

declare(strict_types=1);

namespace App\Domain\Search\Index\Rollover;

use App\Domain\Search\Index\ElasticIndex\ElasticIndexManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Set the elasticsearch alias to the given index.
 */
#[AsMessageHandler]
readonly class SetElasticAliasHandler
{
    public function __construct(
        private ElasticIndexManager $indexService,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(SetElasticAliasCommand $message): void
    {
        try {
            $this->indexService->switch(
                $message->aliasName,
                srcIndex: '*',
                dstIndex: $message->indexName,
            );
        } catch (\Exception $e) {
            $this->logger->error('Failed to switch elasticsearch alias', [
                'index_name' => $message->indexName,
                'alias_name' => $message->aliasName,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
