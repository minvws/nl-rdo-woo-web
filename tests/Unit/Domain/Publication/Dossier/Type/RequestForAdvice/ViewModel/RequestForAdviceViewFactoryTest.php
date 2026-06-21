<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type\RequestForAdvice\ViewModel;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdvice;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\ViewModel\RequestForAdviceViewFactory;
use Shared\Domain\Publication\Dossier\ViewModel\CommonDossierProperties;
use Shared\Domain\Publication\Dossier\ViewModel\CommonDossierPropertiesViewFactory;
use Shared\Domain\Publication\Dossier\ViewModel\Department;
use Shared\Domain\Publication\Dossier\ViewModel\Subject;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;
use Shared\ValueObject\PlainDate;

final class RequestForAdviceViewFactoryTest extends UnitTestCase
{
    private CommonDossierPropertiesViewFactory&MockInterface $commonDossierViewFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commonDossierViewFactory = Mockery::mock(CommonDossierPropertiesViewFactory::class);
    }

    public function testMake(): void
    {
        $expectedMainDepartment = Mockery::mock(Department::class);

        $this->commonDossierViewFactory
            ->expects('make')
            ->andReturn(new CommonDossierProperties(
                dossierId: $expectedUuid = 'my uuid',
                dossierNr: $expectedDossierNr = 'my dossier nr',
                documentPrefix: $expectedDocumentPrefix = 'my document prefix',
                isPreview: $expectedIsPreview = true,
                title: $expectedTitle = DossierTitle::create('my title'),
                publicationDate: $publicationDate = PlainDate::today(),
                mainDepartment: $expectedMainDepartment,
                summary: $expectedSummary = 'my summary',
                type: $expectedType = DossierType::REQUEST_FOR_ADVICE,
                subject: $expectedSubject = Mockery::mock(Subject::class),
            ));

        $dossier = Mockery::mock(RequestForAdvice::class);
        $dossier->expects('getDateFrom')->andReturn($expectedDate = PlainDate::today());
        $dossier->expects('getLink')->andReturn($expectedLink = 'http://foo.bar');
        $dossier->expects('getAdvisoryBodies')->andReturn($expectedAdvisoryBodies = ['FooBar']);

        $result = new RequestForAdviceViewFactory($this->commonDossierViewFactory)->make($dossier);

        $this->assertSame($expectedUuid, $result->getDossierId());
        $this->assertSame($expectedDossierNr, $result->getDossierNr());
        $this->assertSame($expectedDocumentPrefix, $result->getDocumentPrefix());
        $this->assertSame($expectedIsPreview, $result->isPreview());
        $this->assertSame($expectedTitle, $result->getTitle());
        $this->assertSame($publicationDate, $result->getPublicationDate());
        $this->assertSame($expectedMainDepartment, $result->getMainDepartment());
        $this->assertSame($expectedSubject, $result->getSubject());
        $this->assertTrue($result->hasSubject());
        $this->assertSame($expectedSummary, $result->getSummary());
        $this->assertSame($expectedType, $result->getType());
        $this->assertSame($expectedDate, $result->date);
        $this->assertSame($expectedLink, $result->link);
        $this->assertSame($expectedAdvisoryBodies, $result->advisoryBodies);
    }
}
