<?php

namespace App\Controller;

use App\DTO\Store\MessageStoreDTORequest;
use App\Entity\Chat;
use App\Entity\Message;
use App\Entity\User;
use App\Http\ApiResponse;
use App\Service\JWTUserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ChatController
 * @package App\Controller
 *
 * @Route("message", name="message.")
 */
class MessageController extends AbstractController
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var \Doctrine\Persistence\ObjectRepository
     */
    private $messageRepo;

    /**
     * ChatController constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->messageRepo = $entityManager->getRepository(Message::class);
    }

    /**
     * @Route("/", name="store", methods={"POST"})
     *

     * @param MessageStoreDTORequest $request
     * @param JWTUserService $userHolder
     * @param TranslatorInterface $translator
     * @return ApiResponse
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function store(
        MessageStoreDTORequest $request,
        JWTUserService $userHolder,
        TranslatorInterface $translator
    ) {
        $user = $userHolder->getUser($request->getRequest());
        $chat = $this->entityManager
            ->getRepository(Chat::class)->findChatByUuidAndUser($request->chat_uuid, $user);
        if(is_null($chat)){
            throw new NotFoundHttpException(
                $translator->trans('You dont participate in chat with uuid = ' . $request->chat_uuid)
            );
        }
        $messageEntity = new Message();
        $messageEntity->setUser($user);
        $messageEntity->setChat($chat);
        $messageEntity->setText($request->text);
        $this->entityManager->persist($messageEntity);
        $this->entityManager->flush($messageEntity);

        return new ApiResponse($messageEntity, Response::HTTP_CREATED);
    }

    /**
     * @Route("/{id}", name="destroy", requirements={"id":"\d+"},  methods={"DELETE"})
     *

     * @param Request $request
     * @param JWTUserService $userHolder
     * @return ApiResponse
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function destroy(
        Request $request,
        JWTUserService $userHolder
    ) {
        $loggedUser = $userHolder->getUser($request);
        $message = $this->messageRepo->findOneBy([
            'id' => $request->query->get('id'),
            'user_id' => $loggedUser->getId()
        ]);
        if($message){
            $this->em->remove($message);
            $this->em->flush();
        }

        return new ApiResponse();
    }
}
