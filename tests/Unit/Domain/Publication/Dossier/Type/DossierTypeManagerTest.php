<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier\Type;

use Mockery\MockInterface;
use Shared\Domain\Publication\Dossier\Type\DossierType;
use Shared\Domain\Publication\Dossier\Type\DossierTypeConfigInterface;
use Shared\Domain\Publication\Dossier\Type\DossierTypeException;
use Shared\Domain\Publication\Dossier\Type\DossierTypeManager;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class DossierTypeManagerTest extends UnitTestCase
{
    private DossierTypeConfigInterface&MockInterface $configWoo;
    private DossierTypeConfigInterface&MockInterface $configCovenant;
    private AuthorizationCheckerInterface&MockInterface $authChecker;

    protected function setUp(): void
    {
        $this->configWoo = \Mockery::mock(DossierTypeConfigInterface::class);
        $this->configWoo->shouldReceive('getDossierType')->andReturn(DossierType::WOO_DECISION);

        $this->configCovenant = \Mockery::mock(DossierTypeConfigInterface::class);
        $this->configCovenant->shouldReceive('getDossierType')->andReturn(DossierType::COVENANT);

        $this->authChecker = \Mockery::mock(AuthorizationCheckerInterface::class);
    }

    public function testGetConfigReturnsCorrectConfigByType(): void
    {
        $manager = new DossierTypeManager($this->authChecker, [$this->configWoo, $this->configCovenant]);

        self::assertSame(
            $this->configCovenant,
            $manager->getConfig(DossierType::COVENANT)
        );
    }

    public function testGetConfigWithAccessCheckReturnsCorrectConfigByTypeWhenAccessIsGranted(): void
    {
        $expressionWoo = new Expression('foo');
        $this->configCovenant->expects('getSecurityExpression')->andReturn($expressionWoo);

        $this->authChecker->expects('isGranted')->with($expressionWoo)->andReturnTrue();

        $manager = new DossierTypeManager($this->authChecker, [$this->configWoo, $this->configCovenant]);

        self::assertSame(
            $this->configCovenant,
            $manager->getConfigWithAccessCheck(DossierType::COVENANT)
        );
    }

    public function testGetConfigThrowsExceptionForUnknownType(): void
    {
        $manager = new DossierTypeManager($this->authChecker, [$this->configWoo]);

        $this->expectExceptionObject(DossierTypeException::forDossierTypeNotAvailable(DossierType::COVENANT));
        $manager->getConfig(DossierType::COVENANT);
    }

    public function testGetConfigThrowsExceptionForTypeWithoutAccess(): void
    {
        $expressionWoo = new Expression('foo');
        $this->configWoo->expects('getSecurityExpression')->andReturn($expressionWoo);
        $this->authChecker->expects('isGranted')->with($expressionWoo)->andReturnFalse();

        $manager = new DossierTypeManager($this->authChecker, [$this->configWoo]);

        $this->expectExceptionObject(DossierTypeException::forAccessDeniedToType(DossierType::WOO_DECISION));
        $manager->getConfigWithAccessCheck(DossierType::WOO_DECISION);
    }

    public function testGetAvailableConfigsReturnsOnlyConfigsWithAccess(): void
    {
        $expressionWoo = new Expression('foo');
        $this->configWoo->expects('getSecurityExpression')->andReturn($expressionWoo);
        $this->authChecker->expects('isGranted')->with($expressionWoo)->andReturnTrue();

        $expressionCovenant = new Expression('bar');
        $this->configCovenant->expects('getSecurityExpression')->andReturn($expressionCovenant);
        $this->authChecker->expects('isGranted')->with($expressionCovenant)->andReturnFalse();

        $manager = new DossierTypeManager($this->authChecker, [$this->configWoo, $this->configCovenant]);

        self::assertEquals(
            [$this->configWoo],
            $manager->getAvailableConfigs(),
        );
    }

    public function testGetAvailableConfigsReturnsConfigWithNullExpression(): void
    {
        $expressionWoo = new Expression('foo');
        $this->configWoo->expects('getSecurityExpression')->andReturn($expressionWoo);
        $this->authChecker->expects('isGranted')->with($expressionWoo)->andReturnTrue();

        $this->configCovenant->expects('getSecurityExpression')->andReturnNull();

        $manager = new DossierTypeManager($this->authChecker, [$this->configWoo, $this->configCovenant]);

        self::assertEquals(
            [$this->configWoo, $this->configCovenant],
            $manager->getAvailableConfigs(),
        );
    }

    public function testCreateDossier(): void
    {
        $manager = new DossierTypeManager($this->authChecker, [$this->configWoo, $this->configCovenant]);

        $this->configWoo->expects('getEntityClass')->andReturn(WooDecision::class);

        self::assertInstanceOf(
            WooDecision::class,
            $manager->createDossier(DossierType::WOO_DECISION),
        );
    }
}
