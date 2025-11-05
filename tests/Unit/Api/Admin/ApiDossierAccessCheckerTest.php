<?php

declare(strict_types=1);

namespace App\Tests\Unit\Api\Admin;

use ApiPlatform\Symfony\Security\Exception\AccessDeniedException;
use App\Api\Admin\ApiDossierAccessChecker;
use App\Domain\Publication\Dossier\DossierRepository;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Uid\Uuid;

final class ApiDossierAccessCheckerTest extends UnitTestCase
{
    private DossierRepository&MockInterface $dossierRepository;
    private AuthorizationCheckerInterface&MockInterface $authorizationChecker;
    private ApiDossierAccessChecker $dossierAccessChecker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dossierRepository = \Mockery::mock(DossierRepository::class);
        $this->authorizationChecker = \Mockery::mock(AuthorizationCheckerInterface::class);
        $this->dossierAccessChecker = new ApiDossierAccessChecker(
            $this->dossierRepository,
            $this->authorizationChecker,
        );
    }

    public function testEnsureUserIsAllowedToUpdateDossierThrowsExceptionWhenNotAllowed(): void
    {
        $dossierId = Uuid::v6();
        $dossier = \Mockery::mock(WooDecision::class);

        $this->dossierRepository
            ->expects('findOneByDossierId')
            ->with($dossierId)
            ->andReturn($dossier);

        $this->authorizationChecker
            ->expects('isGranted')
            ->with('AuthMatrix.dossier.update', $dossier)
            ->andReturnFalse();

        $this->expectException(AccessDeniedException::class);
        $this->dossierAccessChecker->ensureUserIsAllowedToUpdateDossier($dossierId);
    }

    public function testEnsureUserIsAllowedToUpdateDossierThrowsNoExceptionWhenAllowed(): void
    {
        $dossierId = Uuid::v6();
        $dossier = \Mockery::mock(WooDecision::class);

        $this->dossierRepository
            ->expects('findOneByDossierId')
            ->with($dossierId)
            ->andReturn($dossier);

        $this->authorizationChecker
            ->expects('isGranted')
            ->with('AuthMatrix.dossier.update', $dossier)
            ->andReturnTrue();

        $this->dossierAccessChecker->ensureUserIsAllowedToUpdateDossier($dossierId);
    }
}
