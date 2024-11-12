<?php

declare(strict_types=1);

namespace App\Repository;

use App\Domain\Publication\Attachment\AbstractAttachment;
use App\Domain\Publication\Attachment\AttachmentRepositoryInterface;
use App\Domain\Publication\Attachment\Command\CreateAttachmentCommand;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Entity\DecisionAttachment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<DecisionAttachment>
 */
class DecisionAttachmentRepository extends ServiceEntityRepository implements AttachmentRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DecisionAttachment::class);
    }

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
     * @return ArrayCollection<array-key, DecisionAttachment>
     */
    public function findAllForDossier(Uuid $dossierId): ArrayCollection
    {
        $qb = $this->createQueryBuilder('a')
            ->where('dos.id = :dossierId')
            ->innerJoin('a.dossier', 'dos')
            ->setParameter('dossierId', $dossierId)
        ;

        return new ArrayCollection($qb->getQuery()->getResult());
    }

    public function findOneForDossier(Uuid $dossierId, Uuid $id): DecisionAttachment
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.id = :id')
            ->andWhere('dos.id = :dossierId')
            ->innerJoin('a.dossier', 'dos')
            ->setParameter('id', $id)
            ->setParameter('dossierId', $dossierId)
        ;

        /** @var DecisionAttachment */
        return $qb->getQuery()->getSingleResult();
    }

    public function findOneOrNullForDossier(Uuid $dossierId, Uuid $id): ?DecisionAttachment
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.id = :id')
            ->andWhere('dos.id = :dossierId')
            ->innerJoin('a.dossier', 'dos')
            ->setParameter('id', $id)
            ->setParameter('dossierId', $dossierId)
        ;

        /** @var ?DecisionAttachment */
        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findForDossierByPrefixAndNr(string $prefix, string $dossierNr, string $id): ?DecisionAttachment
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.id = :id')
            ->innerJoin('a.dossier', 'd')
            ->andWhere('d.dossierNr = :dossierNr')
            ->andWhere('d.documentPrefix = :prefix')
            ->setParameter('dossierNr', $dossierNr)
            ->setParameter('prefix', $prefix)
            ->setParameter('id', $id);

        /** @var ?DecisionAttachment */
        return $qb->getQuery()->getOneOrNullResult();
    }

    public function create(AbstractDossier $dossier, CreateAttachmentCommand $command): AbstractAttachment
    {
        return new DecisionAttachment(
            dossier: $dossier,
            formalDate: $command->formalDate,
            type: $command->type,
            language: $command->language,
        );
    }
}
