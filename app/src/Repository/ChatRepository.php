<?php

namespace App\Repository;

use App\Entity\Chat;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr\Join;
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
     * @param string $uuid
     * @param User $user
     * @return Chat|null
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findChatByUuid(string $uuid, User $user):? Chat
    {
        return $this->createQueryBuilder('c')
            ->andWhere('(c.owner_id = :user_id OR c.user_id = :user_id) AND (c.uuid = :uuid)')
            ->setParameter('user_id', $user->getId())
            ->setParameter('uuid', $uuid)
            ->getQuery()
            ->getSingleResult();
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
            $messageCountCondition = 'c.unread_messages_count > 0 AND ';
        } else {
            $messageCountCondition = '';
        }

        $result = $this->createQueryBuilder('c')
            ->leftJoin('c.unread_messages_sender', 'ums')
            ->leftJoin(
                'c.messages',
                'm',
                Join::WITH,
                'c.id = m.chat_id AND m.ordering = ( SELECT MAX(msg.ordering) FROM App\Entity\Message msg WHERE msg.chat_id = c.id )'
                )
            ->addSelect('ums')
            ->addSelect('m')
            ->andWhere($messageCountCondition .' (c.owner_id = :owner_id OR c.user_id = :user_id) AND (ums.id <> :user_id)')
            ->setParameter('owner_id', $user->getId())
            ->setParameter('user_id', $user->getId())
            ->setFirstResult($offset)
            ->setMaxResults($perPage)
            ->getQuery()
            ->setFetchMode('App\Entity\Chat', "messages", ClassMetadata::FETCH_EAGER)
            ->getArrayResult();

        return $result;
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
