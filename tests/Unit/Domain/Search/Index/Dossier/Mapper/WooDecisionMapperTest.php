<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Index\Dossier\Mapper;

use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\WooDecision\Decision\DecisionType;
use App\Domain\Publication\Dossier\Type\WooDecision\Inquiry\Inquiry;
use App\Domain\Publication\Dossier\Type\WooDecision\PublicationReason;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Search\Index\Dossier\Mapper\DefaultDossierMapper;
use App\Domain\Search\Index\Dossier\Mapper\ElasticDossierMapperInterface;
use App\Domain\Search\Index\Dossier\Mapper\WooDecisionMapper;
use App\Domain\Search\Index\ElasticDocument;
use App\Domain\Search\Index\ElasticDocumentType;
use Doctrine\Common\Collections\ArrayCollection;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Uid\Uuid;

class WooDecisionMapperTest extends MockeryTestCase
{
    private WooDecisionMapper $mapper;
    private ElasticDossierMapperInterface&MockInterface $defaultMapper;

    public function setUp(): void
    {
        $this->defaultMapper = \Mockery::mock(DefaultDossierMapper::class);

        $this->mapper = new WooDecisionMapper(
            $this->defaultMapper,
        );

        parent::setUp();
    }

    public function testSupportsReturnsTrueForWooDecision(): void
    {
        self::assertTrue($this->mapper->supports(new WooDecision()));
    }

    public function testSupportsReturnsFalseForCovenant(): void
    {
        self::assertFalse($this->mapper->supports(new Covenant()));
    }

    public function testMap(): void
    {
        $inquiryId = Uuid::v6();
        $caseNr = '123-45';
        $inquiry = \Mockery::mock(Inquiry::class);
        $inquiry->shouldReceive('getId')->andReturn($inquiryId);
        $inquiry->shouldReceive('getCasenr')->andReturn($caseNr);

        $dossierNr = 'test-123';

        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getDossierNr')->andReturn($dossierNr);
        $dossier->shouldReceive('getInquiries')->andReturn(new ArrayCollection([$inquiry]));
        $dossier->shouldReceive('getPublicationReason')->andReturn(PublicationReason::WOO_REQUEST);
        $dossier->shouldReceive('getDecisionDate')->andReturn(new \DateTimeImmutable('2024-04-16 10:54:15'));
        $dossier->shouldReceive('getDecision')->andReturn(DecisionType::PUBLIC);

        $this->defaultMapper
            ->expects('map')
            ->with($dossier)
            ->andReturn(new ElasticDocument($dossierNr, ElasticDocumentType::WOO_DECISION, null, ['foo' => 'bar']));

        $doc = $this->mapper->map($dossier);

        self::assertEquals(
            [
                'foo' => 'bar',
                'publication_reason' => PublicationReason::WOO_REQUEST,
                'decision_date' => '2024-04-16T10:54:15+00:00',
                'decision' => DecisionType::PUBLIC,
                'inquiry_ids' => [
                    $inquiryId,
                ],
                'inquiry_case_nrs' => [
                    $caseNr,
                ],
            ],
            $doc->getFields(),
        );

        self::assertEquals($dossierNr, $doc->getId());
    }
}
