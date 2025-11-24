<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\FileProvider;

use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileProviderException;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileProviderInterface;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileProviderManager;
use Shared\Domain\Publication\Dossier\FileProvider\DossierFileType;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use Shared\Tests\Unit\UnitTestCase;

class DossierFileProviderManagerTest extends UnitTestCase
{
    private DossierFileProviderInterface&MockInterface $providerA;
    private DossierFileProviderInterface&MockInterface $providerB;
    private DossierFileProviderManager $manager;

    protected function setUp(): void
    {
        $this->providerA = \Mockery::mock(DossierFileProviderInterface::class);
        $this->providerB = \Mockery::mock(DossierFileProviderInterface::class);

        $this->manager = new DossierFileProviderManager([
            $this->providerA,
            $this->providerB,
        ]);

        parent::setUp();
    }

    public function testGetEntityForPublicUseThrowsExceptionWhenNoProviderIsAvailable(): void
    {
        $dossier = \Mockery::mock(Covenant::class);
        $id = 'foo-123';

        $this->providerA->expects('getType')->andReturn(DossierFileType::MAIN_DOCUMENT);
        $this->providerB->expects('getType')->andReturn(DossierFileType::DOCUMENT);

        $this->expectException(DossierFileProviderException::class);

        $this->manager->getEntityForPublicUse(DossierFileType::ATTACHMENT, $dossier, $id);
    }

    public function testGetEntityForPublicUseUsesMatchingProvider(): void
    {
        $dossier = \Mockery::mock(Covenant::class);
        $id = 'foo-123';

        $entity = \Mockery::mock(CovenantAttachment::class);

        $this->providerA->expects('getType')->andReturn(DossierFileType::MAIN_DOCUMENT);
        $this->providerB->expects('getType')->andReturn(DossierFileType::ATTACHMENT);
        $this->providerB->expects('getEntityForPublicUse')->with($dossier, $id)->andReturn($entity);

        self::assertSame(
            $entity,
            $this->manager->getEntityForPublicUse(DossierFileType::ATTACHMENT, $dossier, $id),
        );
    }

    public function testGetEntityForAdminUse(): void
    {
        $dossier = \Mockery::mock(Covenant::class);
        $id = 'foo-123';

        $entity = \Mockery::mock(CovenantAttachment::class);

        $this->providerA->expects('getType')->andReturn(DossierFileType::MAIN_DOCUMENT);
        $this->providerB->expects('getType')->andReturn(DossierFileType::ATTACHMENT);
        $this->providerB->expects('getEntityForAdminUse')->with($dossier, $id)->andReturn($entity);

        self::assertSame(
            $entity,
            $this->manager->getEntityForAdminUse(DossierFileType::ATTACHMENT, $dossier, $id),
        );
    }
}
