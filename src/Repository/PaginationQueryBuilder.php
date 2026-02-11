<?php

declare(strict_types=1);

namespace Shared\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

use function array_key_exists;
use function base64_decode;
use function is_array;
use function json_decode;

readonly class PaginationQueryBuilder
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param class-string<object> $entityClass
     */
    public function getPaginated(string $entityClass, int $itemsPerPage, ?string $cursor): QueryBuilder
    {
        $queryBuilder = $this->entityManager
            ->getRepository($entityClass)
            ->createQueryBuilder('entity');

        if ($cursor !== null) {
            $decodedCursor = json_decode(base64_decode($cursor), true);
            if (is_array($decodedCursor) && array_key_exists('id', $decodedCursor)) {
                $id = $decodedCursor['id'];

                $queryBuilder->andWhere('entity.id > :id')
                    ->setParameter('id', $id);
            }
        }

        return $queryBuilder
            ->orderBy('entity.id', 'ASC')
            ->setMaxResults($itemsPerPage + 1);
    }
}
