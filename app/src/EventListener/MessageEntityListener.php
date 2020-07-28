<?php

namespace App\EventListener;

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
    public function prePersist(LifecycleEventArgs $eventArgs)
    {
        $em = $eventArgs->getEntityManager();
        $entity = $eventArgs->getEntity();
        if($entity->getIsRead() == false){
            $chat = $entity->getChat();
            $chat->setUnreadMessagesCount($chat->getUnreadMessagesCount() + 1);
            $em->persist($chat);
        }
    }
}
