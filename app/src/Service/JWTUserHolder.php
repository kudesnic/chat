<?php

namespace App\Service;


use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\TokenExtractorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class JWTUserHolder
{
    private $user;
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
     * @return mixed
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
}