<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\UpdateDepartmentMessage;
use App\Service\Elastic\ElasticService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UpdateDepartmentHandler
{
    protected EntityManagerInterface $doctrine;
    protected LoggerInterface $logger;
    protected ElasticService $elasticService;

    public function __construct(
        ElasticService $elasticService,
        LoggerInterface $logger
    ) {
        $this->elasticService = $elasticService;
        $this->logger = $logger;
    }

    public function __invoke(UpdateDepartmentMessage $message): void
    {
        try {
            $this->elasticService->updateDepartment($message->getOld(), $message->getNew());
        } catch (\Exception $e) {
            $this->logger->error('Failed to update department in elasticsearch', [
                'id' => $message->getOld()->getId(),
                'old' => $message->getOld()->getName(),
                'new' => $message->getNew()->getName(),
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
