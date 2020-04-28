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

    public function testGetUser()
    {
        $client = $this->createAuthenticatedClient('andrey-super-admin-1@gmail.com', '12345678a');
        //just to get new request object for authenticated user. Route api/login used only because we aware that it exists
        $client->request('POST', 'api/login');
        $user = $this->service->getUser($client->getRequest());
        $this->assertInstanceOf(UserInterface::class, $user);
    }

    public function testGetUserDataFromToken()
    {
        $client = $this->createAuthenticatedClient('andrey-super-admin-1@gmail.com', '12345678a');
        $client->request('POST', 'api/login');
        $userData = $this->service->getUserDataFromToken($client->getRequest());
        $this->assertIsArray($userData);
        $this->assertEquals($userData['email'], 'andrey-super-admin-1@gmail.com');

        return $this->userFromToken;
    }

    public function testCheckPassword()
    {
        $client = $this->createAuthenticatedClient('andrey-super-admin-1@gmail.com', '12345678a');
        $client->request('POST', 'api/login');
        $valid = $this->service->checkPassword($client->getRequest(), '12345678a');
        $this->assertEquals(true, $valid);
        $invalid = $this->service->checkPassword($client->getRequest(), 'wrong_password');
        $this->assertEquals(false, $invalid);
    }

}