<?php

declare(strict_types=1);

namespace App\Domain\Search\Index\SubType;

use App\Domain\Publication\Attachment\AbstractAttachment;
use App\Domain\Publication\Attachment\AttachmentDeleteStrategyInterface;
use App\Domain\Search\SearchDispatcher;

readonly class ElasticAttachmentDeleteStrategy implements AttachmentDeleteStrategyInterface
{
    public function __construct(
        private SearchDispatcher $dispatcher,
        private SubTypeIndexer $subTypeIndexer,
    ) {
    }

    public function delete(AbstractAttachment $attachment): void
    {
        $this->dispatcher->dispatchDeleteElasticDocumentCommand(
            $this->subTypeIndexer->getDocumentId($attachment),
        );
    }
}
