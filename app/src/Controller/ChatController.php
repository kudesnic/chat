<?php

namespace App\Controller;

use App\Entity\Chat;
use App\Entity\Message;
use App\Entity\Participant;
use App\Http\ApiResponse;
use App\Repository\ChatRepository;
use App\Security\ChatVoter;
use App\Service\JWTUserService;
use App\Service\PaginationServiceByQueryBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\WebLink\Link;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Routing\Annotation\Route;
use App\Validator\CustomUuidValidator;

/**
 * Class ChatController
 * @package App\Controller
 *
 * @Route("/chat", name="chat.")
 */
class ChatController extends AbstractController
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ChatRepository
     */
    private $chatRepo;

    /**
     * ChatController constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->chatRepo = $entityManager->getRepository(Chat::class);
    }

    /**
     * @Route("", name="list")
     *
     * @param Chat $chat
     * @param Request $request
     * @param JWTUserService $userHolder
     * @param PaginationServiceByQueryBuilder $paginationServiceByQueryBuilder
     * @return ApiResponse
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function index(Request $request,  JWTUserService $userHolder, PaginationServiceByQueryBuilder $paginationServiceByQueryBuilder)
    {
        $qb = $this->chatRepo->getNewAndUpdatedChatsQueryBuilder($userHolder->getUser($request), false);
        $paginationServiceByQueryBuilder->setRepository(Chat::class)
            ->buildQuery($qb, $request->query->get('page'), null);
        //add eager selections to query passed by reference
        $this->chatRepo->modifyQueryToEager($paginationServiceByQueryBuilder->query);

        $result = $paginationServiceByQueryBuilder->paginateFromQuery();

        $hubUrl = $this->getParameter('mercure.default_hub');
        $this->addLink($request, new Link('mercure', $hubUrl));

        return new ApiResponse($result, 200);
    }

    /**
     * @Route("/{uuid}", name="show", requirements={"uuid":CustomUuidValidator::VALID_PATTERN},  methods={"GET"})
     *
     * @param string $uuid
     * @param Chat $chat
     * @param Request $request
     * @param JWTUserService $userHolder
     * @param TranslatorInterface $translator
     * @return ApiResponse
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function show(Chat $chat)
    {
        $this->denyAccessUnlessGranted(ChatVoter::VIEW, $chat);

        return new ApiResponse($chat);
    }

    /**
     * @Route("/{uuid}/messages", name="chat_messages", requirements={"uuid":CustomUuidValidator::VALID_PATTERN},  methods={"GET"})
     *
     * @param Chat $chat
     * @param Request $request
     * @param JWTUserService $userHolder
     * @param PaginationServiceByQueryBuilder $paginationServiceByQueryBuilder
     * @param TranslatorInterface $translator
     * @return ApiResponse
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function chatMessages(
        Chat $chat,
        Request $request,
        JWTUserService $userHolder,
        PaginationServiceByQueryBuilder $paginationServiceByQueryBuilder
    ) {
        $loggedUser = $userHolder->getUser($request);
        $messageRepo = $this->entityManager->getRepository(Message::class);
        $this->denyAccessUnlessGranted(ChatVoter::VIEW, $chat);
        $qb = $messageRepo->getChatMessagesQueryBuilder($chat, $loggedUser);
        $paginationServiceByQueryBuilder->setRepository(Message::class)
            ->buildQuery($qb, $request->query->get('page'), null);
        //add eager selections to query passed by reference
        $messageRepo->modifyQueryToEager($paginationServiceByQueryBuilder->query);
        $result = $paginationServiceByQueryBuilder->paginateFromQuery();

        return new ApiResponse($result);
    }

    /**
     * @Route("/{uuid}/set-messages-as-read", name="set_messages_as_read", requirements={"uuid":CustomUuidValidator::VALID_PATTERN},  methods={"PUT"})
     *
     * @param Chat $chat
     * @param Request $request
     * @param JWTUserService $userHolder
     * @param TranslatorInterface $translator
     * @return ApiResponse
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function setChatMessagesAsRead(
        Chat $chat,
        Request $request,
        JWTUserService $userHolder
    ) {
        $loggedUser = $userHolder->getUser($request);
        $this->denyAccessUnlessGranted(ChatVoter::VIEW, $chat);
        $participantRepo = $this->entityManager->getRepository(Participant::class);
        $participant = $participantRepo->findOneBy(['chat' => $chat, 'user' => $loggedUser]);
        $participant->setUnreadMessagesCount(null);
        $this->entityManager->persist($participant);
        $this->entityManager->flush($participant);

        return new ApiResponse([]);
    }

    /**
     * @Route("/{uuid}", name="delete", requirements={"uuid":CustomUuidValidator::VALID_PATTERN},  methods={"DELETE"})
     *
     * @param Chat $chat
     * @param Request $request
     * @param JWTUserService $userHolder
     * @param TranslatorInterface $translator
     * @return ApiResponse
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function destroy(Chat $chat)
    {
        $this->denyAccessUnlessGranted(ChatVoter::DELETE, $chat);
        $this->entityManager->remove($chat);
        $this->entityManager->flush();

        return new ApiResponse();
    }
}
