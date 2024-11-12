<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use App\Domain\Publication\MainDocument\Command\CreateMainDocumentCommand;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Component\Uid\Uuid;

/**
 * @template T of AbstractMainDocument
 *
 * @extends ServiceEntityRepository<T>
 */
abstract class AbstractMainDocumentRepository extends ServiceEntityRepository
{
    /**
     * @param T $entity
     */
    public function save(AbstractMainDocument $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param T $entity
     */
    public function remove(AbstractMainDocument $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return ?T
     */
    public function findOneByDossierId(Uuid $dossierId): ?AbstractMainDocument
    {
        $qb = $this->createQueryBuilder('d')
            ->innerJoin('d.dossier', 'dos')
            ->where('dos.id = :dossierId')
            ->setParameter('dossierId', $dossierId)
        ;

        /** @var ?T */
        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @return ?T
     */
    public function findForDossierByPrefixAndNr(string $prefix, string $dossierNr): ?AbstractMainDocument
    {
        $qb = $this->createQueryBuilder('d')
            ->innerJoin('d.dossier', 'dos')
            ->where('dos.dossierNr = :dossierNr')
            ->andWhere('dos.documentPrefix = :prefix')
            ->setParameter('dossierNr', $dossierNr)
            ->setParameter('prefix', $prefix);

        /** @var ?T */
        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @return T
     */
    public function create(AbstractDossier $dossier, CreateMainDocumentCommand $command): AbstractMainDocument
    {
        /** @var T */
        return new ($this->getEntityName())(
            dossier: $dossier,
            formalDate: $command->formalDate,
            type: $command->type,
            language: $command->language,
        );
    }
}
