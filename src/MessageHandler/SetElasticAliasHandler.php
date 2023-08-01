<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\SetElasticAliasMessage;
use App\Service\Elastic\IndexService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class SetElasticAliasHandler
{
    public function __construct(
        protected IndexService $indexService,
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
