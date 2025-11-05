<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier\Type\WooDecision\Document;

use App\Domain\Publication\Dossier\FileProvider\DossierFileNotFoundException;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentDossierFileProvider;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Service\Security\DossierVoter;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Uid\Uuid;

final class DocumentDossierFileProviderTest extends UnitTestCase
{
    private DocumentRepository&MockInterface $repository;
    private AuthorizationCheckerInterface&MockInterface $authorizationChecker;
    private DocumentDossierFileProvider $provider;

    protected function setUp(): void
    {
        $this->repository = \Mockery::mock(DocumentRepository::class);
        $this->authorizationChecker = \Mockery::mock(AuthorizationCheckerInterface::class);

        $this->provider = new DocumentDossierFileProvider(
            $this->repository,
            $this->authorizationChecker,
        );

        parent::setUp();
    }

    public function testGetEntityForPublicUseThrowsExceptionForDossierTypeMismatch(): void
    {
        $dossier = \Mockery::mock(Covenant::class);
        $dossier->shouldReceive('getId')->andReturn(Uuid::v6());

        $this->expectException(DossierFileNotFoundException::class);

        $this->provider->getEntityForPublicUse($dossier, '');
    }

    public function testGetEntityForPublicUseThrowsExceptionWhenEntityIsNotFound(): void
    {
        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getId')->andReturn(Uuid::v6());

        $idInput = '55ae5de9-55f4-3420-b50b-5cde6e07fc5a';

        $this->repository->expects('findOneByDossierAndId')->with(
            $dossier,
            \Mockery::on(static fn (Uuid $id): bool => $id->toRfc4122() === $idInput),
        )->andReturnNull();

        $this->expectException(DossierFileNotFoundException::class);

        $this->provider->getEntityForPublicUse($dossier, $idInput);
    }

    public function testGetEntityForPublicUseThrowsExceptionWhenEntityShouldNotBeUploaded(): void
    {
        $document = \Mockery::mock(Document::class);
        $document->expects('shouldBeUploaded')->andReturnFalse();

        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getId')->andReturn(Uuid::v6());

        $idInput = '55ae5de9-55f4-3420-b50b-5cde6e07fc5a';

        $this->repository->expects('findOneByDossierAndId')->with(
            $dossier,
            \Mockery::on(static fn (Uuid $id): bool => $id->toRfc4122() === $idInput),
        )->andReturn($document);

        $this->expectException(DossierFileNotFoundException::class);

        $this->provider->getEntityForPublicUse($dossier, $idInput);
    }

    public function testGetEntityForPublicUse(): void
    {
        $document = \Mockery::mock(Document::class);
        $document->expects('shouldBeUploaded')->andReturnTrue();

        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getId')->andReturn(Uuid::v6());

        $idInput = '55ae5de9-55f4-3420-b50b-5cde6e07fc5a';

        $this->repository->expects('findOneByDossierAndId')->with(
            $dossier,
            \Mockery::on(static fn (Uuid $id): bool => $id->toRfc4122() === $idInput),
        )->andReturn($document);

        $this->authorizationChecker->expects('isGranted')->with(DossierVoter::VIEW, $document)->andReturnTrue();

        self::assertSame(
            $document,
            $this->provider->getEntityForPublicUse($dossier, $idInput),
        );
    }

    public function testGetEntityForAdminUse(): void
    {
        $document = \Mockery::mock(Document::class);
        $document->expects('shouldBeUploaded')->andReturnTrue();

        $dossier = \Mockery::mock(WooDecision::class);
        $dossier->shouldReceive('getId')->andReturn(Uuid::v6());

        $idInput = '55ae5de9-55f4-3420-b50b-5cde6e07fc5a';

        $this->repository->expects('findOneByDossierAndId')->with(
            $dossier,
            \Mockery::on(static fn (Uuid $id): bool => $id->toRfc4122() === $idInput),
        )->andReturn($document);

        $this->authorizationChecker->expects('isGranted')->with(DossierVoter::VIEW, $document)->andReturnTrue();

        self::assertSame(
            $document,
            $this->provider->getEntityForAdminUse($dossier, $idInput),
        );
    }
}
