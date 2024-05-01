<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Index\WooDecision;

use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Search\Index\AbstractDossierMapper;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Index\WooDecision\WooDecisionMapper;
use App\Entity\Inquiry;
use Doctrine\Common\Collections\ArrayCollection;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Uid\Uuid;

class WooDecisionMapperTest extends MockeryTestCase
{
    private WooDecisionMapper $mapper;
    private AbstractDossierMapper&MockInterface $abstractDossierMapper;

    public function setUp(): void
    {
        $this->abstractDossierMapper = \Mockery::mock(AbstractDossierMapper::class);

        $this->mapper = new WooDecisionMapper($this->abstractDossierMapper);

        parent::setUp();
    }

    public function testMap(): void
    {
        $inquiryId = Uuid::v6();
        $inquiry = \Mockery::mock(Inquiry::class);
        $inquiry->shouldReceive('getId')->andReturn($inquiryId);

        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getInquiries')->andReturn(new ArrayCollection([$inquiry]));
        $dossier->shouldReceive('getPublicationReason')->andReturn('test-reason');
        $dossier->shouldReceive('getDecisionDate')->andReturn(new \DateTimeImmutable('2024-04-16 10:54:15'));
        $dossier->shouldReceive('getDecision')->andReturn('test-decision');

        $this->abstractDossierMapper->expects('mapCommonFields')->with($dossier)->andReturn([
            'foo' => 'bar',
        ]);

        $doc = $this->mapper->map($dossier);

        self::assertEquals(
            [
                'type' => ElasticDocumentType::WOO_DECISION,
                'foo' => 'bar',
                'publication_reason' => 'test-reason',
                'decision_date' => '2024-04-16T10:54:15+00:00',
                'decision' => 'test-decision',
                'inquiry_ids' => [
                    $inquiryId,
                ],
            ],
            $doc->getFieldValues(),
        );
    }
}
