<?php

namespace App\Repository;

use App\Entity\Chat;
use App\Entity\Client;
use App\Entity\Participant;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Participant|null find($id, $lockMode = null, $lockVersion = null)
 * @method Participant|null findOneBy(array $criteria, array $orderBy = null)
 * @method Participant[]    findAll()
 * @method Participant[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ParticipantRepository extends ServiceEntityRepository
{
    /**
     * ParticipantRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Participant::class);
    }

    /**
     * @param Chat $chat
     * @param User $user
     * @return Participant
     */
    public function createUserParticipant(Chat $chat, User $user): Participant
    {
        $participant = new Participant();
        $participant->setChat($chat);
        $participant->setUser($user);

        return $participant;
    }

    /**
     * @param Chat $chat
     * @param Client $client
     * @return Participant
     */
    public function createClientParticipant(Chat $chat, Client $client): Participant
    {
        $participant = new Participant();
        $participant->setChat($chat);
        $participant->setClient($client);

        return $participant;
    }
    // /**
    //  * @return Participant[] Returns an array of Participant objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Participant
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
