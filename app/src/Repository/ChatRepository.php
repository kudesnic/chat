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
     * Extracts new chats and existing chats with new messages
     *
     * @param User $user
     * @param bool $onlyUpdatedChats
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getNewAndUpdatedChats(User $user, bool $onlyUpdatedChats = true, int $page = 1, int $perPage = 20):array
    {
        $offset = ($page - 1) * $perPage;
        if($onlyUpdatedChats){
            $messageCountComparison = '>';
        } else {
            $messageCountComparison = '=';
        }

        return $this->createQueryBuilder('c')
            ->andWhere('c.unread_messages_count ' . $messageCountComparison . ' 0 AND (c.owner_id = :owner_id OR c.user_id = :user_id)')
            ->leftJoin('c.user', 'u')
            ->leftJoin('c.owner', 'o')
            ->setParameter('owner_id', $user->getId())
            ->setParameter('user_id', $user->getId())
            ->addSelect('c')
            ->addSelect('u')
            ->addSelect('o')
            ->setFirstResult($offset)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }
//
//    /**
//     * Extracts new chats and existing chats with new messages
//     *
//     * @param User $user
//     * @param int $page
//     * @return array
//     */
//    public function getNewAndUpdatedChats(User $user, int $page = 0):array
//    {
//        $perPage = 20;
//        $entityManager = $this->getEntityManager();
//        $offset = $page * $perPage;
//
//        return $entityManager->createQuery(
//            'SELECT c FROM App\Entity\Chat c
//            WHERE c.unread_messages_count > 0 AND (c.owner_id = :owner_id OR c.user_id = :user_id)
//            ORDER BY c.updated DESC')
//            ->leftJoin('p.user', 'u')
//            ->leftJoin('p.owner', 'o')
//            ->setParameter('owner_id', $user->getId())
//            ->setParameter('user_id', $user->getId())
//            ->addSelect('u')
//            ->addSelect('o')
//            ->setFirstResult($offset)
//            ->setMaxResults($perPage)
//            ->getResult();
//    }

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
