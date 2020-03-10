<?php

namespace App\Repository;

use App\Entity\User;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends  NestedTreeRepository implements PasswordUpgraderInterface
{

    /**
     * Get nested set children nodes in boundaries of limit and offset
     *
     * @param $node
     * @param array|null $orderBy
     * @param null $limit
     * @param null $offset
     */
    public function findChildrenBy($node, array $orderBy = null, $limit = null, $offset = null)
    {
        $qb = $this->getChildrenQueryBuilder($node, false, key($orderBy), array_shift($orderBy));
        return $qb->setMaxResults($limit)->setFirstResult($offset)->getQuery()->execute();

    }
    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(UserInterface $user, string $newEncodedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newEncodedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    public function generateToken(UserInterface $user, JWTTokenManagerInterface $jWTManager):string
    {
        $token = $$jWTManager->create(['name' => $user->getName(), 'date' => time()]);
        $user->setApiToken($token);
        $this->_em->persist($user);
        $this->_em->flush();
        return $token;
    }
    /**
     * Checks whether email valid or not
     *
     * @param [type] $email
     * @return boolean
     */
    public function isEmailUnique($email):bool
    {
        $count = $this->count(['email' => $email]);
    
        return (bool)$count;
    }
    // /**
    //  * @return Users[] Returns an array of Users objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Users
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
