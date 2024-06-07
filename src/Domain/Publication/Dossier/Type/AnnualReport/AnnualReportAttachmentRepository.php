<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\AnnualReport;

use App\Domain\Publication\Attachment\AbstractAttachment;
use App\Domain\Publication\Attachment\AttachmentRepositoryInterface;
use App\Domain\Publication\Attachment\Command\CreateAttachmentCommand;
use App\Domain\Publication\Dossier\AbstractDossier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<AnnualReportAttachment>
 *
 * @method AnnualReportAttachment|null find($id, $lockMode = null, $lockVersion = null)
 * @method AnnualReportAttachment|null findOneBy(array $criteria, array $orderBy = null)
 * @method AnnualReportAttachment[]    findAll()
 * @method AnnualReportAttachment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AnnualReportAttachmentRepository extends ServiceEntityRepository implements AttachmentRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnnualReportAttachment::class);
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
     * @return ArrayCollection<array-key,AnnualReportAttachment>
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

    public function findOneForDossier(Uuid $dossierId, Uuid $id): AnnualReportAttachment
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.id = :id')
            ->andWhere('dos.id = :dossierId')
            ->innerJoin('a.dossier', 'dos')
            ->setParameter('id', $id)
            ->setParameter('dossierId', $dossierId)
        ;

        /** @var AnnualReportAttachment */
        return $qb->getQuery()->getSingleResult();
    }

    public function findOneOrNullForDossier(Uuid $dossierId, Uuid $id): ?AnnualReportAttachment
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.id = :id')
            ->andWhere('dos.id = :dossierId')
            ->innerJoin('a.dossier', 'dos')
            ->setParameter('id', $id)
            ->setParameter('dossierId', $dossierId)
        ;

        /** @var ?AnnualReportAttachment */
        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findForDossierPrefixAndNr(string $prefix, string $dossierNr, string $id): ?AnnualReportAttachment
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.id = :id')
            ->innerJoin('a.dossier', 'dos')
            ->andWhere('dos.dossierNr = :dossierNr')
            ->andWhere('dos.documentPrefix = :prefix')
            ->setParameter('dossierNr', $dossierNr)
            ->setParameter('prefix', $prefix)
            ->setParameter('id', $id);

        /** @var ?AnnualReportAttachment */
        return $qb->getQuery()->getOneOrNullResult();
    }

    public function create(AbstractDossier $dossier, CreateAttachmentCommand $command): AbstractAttachment
    {
        return new AnnualReportAttachment(
            dossier: $dossier,
            formalDate: $command->formalDate,
            type: $command->type,
            language: $command->language,
        );
    }
}
