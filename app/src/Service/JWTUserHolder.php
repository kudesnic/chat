<?php

namespace App\Service;

use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\TokenExtractorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Class JWTUserHolder
 * This class serves as a user holder and user extractor.
 * Can be used only for users with active status, cause we inject only main_user_provider into it
 *
 * @package App\Service
 */
class JWTUserHolder
{
    private $user;
    private $userFromToken;
    private $encoder;
    private $extractor;
    private $userProvider;

    public function __construct(JWTEncoderInterface $encoder, TokenExtractorInterface $extractor,   UserProviderInterface $userProvider)
    {
        $this->encoder = $encoder;
        $this->extractor = $extractor;
        $this->userProvider = $userProvider;
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
            $user = $this->encoder->decode($token);
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
            $this->userFromToken = $this->encoder->decode($token);
        }

        return $this->userFromToken;
    }
}