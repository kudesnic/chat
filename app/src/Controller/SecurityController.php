<?php

namespace App\Controller;

use App\DTO\Another\ActivateUserDTORequest;
use App\DTO\Another\RegisterDTORequest;
use App\Entity\User;
use App\Http\ApiResponse;
use App\Security\InvitedUserAuthenticationSuccessHandler;
use App\Security\InvitedUserProvider;
use App\Service\Base64ImageService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\TokenExtractorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Routing\Annotation\Route;

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
        AuthenticationSuccessHandler $authHandler,
        Base64ImageService $imageService
    ) {
        $user = new User();
        $entity = $request->populateEntity($user);
        $encodedPassword = $encoder->encodePassword($entity, $request->password);
        $entity->setPassword($encodedPassword);
        $entity->setStatus(User::STATUS_ACTIVE);
        $entity->setRoles([User::ROLE_SUPER_ADMIN]);

        if($request->img_encoded){
            $imgDirectory = User::UPLOAD_DIRECTORY . '/' . $user->getId() . '/' . User::AVATAR_PATH ;
            $imgPath = $imageService->saveImage($request->img_encoded, $imgDirectory, uniqid());
            $entity->setImg($imgPath);
        }

        $em->persist($entity);
        $em->flush($entity);
        $response = $authHandler->handleAuthenticationSuccess($entity);

        return $response;
    }


    /**
     * Register main user
     *
     * @Route("/activate-user", name="activate-user", methods={"PUT"})
     *
     * @param ActivateUserDTORequest $activateUserDTORequest
     * @param EntityManagerInterface $em
     * @param TokenExtractorInterface $tokenExtractor
     * @param JWTEncoderInterface $JWTEncoder
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param InvitedUserProvider $userProvider
     * @param AuthenticationSuccessHandler $authHandler
     * @return \Lexik\Bundle\JWTAuthenticationBundle\Response\JWTAuthenticationSuccessResponse
     * @throws \Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException
     */
    public function activateUser(
        ActivateUserDTORequest $activateUserDTORequest,
        EntityManagerInterface $em,
        TokenExtractorInterface $tokenExtractor,
        JWTEncoderInterface $JWTEncoder,
        UserPasswordEncoderInterface $passwordEncoder,
        InvitedUserProvider $userProvider,
        AuthenticationSuccessHandler $authHandler,
        Base64ImageService $imageService
    ) {
        $token = $tokenExtractor->extract($activateUserDTORequest->getRequest());
        //decode throws an exception in case of wrong token
        $user = $JWTEncoder->decode($token);
        $user = $userProvider->loadUserByUsername($user['email']);
        $entity = $activateUserDTORequest->populateEntity($user);
        $encodedPassword = $passwordEncoder->encodePassword($entity, $activateUserDTORequest->password);
        $entity->setPassword($encodedPassword);
        $entity->setStatus(User::STATUS_ACTIVE);

        if($activateUserDTORequest->img_encoded){
            $imgDirectory = User::UPLOAD_DIRECTORY . '/' . $user->getId() . '/' . User::AVATAR_PATH ;
            $imgPath = $imageService->saveImage($activateUserDTORequest->img_encoded, $imgDirectory, $user->getId());
            $entity->setImg($imgPath);
        }

        $em->persist($entity);
        $em->flush($entity);
        $response = $authHandler->handleAuthenticationSuccess($entity);

        return $response;
    }

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
