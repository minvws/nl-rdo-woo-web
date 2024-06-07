<?php

declare(strict_types=1);

namespace App\Domain\Publication\Dossier\Type\ComplaintJudgement;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use App\Domain\Publication\MainDocument\Command\CreateMainDocumentCommand;
use App\Domain\Publication\MainDocument\MainDocumentRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

/**
 * @extends ServiceEntityRepository<ComplaintJudgementDocument>
 *
 * @method ComplaintJudgementDocument|null find($id, $lockMode = null, $lockVersion = null)
 * @method ComplaintJudgementDocument|null findOneBy(array $criteria, array $orderBy = null)
 * @method ComplaintJudgementDocument[]    findAll()
 * @method ComplaintJudgementDocument[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ComplaintJudgementDocumentRepository extends ServiceEntityRepository implements MainDocumentRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ComplaintJudgementDocument::class);
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

    public function findOneByDossierId(Uuid $dossierId): ?ComplaintJudgementDocument
    {
        $qb = $this->createQueryBuilder('d')
            ->innerJoin('d.dossier', 'dos')
            ->where('dos.id = :dossierId')
            ->setParameter('dossierId', $dossierId)
        ;

        /** @var ?ComplaintJudgementDocument */
        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findForDossierPrefixAndNr(string $prefix, string $dossierNr): ?ComplaintJudgementDocument
    {
        $qb = $this->createQueryBuilder('d')
            ->innerJoin('d.dossier', 'dos')
            ->where('dos.dossierNr = :dossierNr')
            ->andWhere('dos.documentPrefix = :prefix')
            ->setParameter('dossierNr', $dossierNr)
            ->setParameter('prefix', $prefix);

        /** @var ?ComplaintJudgementDocument */
        return $qb->getQuery()->getOneOrNullResult();
    }

    public function create(AbstractDossier $dossier, CreateMainDocumentCommand $command): AbstractMainDocument
    {
        Assert::isInstanceOf($dossier, ComplaintJudgement::class);

        return new ComplaintJudgementDocument(
            dossier: $dossier,
            formalDate: $command->formalDate,
            type: $command->type,
            language: $command->language,
        );
    }
}
