<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\Disposition;

use App\Domain\Publication\Attachment\AbstractAttachment;
use App\Domain\Publication\Attachment\AttachmentRepositoryInterface;
use App\Domain\Publication\Attachment\Command\CreateAttachmentCommand;
use App\Domain\Publication\Dossier\AbstractDossier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<DispositionAttachment>
 */
class DispositionAttachmentRepository extends ServiceEntityRepository implements AttachmentRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DispositionAttachment::class);
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
     * @return ArrayCollection<array-key, DispositionAttachment>
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

    public function findOneForDossier(Uuid $dossierId, Uuid $id): DispositionAttachment
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.id = :id')
            ->andWhere('dos.id = :dossierId')
            ->innerJoin('a.dossier', 'dos')
            ->setParameter('id', $id)
            ->setParameter('dossierId', $dossierId)
        ;

        /** @var DispositionAttachment */
        return $qb->getQuery()->getSingleResult();
    }

    public function findOneOrNullForDossier(Uuid $dossierId, Uuid $id): ?DispositionAttachment
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.id = :id')
            ->andWhere('dos.id = :dossierId')
            ->innerJoin('a.dossier', 'dos')
            ->setParameter('id', $id)
            ->setParameter('dossierId', $dossierId)
        ;

        /** @var ?DispositionAttachment */
        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findForDossierByPrefixAndNr(string $prefix, string $dossierNr, string $id): ?DispositionAttachment
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.id = :id')
            ->innerJoin('a.dossier', 'dos')
            ->andWhere('dos.dossierNr = :dossierNr')
            ->andWhere('dos.documentPrefix = :prefix')
            ->setParameter('dossierNr', $dossierNr)
            ->setParameter('prefix', $prefix)
            ->setParameter('id', $id);

        /** @var ?DispositionAttachment */
        return $qb->getQuery()->getOneOrNullResult();
    }

    public function create(AbstractDossier $dossier, CreateAttachmentCommand $command): AbstractAttachment
    {
        return new DispositionAttachment(
            dossier: $dossier,
            formalDate: $command->formalDate,
            type: $command->type,
            language: $command->language,
        );
    }
}
