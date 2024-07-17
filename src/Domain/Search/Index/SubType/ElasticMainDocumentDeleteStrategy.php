<?php

declare(strict_types=1);

namespace App\Domain\Search\Index\SubType;

use App\Domain\Publication\MainDocument\AbstractMainDocument;
use App\Domain\Publication\MainDocument\MainDocumentDeleteStrategyInterface;
use App\Domain\Search\Index\DeleteElasticDocumentCommand;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class ElasticMainDocumentDeleteStrategy implements MainDocumentDeleteStrategyInterface
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private SubTypeIndexer $subTypeIndexer,
    ) {
    }

    public function delete(AbstractMainDocument $mainDocument): void
    {
        $this->messageBus->dispatch(
            new DeleteElasticDocumentCommand(
                $this->subTypeIndexer->getDocumentId($mainDocument)
            )
        );
    }
}
