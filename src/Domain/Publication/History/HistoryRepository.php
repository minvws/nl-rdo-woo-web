<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\History;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Service\HistoryService;
use Shared\ValueObject\PlainDate;
use SortDirection;

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
     * @return array<array-key, History>
     */
    public function getHistory(string $type, string $identifier, string $mode, ?int $max): array
    {
        $qb = $this->createQueryBuilder('h')
            ->andWhere('h.type = :type')
            ->andWhere('h.identifier = :identifier')
            ->setParameter('type', $type)
            ->setParameter('identifier', $identifier)
            ->orderBy('h.createdDt', SortDirection::Descending)
            // createdDt is a TIMESTAMP(0), so entries from one request tie; ids are time-ordered.
            ->addOrderBy('h.id', SortDirection::Descending);

        if ($mode === HistoryService::MODE_PUBLIC) {
            if ($type == HistoryService::TYPE_DOSSIER) {
                // If we show frontend dossiers, we only have to show entries since publication date
                $qb->leftJoin(AbstractDossier::class, 'd', 'WITH', 'd.id = h.identifier')
                    ->andWhere('h.createdDt >= d.publicationDate');
            }

            if ($type == HistoryService::TYPE_DOCUMENT) {
                $cutOffDate = $this->getPublicHistoryCutOffDate($identifier);
                if ($cutOffDate === null) {
                    return [];
                }

                $qb->andWhere('h.createdDt >= :pubdate')->setParameter('pubdate', $cutOffDate);
            }
        }

        $qb->andWhere('h.site IN (:mode, :both)')->setParameter('mode', $mode)->setParameter('both', HistoryService::MODE_BOTH);

        if ($max !== null) {
            $qb->setMaxResults($max);
        }

        return $qb->getQuery()->getResult();
    }

    private function getPublicHistoryCutOffDate(string $identifier): ?PlainDate
    {
        $document = $this->getEntityManager()->getRepository(Document::class)->find($identifier);
        if ($document === null) {
            return null;
        }

        $cutOffDate = null;

        /** @var WooDecision $dossier */
        foreach ($document->getDossiers() as $dossier) {
            $publicationDate = $dossier->getPublicationDate();
            if ($publicationDate === null || ! $dossier->getStatus()->isPubliclyAvailable()) {
                continue;
            }

            if ($cutOffDate === null || $publicationDate->isBefore($cutOffDate)) {
                $cutOffDate = $publicationDate;
            }
        }

        return $cutOffDate;
    }
}
