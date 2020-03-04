<?php

namespace App\Controller;

use App\Entity\User;
use App\Http\ApiResponse;
use App\Service\PaginationManger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class UserController extends AbstractController
{
    /**
     * @Route("/user", name="user_list",  defaults={"page": 1},  methods={"GET"})
     */
    public function index(PaginationManger $paginationManger, Request $request)
    {
        $page = $request->query->get('page');
        $result = $paginationManger->setRepository(User::class)
            ->paginate([], ['name' => 'asc'], $page);

        return new ApiResponse($result);
    }

    /**
     * @Route("/user/{id}", name="user_show", requirements={"id":"\d+"},  methods={"GET"})
     * @ParamConverter("id", class="App\Entity\User", options={"id": "id"})
     */
    public function show(User $user)
    {
        return new ApiResponse($user);
    }
}
