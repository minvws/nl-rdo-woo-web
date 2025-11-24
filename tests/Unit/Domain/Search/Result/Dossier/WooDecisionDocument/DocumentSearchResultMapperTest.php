<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Search\Result\Dossier\WooDecisionDocument;

use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Domain\Search\Index\ElasticDocumentType;
use Shared\Domain\Search\Result\SubType\WooDecisionDocument\DocumentSearchResultMapper;
use Shared\Tests\Unit\UnitTestCase;

class DocumentSearchResultMapperTest extends UnitTestCase
{
    private DocumentRepository&MockInterface $documentRepository;
    private WooDecisionRepository&MockInterface $dossierRepository;
    private DocumentSearchResultMapper $mapper;

    protected function setUp(): void
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
