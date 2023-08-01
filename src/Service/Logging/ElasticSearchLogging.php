<?php

declare(strict_types=1);

namespace App\Service\Logging;

use App\DataCollector\ElasticCollector;
use App\Service\Elastic\ElasticService;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ElasticSearchLogging implements LoggingTypeInterface
{
    private LoggerInterface $logger;
    private bool $disabled = false;

    public function __construct(
        private readonly ElasticService $elasticService,
        private readonly ElasticCollector $elasticCollector,
    ) {
    }

    public function disable(): void
    {
        $this->logger = $this->elasticService->getLogger();
        $this->elasticService->setLogger(new NullLogger());
        $this->elasticCollector->disable();

        $this->disabled = true;
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    public function restore(): void
    {
        $this->elasticService->setLogger($this->logger);
        $this->elasticCollector->enable();

        unset($this->logger);
    }
}
