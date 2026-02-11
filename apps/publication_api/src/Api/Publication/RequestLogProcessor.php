<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication;

use Monolog\Attribute\AsMonologProcessor;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use PublicationApi\Domain\Security\ApiUser;
use Shared\Service\Security\ApplicationMode\ApplicationMode;
use Symfony\Bundle\SecurityBundle\Security;

#[AsMonologProcessor()]
readonly class RequestLogProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
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
