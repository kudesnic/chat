<?php

namespace App\Controller;

use App\DTO\Store\UserStoreDTORequest;
use App\DTO\Update\UserUpdateDTORequest;
use App\Entity\User;
use App\Http\ApiResponse;
use App\Service\JWTUserHolder;
use App\Service\PaginationManger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * Class UserController
 * @package App\Controller
 */
class UserController extends AbstractController
{
    /**
     * @Route("/user", name="user_list",  defaults={"page": 1},  methods={"GET"})
     *
     * @param Request $request
     * @param PaginationManger $paginationManger
     * @param JWTUserHolder $userHolder
     * @return ApiResponse
     */
    public function index(Request $request, PaginationManger $paginationManger, JWTUserHolder $userHolder)
    {
        $page = $request->query->get('page');
        $user = $userHolder->getUser($request);
        $result = $paginationManger->setRepository(User::class)
                ->paginateNodeChildren($user, ['name' => 'asc'], $page, null, false);

        return new ApiResponse($result);
    }

    /**
     * @Route("/user/{id}", name="user_show", requirements={"id":"\d+"},  methods={"GET"})
     * @ParamConverter("id", class="App\Entity\User", options={"id": "id"})
     *
     * @param User $user
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param JWTUserHolder $userHolder
     * @return ApiResponse
     */
    public function show(User $user,Request $request, EntityManagerInterface $em, JWTUserHolder $userHolder)
    {
        $loggedUser = $userHolder->getUser($request);
        $repository = $em->getRepository(User::class);
        $canShow = $repository->isNodeAChild($loggedUser, $user);
        if($canShow == false && $loggedUser->getId() !== $user->getId()){
            throw new AuthenticationException('You can\'t see this user');
        }

        return new ApiResponse($user);
    }

    /**
     * @Route("/user/invite-user", name="user_store", methods={"POST"})
     *
     * @param UserStoreDTORequest $request
     * @param EntityManagerInterface $em
     * @param JWTUserHolder $userHolder
     * @return ApiResponse
     */
    public function store(UserStoreDTORequest $request, EntityManagerInterface $em, JWTUserHolder $userHolder)
    {
        $user = $userHolder->getUser($request->getRequest());
        $userEntity = new User();
        $userEntity = $request->populateEntity($userEntity);
        $userEntity->setParent($user);
        $em->persist($userEntity);
        $em->flush($userEntity);

        return new ApiResponse($userEntity);
    }

    /**
     * @Route("/user/{id}", name="user_update", methods={"PUT"})
     *
     * @param UserUpdateDTORequest $request
     * @param EntityManagerInterface $em
     * @param JWTUserHolder $userHolder
     * @return ApiResponse
     */
    public function update(UserUpdateDTORequest $request, EntityManagerInterface $em, JWTUserHolder $userHolder)
    {
        $user = $userHolder->getUser($request->getRequest());
        $userEntity = new User();
        $userEntity = $request->populateEntity($userEntity);
        $userEntity->setParent($user);
        $em->persist($userEntity);
        $em->flush($userEntity);

        return new ApiResponse($userEntity);
    }

    /**
     * @Route("/user/{id}", name="user_delete", requirements={"id":"\d+"},  methods={"DELETE"})
     * @ParamConverter("id", class="App\Entity\User", options={"id": "id"})
     *
     * @param User $userToDelete
     * @param Request $request
     * @param JWTUserHolder $userHolder
     * @param EntityManagerInterface $em
     * @return ApiResponse
     */
    public function destroy(User $userToDelete, Request $request, JWTUserHolder $userHolder, EntityManagerInterface $em)
    {
        $loggedUser = $userHolder->getUser($request);
        $repository = $em->getRepository(User::class);
        $canDelete = $repository->isNodeAChild($loggedUser, $userToDelete);
        if($loggedUser->getId() == $userToDelete->getId()){
            throw new AuthenticationException('You can not delete yourself!');
        } elseif($canDelete == false){
            throw new AuthenticationException('You can not delete this user, because it doesn\'t belong to you!');
        }

        $em->remove($userToDelete);
        $em->flush();

        return new ApiResponse();
    }
}
