<?php

declare(strict_types=1);

namespace App\Domain\Publication\History;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Service\HistoryService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<History>
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
                $qb->leftJoin(AbstractDossier::class, 'd', 'WITH', 'd.id = h.identifier')
                    ->andWhere('h.createdDt >= d.publicationDate');
            }

            if ($type == HistoryService::TYPE_DOCUMENT) {
                $document = $this->getEntityManager()->getRepository(Document::class)->find($identifier);
                if ($document) {
                    /** @var WooDecision|null $dossier */
                    $dossier = $document->getDossiers()[0] ?? null;
                    if ($dossier) {
                        // If we show frontend dossiers, we only have to show entries since publication date of the dossier
                        $qb->andWhere('h.createdDt >= :pubdate')
                            ->setParameter('pubdate', $dossier->getPublicationDate() ?? '1970-01-01');
                    }
                }
            }
        }

        $qb->andWhere('h.site IN (:mode, :both)')->setParameter('mode', $mode)->setParameter('both', HistoryService::MODE_BOTH);

        if ($max !== null) {
            $qb->setMaxResults($max);
        }

        return $qb->getQuery()->getResult();
    }
}
