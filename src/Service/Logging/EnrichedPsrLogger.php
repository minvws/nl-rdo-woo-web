<?php

declare(strict_types=1);

namespace App\Service\Logging;

use App\Entity\User;
use Monolog\Formatter\NormalizerFormatter;
use Monolog\Handler\FormattableHandlerInterface;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

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

    public function emergency($message, array $context = []): void
    {
        $this->logger->emergency($message, $this->enriched($context));
    }

    public function alert($message, array $context = []): void
    {
        $this->logger->alert($message, $this->enriched($context));
    }

    public function critical($message, array $context = []): void
    {
        $this->logger->critical($message, $this->enriched($context));
    }

    public function error($message, array $context = []): void
    {
        $this->logger->error($message, $this->enriched($context));
    }

    public function warning($message, array $context = []): void
    {
        $this->logger->warning($message, $this->enriched($context));
    }

    public function notice($message, array $context = []): void
    {
        $this->logger->notice($message, $this->enriched($context));
    }

    public function info($message, array $context = []): void
    {
        $this->logger->info($message, $this->enriched($context));
    }

    public function debug($message, array $context = []): void
    {
        $this->logger->debug($message, $this->enriched($context));
    }

    public function log($level, $message, array $context = []): void
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
            if ($user) {
                /** @var User $user */
                $userInfo['id'] = (string) $user->getId();
                $userInfo['roles'] = $user->getRoles();
            }
        }

        return array_merge($context, [
            'user_info' => $userInfo,
        ]);
    }
}
