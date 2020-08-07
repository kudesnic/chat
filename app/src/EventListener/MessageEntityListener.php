<?php

namespace App\EventListener;

use App\Entity\Message;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;

/**
 * Event listener that increases count of chat unread messages
 *
 * @package    Chat
 * @author     Andrew Derevinako <andreyy.derevjanko@gmail.com>
 * @version    1.0
 */
class MessageEntityListener
{
    /**
     * @param Message $entity
     * @param LifecycleEventArgs $eventArgs
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function prePersist(Message $entity, LifecycleEventArgs $eventArgs)
    {
        $em = $eventArgs->getEntityManager();
        if($entity->getIsRead() == false){
            $chat = $entity->getChat();
            $chat->setUnreadMessagesCount($chat->getUnreadMessagesCount() + 1);
            if(!is_null($entity->getUser())){
                $chat->setUnreadMessagesSender($entity->getUser());
            }
            $em->persist($chat);
            $em->flush($chat);
        }
    }

}
