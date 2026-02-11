<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Attachment;

use Doctrine\ORM\NoResultException;
use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Publication\Attachment\AttachmentDossierFileProvider;
use Shared\Domain\Publication\Attachment\Repository\AttachmentRepository;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileNotFoundException;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

final class AttachmentDossierFileProviderTest extends UnitTestCase
{
    private AttachmentRepository&MockInterface $repository;
    private AttachmentDossierFileProvider $provider;

    protected function setUp(): void
    {
        $this->repository = Mockery::mock(AttachmentRepository::class);

        $this->provider = new AttachmentDossierFileProvider(
            $this->repository,
        );

        parent::setUp();
    }

    public function testGetEntityForPublicUseThrowsExceptionWhenEntityIsNotFound(): void
    {
        $dossier = Mockery::mock(Covenant::class);
        $dossier->shouldReceive('getId')->andReturn($dossierId = Uuid::v6());

        $idInput = '55ae5de9-55f4-3420-b50b-5cde6e07fc5a';

        $this->repository->expects('findOneForDossier')->with(
            $dossierId,
            Mockery::on(fn (Uuid $id): bool => $id->toRfc4122() === $idInput),
        )->andThrow(NoResultException::class);

        $this->expectException(DossierFileNotFoundException::class);

        $this->provider->getEntityForPublicUse($dossier, $idInput);
    }

    public function testGetEntityForPublicUse(): void
    {
        $attachment = Mockery::mock(CovenantAttachment::class);

        $dossier = Mockery::mock(Covenant::class);
        $dossier->shouldReceive('getId')->andReturn($dossierId = Uuid::v6());

        $idInput = '55ae5de9-55f4-3420-b50b-5cde6e07fc5a';

        $this->repository->expects('findOneForDossier')->with(
            $dossierId,
            Mockery::on(fn (Uuid $id): bool => $id->toRfc4122() === $idInput),
        )->andReturn($attachment);

        self::assertSame(
            $attachment,
            $this->provider->getEntityForPublicUse($dossier, $idInput),
        );
    }

    public function testGetEntityForAdminUse(): void
    {
        $attachment = Mockery::mock(CovenantAttachment::class);

        $dossier = Mockery::mock(Covenant::class);
        $dossier->shouldReceive('getId')->andReturn($dossierId = Uuid::v6());

        $idInput = '55ae5de9-55f4-3420-b50b-5cde6e07fc5a';

        $this->repository->expects('findOneForDossier')->with(
            $dossierId,
            Mockery::on(fn (Uuid $id): bool => $id->toRfc4122() === $idInput),
        )->andReturn($attachment);

        self::assertSame(
            $attachment,
            $this->provider->getEntityForAdminUse($dossier, $idInput),
        );
    }
}
