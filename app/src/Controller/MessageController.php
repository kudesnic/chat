<?php

namespace App\Controller;

use App\DTO\Store\MessageStoreDTORequest;
use App\Entity\Chat;
use App\Entity\Message;
use App\Entity\User;
use App\Http\ApiResponse;
use App\Repository\ParticipantRepository;
use App\Security\ChatVoter;
use App\Service\JWTUserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\PublisherInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ChatController
 * @package App\Controller
 *
 * @Route("/message", name="message.")
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
     * @Route("", name="store", methods={"POST"})
     *
     * @param MessageStoreDTORequest $request
     * @param JWTUserService $userHolder
     * @param ParticipantRepository $participantRepository
     * @param SerializerInterface $serializer
     * @param PublisherInterface $publisher
     * @param TranslatorInterface $translator
     * @return ApiResponse
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function store(
        MessageStoreDTORequest $request,
        JWTUserService $userHolder,
        ParticipantRepository $participantRepository,
        SerializerInterface $serializer,
        PublisherInterface $publisher,
        TranslatorInterface $translator
    ) {
        $user = $userHolder->getUser($request->getRequest());
        $chatRepo = $this->entityManager->getRepository(Chat::class);

        if($request->chat_uuid){
            $chat = $chatRepo->findChatByUuidAndUser($request->chat_uuid, $user);
        } else {
            $userRepository = $this->entityManager->getRepository(User::class);
            $receiverUser = $userRepository->find($request->to_user_id);

            $chat = $chatRepo->findPrivateChatByTwoUsers($user, $receiverUser);

            if(is_null($chat)) {
                $chat = new Chat();

                $chat->setStrategy(Chat::STRATEGY_INTERNAL_CHAT);
                $chat->setOwner($user);
                $this->entityManager->persist($chat);
                $receiverParticipant = $participantRepository->createUserParticipant($chat, $receiverUser);
                $this->entityManager->persist($receiverParticipant);

                $senderParticipant = $participantRepository->createUserParticipant($chat, $user);
                $this->entityManager->persist($senderParticipant);
            }
        }
        $this->denyAccessUnlessGranted(ChatVoter::VIEW, $chat);

        $messageEntity = new Message();
        $messageEntity->setUser($user);
        $messageEntity->setChat($chat);
        $messageEntity->setText($request->text);
        $this->entityManager->persist($messageEntity);
        $this->entityManager->flush();
        $chat->addMessage($messageEntity);
        $topicsArray = [
            sprintf('conversations/%s', $chat->getUuid())
        ];
        $chat = $chatRepo->find($chat->getId());
        foreach ($chat->getParticipants() as $recepient){
            if($recepient->getUser()->  getId() != $user->getId()){
                $topicsArray[] = sprintf('conversations/%s', $recepient->getUser()->getEmail());
            }
        }

        $update = new Update(
            $topicsArray,
            $serializer->serialize($messageEntity, 'json', ['groups' => 'APIGroup']),
            true // private
        );
        $publisher->__invoke($update);

        return new ApiResponse($chat , Response::HTTP_CREATED);
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
