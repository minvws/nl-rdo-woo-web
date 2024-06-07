<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\InvestigationReport;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use App\Domain\Publication\MainDocument\Command\CreateMainDocumentCommand;
use App\Domain\Publication\MainDocument\MainDocumentRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

/**
 * @extends ServiceEntityRepository<InvestigationReportDocument>
 *
 * @method InvestigationReportDocument|null find($id, $lockMode = null, $lockVersion = null)
 * @method InvestigationReportDocument|null findOneBy(array $criteria, array $orderBy = null)
 * @method InvestigationReportDocument[]    findAll()
 * @method InvestigationReportDocument[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InvestigationReportDocumentRepository extends ServiceEntityRepository implements MainDocumentRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InvestigationReportDocument::class);
    }

    public function save(AbstractMainDocument $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AbstractMainDocument $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneByDossierId(Uuid $dossierId): ?InvestigationReportDocument
    {
        $qb = $this->createQueryBuilder('d')
            ->innerJoin('d.dossier', 'dos')
            ->where('dos.id = :dossierId')
            ->setParameter('dossierId', $dossierId)
        ;

        /** @var ?InvestigationReportDocument */
        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findForDossierPrefixAndNr(string $prefix, string $dossierNr): ?InvestigationReportDocument
    {
        $qb = $this->createQueryBuilder('d')
            ->innerJoin('d.dossier', 'dos')
            ->where('dos.dossierNr = :dossierNr')
            ->andWhere('dos.documentPrefix = :prefix')
            ->setParameter('dossierNr', $dossierNr)
            ->setParameter('prefix', $prefix);

        /** @var ?InvestigationReportDocument */
        return $qb->getQuery()->getOneOrNullResult();
    }

    public function create(AbstractDossier $dossier, CreateMainDocumentCommand $command): AbstractMainDocument
    {
        Assert::isInstanceOf($dossier, InvestigationReport::class);

        return new InvestigationReportDocument(
            dossier: $dossier,
            formalDate: $command->formalDate,
            type: $command->type,
            language: $command->language,
        );
    }
}
