<?php

namespace App\Controller;

use App\Entity\User;
use App\Http\ApiResponse;
use App\Service\PaginationManger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * Class UserController
 * @package App\Controller
 */
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

    /**
     * @Route("/user/{id}", name="user_delete", requirements={"id":"\d+"},  methods={"DELETE"})
     * @ParamConverter("id", class="App\Entity\User", options={"id": "id"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function destroy(User $user)
    {
        $user = $this->getUser();
        if($user->getId() == $user->getId()){
            throw new AuthenticationException('You can not delete yourself');
        }

        return new ApiResponse($user);
    }
}
