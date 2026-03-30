<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication;

use Monolog\Attribute\AsMonologProcessor;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Shared\Service\Security\ApiUser;
use Shared\Service\Security\ApplicationMode\ApplicationMode;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsMonologProcessor()]
readonly class RequestLogProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        #[Autowire(service: ApplicationMode::class)]
        private ApplicationMode $applicationMode,
    ) {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        if (! $this->applicationMode->isApiOrAll()) {
            return $record;
        }

        $user = $this->security->getUser();

        if (! $user instanceof ApiUser) {
            return $record;
        }

        $record->extra['commonName'] = $user->getUserIdentifier();

        return $record;
    }
}
