<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\ViewModel;

use App\Domain\Publication\Dossier\Type\DossierType as DossierTypeEnum;
use App\Domain\Publication\Dossier\Type\DossierTypeConfigInterface;
use App\Domain\Publication\Dossier\ViewModel\DossierTypeViewFactory;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class DossierTypeViewFactoryTest extends UnitTestCase
{
    private TranslatorInterface&MockInterface $translator;
    private UrlGeneratorInterface&MockInterface $urlGenerator;
    private DossierTypeViewFactory $factory;

    protected function setUp(): void
    {
        $this->translator = \Mockery::mock(TranslatorInterface::class);
        $this->urlGenerator = \Mockery::mock(UrlGeneratorInterface::class);

        $this->factory = new DossierTypeViewFactory($this->translator, $this->urlGenerator);
    }

    public function testMake(): void
    {
        $dossierTypeConfig = $this->createDossierTypeConfig(
            DossierTypeEnum::COVENANT,
            'dossier_create',
            $createUrl = '/dossier/create',
        );

        $got = $this->factory->make($dossierTypeConfig);

        $this->assertSame(DossierTypeEnum::COVENANT->value, $got->type);
        $this->assertSame($createUrl, $got->createUrl);
    }

    public function testMakeCollection(): void
    {
        $input = [
            $this->createDossierTypeConfig(DossierTypeEnum::COVENANT, 'one', $createUrlOne = '/one'),
            $this->createDossierTypeConfig(DossierTypeEnum::ANNUAL_REPORT, 'two', $createUrlTwo = '/two'),
            $this->createDossierTypeConfig(DossierTypeEnum::DISPOSITION, 'three', $createUrlThree = '/three'),
            $this->createDossierTypeConfig(DossierTypeEnum::COMPLAINT_JUDGEMENT, 'four', $createUrlFour = '/four'),
        ];

        $this->translator->shouldReceive('trans')->andReturnArg(0);

        $got = $this->factory->makeCollection($input);

        $this->assertCount(4, $got);
        $this->assertSame(
            [
                DossierTypeEnum::ANNUAL_REPORT->value,
                DossierTypeEnum::COMPLAINT_JUDGEMENT->value,
                DossierTypeEnum::COVENANT->value,
                DossierTypeEnum::DISPOSITION->value,
            ],
            array_map(fn ($dossierType): string => $dossierType->type, $got),
        );

        $this->assertSame([$got[0]->type, $got[0]->createUrl], [DossierTypeEnum::ANNUAL_REPORT->value, $createUrlTwo]);
        $this->assertSame([$got[1]->type, $got[1]->createUrl], [DossierTypeEnum::COMPLAINT_JUDGEMENT->value, $createUrlFour]);
        $this->assertSame([$got[2]->type, $got[2]->createUrl], [DossierTypeEnum::COVENANT->value, $createUrlOne]);
        $this->assertSame([$got[3]->type, $got[3]->createUrl], [DossierTypeEnum::DISPOSITION->value, $createUrlThree]);
    }

    private function createDossierTypeConfig(
        DossierTypeEnum $dossierType,
        string $routeName,
        string $createUrl,
    ): DossierTypeConfigInterface&MockInterface {
        /** @var DossierTypeConfigInterface&MockInterface $dossierTypeConfig */
        $dossierTypeConfig = \Mockery::mock(DossierTypeConfigInterface::class);
        $dossierTypeConfig->shouldReceive('getDossierType')->andReturn($dossierType);
        $dossierTypeConfig->shouldReceive('getCreateRouteName')->andReturn($routeName);

        $this->urlGenerator
            ->shouldReceive('generate')
            ->with($routeName)
            ->andReturn($createUrl);

        return $dossierTypeConfig;
    }
}
