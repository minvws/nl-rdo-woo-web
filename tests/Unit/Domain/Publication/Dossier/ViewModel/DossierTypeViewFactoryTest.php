<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\ViewModel;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\Type\DossierType as DossierTypeEnum;
use Shared\Domain\Publication\Dossier\Type\DossierTypeConfigInterface;
use Shared\Domain\Publication\Dossier\ViewModel\DossierTypeViewFactory;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function array_map;

final class DossierTypeViewFactoryTest extends UnitTestCase
{
    private TranslatorInterface&MockInterface $translator;
    private UrlGeneratorInterface&MockInterface $urlGenerator;
    private DossierTypeViewFactory $factory;

    protected function setUp(): void
    {
        $this->translator = Mockery::mock(TranslatorInterface::class);
        $this->urlGenerator = Mockery::mock(UrlGeneratorInterface::class);

        $this->factory = new DossierTypeViewFactory($this->translator, $this->urlGenerator);
    }

    public function testMake(): void
    {
        $createUrl = '/dossier/create';

        $dossierTypeConfig = Mockery::mock(DossierTypeConfigInterface::class);
        $dossierTypeConfig->expects('getDossierType')->andReturn(DossierTypeEnum::COVENANT);
        $dossierTypeConfig->expects('getCreateRouteName')->andReturn('dossier_create');

        $this->urlGenerator->expects('generate')->with('dossier_create')->andReturn($createUrl);

        $got = $this->factory->make($dossierTypeConfig);

        $this->assertSame(DossierTypeEnum::COVENANT->value, $got->type);
        $this->assertSame($createUrl, $got->createUrl);
    }

    public function testMakeCollection(): void
    {
        $one = 'one';
        $two = 'two';
        $three = 'three';
        $four = 'four';

        $urlOne = '/one';
        $urlTwo = '/two';
        $urlThree = '/three';
        $urlFour = '/four';

        $dossierTypeConfig1 = Mockery::mock(DossierTypeConfigInterface::class);
        $dossierTypeConfig1->expects('getDossierType')->times(2)->andReturn(DossierTypeEnum::COVENANT);
        $dossierTypeConfig1->expects('getCreateRouteName')->andReturn($one);

        $dossierTypeConfig2 = Mockery::mock(DossierTypeConfigInterface::class);
        $dossierTypeConfig2->expects('getDossierType')->times(2)->andReturn(DossierTypeEnum::ANNUAL_REPORT);
        $dossierTypeConfig2->expects('getCreateRouteName')->andReturn($two);

        $dossierTypeConfig3 = Mockery::mock(DossierTypeConfigInterface::class);
        $dossierTypeConfig3->expects('getDossierType')->times(2)->andReturn(DossierTypeEnum::DISPOSITION);
        $dossierTypeConfig3->expects('getCreateRouteName')->andReturn($three);

        $dossierTypeConfig4 = Mockery::mock(DossierTypeConfigInterface::class);
        $dossierTypeConfig4->expects('getDossierType')->times(2)->andReturn(DossierTypeEnum::COMPLAINT_JUDGEMENT);
        $dossierTypeConfig4->expects('getCreateRouteName')->andReturn($four);

        $this->urlGenerator->expects('generate')->with($one)->andReturn($urlOne);
        $this->urlGenerator->expects('generate')->with($two)->andReturn($urlTwo);
        $this->urlGenerator->expects('generate')->with($three)->andReturn($urlThree);
        $this->urlGenerator->expects('generate')->with($four)->andReturn($urlFour);

        $input = [
            $dossierTypeConfig1,
            $dossierTypeConfig2,
            $dossierTypeConfig3,
            $dossierTypeConfig4,
        ];

        $this->translator->expects('trans')->times(4)->andReturnArg(0);

        $result = $this->factory->makeCollection($input);

        $this->assertCount(4, $result);
        $this->assertSame(
            [
                DossierTypeEnum::ANNUAL_REPORT->value,
                DossierTypeEnum::COMPLAINT_JUDGEMENT->value,
                DossierTypeEnum::COVENANT->value,
                DossierTypeEnum::DISPOSITION->value,
            ],
            array_map(fn ($dossierType): string => $dossierType->type, $result),
        );

        $this->assertSame([$result[0]->type, $result[0]->createUrl], [DossierTypeEnum::ANNUAL_REPORT->value, $urlTwo]);
        $this->assertSame([$result[1]->type, $result[1]->createUrl], [DossierTypeEnum::COMPLAINT_JUDGEMENT->value, $urlFour]);
        $this->assertSame([$result[2]->type, $result[2]->createUrl], [DossierTypeEnum::COVENANT->value, $urlOne]);
        $this->assertSame([$result[3]->type, $result[3]->createUrl], [DossierTypeEnum::DISPOSITION->value, $urlThree]);
    }
}
