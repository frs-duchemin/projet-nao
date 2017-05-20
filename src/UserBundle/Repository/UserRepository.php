<?php

namespace UserBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * UserRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserRepository extends EntityRepository
{
    public function findUserByRole($role) {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('u')
            ->from('UserBundle:User', 'u')
            ->where('u.roles LIKE :roles')
            ->setParameter('roles', '%"' . $role . '"%');
        return $qb->getQuery()->getResult();
    }

    public function findUserByRoleUser() {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('u')
            ->from('UserBundle:User', 'u')
            ->Where('u.roles NOT LIKE :roles2')
            ->andWhere('u.roles NOT LIKE :roles3')
            ->andWhere('u.roles NOT LIKE :roles4')
            ->setParameter('roles2', '%"ROLE_USERNAT"%' )
            ->setParameter('roles3', '%"ROLE_MODERATEUR"%' )
            ->setParameter('roles4', '%"ROLE_SUPER_ADMIN"%' );
        return $qb->getQuery()->getResult();
    }
}