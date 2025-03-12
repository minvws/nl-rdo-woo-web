<?php

declare(strict_types=1);

namespace App\Domain\Search\Index\SubType;

use App\Domain\Publication\Attachment\AttachmentDeleteStrategyInterface;
use App\Domain\Publication\Attachment\Entity\AbstractAttachment;
use App\Domain\Search\Index\ElasticDocumentId;
use App\Domain\Search\SearchDispatcher;

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
