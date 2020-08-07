<?php

namespace App\Repository;

use App\Entity\Chat;
use App\Entity\Message;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Message|null find($id, $lockMode = null, $lockVersion = null)
 * @method Message|null findOneBy(array $criteria, array $orderBy = null)
 * @method Message[]    findAll()
 * @method Message[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * Gets max order for considerable chat entity
     * @param Chat $chat
     * @return int
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getMaxOrderForChat(Chat $chat):int
    {
        return (int) $this->createQueryBuilder('message')
            ->select('MAX(message.ordering)')
            ->andWhere('message.chat_id = :chat_id')
            ->setParameter('chat_id', $chat->getId())
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getChatMessages(Chat $chat, User $user, int $page = 1, int $perPage = 20):array
    {
        $offset = ($page - 1) * $perPage;
        $result = $this->createQueryBuilder('m')
            ->leftJoin('m.user', 'u')
            ->leftJoin('m.chat', 'c')
            ->addSelect('u')
            ->addSelect('c')
            ->andWhere('(m.chat_id = :chat_id) AND (c.owner_id = :user_id OR c.user_id = :user_id) ')
            ->setParameter('chat_id', $chat->getId())
            ->setParameter('user_id', $user->getId())
            ->setFirstResult($offset)
            ->setMaxResults($perPage)
            ->getQuery()
            ->setFetchMode('App\Entity\User', "user", ClassMetadata::FETCH_EAGER)
            ->getArrayResult();

        return $result;
    }

    // /**
    //  * @return Message[] Returns an array of Message objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('m.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Message
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
