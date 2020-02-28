<?php

namespace App\Controller;

use App\DTO\RegisterDTORequest;
use App\Entity\User;
use App\Exception\ValidationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Annotation\Route;


class SecurityController extends AbstractController
{

    /**
     * @Route("/register", name="app_register", methods={"POST"})
     */
    public function register(RegisterDTORequest $request)
    {
        $user = new User();
        dd($request->populateEntity($user));
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout()
    {
        throw new \Exception('This method can be blank - it will be intercepted by the logout key on your firewall');
    }
}
