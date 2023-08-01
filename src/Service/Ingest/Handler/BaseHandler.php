<?php

declare(strict_types=1);

namespace App\Service\Ingest\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

abstract class BaseHandler
{
    protected LoggerInterface $logger;
    protected MessageBusInterface $bus;
    protected EntityManagerInterface $doctrine;

    public function __construct(
        MessageBusInterface $bus,
        EntityManagerInterface $doctrine,
        LoggerInterface $logger
    ) {
        $this->bus = $bus;
        $this->doctrine = $doctrine;
        $this->logger = $logger;
    }
}
