<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\ViewModel;

use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\Type\Advice\Advice;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\DossierReference;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublication;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdvice;
use Shared\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Routing\RouterInterface;

final class DossierPathHelperTest extends UnitTestCase
{
    private RouterInterface&MockInterface $router;
    private string $baseUrl = 'https://foo.bar';
    private DossierPathHelper $pathHelper;

    protected function setUp(): void
    {
        $this->router = \Mockery::mock(RouterInterface::class);

        $this->pathHelper = new DossierPathHelper(
            $this->router,
            $this->baseUrl,
        );

        parent::setUp();
    }

    public function testGetDetailsPathWithDossierReference(): void
    {
        $reference = new DossierReference(
            'dos-nr',
            'doc-prefix',
            'dos-title',
            DossierType::COVENANT,
        );

        $this->router->expects('generate')->with(
            'app_covenant_detail',
            [
                'prefix' => 'doc-prefix',
                'dossierId' => 'dos-nr',
            ]
        )->andReturn('foo-bar');

        self::assertEquals(
            'foo-bar',
            $this->pathHelper->getDetailsPath($reference),
        );
    }

    public function testGetDetailsPathWithDossier(): void
    {
        $dossier = \Mockery::mock(Covenant::class);
        $dossier->shouldReceive('getDossierNr')->andReturn('dos-nr');
        $dossier->shouldReceive('getDocumentPrefix')->andReturn('dos-prefix');
        $dossier->shouldReceive('getType')->andReturn(DossierType::COVENANT);

        $this->router->expects('generate')->with(
            'app_covenant_detail',
            [
                'prefix' => 'dos-prefix',
                'dossierId' => 'dos-nr',
            ]
        )->andReturn('foo-bar');

        self::assertEquals(
            'foo-bar',
            $this->pathHelper->getDetailsPath($dossier),
        );
    }

    public function testGetAbsoluteDetailsPathWithDossierReference(): void
    {
        $reference = new DossierReference(
            'dos-nr',
            'doc-prefix',
            'dos-title',
            DossierType::COMPLAINT_JUDGEMENT,
        );

        $this->router->expects('generate')->with(
            'app_complaintjudgement_detail',
            [
                'prefix' => 'doc-prefix',
                'dossierId' => 'dos-nr',
            ]
        )->andReturn('/foo-bar');

        self::assertEquals(
            'https://foo.bar/foo-bar',
            $this->pathHelper->getAbsoluteDetailsPath($reference),
        );
    }

    public function testGetDetailsPathWithOtherPublication(): void
    {
        $dossier = \Mockery::mock(OtherPublication::class);
        $dossier->shouldReceive('getDossierNr')->andReturn('dos-nr');
        $dossier->shouldReceive('getDocumentPrefix')->andReturn('dos-prefix');
        $dossier->shouldReceive('getType')->andReturn(DossierType::OTHER_PUBLICATION);

        $this->router->expects('generate')->with(
            'app_otherpublication_detail',
            [
                'prefix' => 'dos-prefix',
                'dossierId' => 'dos-nr',
            ]
        )->andReturn('foo-bar');

        self::assertEquals(
            'foo-bar',
            $this->pathHelper->getDetailsPath($dossier),
        );
    }

    public function testGetDetailsPathWithAdvice(): void
    {
        $dossier = \Mockery::mock(Advice::class);
        $dossier->shouldReceive('getDossierNr')->andReturn('dos-nr');
        $dossier->shouldReceive('getDocumentPrefix')->andReturn('dos-prefix');
        $dossier->shouldReceive('getType')->andReturn(DossierType::ADVICE);

        $this->router->expects('generate')->with(
            'app_advice_detail',
            [
                'prefix' => 'dos-prefix',
                'dossierId' => 'dos-nr',
            ]
        )->andReturn('foo-bar');

        self::assertEquals(
            'foo-bar',
            $this->pathHelper->getDetailsPath($dossier),
        );
    }

    public function testGetDetailsPathWithRequestForAdvice(): void
    {
        $dossier = \Mockery::mock(RequestForAdvice::class);
        $dossier->shouldReceive('getDossierNr')->andReturn('dos-nr');
        $dossier->shouldReceive('getDocumentPrefix')->andReturn('dos-prefix');
        $dossier->shouldReceive('getType')->andReturn(DossierType::REQUEST_FOR_ADVICE);

        $this->router->expects('generate')->with(
            'app_requestforadvice_detail',
            [
                'prefix' => 'dos-prefix',
                'dossierId' => 'dos-nr',
            ]
        )->andReturn('foo-bar');

        self::assertEquals(
            'foo-bar',
            $this->pathHelper->getDetailsPath($dossier),
        );
    }
}
