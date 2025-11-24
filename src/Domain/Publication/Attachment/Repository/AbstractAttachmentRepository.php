<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Attachment\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Shared\Domain\Publication\Attachment\Command\CreateAttachmentCommand;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Symfony\Component\Uid\Uuid;

/**
 * @template T of AbstractAttachment
 *
 * @extends ServiceEntityRepository<T>
 */
abstract class AbstractAttachmentRepository extends ServiceEntityRepository
{
    public function save(AbstractAttachment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AbstractAttachment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return ArrayCollection<array-key,covariant T>
     */
    public function findAllForDossier(Uuid $dossierId): ArrayCollection
    {
        $qb = $this->createQueryBuilder('a')
            ->where('dos.id = :dossierId')
            ->andWhere('a.withdrawn = false')
            ->innerJoin('a.dossier', 'dos')
            ->setParameter('dossierId', $dossierId)
        ;

        /** @var ArrayCollection<array-key,covariant T> */
        return new ArrayCollection($qb->getQuery()->getResult());
    }

    public function findOneForDossier(Uuid $dossierId, Uuid $id): AbstractAttachment
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.id = :id')
            ->andWhere('dos.id = :dossierId')
            ->innerJoin('a.dossier', 'dos')
            ->setParameter('id', $id)
            ->setParameter('dossierId', $dossierId)
        ;

        /** @var AbstractAttachment */
        return $qb->getQuery()->getSingleResult();
    }

    public function findOneOrNullForDossier(Uuid $dossierId, Uuid $id): ?AbstractAttachment
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.id = :id')
            ->andWhere('dos.id = :dossierId')
            ->innerJoin('a.dossier', 'dos')
            ->setParameter('id', $id)
            ->setParameter('dossierId', $dossierId)
        ;

        /** @var ?AbstractAttachment */
        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findForDossierByPrefixAndNr(string $prefix, string $dossierNr, string $id): ?AbstractAttachment
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.id = :id')
            ->innerJoin('a.dossier', 'dos')
            ->andWhere('dos.dossierNr = :dossierNr')
            ->andWhere('dos.documentPrefix = :prefix')
            ->setParameter('dossierNr', $dossierNr)
            ->setParameter('prefix', $prefix)
            ->setParameter('id', $id);

        /** @var ?AbstractAttachment */
        return $qb->getQuery()->getOneOrNullResult();
    }

    public function create(AbstractDossier $dossier, CreateAttachmentCommand $command): AbstractAttachment
    {
        /** @var AbstractAttachment */
        return new ($this->getEntityName())(
            dossier: $dossier,
            formalDate: $command->formalDate,
            type: $command->type,
            language: $command->language,
        );
    }
}
