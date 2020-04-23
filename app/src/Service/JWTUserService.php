<?php

namespace App\Service;

use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\TokenExtractorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Class JWTUserService
 * This class serves as a user holder and user extractor.
 * Can be used only for users with active status, cause we inject only main_user_provider into it
 *
 * @author     Andrew Derevinako <andreyy.derevjanko@gmail.com>
 * @version    1.0
 */
class JWTUserService
{
    private $user;
    private $userFromToken;
    private $JWTEncoder;
    private $extractor;
    private $userProvider;
    private $userPasswordEncoder;

    public function __construct(
        JWTEncoderInterface $JWTEncoder,
        UserPasswordEncoderInterface $userPasswordEncoder,
        TokenExtractorInterface $extractor,
        UserProviderInterface $userProvider
    ) {
        $this->JWTEncoder = $JWTEncoder;
        $this->extractor = $extractor;
        $this->userProvider = $userProvider;
        $this->userPasswordEncoder = $userPasswordEncoder;
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\Security\Core\User\UserInterface
     * @throws \Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException
     */
    public function getUser(Request $request)
    {
        if(is_null($this->user)){
            $token = $this->extractor->extract($request);
            //decode throws an exception in case of wrong token
            $user = $this->JWTEncoder->decode($token);
            $this->user = $this->userProvider->loadUserByUsername($user['email']);
        }

        return $this->user;
    }

    /**
     * @param Request $request
     * @return array
     * @throws \Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException
     */
    public function getUserDataFromToken(Request $request)
    {
        if(is_null($this->userFromToken)){
            $token = $this->extractor->extract($request);
            //decode throws an exception in case of wrong token
            $this->userFromToken = $this->JWTEncoder->decode($token);
        }

        return $this->userFromToken;
    }

    public function checkPassword(Request $request, string $plainPassword)
    {
        $user = $this->getUser($request);
        return $this->userPasswordEncoder->isPasswordValid($user, $plainPassword, $user->getSalt());
    }

}