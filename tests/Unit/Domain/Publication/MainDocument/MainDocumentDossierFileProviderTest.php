<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\MainDocument;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileNotFoundException;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantMainDocument;
use Shared\Domain\Publication\MainDocument\MainDocumentDossierFileProvider;
use Shared\Domain\Publication\MainDocument\MainDocumentRepository;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

final class MainDocumentDossierFileProviderTest extends UnitTestCase
{
    private MainDocumentRepository&MockInterface $repository;
    private MainDocumentDossierFileProvider $provider;

    protected function setUp(): void
    {
        $this->repository = Mockery::mock(MainDocumentRepository::class);

        $this->provider = new MainDocumentDossierFileProvider(
            $this->repository,
        );

        parent::setUp();
    }

    public function testGetEntityForPublicUseThrowsExceptionWhenEntityIsNotFound(): void
    {
        $dossier = Mockery::mock(Covenant::class);
        $dossier->shouldReceive('getId')->andReturn($dossierId = Uuid::v6());

        $idInput = '55ae5de9-55f4-3420-b50b-5cde6e07fc5a';

        $this->repository->expects('findOneOrNullForDossier')->with(
            $dossierId,
            Mockery::on(fn (Uuid $id): bool => $id->toRfc4122() === $idInput),
        )->andReturnNull();

        $this->expectException(DossierFileNotFoundException::class);

        $this->provider->getEntityForPublicUse($dossier, $idInput);
    }

    public function testGetEntityForPublicUse(): void
    {
        $mainDocument = Mockery::mock(CovenantMainDocument::class);

        $dossier = Mockery::mock(Covenant::class);
        $dossier->shouldReceive('getId')->andReturn($dossierId = Uuid::v6());

        $idInput = '55ae5de9-55f4-3420-b50b-5cde6e07fc5a';

        $this->repository->expects('findOneOrNullForDossier')->with(
            $dossierId,
            Mockery::on(fn (Uuid $id): bool => $id->toRfc4122() === $idInput),
        )->andReturn($mainDocument);

        self::assertSame(
            $mainDocument,
            $this->provider->getEntityForPublicUse($dossier, $idInput),
        );
    }

    public function testGetEntityForAdminUse(): void
    {
        $mainDocument = Mockery::mock(CovenantMainDocument::class);

        $dossier = Mockery::mock(Covenant::class);
        $dossier->shouldReceive('getId')->andReturn($dossierId = Uuid::v6());

        $idInput = '55ae5de9-55f4-3420-b50b-5cde6e07fc5a';

        $this->repository->expects('findOneOrNullForDossier')->with(
            $dossierId,
            Mockery::on(fn (Uuid $id): bool => $id->toRfc4122() === $idInput),
        )->andReturn($mainDocument);

        self::assertSame(
            $mainDocument,
            $this->provider->getEntityForAdminUse($dossier, $idInput),
        );
    }
}
