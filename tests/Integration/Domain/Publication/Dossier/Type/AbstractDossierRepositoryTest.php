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
    /**
     * @return AbstractDossierRepository<InvestigationReport>
     */
    private function getRepository(): AbstractDossierRepository
    {
        $managerRegistry = self::getContainer()->get(ManagerRegistry::class);

        return new
        /** @extends AbstractDossierRepository<InvestigationReport> */
        class($managerRegistry) extends AbstractDossierRepository {
            public function __construct(ManagerRegistry $managerRegistry)
            {
                parent::__construct($managerRegistry, InvestigationReport::class);
            }
        };
    }

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
    }

    public function testSave(): void
    {
        $organisation = OrganisationFactory::createOne();

        /** @var InvestigationReport $dossier */
        $dossier = InvestigationReportFactory::new()->withoutPersisting()->create(['organisation' => $organisation])->_real();

        $repository = $this->getRepository();
        $repository->save($dossier, true);

        $result = $this->getRepository()->findOneByDossierId($dossier->getId());
        self::assertEquals($dossier, $result);
    }

    public function testFindAndRemove(): void
    {
        $dossier = InvestigationReportFactory::createOne();

        $repository = $this->getRepository();

        $result = $this->getRepository()->findOneByDossierId($dossier->getId());
        self::assertEquals($dossier->getId(), $result->getId());

        $repository->remove($result, true);

        $this->expectException(NoResultException::class);
        $this->getRepository()->findOneByDossierId($dossier->getId());
    }
}
