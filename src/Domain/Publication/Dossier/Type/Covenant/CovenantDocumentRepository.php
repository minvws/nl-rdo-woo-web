<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\Covenant;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use App\Domain\Publication\MainDocument\Command\CreateMainDocumentCommand;
use App\Domain\Publication\MainDocument\MainDocumentRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

/**
 * @extends ServiceEntityRepository<CovenantDocument>
 *
 * @method CovenantDocument|null find($id, $lockMode = null, $lockVersion = null)
 * @method CovenantDocument|null findOneBy(array $criteria, array $orderBy = null)
 * @method CovenantDocument[]    findAll()
 * @method CovenantDocument[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CovenantDocumentRepository extends ServiceEntityRepository implements MainDocumentRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CovenantDocument::class);
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

    public function findOneByDossierId(Uuid $dossierId): ?CovenantDocument
    {
        $qb = $this->createQueryBuilder('d')
            ->innerJoin('d.dossier', 'dos')
            ->where('dos.id = :dossierId')
            ->setParameter('dossierId', $dossierId)
        ;

        /** @var ?CovenantDocument */
        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findForDossierByPrefixAndNr(string $prefix, string $dossierNr): ?CovenantDocument
    {
        $qb = $this->createQueryBuilder('d')
            ->innerJoin('d.dossier', 'dos')
            ->where('dos.dossierNr = :dossierNr')
            ->andWhere('dos.documentPrefix = :prefix')
            ->setParameter('dossierNr', $dossierNr)
            ->setParameter('prefix', $prefix);

        /** @var ?CovenantDocument */
        return $qb->getQuery()->getOneOrNullResult();
    }

    public function create(AbstractDossier $dossier, CreateMainDocumentCommand $command): AbstractMainDocument
    {
        Assert::isInstanceOf($dossier, Covenant::class);

        return new CovenantDocument(
            dossier: $dossier,
            formalDate: $command->formalDate,
            language: $command->language,
        );
    }
}
