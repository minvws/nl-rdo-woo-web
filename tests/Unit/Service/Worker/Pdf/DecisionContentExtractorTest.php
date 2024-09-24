<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Worker\Pdf;

use App\Domain\Ingest\Content\ContentExtractCollection;
use App\Domain\Ingest\Content\ContentExtractOptions;
use App\Domain\Ingest\Content\ContentExtractService;
use App\Domain\Ingest\Content\Extractor\ContentExtractorKey;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Entity\DecisionDocument;
use App\Service\Elastic\ElasticService;
use App\Service\Worker\Pdf\Extractor\DecisionContentExtractor;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;

final class DecisionContentExtractorTest extends UnitTestCase
{
    private ElasticService&MockInterface $elasticService;
    private ContentExtractService&MockInterface $contentExtractService;
    private WooDecision&MockInterface $dossier;
    private DecisionDocument&MockInterface $decisionDocument;
    private DecisionContentExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->contentExtractService = \Mockery::mock(ContentExtractService::class);
        $this->elasticService = \Mockery::mock(ElasticService::class);
        $this->dossier = \Mockery::mock(WooDecision::class);
        $this->decisionDocument = \Mockery::mock(DecisionDocument::class);

        $this->extractor = new DecisionContentExtractor(
            $this->contentExtractService,
            $this->elasticService,
        );
    }

    public function testExtract(): void
    {
        $text = "lorem ipsum tika\nlorem ipsum tesseract";
        $collection = \Mockery::mock(ContentExtractCollection::class);
        $collection->shouldReceive('getCombinedContent')->andReturn($text);

        $this->contentExtractService
            ->expects('getExtracts')
            ->with($this->decisionDocument, \Mockery::on(
                static function (ContentExtractOptions $options): bool {
                    self::assertFalse($options->hasRefresh());
                    self::assertCount(count(ContentExtractorKey::cases()), $options->getEnabledExtractors());

                    return true;
                }
            ))
            ->andReturn($collection);

        $this->elasticService
            ->shouldReceive('updateDossierDecisionContent')
            ->once()
            ->with($this->dossier, $text);

        $this->extractor->extract($this->dossier, $this->decisionDocument, false);
    }
}
