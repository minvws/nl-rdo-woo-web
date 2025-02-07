<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\ComplaintJudgement\ViewModel;

use App\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgement;
use App\Domain\Publication\Dossier\Type\ComplaintJudgement\ViewModel\ComplaintJudgementViewFactory;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\Type\ViewModel\CommonDossierProperties;
use App\Domain\Publication\Dossier\Type\ViewModel\CommonDossierPropertiesViewFactory;
use App\Domain\Publication\Dossier\Type\ViewModel\Subject;
use App\Domain\Publication\Dossier\ViewModel\Department;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;

final class ComplaintJudgementViewFactoryTest extends UnitTestCase
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
                type: $expectedType = DossierType::COMPLAINT_JUDGEMENT,
                subject: $expectedSubject = \Mockery::mock(Subject::class),
            ));

        /** @var ComplaintJudgement&MockInterface $dossier */
        $dossier = \Mockery::mock(ComplaintJudgement::class);
        $dossier->shouldReceive('getDateFrom')->andReturn($expectedDate = new \DateTimeImmutable());

        $result = (new ComplaintJudgementViewFactory($this->commonDossierViewFactory))->make($dossier);

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
        $this->assertSame($expectedDate, $result->date);
    }
}
