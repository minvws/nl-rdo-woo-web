<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class CollectingMessageBus implements MessageBusInterface
{
    /** @var object[] */
    protected array $dispatchedMessages = [];

    public function dispatch($message, array $stamps = []): Envelope
    {
        $this->dispatchedMessages[] = $message;

        return Envelope::wrap($message, $stamps);
    }

    /** @return object[] */
    public function dispatchedMessages(): array
    {
        return $this->dispatchedMessages;
    }
}
