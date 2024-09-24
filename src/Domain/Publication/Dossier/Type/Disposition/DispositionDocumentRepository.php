<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\Disposition;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use App\Domain\Publication\MainDocument\Command\CreateMainDocumentCommand;
use App\Domain\Publication\MainDocument\MainDocumentRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

/**
 * @extends ServiceEntityRepository<DispositionDocument>
 *
 * @method DispositionDocument|null find($id, $lockMode = null, $lockVersion = null)
 * @method DispositionDocument|null findOneBy(array $criteria, array $orderBy = null)
 * @method DispositionDocument[]    findAll()
 * @method DispositionDocument[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DispositionDocumentRepository extends ServiceEntityRepository implements MainDocumentRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DispositionDocument::class);
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

    public function findOneByDossierId(Uuid $dossierId): ?DispositionDocument
    {
        $qb = $this->createQueryBuilder('d')
            ->innerJoin('d.dossier', 'dos')
            ->where('dos.id = :dossierId')
            ->setParameter('dossierId', $dossierId)
        ;

        /** @var ?DispositionDocument */
        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findForDossierByPrefixAndNr(string $prefix, string $dossierNr): ?DispositionDocument
    {
        $qb = $this->createQueryBuilder('d')
            ->innerJoin('d.dossier', 'dos')
            ->where('dos.dossierNr = :dossierNr')
            ->andWhere('dos.documentPrefix = :prefix')
            ->setParameter('dossierNr', $dossierNr)
            ->setParameter('prefix', $prefix);

        /** @var ?DispositionDocument */
        return $qb->getQuery()->getOneOrNullResult();
    }

    public function create(AbstractDossier $dossier, CreateMainDocumentCommand $command): AbstractMainDocument
    {
        Assert::isInstanceOf($dossier, Disposition::class);

        return new DispositionDocument(
            dossier: $dossier,
            formalDate: $command->formalDate,
            type: $command->type,
            language: $command->language,
        );
    }
}
