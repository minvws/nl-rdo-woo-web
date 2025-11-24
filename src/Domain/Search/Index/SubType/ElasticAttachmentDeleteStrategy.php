<?php

declare(strict_types=1);

namespace Shared\Domain\Search\Index\SubType;

use Shared\Domain\Publication\Attachment\AttachmentDeleteStrategyInterface;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Search\Index\ElasticDocumentId;
use Shared\Domain\Search\SearchDispatcher;

readonly class ElasticAttachmentDeleteStrategy implements AttachmentDeleteStrategyInterface
{
    public function __construct(
        private SearchDispatcher $dispatcher,
    ) {
    }

    public function delete(AbstractAttachment $attachment): void
    {
        $this->dispatcher->dispatchDeleteElasticDocumentCommand(
            ElasticDocumentId::forObject($attachment),
        );
    }
}
