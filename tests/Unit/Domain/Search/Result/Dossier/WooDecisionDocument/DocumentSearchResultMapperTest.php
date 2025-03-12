<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Result\Dossier\WooDecisionDocument;

use App\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Result\SubType\WooDecisionDocument\DocumentSearchResultMapper;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class DocumentSearchResultMapperTest extends MockeryTestCase
{
    private DocumentRepository&MockInterface $documentRepository;
    private WooDecisionRepository&MockInterface $dossierRepository;
    private DocumentSearchResultMapper $mapper;

    public function setUp(): void
    {
        $this->dossierRepository = \Mockery::mock(WooDecisionRepository::class);
        $this->documentRepository = \Mockery::mock(DocumentRepository::class);

        $this->mapper = new DocumentSearchResultMapper(
            $this->documentRepository,
            $this->dossierRepository,
        );
    }

    public function testSupports(): void
    {
        self::assertTrue($this->mapper->supports(ElasticDocumentType::WOO_DECISION_DOCUMENT));
        self::assertFalse($this->mapper->supports(ElasticDocumentType::COMPLAINT_JUDGEMENT_MAIN_DOCUMENT));
    }
}
