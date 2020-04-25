<?php

namespace App\Controller;

use App\DTO\Store\UserInviteDTORequest;
use App\DTO\Update\UserUpdateDTORequest;
use App\Entity\User;
use App\Exception\APIResponseException;
use App\Http\ApiResponse;
use App\Service\Base64ImageService;
use App\Service\JWTUserService;
use App\Service\PaginationServiceByCriteria;
use App\Service\PaginationServiceByQueryBuilder;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Proxy;
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
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;

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
     * @param EntityManagerInterface $em
     * @param PaginationServiceByQueryBuilder $paginationManger
     * @param JWTUserService $userHolder
     * @return ApiResponse
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function index(Request $request, EntityManagerInterface $em, PaginationServiceByQueryBuilder $paginationManger, JWTUserService $userHolder)
    {
        $page = $request->query->get('page');
        $user = $userHolder->getUser($request);
        $repository = $em->getRepository(User::class);
        $qb = $repository->getSameTreeUserQuery($user);
        if($request->query->has('role')){
            $role = json_encode([$request->query->get('role')], true);
//            dd('CONTAINS( node.roles, "' . $role . '") = true');
            $qb->andWhere('CONTAINS( node.roles, \'' . $role . '\') = true');
        }
        $result = $paginationManger->setRepository(User::class)
                ->paginate($qb, $page, null);

        return new ApiResponse($result);
    }

    /**
     * Gets only children nodes of nested set
     *
     * @Route("/user/children", name="user_list",  defaults={"page": 1},  methods={"GET"})
     *
     * @param Request $request
     * @param PaginationServiceByCriteria $paginationManger
     * @param JWTUserService $userHolder
     * @return ApiResponse
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function getChildrenUsers(Request $request, PaginationServiceByCriteria $paginationManger, JWTUserService $userHolder)
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
     *
     * @param User $user
     * @param Request $request
     * @param EntityManagerInterface $em
     * @param JWTUserService $userHolder
     * @param TranslatorInterface $translator
     * @return ApiResponse
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function show(
        User $user,
        Request $request,
        EntityManagerInterface $em,
        JWTUserService $userHolder,
        TranslatorInterface $translator
    ) {
        $loggedUser = $userHolder->getUser($request);
        $repository = $em->getRepository(User::class);
        $canShow = $repository->areBelongedToTheSameTree($loggedUser, $user);
        if($canShow == false){
            throw new AccessDeniedHttpException($translator->trans('You can\'t see this user'));
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
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
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
        if($request->parent_id){
            $repository = $em->getRepository(get_class($userEntity));
            $parentUser = $repository->find($request->parent_id);
            $userEntity->setParent($parentUser);
            $repository->persistAsLastChildOf($userEntity,$parentUser);
        } else {
            $userEntity->setParent($user);
        }
        $em->persist($userEntity);
        $em->flush($userEntity);

        return new ApiResponse($userEntity, 201);
    }

    /**
     * @Route("/user/{id}", name="user_update", requirements={"id":"\d+"}, methods={"PUT"})
     *
     * @param User $userToUpdate
     * @param UserUpdateDTORequest $request
     * @param EntityManagerInterface $em
     * @param JWTUserService $userHolder
     * @param Base64ImageService $imageService
     * @param UserPasswordEncoderInterface $encoder
     * @param TranslatorInterface $translator
     * @return ApiResponse
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function update(
        User $userToUpdate,
        UserUpdateDTORequest $request,
        EntityManagerInterface $em,
        JWTUserService $userHolder,
        Base64ImageService $imageService,
        UserPasswordEncoderInterface $encoder,
        TranslatorInterface $translator
    ) {
        $loggedUser = $userHolder->getUser($request->getRequest());
        $repository = $em->getRepository(get_class($userToUpdate));

        $isChild = $repository->isNodeAChild($loggedUser, $userToUpdate);
        if($isChild == false && $loggedUser->getId() != $userToUpdate->getId()){
            throw new AccessDeniedHttpException(
                $translator->trans('You can not update this user, because it doesn\'t belong to you!')
            );
        }

        $userEntity = $request->populateEntity($userToUpdate);
        if($request->roles && $loggedUser->getId() == $userEntity->getId()){
            throw new AccessDeniedHttpException(
                $translator->trans('You can not update your role, only your boss can do this')
            );
        }
        //old password validated and checked by DTO
        if($request->old_password && $request->password){
            $newEncodedPassword = $encoder->encodePassword($userEntity, $request->password);
            $userEntity->setPassword($newEncodedPassword);
        }
        if($request->parent_id){
            $parentUser = $repository->find($request->parent_id);
            if(
                in_array(User::ROLE_ADMIN, $parentUser->getRoles())
                || in_array(User::ROLE_ADMIN, $parentUser->getRoles())
            ){
                throw new AccessDeniedHttpException(
                    $translator->trans('Parent should have ADMIN or SUPER_ADMIN role!')
                );
            }
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
     *
     * @param User $userToDelete
     * @param Request $request
     * @param JWTUserService $userHolder
     * @param EntityManagerInterface $em
     * @param TranslatorInterface $translator
     * @return ApiResponse
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function destroy(
        User $userToDelete,
        Request $request,
        JWTUserService $userHolder,
        EntityManagerInterface $em,
        TranslatorInterface $translator
    ) {
        $loggedUser = $userHolder->getUser($request);
        $repository = $em->getRepository(User::class);
        $isChild = $repository->isNodeAChild($loggedUser, $userToDelete);
        if($loggedUser->getId() == $userToDelete->getId() && $repository->countChildren($userToDelete)){
            throw new AccessDeniedHttpException(
                $translator->trans('You can not delete yourself, if you have subordinates managers!')
            );
        } elseif($isChild == false && $loggedUser->getId() != $userToDelete->getId()){
            throw new AccessDeniedHttpException(
                $translator->trans('You can not delete this user, because it doesn\'t belong to you!')
            );
        }
        $em->remove($userToDelete);
        $em->flush();

        return new ApiResponse();
    }
}
