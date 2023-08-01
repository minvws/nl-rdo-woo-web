<?php

declare(strict_types=1);

namespace App\Service\Logging;

use Doctrine\Bundle\DoctrineBundle\Middleware\DebugMiddleware;
use Doctrine\DBAL\Driver\Middleware;
use Doctrine\DBAL\Logging\Middleware as LoggingMiddleware;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Used to disable logging in Doctrine, because it is very verbose and slows down the application
 * when using in large quantities. For instance, when generating a lot of dummy entities.
 */
class DoctrineLogging implements LoggingTypeInterface
{
    /**
     * @var Middleware[]|null
     */
    private ?array $middlewares = null;

    public function __construct(
        private readonly EntityManagerInterface $doctrine,
    ) {
    }

    public function disable(): void
    {
        $this->middlewares = $this->doctrine->getConnection()->getConfiguration()->getMiddlewares();

        // Disable debug and logging middleware by removing them from the configuration
        $disallowed = [DebugMiddleware::class, LoggingMiddleware::class];

        $allowed = [];
        foreach ($this->middlewares as $middleware) {
            // Check if $middleware is an instance of one of $disallowed
            foreach ($disallowed as $disallowedClass) {
                if ($middleware instanceof $disallowedClass) {
                    continue 2;
                }
            }

            $allowed[] = $middleware;
        }

        $this->doctrine->getConnection()->getConfiguration()->setMiddlewares($allowed);
    }

    public function isDisabled(): bool
    {
        return $this->middlewares !== null;
    }

    public function restore(): void
    {
        $this->doctrine->getConnection()->getConfiguration()->setMiddlewares($this->middlewares ?? []);

        unset($this->middlewares);
    }
}
