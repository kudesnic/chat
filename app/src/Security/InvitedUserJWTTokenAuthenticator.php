<?php

namespace App\Security;

use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Guard\JWTTokenAuthenticator;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\TokenExtractorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class InvitedUserJWTTokenAuthenticator extends JWTTokenAuthenticator
{
    use TargetPathTrait;

    private $entityManager;
    private $urlGenerator;

    public function __construct(
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator,
        JWTTokenManagerInterface $jwtManager,
        EventDispatcherInterface $dispatcher,
        TokenExtractorInterface $tokenExtractor
    ) {
        $this->entityManager = $entityManager;
        $this->urlGenerator = $urlGenerator;

        parent::__construct($jwtManager, $dispatcher, $tokenExtractor);
    }

    public function supports(Request $request)
    {
        return 'api_user_activation' === $request->attributes->get('_route')
            && $request->isMethod('POST');
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        return true;
    }

}
