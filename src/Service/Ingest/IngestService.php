<?php

declare(strict_types=1);

namespace App\Service\Ingest;

use App\Entity\Document;

/**
 * This class is responsible for ingesting documents into the system. It checks which handler can handle the given document, and passes
 * it to that handler.
 */
class IngestService
{
    /** @var Handler[] */
    protected array $handlers;

    /**
     * @param iterable|Handler[] $handlers
     */
    public function __construct(
        iterable $handlers,
    ) {
        $this->handlers = $handlers instanceof \Traversable ? iterator_to_array($handlers) : $handlers;
    }

    public function ingest(Document $document, Options $options): void
    {
        foreach ($this->handlers as $handler) {
            if ($handler->canHandle($document->getFileInfo())) {
                $handler->handle($document, $options);

                return;
            }
        }

        throw new \RuntimeException('no matching ingest handler found for document ' . $document->getId()->toRfc4122());
    }
}
