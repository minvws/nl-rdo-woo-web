<?php

declare(strict_types=1);

namespace App\Tests\Unit\ValueResolver;

use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Exception\ViewingNotAllowedException;
use App\Service\DossierService;
use App\ValueResolver\DossierWithAccessCheckValueResolver;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class DossierWithAccessCheckValueResolverTest extends MockeryTestCase
{
    private EntityManagerInterface&MockInterface $entityManager;
    private DossierService&MockInterface $dossierService;
    private DossierWithAccessCheckValueResolver $resolver;

    public function setUp(): void
    {
        $this->entityManager = \Mockery::mock(EntityManagerInterface::class);
        $this->dossierService = \Mockery::mock(DossierService::class);

        $this->resolver = new DossierWithAccessCheckValueResolver(
            $this->entityManager,
            $this->dossierService,
        );

        parent::setUp();
    }

    public function testResolverReturnsEmptyArrayForUnsupportedArgumentType(): void
    {
        $request = new Request();
        $argument = \Mockery::mock(ArgumentMetadata::class);
        $argument->shouldReceive('getType')->andReturn(__CLASS__);

        self::assertEquals(
            [],
            $this->resolver->resolve($request, $argument),
        );
    }

    public function testResolverReturnsEmptyArrayForMissingPrefix(): void
    {
        $request = new Request(attributes: ['dossierId' => 'bar']);
        $argument = \Mockery::mock(ArgumentMetadata::class);
        $argument->shouldReceive('getType')->andReturn(Covenant::class);

        self::assertEquals(
            [],
            $this->resolver->resolve($request, $argument),
        );
    }

    public function testResolverReturnsEmptyArrayForMissingDocumentId(): void
    {
        $request = new Request(attributes: ['prefix' => 'foo']);
        $argument = \Mockery::mock(ArgumentMetadata::class);
        $argument->shouldReceive('getType')->andReturn(Covenant::class);

        self::assertEquals(
            [],
            $this->resolver->resolve($request, $argument),
        );
    }

    public function testResolverReturnsEmptyArrayWhenDossierCannotBeFound(): void
    {
        $request = new Request(attributes: ['prefix' => 'foo', 'dossierId' => 'bar']);
        $argument = \Mockery::mock(ArgumentMetadata::class);
        $argument->shouldReceive('getType')->andReturn(Covenant::class);

        $repository = \Mockery::mock(ServiceEntityRepository::class);
        $repository->expects('findOneBy')->with(
            [
                'documentPrefix' => 'foo',
                'dossierNr' => 'bar',
            ]
        )->andReturnNull();

        $this->entityManager->shouldReceive('getRepository')->with(Covenant::class)->andReturn($repository);

        self::assertEquals(
            [],
            $this->resolver->resolve($request, $argument),
        );
    }

    public function testResolverReturnsEmptyArrayWhenDossierIsNotAccessible(): void
    {
        $request = new Request(attributes: ['prefix' => 'foo', 'dossierId' => 'bar']);
        $argument = \Mockery::mock(ArgumentMetadata::class);
        $argument->shouldReceive('getType')->andReturn(Covenant::class);

        $dossier = \Mockery::mock(Covenant::class);

        $repository = \Mockery::mock(ServiceEntityRepository::class);
        $repository->expects('findOneBy')->with(
            [
                'documentPrefix' => 'foo',
                'dossierNr' => 'bar',
            ]
        )->andReturn($dossier);

        $this->entityManager->shouldReceive('getRepository')->with(Covenant::class)->andReturn($repository);

        $this->dossierService->expects('isViewingAllowed')->with($dossier)->andReturnFalse();

        $this->expectException(ViewingNotAllowedException::class);
        $this->resolver->resolve($request, $argument);
    }

    public function testResolverReturnsDossierWhenFoundAndAccessible(): void
    {
        $request = new Request(attributes: ['prefix' => 'foo', 'dossierId' => 'bar']);
        $argument = \Mockery::mock(ArgumentMetadata::class);
        $argument->shouldReceive('getType')->andReturn(Covenant::class);

        $dossier = \Mockery::mock(Covenant::class);

        $repository = \Mockery::mock(ServiceEntityRepository::class);
        $repository->expects('findOneBy')->with(
            [
                'documentPrefix' => 'foo',
                'dossierNr' => 'bar',
            ]
        )->andReturn($dossier);

        $this->entityManager->shouldReceive('getRepository')->with(Covenant::class)->andReturn($repository);

        $this->dossierService->expects('isViewingAllowed')->with($dossier)->andReturnTrue();

        self::assertEquals(
            [$dossier],
            $this->resolver->resolve($request, $argument),
        );
    }
}
