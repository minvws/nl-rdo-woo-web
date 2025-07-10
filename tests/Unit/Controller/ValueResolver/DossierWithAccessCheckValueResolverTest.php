<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\ValueResolver;

use App\Controller\ValueResolver\DossierWithAccessCheckValueResolver;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Exception\ViewingNotAllowedException;
use App\Service\Security\DossierVoter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class DossierWithAccessCheckValueResolverTest extends MockeryTestCase
{
    private EntityManagerInterface&MockInterface $entityManager;
    private AuthorizationCheckerInterface&MockInterface $authorizationChecker;
    private DossierWithAccessCheckValueResolver $resolver;

    public function setUp(): void
    {
        $this->entityManager = \Mockery::mock(EntityManagerInterface::class);
        $this->authorizationChecker = \Mockery::mock(AuthorizationCheckerInterface::class);

        $this->resolver = new DossierWithAccessCheckValueResolver(
            $this->entityManager,
            $this->authorizationChecker,
        );

        parent::setUp();
    }

    public function testResolverThrowsExceptionForUnsupportedArgumentType(): void
    {
        $request = new Request();
        $argument = \Mockery::mock(ArgumentMetadata::class);
        $argument->shouldReceive('getType')->andReturn(self::class);

        $this->expectException(ViewingNotAllowedException::class);
        $this->resolver->resolve($request, $argument);
    }

    public function testResolverThrowsExceptionForMissingPrefix(): void
    {
        $request = new Request(attributes: ['dossierId' => 'bar']);
        $argument = \Mockery::mock(ArgumentMetadata::class);
        $argument->shouldReceive('getType')->andReturn(Covenant::class);

        $this->expectException(ViewingNotAllowedException::class);
        $this->resolver->resolve($request, $argument);
    }

    public function testResolverThrowsExceptionForMissingDocumentId(): void
    {
        $request = new Request(attributes: ['prefix' => 'foo']);
        $argument = \Mockery::mock(ArgumentMetadata::class);
        $argument->shouldReceive('getType')->andReturn(Covenant::class);

        $this->expectException(ViewingNotAllowedException::class);
        $this->resolver->resolve($request, $argument);
    }

    public function testResolverThrowsExceptionWhenDossierCannotBeFound(): void
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

        $this->expectException(ViewingNotAllowedException::class);
        $this->resolver->resolve($request, $argument);
    }

    public function testResolverThrowsExceptionWhenDossierIsNotAccessible(): void
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

        $this->authorizationChecker->expects('isGranted')->with(DossierVoter::VIEW, $dossier)->andReturnFalse();

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

        $this->authorizationChecker->expects('isGranted')->with(DossierVoter::VIEW, $dossier)->andReturnTrue();

        self::assertEquals(
            [$dossier],
            $this->resolver->resolve($request, $argument),
        );
    }
}
