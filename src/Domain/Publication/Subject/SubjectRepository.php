<?php

declare(strict_types=1);

namespace App\Domain\Publication\Subject;

use App\Domain\Organisation\Organisation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Subject>
 */
class SubjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subject::class);
    }

    public function save(Subject $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Subject $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getQueryForOrganisation(Organisation $organisation): Query
    {
        return $this->createQueryBuilder('subject')
            ->where('subject.organisation = :organisation')
            ->setParameter('organisation', $organisation)
            ->orderBy('subject.name', 'ASC')
            ->getQuery();
    }

    public function findByOrganisationAndId(Organisation $organisation, Uuid $subjectId): ?Subject
    {
        /** @var ?Subject */
        return $this->createQueryBuilder('subject')
            ->where('subject.organisation = :organisation')
            ->setParameter('organisation', $organisation)
            ->andWhere('subject.id = :subject_id')
            ->setParameter('subject_id', $subjectId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<Subject>
     */
    public function getByOrganisation(Organisation $organisation, int $itemsPerPage, ?string $cursor): array
    {
        $queryBuilder = $this->createQueryBuilder('subject')
            ->where('subject.organisation = :organisation')
            ->setParameter('organisation', $organisation);

        if ($cursor !== null) {
            $decodedCursor = \json_decode(\base64_decode($cursor), true);
            if (\is_array($decodedCursor) && \array_key_exists('id', $decodedCursor)) {
                $id = $decodedCursor['id'];

                $queryBuilder->andWhere('subject.id > :id')
                    ->setParameter('id', $id);
            }
        }

        return $queryBuilder
            ->orderBy('subject.id', 'ASC')
            ->setMaxResults($itemsPerPage + 1)
            ->getQuery()
            ->getResult();
    }
}
