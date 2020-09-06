<?php

namespace App\EventListener;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\WebLink\GenericLinkProvider;
use Symfony\Component\WebLink\HttpHeaderSerializer;
use Symfony\Component\WebLink\Link;

/**
 * Event listener that populate login response with data of logged in user
 *
 * @package    Authentication
 * @author     Andrew Derevinako <andreyy.derevjanko@gmail.com>
 * @version    1.0
 */
class AuthenticationSuccessListener
{
    private  $parameterBag;

    /**
     * AuthenticationSuccessListener constructor.
     * @param ParameterBagInterface $parameterBag
     */
    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;

    }

    /**
     * @param AuthenticationSuccessEvent $event
     */
    public function onAuthenticationSuccessResponse(AuthenticationSuccessEvent $event)
    {
        $data = $event->getData();
        $user = $event->getUser();

        $hubUrl = $this->parameterBag->get('mercure_subscribe_url');
        $link = new Link('mercure', $hubUrl);
        $linkProvider = new GenericLinkProvider([$link]);
        $event->getResponse()->headers->set('Link', (new HttpHeaderSerializer())->serialize($linkProvider->getLinks()));
        if (!$user instanceof UserInterface) {
            return;
        }
        $tokenBuilder = (new Builder())
            ->withClaim('mercure', ['subscribe' => ['conversations/' . $user->getEmail()]])
            ->getToken(
                new Sha256(),
                new Key($this->parameterBag->get('mercure_secret_key'))
            );
        $data['mercure_token'] = (string)$tokenBuilder;
        $data['data'] = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
            'telephone' => $user->getTelephone(),
            'img' => $user->getImg(),
            'roles' => $user->getRoles(),
            'status' => $user->getStatus(),
        ];

        $event->setData($data);
    }

}
