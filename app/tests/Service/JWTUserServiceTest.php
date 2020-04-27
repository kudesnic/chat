<?php

namespace App\Tests\Service;


use App\DataFixtures\UserFixtures;
use App\Entity\User;
use App\Service\JWTUserService;
use App\Tests\JWTTestHelperTrait;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\TokenExtractorInterface;
use Liip\TestFixturesBundle\Test\FixturesTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Class JWTUserService
 * This class serves as a user holder and user extractor.
 * Can be used only for users with active status, cause we inject only main_user_provider into it
 *
 * @author     Andrew Derevinako <andreyy.derevjanko@gmail.com>
 * @version    1.0
 */
class JWTUserServiceTest extends WebTestCase
{
    use FixturesTrait;
    use JWTTestHelperTrait;

    private $user;
    private $userFromToken;
    private $JWTEncoder;
    private $extractor;
    private $userProvider;
    private $userPasswordEncoder;
    private $request;
    private $service;

    public function setUp():void
    {

        $this->loadFixtures([
            UserFixtures::class
        ]);
        $this->JWTEncoder = self::$container->get(JWTEncoderInterface::class);
        $this->extractor = self::$container->get(TokenExtractorInterface::class);
        $this->userPasswordEncoder = self::$container->get(UserPasswordEncoderInterface::class);
        $this->service = self::$container->get(JWTUserService::class);
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\Security\Core\User\UserInterface
     * @throws \Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException
     */
    public function testGetUser()
    {
        $client = $this->createAuthenticatedClient('andrey-super-admin-1@gmail.com', '12345678a');
        $client->request('POST', '/api/login');
        $user = $this->service->getUser($client->getRequest());
        $this->assertInstanceOf(UserInterface::class, $user);
    }
//
//    /**
//     * @param Request $request
//     * @return array
//     * @throws \Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException
//     */
//    public function getUserDataFromToken(Request $request)
//    {
//        if(is_null($this->userFromToken)){
//            $token = $this->extractor->extract($request);
//            //decode throws an exception in case of wrong token
//            $this->userFromToken = $this->JWTEncoder->decode($token);
//        }
//
//        return $this->userFromToken;
//    }
//
//    public function checkPassword(Request $request, string $plainPassword)
//    {
//        $user = $this->getUser($request);
//        return $this->userPasswordEncoder->isPasswordValid($user, $plainPassword, $user->getSalt());
//    }

}