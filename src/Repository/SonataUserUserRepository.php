<?php

namespace Shared\Repository;

use Shared\Entity\SonataUserUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<SonataUserUser>
 */
class SonataUserUserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SonataUserUser::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof SonataUserUser) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    // public function findOneByUsername(string $username): SonataUserUser
    // {
    //     $q = $this->createQueryBuilder('u')
    //         ->where('u.username = :uname')
    //         ->setParameter('uname', $username)
    //         ->getQuery();
    //     var_dump($q->getOneOrNullResult());
    //     return $q->getOneOrNullResult();
    // }
}
