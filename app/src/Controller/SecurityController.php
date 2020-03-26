<?php

namespace App\Controller;

use App\DTO\Another\RegisterDTORequest;
use App\Entity\User;
use App\Exception\ValidationException;
use App\Http\ApiResponse;
use App\Security\InvitedUserAuthenticationSuccessHandler;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTManager;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Event\AuthenticationSuccessEvent;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Guard\AuthenticatorInterface;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;

class SecurityController extends AbstractController
{

    /**
     * Register main user
     *
     * @Route("/register", name="app_register", methods={"POST"})
     *
     * @param RegisterDTORequest $request
     * @param EntityManagerInterface $em
     * @param UserPasswordEncoderInterface $encoder
     * @param AuthenticationSuccessHandler $authHandler
     * @return \Lexik\Bundle\JWTAuthenticationBundle\Response\JWTAuthenticationSuccessResponse
     */
    public function register(
        RegisterDTORequest $request,
        EntityManagerInterface $em,
        UserPasswordEncoderInterface $encoder,
        AuthenticationSuccessHandler $authHandler
    )
    {
        $user = new User();
        $entity = $request->populateEntity($user);
        $encodedPassword = $encoder->encodePassword($entity, $request->password);
        $entity->setPassword($encodedPassword);
        $entity->setStatus(User::STATUS_ACTIVE);
        $em->persist($entity);
        $em->flush($entity);
        $response = $authHandler->handleAuthenticationSuccess($entity);

        return $response;
    }


    /**
     * Register main user
     *
     * @Route("/activate-user/{id}", name="activate-user", requirements={"id":"\d+"}, methods={"PUT"})
     * @ParamConverter("id", class="App\Entity\User", options={"id": "id"})
     *
     * @param ActivateUserDTORequest $request
     * @param EntityManagerInterface $em
     * @param UserPasswordEncoderInterface $encoder
     * @param AuthenticationSuccessHandler $authHandler
     * @return \Lexik\Bundle\JWTAuthenticationBundle\Response\JWTAuthenticationSuccessResponse
     */
//    public function activateUser(
//        User $user,
//        ActivateUserDTORequest $request,
//        EntityManagerInterface $em,
//        JWTUserHolder $userHolder,
//        UserPasswordEncoderInterface $encoder,
//        AuthenticationSuccessHandler $authHandler
//    ) {
//        $userFromToken = $userHolder->getUserDataFromToken($request->getRequest());
//          if($userFromToken['id'] != $user->getId()){
//              throw new Exception('You cant activate this user');
//          }

//        $entity = $request->populateEntity($user);
//        $encodedPassword = $encoder->encodePassword($entity, $request->password);
//        $entity->setPassword($encodedPassword);
//        $entity->setStatus(User::STATUS_ACTIVE);
//        $em->persist($entity);
//        $em->flush($entity);
//        $response = $authHandler->handleAuthenticationSuccess($entity);
//
//        return $response;
//    }

    /**
     * @Route("/login-for-activation", name="login_for_activation", methods={"POST"})
     *
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param InvitedUserAuthenticationSuccessHandler $authHandler
     * @return ApiResponse|\Lexik\Bundle\JWTAuthenticationBundle\Response\JWTAuthenticationSuccessResponse
     */
    public function loginForActivation(
        Request $request,
        EntityManagerInterface $em,
        InvitedUserAuthenticationSuccessHandler $authHandler
    ) {
        $data = json_decode($request->getContent(), true);
        $user = $em->getRepository(User::class)
            ->findOneBy(
                [
                    'email' => $data['email'],
                    'status' => User::STATUS_INVITED,
                ]
            );
        if(!$user){
            return new ApiResponse([], 'User not found', [], 401);
        }

        return $authHandler->handleAuthenticationSuccess($user);

    }

    /**
     * @Route("/logout", name="app_logout")
     *
     * @throws \Exception
     */
    public function logout()
    {

    }
}
