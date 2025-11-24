<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\Covenant\ViewModel;

use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\Covenant\ViewModel\CovenantViewFactory;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Publication\Dossier\ViewModel\CommonDossierProperties;
use Shared\Domain\Publication\Dossier\ViewModel\CommonDossierPropertiesViewFactory;
use Shared\Domain\Publication\Dossier\ViewModel\Department;
use Shared\Domain\Publication\Dossier\ViewModel\Subject;
use Shared\Tests\Unit\UnitTestCase;

final class CovenantViewFactoryTest extends UnitTestCase
{
    private CommonDossierPropertiesViewFactory&MockInterface $commonDossierViewFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commonDossierViewFactory = \Mockery::mock(CommonDossierPropertiesViewFactory::class);
    }

    public function testMake(): void
    {
        /** @var Department&MockInterface $expectedMainDepartment */
        $expectedMainDepartment = \Mockery::mock(Department::class);

        $this->commonDossierViewFactory
            ->shouldReceive('make')
            ->andReturn(new CommonDossierProperties(
                dossierId: $expectedUuid = 'my uuid',
                dossierNr: $expectedDossierNr = 'my dossier nr',
                documentPrefix: $expectedDocumentPrefix = 'my document prefix',
                isPreview: $expectedIsPreview = true,
                title: $expectedTitle = 'my title',
                pageTitle: $expectedPageTitle = 'my page title',
                publicationDate: $publicationDate = new \DateTimeImmutable(),
                mainDepartment: $expectedMainDepartment = $expectedMainDepartment,
                summary: $expectedSummary = 'my summary',
                type: $expectedType = DossierType::COVENANT,
                subject: $expectedSubject = \Mockery::mock(Subject::class),
            ));

        /** @var Covenant&MockInterface $dossier */
        $dossier = \Mockery::mock(Covenant::class);
        $dossier->shouldReceive('getDateFrom')->andReturn($expectedDateFrom = null);
        $dossier->shouldReceive('getDateTo')->andReturn($expecedDateTo = $this->getRandomDate());
        $dossier->shouldReceive('getPreviousVersionLink')->andReturn($expectedPreviousVersionLink = 'my previous version link');
        $dossier->shouldReceive('getParties')->andReturn($expectedParties = ['part one', 'party rwo']);

        $result = (new CovenantViewFactory($this->commonDossierViewFactory))->make($dossier);

        $this->assertSame($expectedUuid, $result->getDossierId());
        $this->assertSame($expectedDossierNr, $result->getDossierNr());
        $this->assertSame($expectedDocumentPrefix, $result->getDocumentPrefix());
        $this->assertSame($expectedIsPreview, $result->isPreview());
        $this->assertSame($expectedTitle, $result->getTitle());
        $this->assertSame($expectedPageTitle, $result->getPageTitle());
        $this->assertSame($publicationDate, $result->getPublicationDate());
        $this->assertSame($expectedMainDepartment, $result->getMainDepartment());
        $this->assertSame($expectedSubject, $result->getSubject());
        $this->assertTrue($result->hasSubject());
        $this->assertSame($expectedSummary, $result->getSummary());
        $this->assertSame($expectedType, $result->getType());
        $this->assertSame($expectedDateFrom, $result->dateFrom);
        $this->assertSame($expecedDateTo, $result->dateTo);
        $this->assertSame($expectedPreviousVersionLink, $result->previousVersionLink);
        $this->assertSame($expectedParties, $result->parties);
    }

    private function getRandomDate(string $startDate = '-2 years'): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromInterface($this->getFaker()->dateTimeBetween($startDate));
    }
}
