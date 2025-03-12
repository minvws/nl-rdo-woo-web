<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\ViewModel;

use App\Domain\Publication\Dossier\Type\Advice\Advice;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\DossierReference;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\Type\OtherPublication\OtherPublication;
use App\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\Routing\RouterInterface;

final class DossierPathHelperTest extends MockeryTestCase
{
    private RouterInterface&MockInterface $router;
    private string $baseUrl = 'https://foo.bar';
    private DossierPathHelper $pathHelper;

    public function setUp(): void
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
}
