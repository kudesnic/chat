<?php
namespace App\Websocket;


use App\Entity\Chat;
use App\Entity\Message;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Authentication\Token\JWTUserToken;
use Ramsey\Uuid\Uuid;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\ZMQ\Context;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Thruway\ClientSession;
use Thruway\Logging\Logger;
use Thruway\Message\PublishMessage;
use Thruway\Module\RouterModuleInterface;
use Thruway\Peer\Client;
use Thruway\Peer\RouterInterface;
use Thruway\Transport\TransportInterface;
use Thruway\WampErrorException;

class InternalClient extends Client
{
    protected $container;
    protected $em;
    protected $connections;
    private $activeTopics = []; // topic equal to chat
    private $chatUiidToIdMapping = [];
    private $userIdToUserTopicId = [];

    /**
     * InternalClient constructor.
     * @param ContainerInterface $container
     * @param EntityManagerInterface $em
     * @param $realm
     * @param $loop
     */
    public function __construct(ContainerInterface $container, EntityManagerInterface $em, $realm, $loop)
    {
        $this->container = $container;
        $this->em = $em;

        parent::__construct($realm, $loop);
    }

    /**
     * @param \Thruway\ClientSession $session
     * @param TransportInterface $transport
     * @throws \ZMQSocketException
     */
    public function onSessionStart($session, $transport)
    {
        // TODO: now that the session has started, setup the stuff
        echo "--------------- Hello from InternalClient ------------\n";
        $context = new Context($this->getLoop());
        $pull    = $context->getSocket(\ZMQ::SOCKET_PULL);
        $pull->bind('tcp://127.0.0.1:5555');

//        $this->on('publish', [$this, 'incomeMessage']);
        //$this->on('message', [$this, 'message']);
        $session->register('getUserTopic', [$this, 'getUserTopic']);
        $session->register('createActiveChat', [$this, 'createActiveChat']);
        $session->register('getMainUserTopicInfo', [$this, 'getMainUserTopicInfo']);
        $session->register('getNewAndUpdatedChats', [$this, 'getNewAndUpdatedChats']);
        $session->subscribe('wamp.metaevent.session.on_leave', [$this, 'onSessionLeave']);

    }

    /**
     * @param $args
     * @return array
     * @throws WampErrorException
     */
    public function onSessionLeave($args, $kwargs):array
    {
        Logger::debug($this, '-------------On leave event' . implode(',' , $kwargs));


    }/**
     * @param $args
     * @return array
     * @throws WampErrorException
     */
    public function createActiveChat($args, $kwargs):array
    {
        if(isset($kwargs->userToken)){
            //1st app message with connected user
            $user = $this->getAuthenticatedUser($kwargs->userToken);

            $chat = new Chat();
            $chat->setUser($user);
            $chat->setStrategy(Chat::STRATEGY_INTERNAL_CHAT);
            $this->em->persist($chat);
            $this->em->flush($chat);
            $this->getSession()->subscribe($chat->getUuid(), [$this, 'incomeMessage']);
            $this->chatUiidToIdMapping[$chat->getUuid()] = $chat->getId(); // TODO: Remove mapping element in onCLose and onUnsubscribe events
            $this->activeTopics[] = $chat->getUuid();
            //if user online
            if(isset($this->userIdToUserTopicId[$kwargs->calleeId])){
                $this->getSession()->call('connectToNewChat', [], ['chatUuid' => $chat->getUuid()], ['exclude_me' => true]);
//                $this->getSession()->publish($this->userIdToUserTopicId[$kwargs->calleeId],
//                    ['type' => 'income_chat', 'chat_uuid' => $chat->getUuid()],
//                    ['exclude_me' => true]
//                );
            } else {
                Logger::info($this, '-------------Chat to offline user');
            }

        } else {
            //error
            Logger::info($this, '-------------External clients tries to create a chat');///TODO: add support for external client
        }

        return ['chatUuid' => $chat->getUuid()];
    }

    /**
     * Subscribes client to main personal user topic.
     * THis topic handels income messages, info about online users and so on
     *
     * @param $args
     * @return array
     * @throws \Exception
     */
    public function getUserTopic($args, $kwargs):array
    {
        if(isset($kwargs->userToken)){
            //1st app message with connected user

            $user = $this->getAuthenticatedUser($kwargs->userToken);

            $userTopicId = uniqid($user->getId());
            $this->userIdToUserTopicId[$user->getId()] = $userTopicId;
            $this->getSession()->subscribe($userTopicId, [$this, 'allEvents']);

        } else {
            //error
            Logger::info($this, '-------------External clients tries to connect to main user topic');///TODO: add support for external client
        }

        return ['user_topic_id' => $userTopicId];
    }

    /**
     * Handle get PHP version
     *
     * @return array
     */
    public function incomeMessage($args, $kwargs = [])
    {
        $this->validateMessage($kwargs);
        $chatRepo = $this->em->getRepository(Chat::class);
        $messageRepo = $this->em->getRepository(Message::class);
        $activeChat = $chatRepo->findChatByUuid($kwargs->openedChatId);
        $sender = $this->getAuthenticatedUser($kwargs->userToken);
        $message = new Message();
        $message->setChat($activeChat);
        $message->setUser($sender);
        $message->setText($kwargs->message);
        $chatUsers = [$activeChat->getUserId(), $activeChat->getOwnerId()];
        $chatUsers = array_flip($chatUsers);
        unset($chatUsers[$sender->getId()]);
        reset($chatUsers); //now chatUsers contain only one element with calleeId
        if(isset($this->userIdToUserTopicId[$chatUsers])) {
            $message->setIsRead(true);
        } else {
            $message->setIsRead(false);
        }
        $message->setOrdering($messageRepo->getMaxOrderForChat($activeChat) + 1);
        $this->em->persist($message);
        $this->em->flush($message);

        return ['messageId' => $message->getId(), 'message' => $kwargs->message, 'sender' => $sender];
    }

    /**
     * @param $args
     * @param array $kwargs
     * @param array $details
     * @return array
     * @throws WampErrorException
     */
    public function getMainUserTopicInfo($args, $kwargs = [], $details = [])
    {
        return [];
    }

    /**
     * @param $args
     * @param array $kwargs
     * @param array $details
     * @return mixed
     * @throws WampErrorException
     */
    public function getNewAndUpdatedChats($args, $kwargs = [], $details = [])
    {
        try {
            $user = $this->getAuthenticatedUser($kwargs->userToken);
            $chatRepo = $this->em->getRepository(Chat::class);
            $page = (isset($kwargs->page)) ? $kwargs->page : 1;
            $chats = $chatRepo->getNewAndUpdatedChats($user, true, $page);
        } catch (\Exception $e){
            var_dump($e->getTraceAsString());exit;
            Logger::error($this, $e->getMessage(), $e->getTrace());

        }
        return $chats;
    }

    /**
     * @param $args
     * @param array $kwargs
     * @param array $details
     * @return mixed
     * @throws WampErrorException
     */
    public function getChats($args, $kwargs = [], $details = [])
    {
        $user = $this->getAuthenticatedUser($kwargs->userToken);
        $chatRepo = $this->em->getRepository(Chat::class);
        $chats = $chatRepo->getNewAndUpdatedChats($user);

        return $chats;
    }


    /**
     * Handle get PHP version
     *
     * @return array
     */
    public function allEvents($args, $kwargs, $details, $publicationId)
    {
        $value = isset($args[0]) ? $args[0] : '';
        echo '---------------  Received ' . json_encode($value) . ' on topic ' . PHP_EOL;
        Logger::debug($this, '--------------------------all events' );
        return 'aaaalll';
    }

    /**
     * @param string $token
     * @return User
     * @throws WampErrorException
     */
    private function getAuthenticatedUser(string $token): User
    {
        echo "Check user credentials\n";
        //get credentials
        $jwt_encoder = $this->container->get('lexik_jwt_authentication.encoder');
        try{
            $payload = $jwt_encoder->decode($token);
        } catch (\Exception $e){
            $this->getSession()->close($e->getMessage());
            throw new WampErrorException('user.authentication.error', [$e->getMessage()]);
        }

        $user = $this->getUserByEmail($payload['email']);
        if(!$user){
            $this->getSession()->close();
            throw new WampErrorException('user.authentication.error', ['Can\'t find a user for this token']);
        }

        return $user;
    }
    /**
     * Get user from email credential
     *
     * @param $email
     * @return false|User
     * @throws Exception
     */
    private function getUserByEmail($email)
    {
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
            return false;
        }

        $repo = $this->em->getRepository(User::class);

        $user = $repo->findOneBy(array('email' => $email));

        if($user && $user instanceof User){
            return $user;
        } else {
            return false;
        }

    }

    /**
     * Checks whether $kwargs object has required properties or does not
     *
     * @param object $kwargs
     * @param array $requiredProperties
     * @throws WampErrorException
     */
    private function validateMessage(object $kwargs, $requiredProperties = ['openedChatId', 'userToken', 'message'])
    {
        foreach ($requiredProperties as $property){
            if(!property_exists($kwargs, $property)){
                throw new WampErrorException('message.invalid_arguments', [$property . ' is required']);
            }
        }
    }

}