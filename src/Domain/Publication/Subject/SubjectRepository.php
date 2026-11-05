<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Subject;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Shared\Domain\Organisation\Organisation;
use SortDirection;
use Symfony\Component\Uid\Uuid;

use function array_key_exists;
use function base64_decode;
use function is_array;
use function json_decode;

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
            ->orderBy('subject.name', SortDirection::Ascending)
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

    public function findPublishedLandingPageBySlug(string $slug): Subject
    {
        /** @var Subject */
        return $this->createQueryBuilder('subject')
            ->where('subject.landingPageSlug = :slug')
            ->andWhere('subject.landingPageStatus = :status')
            ->setParameter('slug', $slug)
            ->setParameter('status', SubjectLandingPageStatus::PUBLISHED)
            ->getQuery()
            ->getSingleResult();
    }

    /**
     * @return list<Subject>
     */
    public function findWithPublishedLandingPage(?int $maxResults = null): array
    {
        $queryBuilder = $this->createQueryBuilder('subject')
            ->where('subject.landingPageStatus = :status')
            ->andWhere('subject.landingPageSlug IS NOT NULL')
            ->setParameter('status', SubjectLandingPageStatus::PUBLISHED)
            ->orderBy('subject.name', SortDirection::Ascending);

        if ($maxResults !== null) {
            $queryBuilder->setMaxResults($maxResults);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function getAllSubjectIdsWithLandingPageQuery(): Query
    {
        return $this->createQueryBuilder('subject')
            ->select('subject.id')
            ->where('subject.landingPageSlug IS NOT NULL')
            ->getQuery();
    }

    public function countWithLandingPage(): int
    {
        return (int) $this->createQueryBuilder('subject')
            ->select('COUNT(subject.id)')
            ->where('subject.landingPageSlug IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findConceptLandingPageByIdAndPreviewToken(string $id, string $previewToken): Subject
    {
        /** @var Subject */
        return $this->createQueryBuilder('subject')
            ->where('subject.id = :id')
            ->andWhere('subject.landingPageStatus = :status')
            ->andWhere('subject.landingPagePreviewToken = :preview_token')
            ->andWhere('subject.landingPageContentTree IS NOT NULL')
            ->setParameter('id', $id)
            ->setParameter('status', SubjectLandingPageStatus::CONCEPT)
            ->setParameter('preview_token', $previewToken)
            ->getQuery()
            ->getSingleResult();
    }

    public function isInUse(Subject $subject): bool
    {
        return (bool) $this->createQueryBuilder('subject')
            ->select('1')
            ->join('subject.dossiers', 'dossier')
            ->where('subject = :subject')
            ->setParameter('subject', $subject)
            ->setMaxResults(1)
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
            $decodedCursor = json_decode(base64_decode($cursor), true);
            if (is_array($decodedCursor) && array_key_exists('id', $decodedCursor)) {
                $id = $decodedCursor['id'];

                $queryBuilder->andWhere('subject.id > :id')
                    ->setParameter('id', $id);
            }
        }

        return $queryBuilder
            ->orderBy('subject.id', SortDirection::Ascending)
            ->setMaxResults($itemsPerPage + 1)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Subject>
     */
    public function getSubjectsWithDuplicateName(): array
    {
        $duplicate = $this->getEntityManager()->createQueryBuilder()
            ->select('1')
            ->from(Subject::class, 'duplicate')
            ->where('duplicate.organisation = subject.organisation')
            ->andWhere('duplicate.name = subject.name')
            ->andWhere('duplicate.id != subject.id');

        $queryBuilder = $this->createQueryBuilder('subject');

        return $queryBuilder
            ->join('subject.organisation', 'organisation')
            ->addSelect('organisation')
            ->where($queryBuilder->expr()->exists($duplicate->getDQL()))
            ->orderBy('organisation.name')
            ->addOrderBy('subject.name')
            ->getQuery()
            ->getResult();
    }
}
