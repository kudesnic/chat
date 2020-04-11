<?php

namespace App\Controller;

use App\DTO\Store\UserInviteDTORequest;
use App\DTO\Update\UserUpdateDTORequest;
use App\Entity\User;
use App\Exception\APIResponseException;
use App\Http\ApiResponse;
use App\Service\Base64ImageService;
use App\Service\JWTUserService;
use App\Service\PaginationService;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use http\Exception\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
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
     * Gets users from the same tree and all levels
     *
     * @Route("/user", name="users_list",  defaults={"page": 1},  methods={"GET"})
     *
     * @param Request $request
     * @param PaginationService $paginationManger
     * @param JWTUserService $userHolder
     * @return ApiResponse
     */
    public function index(Request $request, PaginationService $paginationManger, JWTUserService $userHolder)
    {
        $page = $request->query->get('page');
        $user = $userHolder->getUser($request);
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('tree_root', $user->getTreeRoot()))
            ->orderBy(['name' =>Criteria::ASC]);
        $result = $paginationManger->setRepository(User::class)
                ->paginate($criteria, $page, null);

        return new ApiResponse($result);
    }

    /**
     * Gets only children nodes of nested set
     *
     * @Route("/user/children", name="user_list",  defaults={"page": 1},  methods={"GET"})
     *
     * @param Request $request
     * @param PaginationService $paginationManger
     * @param JWTUserService $userHolder
     * @return ApiResponse
     */
    public function getChildrenUsers(Request $request, PaginationService $paginationManger, JWTUserService $userHolder)
    {
        $page = $request->query->get('page');
        $user = $userHolder->getUser($request);
        $criteria = Criteria::create()
            ->orderBy(['name' => Criteria::ASC]);
        $result = $paginationManger->setRepository(User::class)
            ->paginateNodeChildren($user, $criteria, $page, null, true);

        return new ApiResponse($result);
    }


    /**
     * @Route("/user/{id}", name="user_show", requirements={"id":"\d+"},  methods={"GET"})
     * @ParamConverter("id", class="App\Entity\User", options={"id": "id"})
     *
     * @param User $user
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param JWTUserService $userHolder
     * @throws AuthenticationException
     * @return ApiResponse
     */
    public function show(User $user, Request $request, EntityManagerInterface $em, JWTUserService $userHolder)
    {
        $loggedUser = $userHolder->getUser($request);
        $repository = $em->getRepository(User::class);
        $canShow = $repository->areBelongedToTheSameTree($loggedUser, $user);
        if($canShow == false){
            throw new AccessDeniedHttpException('You can\'t see this user');
        }

        return new ApiResponse($user);
    }

    /**
     * @Route("/user/invite-user", name="user_store", methods={"POST"})
     *
     * @param UserInviteDTORequest $request
     * @param EntityManagerInterface $em
     * @param JWTUserService $userHolder
     * @param UserPasswordEncoderInterface $encoder
     * @return ApiResponse
     * @throws \Exception
     */
    public function inviteUser(
        UserInviteDTORequest $request,
        EntityManagerInterface $em,
        JWTUserService $userHolder,
        UserPasswordEncoderInterface $encoder
    ) {
        $user = $userHolder->getUser($request->getRequest());
        $userEntity = new User();
        $userEntity = $request->populateEntity($userEntity);
        $userEntity->setStatus(User::STATUS_INVITED);
        //sets random password for invited user, so no one knows it and no one can use it
        $encodedPassword = $encoder->encodePassword($userEntity, bin2hex(random_bytes(10)));
        $userEntity->setPassword($encodedPassword);
        $userEntity->setParent($user);
        $em->persist($userEntity);
        $em->flush($userEntity);

        return new ApiResponse($userEntity);
    }

    /**
     * @Route("/user/{id}", name="user_update", requirements={"id":"\d+"}, methods={"PUT"})
     * @ParamConverter("id", class="App\Entity\User", options={"id": "id"})
     *
     * @param User $userToUpdate
     * @param UserUpdateDTORequest $request
     * @param EntityManagerInterface $em
     * @param JWTUserService $userHolder
     * @param Base64ImageService $imageService
     * @throws AccessDeniedHttpException
     * @return ApiResponse
     */
    public function update(
        User $userToUpdate,
        UserUpdateDTORequest $request,
        EntityManagerInterface $em,
        JWTUserService $userHolder,
        Base64ImageService $imageService
    ) {
        $loggedUser = $userHolder->getUser($request->getRequest());
        $repository = $em->getRepository(get_class($userToUpdate));

        $isChild = $repository->isNodeAChild($loggedUser, $userToUpdate);
        if($isChild == false && $loggedUser->getId() != $userToUpdate->getId()){
            throw new AccessDeniedHttpException('You can not update this user, because it doesn\'t belong to you!');
        }

        $userEntity = $request->populateEntity($userToUpdate);

        if($request->parent_id){
            $parentUser = $repository->find($request->parent_id);
            $userEntity->setParent($parentUser);
            $repository->persistAsLastChildOf($userEntity,$parentUser);
        }

        if($request->img_encoded){
            $imgDirectory = User::UPLOAD_DIRECTORY . '/' . $userEntity->getId() . '/' . User::AVATAR_PATH ;
            $imgPath = $imageService->saveImage($request->img_encoded, $imgDirectory, uniqid(), $userEntity->getImg());
            $userEntity->setImg($imgPath);
        }
        $em->persist($userEntity);

        $em->flush($userEntity);
        $repository->recover();
        $em->flush($userEntity);


        return new ApiResponse($userEntity);
    }

    /**
     * @Route("/user/{id}", name="user_delete", requirements={"id":"\d+"},  methods={"DELETE"})
     * @ParamConverter("id", class="App\Entity\User", options={"id": "id"})
     *
     * @param User $userToDelete
     * @param Request $request
     * @param JWTUserService $userHolder
     * @param EntityManagerInterface $em
     * @throws AccessDeniedHttpException|AccessDeniedHttpException
     * @return ApiResponse
     */
    public function destroy(User $userToDelete, Request $request, JWTUserService $userHolder, EntityManagerInterface $em)
    {
        $loggedUser = $userHolder->getUser($request);
        $repository = $em->getRepository(User::class);
        $isChild = $repository->isNodeAChild($loggedUser, $userToDelete);
        if($loggedUser->getId() == $userToDelete->getId() && $repository->countChildren($userToDelete)){
            throw new AccessDeniedHttpException('You can not delete yourself, if you have subordinates managers!');
        } elseif($isChild == false){
            throw new AccessDeniedHttpException('You can not delete this user, because it doesn\'t belong to you!');
        }

        $em->remove($userToDelete);
        $em->flush();

        return new ApiResponse();
    }
}
