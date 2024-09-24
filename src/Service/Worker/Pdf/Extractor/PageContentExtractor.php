<?php

declare(strict_types=1);

namespace App\Service\Worker\Pdf\Extractor;

use App\Domain\Ingest\Content\ContentExtractCollection;
use App\Domain\Ingest\Content\ContentExtractOptions;
use App\Domain\Ingest\Content\ContentExtractService;
use App\Domain\Search\Index\SubType\SubTypeIndexer;
use App\Entity\EntityWithFileInfo;
use App\Service\Stats\WorkerStatsService;
use Psr\Log\LoggerInterface;
use Webmozart\Assert\Assert;

/**
 * Extractor that will extract content from a single page from a given entity.
 */
readonly class PageContentExtractor implements PageExtractorInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private SubTypeIndexer $subTypeIndexer,
        private ContentExtractService $contentExtractService,
        private WorkerStatsService $statsService,
    ) {
    }

    public function extract(EntityWithFileInfo $entity, int $pageNr, bool $forceRefresh): void
    {
        Assert::true($entity->getFileInfo()->isPaginatable(), 'Entity is not paginatable');

        /** @var ContentExtractCollection $extracts */
        $extracts = $this->statsService->measure(
            'content.extract.entity',
            fn () => $this->contentExtractService->getExtracts(
                $entity,
                ContentExtractOptions::create()
                    ->withAllExtractors()
                    ->withRefresh($forceRefresh)
                    ->withPageNumber($pageNr),
            ),
        );

        $this->statsService->measure(
            'index.full.entity',
            fn () => $this->indexPage($entity, $pageNr, $extracts->getCombinedContent()),
        );
    }

    private function indexPage(EntityWithFileInfo $entity, int $pageNr, string $content): void
    {
        try {
            $this->subTypeIndexer->updatePage($entity, $pageNr, $content);
        } catch (\Exception $e) {
            $this->logger->error('Failed to index page', [
                'id' => $entity->getId(),
                'class' => $entity::class,
                'pageNr' => $pageNr,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
