<?php

namespace App\Security;

use App\Entity\Login;
use App\Entity\User;
use App\Exception\FormException;
use App\Exception\ValidationException;
use App\Http\ApiResponse;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\InvalidPayloadException;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\UserNotFoundException;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Authentication\Token\PreAuthenticationJWTUserToken;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Guard\JWTTokenAuthenticator;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\TokenExtractorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
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

    /**
     * Returns an user object loaded from a JWT token.
     *
     * {@inheritdoc}
     *
     * @param PreAuthenticationJWTUserToken Implementation of the (Security) TokenInterface
     *
     * @throws \InvalidArgumentException If preAuthToken is not of the good type
     * @throws InvalidPayloadException   If the user identity field is not a key of the payload
     * @throws UserNotFoundException     If no user can be loaded from the given token
     */
    public function getUser($preAuthToken, UserProviderInterface $userProvider)
    {
        if (!$preAuthToken instanceof PreAuthenticationJWTUserToken) {
            throw new \InvalidArgumentException(
                sprintf('The first argument of the "%s()" method must be an instance of "%s".', __METHOD__, PreAuthenticationJWTUserToken::class)
            );
        }

        $payload = $preAuthToken->getPayload();

        if (!isset($payload['email'])) {
            throw new InvalidPayloadException('email');
        }

        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(
                [
                    'email' => $payload['email'],
                    'status' => User::STATUS_INVITED,
                ]
            );
        if(!$user){
            throw new UserNotFoundException('email', 'email');
        }

       // $this->preAuthenticationTokenStorage->setToken($preAuthToken);

        return $user;
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        return true;
    }

    protected function getLoginUrl()
    {

        return $this->urlGenerator->generate('api_user_activation');
    }
}
