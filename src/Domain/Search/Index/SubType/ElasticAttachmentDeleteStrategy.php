<?php

declare(strict_types=1);

namespace App\Domain\Search\Index\SubType;

use App\Domain\Publication\Attachment\AbstractAttachment;
use App\Domain\Publication\Attachment\AttachmentDeleteStrategyInterface;
use App\Domain\Search\Index\DeleteElasticDocumentCommand;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class ElasticAttachmentDeleteStrategy implements AttachmentDeleteStrategyInterface
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private SubTypeIndexer $subTypeIndexer,
    ) {
    }

    public function delete(AbstractAttachment $attachment): void
    {
        $this->messageBus->dispatch(
            new DeleteElasticDocumentCommand(
                $this->subTypeIndexer->getDocumentId($attachment)
            )
        );
    }
}
