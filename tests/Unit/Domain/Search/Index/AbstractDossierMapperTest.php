<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Index;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Search\Index\AbstractDossierMapper;
use App\Entity\Department;
use Doctrine\Common\Collections\ArrayCollection;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Symfony\Component\Uid\Uuid;

class AbstractDossierMapperTest extends MockeryTestCase
{
    private AbstractDossierMapper $mapper;

    public function setUp(): void
    {
        $this->mapper = new AbstractDossierMapper();

        parent::setUp();
    }

    public function testMap(): void
    {
        $departmentAid = Uuid::v6();
        $departmentA = \Mockery::mock(Department::class);
        $departmentA->expects('getName')->andReturn('A');
        $departmentA->expects('getId')->andReturn($departmentAid);

        $departmentBid = Uuid::v6();
        $departmentB = \Mockery::mock(Department::class);
        $departmentB->expects('getName')->andReturn('B');
        $departmentB->expects('getId')->andReturn($departmentBid);

        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getDossierNr')->andReturn('dos-123');
        $dossier->shouldReceive('getTitle')->andReturn('test-title');
        $dossier->shouldReceive('getSummary')->andReturn('test-summary');
        $dossier->shouldReceive('getStatus')->andReturn(DossierStatus::PUBLISHED);
        $dossier->shouldReceive('getDocumentPrefix')->andReturn('foo');
        $dossier->shouldReceive('getDateFrom')->andReturn(new \DateTimeImmutable('2023-04-16 10:54:15'));
        $dossier->shouldReceive('getDateTo')->andReturn(new \DateTimeImmutable('2025-04-16 10:54:15'));
        $dossier->shouldReceive('getPublicationDate')->andReturn(new \DateTimeImmutable('2024-04-16 11:30:22'));
        $dossier->shouldReceive('getDepartments')->andReturn(new ArrayCollection([
            $departmentA,
            $departmentB,
        ]));

        $data = $this->mapper->mapCommonFields($dossier);

        self::assertEquals(
            [
                'dossier_nr' => 'dos-123',
                'title' => 'test-title',
                'status' => DossierStatus::PUBLISHED,
                'summary' => 'test-summary',
                'document_prefix' => 'foo',
                'departments' => [
                    [
                        'id' => $departmentAid,
                        'name' => 'A',
                    ],
                    [
                        'id' => $departmentBid,
                        'name' => 'B',
                    ],
                ],
                'date_from' => '2023-04-16T10:54:15+00:00',
                'date_to' => '2025-04-16T10:54:15+00:00',
                'date_range' => [
                    'gte' => '2023-04-16T10:54:15+00:00',
                    'lte' => '2025-04-16T10:54:15+00:00',
                ],
                'date_period' => 'April 2023 - april 2025',
                'publication_date' => '2024-04-16T11:30:22+00:00',
            ],
            $data,
        );
    }
}
