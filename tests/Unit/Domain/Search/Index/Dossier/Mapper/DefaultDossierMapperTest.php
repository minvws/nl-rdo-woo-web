<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Index\Dossier\Mapper;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\Subject\Subject;
use App\Domain\Search\Index\Dossier\Mapper\DefaultDossierMapper;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Index\ElasticField;
use App\Entity\Department;
use App\Tests\Unit\UnitTestCase;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Uid\Uuid;

class DefaultDossierMapperTest extends UnitTestCase
{
    private DefaultDossierMapper $mapper;

    public function setUp(): void
    {
        $this->mapper = new DefaultDossierMapper();

        parent::setUp();
    }

    public function testSupports(): void
    {
        self::assertTrue(
            $this->mapper->supports(new WooDecision())
        );

        self::assertTrue(
            $this->mapper->supports(new Covenant())
        );
    }

    public function testMap(): void
    {
        $departmentAid = Uuid::v6();
        $departmentA = \Mockery::mock(Department::class);
        $departmentA->expects('getShortTag')->andReturn('F');
        $departmentA->expects('getName')->andReturn('Foo');
        $departmentA->expects('getId')->andReturn($departmentAid);

        $departmentBid = Uuid::v6();
        $departmentB = \Mockery::mock(Department::class);
        $departmentB->expects('getName')->andReturn('Bar');
        $departmentB->expects('getShortTag')->andReturn('B');
        $departmentB->expects('getId')->andReturn($departmentBid);

        $subject = \Mockery::mock(Subject::class);
        $subject->expects('getId')->andReturn($subjectId = Uuid::v6());
        $subject->expects('getName')->andReturn($subjectName = 'dummy subject');

        $dossier = \Mockery::mock(Covenant::class);
        $dossier->shouldReceive('getType')->andReturn(DossierType::COVENANT);
        $dossier->shouldReceive('getDossierNr')->andReturn('dos-123');
        $dossier->shouldReceive('getTitle')->andReturn('test-title');
        $dossier->shouldReceive('getSummary')->andReturn('test-summary');
        $dossier->shouldReceive('getStatus')->andReturn(DossierStatus::PUBLISHED);
        $dossier->shouldReceive('getDocumentPrefix')->andReturn('foo');
        $dossier->shouldReceive('getDateFrom')->andReturn(new \DateTimeImmutable('2023-04-16 10:54:15'));
        $dossier->shouldReceive('getDateTo')->andReturn(new \DateTimeImmutable('2025-04-16 10:54:15'));
        $dossier->shouldReceive('getPublicationDate')->andReturn(new \DateTimeImmutable('2024-04-16 11:30:22'));
        $dossier->shouldReceive('getSubject')->andReturn($subject);
        $dossier->shouldReceive('getDepartments')->andReturn(new ArrayCollection([
            $departmentA,
            $departmentB,
        ]));

        $doc = $this->mapper->map($dossier);

        self::assertEquals(
            [
                'type' => ElasticDocumentType::COVENANT,
                'toplevel_type' => ElasticDocumentType::COVENANT,
                'sublevel_type' => null,
                'dossier_nr' => 'dos-123',
                'title' => 'test-title',
                'status' => DossierStatus::PUBLISHED,
                'summary' => 'test-summary',
                'document_prefix' => 'foo',
                'departments' => [
                    [
                        'id' => $departmentAid,
                        'name' => 'F|Foo',
                    ],
                    [
                        'id' => $departmentBid,
                        'name' => 'B|Bar',
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
                ElasticField::SUBJECT->value => [
                    ElasticField::SUBJECT_ID->value => $subjectId,
                    ElasticField::SUBJECT_NAME->value => $subjectName,
                ],
            ],
            $doc->getDocumentValues(),
        );
    }

    public function testMapWithoutSubject(): void
    {
        $departmentAid = Uuid::v6();
        $departmentA = \Mockery::mock(Department::class);
        $departmentA->expects('getShortTag')->andReturn('F');
        $departmentA->expects('getName')->andReturn('Foo');
        $departmentA->expects('getId')->andReturn($departmentAid);

        $departmentBid = Uuid::v6();
        $departmentB = \Mockery::mock(Department::class);
        $departmentB->expects('getName')->andReturn('Bar');
        $departmentB->expects('getShortTag')->andReturn('B');
        $departmentB->expects('getId')->andReturn($departmentBid);

        $dossier = \Mockery::mock(Covenant::class);
        $dossier->shouldReceive('getType')->andReturn(DossierType::COVENANT);
        $dossier->shouldReceive('getDossierNr')->andReturn('dos-123');
        $dossier->shouldReceive('getTitle')->andReturn('test-title');
        $dossier->shouldReceive('getSummary')->andReturn('test-summary');
        $dossier->shouldReceive('getStatus')->andReturn(DossierStatus::PUBLISHED);
        $dossier->shouldReceive('getDocumentPrefix')->andReturn('foo');
        $dossier->shouldReceive('getDateFrom')->andReturn(new \DateTimeImmutable('2023-04-16 10:54:15'));
        $dossier->shouldReceive('getDateTo')->andReturn(new \DateTimeImmutable('2025-04-16 10:54:15'));
        $dossier->shouldReceive('getPublicationDate')->andReturn(new \DateTimeImmutable('2024-04-16 11:30:22'));
        $dossier->shouldReceive('getSubject')->andReturnNull();
        $dossier->shouldReceive('getDepartments')->andReturn(new ArrayCollection([
            $departmentA,
            $departmentB,
        ]));

        $doc = $this->mapper->map($dossier);

        self::assertEquals(
            [
                'type' => ElasticDocumentType::COVENANT,
                'toplevel_type' => ElasticDocumentType::COVENANT,
                'sublevel_type' => null,
                'dossier_nr' => 'dos-123',
                'title' => 'test-title',
                'status' => DossierStatus::PUBLISHED,
                'summary' => 'test-summary',
                'document_prefix' => 'foo',
                'departments' => [
                    [
                        'id' => $departmentAid,
                        'name' => 'F|Foo',
                    ],
                    [
                        'id' => $departmentBid,
                        'name' => 'B|Bar',
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
                'subject' => null,
            ],
            $doc->getDocumentValues(),
        );
    }
}
