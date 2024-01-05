<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Dossier;
use App\Entity\History;
use App\Service\HistoryService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<History>
 *
 * @method History|null find($id, $lockMode = null, $lockVersion = null)
 * @method History|null findOneBy(array $criteria, array $orderBy = null)
 * @method History[]    findAll()
 * @method History[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, History::class);
    }

    /**
     * @return array<History>
     */
    public function getHistory(string $type, string $identifier, string $mode, ?int $max): array
    {
        $qb = $this->createQueryBuilder('h')
            ->andWhere('h.type = :type')
            ->andWhere('h.identifier = :identifier')
            ->setParameter('type', $type)
            ->setParameter('identifier', $identifier)
            ->orderBy('h.createdDt', 'DESC')
        ;

        if ($mode === HistoryService::MODE_PUBLIC) {
            if ($type == HistoryService::TYPE_DOSSIER) {
                // If we show frontend dossiers, we only have to show entries since publication date
                $qb->leftJoin(Dossier::class, 'd', 'WITH', 'd.id = h.identifier')
                    ->andWhere('h.createdDt >= d.publicationDate');
            }
        }

        $qb->andWhere('h.site IN (:mode, :both)')->setParameter('mode', $mode)->setParameter('both', HistoryService::MODE_BOTH);

        if ($max !== null) {
            $qb->setMaxResults($max);
        }

        return $qb->getQuery()->getResult();
    }
}
