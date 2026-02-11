<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier;

use Mockery;
use Mockery\MockInterface;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierFactory;
use Shared\Domain\Publication\Dossier\Type\Disposition\Disposition;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Publication\Dossier\Type\DossierTypeConfigInterface;
use Shared\Domain\Publication\Dossier\Type\DossierTypeManager;
use Shared\Service\Security\Authorization\AuthorizationMatrix;
use Shared\Tests\Unit\UnitTestCase;

class DossierFactoryTest extends UnitTestCase
{
    private AuthorizationMatrix&MockInterface $authorizationMatrix;
    private DossierTypeManager&MockInterface $dossierTypeManager;
    private DossierFactory $factory;

    protected function setUp(): void
    {
        $this->dossierTypeManager = Mockery::mock(DossierTypeManager::class);
        $this->authorizationMatrix = Mockery::mock(AuthorizationMatrix::class);

        $this->factory = new DossierFactory(
            $this->dossierTypeManager,
            $this->authorizationMatrix,
        );

        parent::setUp();
    }

    public function testCreate(): void
    {
        $organisation = Mockery::mock(Organisation::class);
        $this->authorizationMatrix->expects('getActiveOrganisation')->andReturn($organisation);

        $type = DossierType::DISPOSITION;

        $config = Mockery::mock(DossierTypeConfigInterface::class);
        $config->expects('getEntityClass')->andReturn(Disposition::class);

        $this->dossierTypeManager->expects('getConfigWithAccessCheck')->with($type)->andReturn($config);

        $dossier = $this->factory->create($type);

        self::assertInstanceOf(Disposition::class, $dossier);
        self::assertSame($organisation, $dossier->getOrganisation());
    }
}
