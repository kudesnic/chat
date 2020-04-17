<?php

namespace App\EventListener;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTDecodedEvent;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Event listener that processes onJWTDecoded event and checks whether user active or not
 *
 * @package    Authentication
 * @author     Andrew Derevinako <andreyy.derevjanko@gmail.com>
 * @version    1.0
 */
class JWTDecodedListener
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @param JWTDecodedEvent $event
     *
     * @return void
     */
    public function onJWTDecoded(JWTDecodedEvent $event)
    {
        $request = $this->requestStack->getCurrentRequest();
        $payload = $event->getPayload();

        if (
            $request->attributes->get('_firewall_context') == 'security.firewall.map.context.api'
            && $payload['status'] != User::STATUS_ACTIVE
        ) {
            $event->markAsInvalid();
        }
    }
}
