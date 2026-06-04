<?php

declare(strict_types=1);

namespace Shared\Tests\Integration\Domain\Publication\Dossier\Type;

use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use Shared\Domain\Publication\Dossier\Type\AbstractDossierRepository;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;
use Shared\Tests\Factory\OrganisationFactory;
use Shared\Tests\Factory\Publication\Dossier\Type\InvestigationReport\InvestigationReportFactory;
use Shared\Tests\Integration\SharedWebTestCase;

final class AbstractDossierRepositoryTest extends SharedWebTestCase
{
    private AbstractDossierRepository $dossierRepository;

    protected function setUp(): void
    {
        $managerRegistry = self::fromContainer(ManagerRegistry::class);

        $this->dossierRepository = new class($managerRegistry) extends AbstractDossierRepository {
            public function __construct(ManagerRegistry $managerRegistry)
            {
                parent::__construct($managerRegistry, InvestigationReport::class);
            }
        };
    }

    public function testSave(): void
    {
        $organisation = OrganisationFactory::createOne();

        $dossier = InvestigationReportFactory::new()->withoutPersisting()->create(['organisation' => $organisation]);

        $this->dossierRepository->save($dossier, true);

        $result = $this->dossierRepository->findOneByDossierId($dossier->getId());
        self::assertEquals($dossier, $result);
    }

    public function testFindAndRemove(): void
    {
        $dossier = InvestigationReportFactory::createOne();

        $result = $this->dossierRepository->findOneByDossierId($dossier->getId());
        self::assertEquals($dossier->getId(), $result->getId());

        $this->dossierRepository->remove($result, true);

        $this->expectException(NoResultException::class);
        $this->dossierRepository->findOneByDossierId($dossier->getId());
    }
}
