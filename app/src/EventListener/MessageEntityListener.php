<?php

namespace App\EventListener;

use App\Entity\Message;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;

/**
 * Event listener that increases count of chat unread messages
 *
 * @package    Authentication
 * @author     Andrew Derevinako <andreyy.derevjanko@gmail.com>
 * @version    1.0
 */
class MessageEntityListener
{
    public function prePersist(Message $entity, LifecycleEventArgs $eventArgs)
    {
        $em = $eventArgs->getEntityManager();
        if($entity->getIsRead() == false){
            $chat = $entity->getChat();
            $chat->setUnreadMessagesCount($chat->getUnreadMessagesCount() + 1);
            $em->persist($chat);
            $em->flush($chat);
        }
    }

}
