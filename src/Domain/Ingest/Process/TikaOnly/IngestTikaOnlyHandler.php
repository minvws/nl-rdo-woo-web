<?php

declare(strict_types=1);

namespace App\Domain\Ingest\Process\TikaOnly;

use App\Domain\Ingest\Content\ContentExtractOptions;
use App\Domain\Ingest\Content\ContentExtractService;
use App\Domain\Ingest\Content\Extractor\ContentExtractorKey;
use App\Domain\Publication\EntityWithFileInfo;
use App\Domain\Search\Index\SubType\SubTypeIndexer;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Webmozart\Assert\Assert;

#[AsMessageHandler]
final readonly class IngestTikaOnlyHandler
{
    public function __construct(
        private EntityManagerInterface $doctrine,
        private LoggerInterface $logger,
        private ContentExtractService $contentExtractService,
        private SubTypeIndexer $subTypeIndexer,
    ) {
    }

    public function __invoke(IngestTikaOnlyCommand $message): void
    {
        $entity = $this->doctrine->getRepository($message->getEntityClass())->find($message->getEntityId());
        if (is_null($entity)) {
            $this->logger->warning('No entity found in IngestTikaOnlyHandler', [
                'id' => $message->getEntityId(),
                'class' => $message->getEntityClass(),
            ]);

            return;
        }

        /** @var EntityWithFileInfo $entity */
        Assert::isInstanceOf($entity, EntityWithFileInfo::class);

        $extracts = $this->contentExtractService->getExtracts(
            $entity,
            ContentExtractOptions::create()
                ->withExtractor(ContentExtractorKey::TIKA)
                ->withRefresh($message->getForceRefresh())
        );

        try {
            $this->subTypeIndexer->index(
                $entity,
                [],
                [
                    [
                        'page_nr' => 0,
                        'content' => $extracts->getCombinedContent(),
                    ],
                ]
            );
        } catch (\Exception $e) {
            $this->logger->error('Failed to index tika content as page', [
                'id' => $entity->getId(),
                'class' => $entity::class,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
