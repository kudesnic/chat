<?php

namespace App\Repository;

use App\Entity\Chat;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Chat|null find($id, $lockMode = null, $lockVersion = null)
 * @method Chat|null findOneBy(array $criteria, array $orderBy = null)
 * @method Chat[]    findAll()
 * @method Chat[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ChatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Chat::class);
    }

    /**
     * Finds chat by uuid
     * @param string $uuid
     * @return Chat|null
     */
    public function findChatByUuid(string $uuid):? Chat
    {
        return $this->findOneBy(['uuid' => $uuid]);
    }

    /**
     * @param User $user
     * @return mixed
     */
    public function getNewAndUpdatedChats(User $user):array
    {
        $entityManager = $this->getEntityManager();

        $query = $entityManager->createQuery(
            'SELECT c, m
            FROM chat c
            LEFT JOIN message m ON c.id = m.chat_id
            WHERE c.unread_messages > 0 AND (WHERE owner_id = :owner_id OR WHERE user_id = :user_id)
            ORDER BY updated_at ASC')
            ->setParameter('owner_id', $user->getId())
            ->setParameter('user_id', $user->getId())
            ->getQuery()
            ->getResult();

    }

    // /**
    //  * @return Chat[] Returns an array of Chat objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Chat
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
