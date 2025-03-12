<?php

declare(strict_types=1);

namespace App\Domain\WooIndex\Producer;

use App\Domain\Publication\Attachment\Entity\AbstractAttachment;
use App\Domain\Publication\Attachment\Repository\AttachmentRepository;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use App\Domain\Publication\MainDocument\MainDocumentRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class UrlProducer
{
    public function __construct(
        private DocumentRepository $documentRepository,
        private AttachmentRepository $attachmentRepository,
        private MainDocumentRepository $mainDocumentRepository,
        private UrlMapper $urlMapper,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return \Generator<int,Url>
     */
    public function getAll(): \Generator
    {
        foreach ($this->fetchData() as $document) {
            yield $this->urlMapper->fromEntity($document);

            $this->entityManager->detach($document);
        }
    }

    /**
     * @return \Generator<int,\Generator<int,Url>>
     */
    public function getChunked(int $chunkSize = 50_000): \Generator
    {
        yield from $this->doChunking($this->getAll(), $chunkSize);
    }

    /**
     * @template T
     *
     * @param \Generator<int,T> $producer
     *
     * @return \Generator<int,\Generator<int,T>>
     */
    private function doChunking(\Generator $producer, int $chunkSize): \Generator
    {
        while ($producer->valid()) {
            if (isset($chunkGen) && $chunkGen->valid()) {
                throw UnconsumedPreviousChunkGeneratorException::create();
            }

            yield $chunkGen = (function () use ($chunkSize, &$producer) {
                for ($i = 0; $i < $chunkSize && $producer->valid(); $i++) {
                    $signal = yield $producer->current();

                    if ($signal === ProducerSignal::STOP_CHUNK) {
                        break;
                    }

                    $producer->next();
                }
            })();
        }
    }

    /**
     * @return iterable<int,Document|AbstractAttachment|AbstractMainDocument>
     */
    private function fetchData(): iterable
    {
        yield from $this->documentRepository->getPublishedDocumentsIterable();
        yield from $this->attachmentRepository->getPublishedAttachmentsIterable();
        yield from $this->mainDocumentRepository->getPublishedMainDocumentsIterable();
    }
}
