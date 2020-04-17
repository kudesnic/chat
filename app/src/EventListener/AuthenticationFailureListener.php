<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Response\JWTAuthenticationFailureResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Event listener that translates errors for json_login.
 *
 * @package    Authentication
 * @author     Andrew Derevinako <andreyy.derevjanko@gmail.com>
 * @version    1.0
 */
class AuthenticationFailureListener
{
    /**
    * @var RequestStack
    */
    private $requestStack;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * AuthenticationFailureListener constructor.
     * @param RequestStack $requestStack
     * @param TranslatorInterface $translator
     */
    public function __construct(RequestStack $requestStack, TranslatorInterface $translator)
    {
        $this->requestStack = $requestStack;
        $this->translator = $translator;
    }

    /**
     * @param AuthenticationFailureEvent $event
     */
    public function onAuthenticationFailureResponse(AuthenticationFailureEvent $event)
    {
        $message = $this->translator->trans('Bad credentials, please verify that your username/password are correctly set');

        $response = new JWTAuthenticationFailureResponse($message);

        $event->setResponse($response);
    }

}
