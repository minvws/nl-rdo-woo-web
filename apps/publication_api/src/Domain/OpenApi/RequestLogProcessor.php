<?php

declare(strict_types=1);

namespace PublicationApi\Domain\OpenApi;

use Monolog\Attribute\AsMonologProcessor;
use Monolog\LogRecord;
use Shared\Service\Security\ApiUser;
use Symfony\Bundle\SecurityBundle\Security;

#[AsMonologProcessor()]
readonly class RequestLogProcessor
{
    public function __construct(
        private Security $security,
    ) {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $user = $this->security->getUser();
        if (! $user instanceof ApiUser) {
            return $record;
        }

        $record->extra['commonName'] = $user->getUserIdentifier();

        return $record;
    }
}
