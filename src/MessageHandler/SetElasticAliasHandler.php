<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Domain\Search\Index\ElasticIndex\ElasticIndexManager;
use App\Message\SetElasticAliasMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Set the elasticsearch alias to the given index.
 */
#[AsMessageHandler]
class SetElasticAliasHandler
{
    public function __construct(
        protected ElasticIndexManager $indexService,
        protected LoggerInterface $logger,
        protected MessageBusInterface $bus,
    ) {
    }

    public function __invoke(SetElasticAliasMessage $message): void
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
