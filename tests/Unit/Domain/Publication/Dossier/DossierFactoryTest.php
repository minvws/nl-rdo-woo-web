<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier;

use App\Domain\Organisation\Organisation;
use App\Domain\Publication\Dossier\DossierFactory;
use App\Domain\Publication\Dossier\Type\Disposition\Disposition;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\Type\DossierTypeConfigInterface;
use App\Domain\Publication\Dossier\Type\DossierTypeManager;
use App\Service\Security\Authorization\AuthorizationMatrix;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;

class DossierFactoryTest extends MockeryTestCase
{
    private AuthorizationMatrix&MockInterface $authorizationMatrix;
    private DossierTypeManager&MockInterface $dossierTypeManager;
    private DossierFactory $factory;

    public function setUp(): void
    {
        $this->dossierTypeManager = \Mockery::mock(DossierTypeManager::class);
        $this->authorizationMatrix = \Mockery::mock(AuthorizationMatrix::class);

        $this->factory = new DossierFactory(
            $this->dossierTypeManager,
            $this->authorizationMatrix,
        );

        parent::setUp();
    }

    public function testCreate(): void
    {
        $organisation = \Mockery::mock(Organisation::class);
        $this->authorizationMatrix->expects('getActiveOrganisation')->andReturn($organisation);

        $type = DossierType::DISPOSITION;

        $config = \Mockery::mock(DossierTypeConfigInterface::class);
        $config->expects('getEntityClass')->andReturn(Disposition::class);

        $this->dossierTypeManager->expects('getConfigWithAccessCheck')->with($type)->andReturn($config);

        $dossier = $this->factory->create($type);

        self::assertInstanceOf(Disposition::class, $dossier);
        self::assertSame($organisation, $dossier->getOrganisation());
    }
}
