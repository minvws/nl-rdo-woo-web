<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Organisation;
use App\Entity\User;
use App\Roles;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
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
            ->andWhere($qb->expr()->orX(
                "CONTAINS(u.roles, '\"" . Roles::ROLE_VIEW_ACCESS . "\"') = true",
                "CONTAINS(u.roles, '\"" . Roles::ROLE_DOSSIER_ADMIN . "\"') = true",
                "CONTAINS(u.roles, '\"" . Roles::ROLE_ORGANISATION_ADMIN . "\"') = true",
            ))
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
            ->andWhere($qb->expr()->orX(
                "CONTAINS(u.roles, '\"" . Roles::ROLE_VIEW_ACCESS . "\"') = true",
                "CONTAINS(u.roles, '\"" . Roles::ROLE_DOSSIER_ADMIN . "\"') = true",
                "CONTAINS(u.roles, '\"" . Roles::ROLE_ORGANISATION_ADMIN . "\"') = true",
            ))
            ->setParameter('val', $organisation->getId())
            ->orderBy('u.id', 'ASC');

        return $qb->getQuery();
    }

    public function findActiveAdminsQuery(): Query
    {
        $qb = $this->createQueryBuilder('u');
        $qb->join('u.organisation', 'o')
            ->andWhere('u.enabled = true')
            ->andWhere($qb->expr()->orX(
                "CONTAINS(u.roles, '\"" . Roles::ROLE_SUPER_ADMIN . "\"') = true",
                "CONTAINS(u.roles, '\"" . Roles::ROLE_GLOBAL_ADMIN . "\"') = true",
            ))
            ->orderBy('u.id', 'ASC');

        return $qb->getQuery();
    }

    public function findDeactivatedAdminsQuery(): Query
    {
        $qb = $this->createQueryBuilder('u');
        $qb->join('u.organisation', 'o')
            ->andWhere('u.enabled = false')
            ->andWhere($qb->expr()->orX(
                "CONTAINS(u.roles, '\"" . Roles::ROLE_SUPER_ADMIN . "\"') = true",
                "CONTAINS(u.roles, '\"" . Roles::ROLE_GLOBAL_ADMIN . "\"') = true",
            ))
            ->orderBy('u.id', 'ASC');

        return $qb->getQuery();
    }
}
