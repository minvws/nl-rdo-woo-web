<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\PostDeploy;

use Doctrine\ORM\EntityManagerInterface;
use Shared\Domain\Department\Department;
use Shared\Domain\Department\DepartmentRepository;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Organisation\OrganisationRepository;
use Shared\Domain\PostDeploy\InitialTenantSetup;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Integration\SharedWebTestCase;

final class InitialTenantSetupTest extends SharedWebTestCase
{
    private OrganisationRepository $organisationRepository;
    private DepartmentRepository $departmentRepository;
    private InitialTenantSetup $initialSetup;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organisationRepository = self::fromContainer(OrganisationRepository::class);
        $this->departmentRepository = self::fromContainer(DepartmentRepository::class);

        $this->initialSetup = new InitialTenantSetup(
            $this->organisationRepository,
            $this->departmentRepository,
        );
    }

    public function testIsNewEnvironmentWhenNoOrganisationExists(): void
    {
        self::assertTrue($this->initialSetup->isNewEnvironment());
    }

    public function testIsNotNewEnvironmentWhenAnOrganisationExists(): void
    {
        OrganisationFactory::createOne();

        self::assertFalse($this->initialSetup->isNewEnvironment());
    }

    public function testExecCreatesInitialOrganisationAndDepartmentOnNewEnvironment(): void
    {
        $organisation = $this->initialSetup->exec();

        self::assertInstanceOf(Organisation::class, $organisation);
        self::assertSame('Placeholder Organisation', $organisation->getName());
        self::assertSame('PLACEHOLDER-ORGANISATION', $organisation->getPrefix()->toString());

        self::assertCount(1, $organisation->getDepartments());
        $department = $organisation->getDepartments()->first();
        self::assertInstanceOf(Department::class, $department);
        self::assertSame('Placeholder Department', $department->getName());
        self::assertSame('placeholder_department', $department->getSlug());

        self::fromContainer(EntityManagerInterface::class)->clear();

        self::assertCount(1, $this->organisationRepository->findAll());
        self::assertCount(1, $this->departmentRepository->findAll());
        self::assertFalse($this->initialSetup->isNewEnvironment());
    }

    public function testExecDoesNothingWhenAnOrganisationAlreadyExists(): void
    {
        OrganisationFactory::createOne();

        self::assertFalse($this->initialSetup->exec());

        self::assertCount(1, $this->organisationRepository->findAll());
        self::assertCount(1, $this->departmentRepository->findAll());
    }
}
