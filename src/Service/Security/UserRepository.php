<?php

declare(strict_types=1);

namespace Shared\Service\Security;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Shared\Domain\Organisation\Organisation;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function save(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (! $user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);

        $this->save($user, true);
    }

    public function findActiveUsersForOrganisationQuery(Organisation $organisation): Query
    {
        $qb = $this->createQueryBuilder('u');
        $qb->join('u.organisation', 'o')
            ->andWhere('o.id = :val')
            ->andWhere('u.enabled = true')
            ->andWhere($this->oneOfGivenRolesExpression($qb, [
                Roles::ROLE_VIEW_ACCESS,
                Roles::ROLE_DOSSIER_ADMIN,
                Roles::ROLE_ORGANISATION_ADMIN,
            ]))
            ->setParameter('val', $organisation->getId())
            ->orderBy('u.id', 'ASC');

        return $qb->getQuery();
    }

    public function findDeactivatedUsersForOrganisationQuery(Organisation $organisation): Query
    {
        $qb = $this->createQueryBuilder('u');
        $qb->join('u.organisation', 'o')
            ->andWhere('o.id = :val')
            ->andWhere('u.enabled = false')
            ->andWhere($this->oneOfGivenRolesExpression($qb, [
                Roles::ROLE_VIEW_ACCESS,
                Roles::ROLE_DOSSIER_ADMIN,
                Roles::ROLE_ORGANISATION_ADMIN,
            ]))
            ->setParameter('val', $organisation->getId())
            ->orderBy('u.id', 'ASC');

        return $qb->getQuery();
    }

    public function findActiveAdminsQuery(): Query
    {
        $qb = $this->createQueryBuilder('u');
        $qb->join('u.organisation', 'o')
            ->andWhere('u.enabled = true')
            ->andWhere($this->oneOfGivenRolesExpression($qb, [
                Roles::ROLE_SUPER_ADMIN,
            ]))
            ->orderBy('u.id', 'ASC');

        return $qb->getQuery();
    }

    public function findDeactivatedAdminsQuery(): Query
    {
        $qb = $this->createQueryBuilder('u');
        $qb->join('u.organisation', 'o')
            ->andWhere('u.enabled = false')
            ->andWhere($this->oneOfGivenRolesExpression($qb, [
                Roles::ROLE_SUPER_ADMIN,
            ]))
            ->orderBy('u.id', 'ASC');

        return $qb->getQuery();
    }

    /**
     * @param string[] $roles
     */
    private function oneOfGivenRolesExpression(QueryBuilder $queryBuilder, array $roles): Orx
    {
        $conditions = [];
        foreach ($roles as $role) {
            $conditions[] = sprintf('CONTAINS(u.roles, \'"%s"\') = true', $role);
        }

        return $queryBuilder->expr()->orX(...$conditions);
    }
}
