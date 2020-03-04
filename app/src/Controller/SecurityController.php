<?php

namespace App\Controller;

use App\DTO\RegisterDTORequest;
use App\Entity\User;
use App\Exception\ValidationException;
use App\Http\ApiResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Guard\AuthenticatorInterface;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;

class SecurityController extends AbstractController
{

    /**
     * @Route("/register", name="app_register", methods={"POST"})
     *
     * @param RegisterDTORequest $request
     * @param EntityManagerInterface $em
     * @param UserPasswordEncoderInterface $encoder
     * @param GuardAuthenticatorHandler $guard
     * @param AuthenticatorInterface $authenticator
     *
     * @return null|\Symfony\Component\HttpFoundation\Response
     */
    public function register(
        RegisterDTORequest $request,
        EntityManagerInterface $em,
        UserPasswordEncoderInterface $encoder,
        GuardAuthenticatorHandler $guard,
        AuthenticatorInterface $authenticator
    ) {
        $user = new User();
        $entity = $request->populateEntity($user);
        $encodedPassword = $encoder->encodePassword($entity, $request->password);
        $entity->setPassword($encodedPassword);
        $em->persist($entity);
        $em->flush($entity);

        $response = $guard->authenticateUserAndHandleSuccess(
            $entity,
            $request->getRequest(),
            $authenticator,
            'main'
        );

        return $response;
    }
    /**
     * @Route("/login", name="login", methods={"POST"})
     */
    public function login(Request $request, EntityManagerInterface $em)
    {
        $data = json_decode($request->getContent(), true);
        $user = $em->getRepository(User::class)
            ->findOneBy(['email' => $data['email']]);
        return $this->json([
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ]);
    }
    /**
     * @Route("/logout", name="app_logout")
     *
     * @throws \Exception
     */
    public function logout()
    {
        throw new \Exception('This method can be blank - it will be intercepted by the logout key on your firewall');
    }
}
