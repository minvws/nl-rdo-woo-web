<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Index\Rollover;

use Psr\Log\LoggerInterface;
use Shared\Domain\Search\Index\ElasticIndex\ElasticIndexManager;
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
