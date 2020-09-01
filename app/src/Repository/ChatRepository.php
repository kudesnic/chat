<?php

namespace App\Repository;

use App\Entity\Chat;
use App\Entity\Message;
use App\Entity\Participant;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

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
     * Finds chat with last 20 messages
     * @param string $uuid
     * @param User $user
     * @return Chat|null
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findChatByUuidAndUser(string $uuid, User $user):? Chat
    {
        return $this->createQueryBuilder('c')
            ->join('c.participants', 'p')
            ->leftJoin(
                'c.messages',
                'm',
                Join::WITH,
                    '(c.id = m.chat_id) AND ((m.ordering + 20) > ( SELECT MAX(msg.ordering) FROM App\Entity\Message msg WHERE msg.chat_id = c.id ))'
            )
            ->join('m.user', 'message_user')
            ->addSelect('p')
            ->addSelect('m')
            ->addSelect('message_user')
            ->andWhere('p.user = :user AND c.uuid = :uuid')
            ->setParameter('user', $user)
            ->setParameter('uuid', $uuid)
            ->getQuery()
            ->setFetchMode(Message::class, "messages", ClassMetadata::FETCH_EAGER)
            ->getOneOrNullResult();
    }

    /**
     * Check whether user has rights to see a chat data or doesnt. NO ONE can see a private messages!
     *
     * @param Chat $chat
     * @param UserInterface $loggedUser
     * @return bool
     */
    public function hasAccessToChat(Chat $chat, UserInterface $loggedUser): bool
    {
        $canSeeChat = false;
        $userRepo = $this->_em->getRepository(User::class);
        $participantRepo = $this->_em->getRepository(Participant::class);
        if($loggedUser->getId() ==  $chat->getOwner()->getId()){
            $canSeeChat = true;
        } elseif($participantRepo->findOneBy(['chat' => $chat, 'user' => $loggedUser])) {
            $canSeeChat = true;
        } elseif(
            $userRepo->isNodeAChild($loggedUser, $chat->getOwner()) &&
            $chat->getStrategy() == Chat::STRATEGY_EXTERNAL_CHAT
        ) {
            $canSeeChat = true;
        }

        return $canSeeChat;
    }

    /**
     * @param User $user1
     * @param User $user2
     * @return Chat|null
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findPrivateChatByTwoUsers(User $user1, User $user2):? Chat
    {
        return $this->createQueryBuilder('c')
            ->join('c.participants', 'p')
            ->leftJoin(
                'c.messages',
                'm',
                Join::WITH,
                '(c.id = m.chat_id) AND (m.ordering = ( SELECT MAX(msg.ordering) FROM App\Entity\Message msg WHERE msg.chat_id = c.id ))'
            )
            ->join('m.user', 'message_user')
            ->addSelect('p')
            ->addSelect('m')
            ->addSelect('message_user')
            ->andWhere('c.strategy = :strategy AND (p.user = :first_user_id OR p.user = :second_user_id)')
            ->setParameter('first_user_id', $user1)
            ->setParameter('second_user_id', $user2)
            ->setParameter('strategy', Chat::STRATEGY_INTERNAL_CHAT)
            ->getQuery()
            ->setFetchMode(Message::class, "messages", ClassMetadata::FETCH_EAGER)
            ->getOneOrNullResult();
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
    public function getNewAndUpdatedChats(User $user, bool $onlyUpdatedChats = true):array
    {
        return $this->getNewAndUpdatedChatsQueryBuilder($user, $onlyUpdatedChats)
            ->getArrayResult();
    }

    /**
     * @param User $user
     * @param bool $onlyUpdatedChats
     * @return QueryBuilder
     */
    public function getNewAndUpdatedChatsQueryBuilder(User $user, bool $onlyUpdatedChats = true):QueryBuilder
    {
        $qb = $this->createQueryBuilder('c')
            ->join('c.participants', 'p')
            ->leftJoin(
                'c.messages',
                'm',
                Join::WITH,
                'c.id = m.chat_id AND m.ordering = ( SELECT MAX(msg.ordering) FROM App\Entity\Message msg WHERE msg.chat_id = c.id )'
            )
            ->leftJoin(
                'c.participants',
                'other_participants',
                Join::WITH,
                'c.id = other_participants.chat_id AND other_participants.user_id <> :user_id'
            )
            ->join('other_participants.user', 'otherp_user')
            ->addSelect('ums')
            ->addSelect('other_participants')
            ->addSelect('otherp_user')
            ->addSelect('m')
            ->andWhere('p.user_id = :owner_id AND p.unread_messages_count IS NOT NULL')
            ->setParameter('owner_id', $user->getId())
            ->setParameter('user_id', $user->getId());

        return $qb;
    }

    /**
     * @param Query $query
     */
    public function modifyQueryToEager(Query &$query)
    {
         $query->setFetchMode('App\Entity\Chat', "messages", ClassMetadata::FETCH_EAGER)
            ->setFetchMode('App\Entity\Participant', "other_participants", ClassMetadata::FETCH_EAGER)
            ->setFetchMode('App\Entity\User', "otherp_user", ClassMetadata::FETCH_EAGER);
    }
}
