<?php

declare(strict_types=1);

namespace Shared\Service\Logging;

use Monolog\Formatter\NormalizerFormatter;
use Monolog\Handler\FormattableHandlerInterface;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Shared\Service\Security\User;
use Stringable;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use function array_merge;

class EnrichedPsrLogger implements LoggerInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly RequestStack $requestStack,
    ) {
        if ($this->logger instanceof Logger) {
            foreach ($this->logger->getHandlers() as $handler) {
                if ($handler instanceof FormattableHandlerInterface) {
                    $formatter = $handler->getFormatter();
                    if ($formatter instanceof NormalizerFormatter) {
                        $formatter->setMaxNormalizeDepth(20);
                    }
                }
            }
        }
    }

    public function emergency(string|Stringable $message, array $context = []): void
    {
        $this->logger->emergency($message, $this->enriched($context));
    }

    public function alert(string|Stringable $message, array $context = []): void
    {
        $this->logger->alert($message, $this->enriched($context));
    }

    public function critical(string|Stringable $message, array $context = []): void
    {
        $this->logger->critical($message, $this->enriched($context));
    }

    public function error(string|Stringable $message, array $context = []): void
    {
        $this->logger->error($message, $this->enriched($context));
    }

    public function warning(string|Stringable $message, array $context = []): void
    {
        $this->logger->warning($message, $this->enriched($context));
    }

    public function notice(string|Stringable $message, array $context = []): void
    {
        $this->logger->notice($message, $this->enriched($context));
    }

    public function info(string|Stringable $message, array $context = []): void
    {
        $this->logger->info($message, $this->enriched($context));
    }

    public function debug(string|Stringable $message, array $context = []): void
    {
        $this->logger->debug($message, $this->enriched($context));
    }

    public function log(mixed $level, string|Stringable $message, array $context = []): void
    {
        $this->logger->log($level, $message, $this->enriched($context));
    }

    /**
     * @param mixed[] $context
     *
     * @return mixed[]
     */
    protected function enriched(array $context): array
    {
        $userInfo = [
            'ip' => $this->requestStack->getCurrentRequest()?->getClientIps() ?? [],
        ];

        $token = $this->tokenStorage->getToken();
        if ($token) {
            $user = $token->getUser();
            if ($user instanceof User) {
                $userInfo['id'] = (string) $user->getId();
                $userInfo['roles'] = $user->getRoles();
            }
        }

        return array_merge($context, [
            'user_info' => $userInfo,
        ]);
    }
}
