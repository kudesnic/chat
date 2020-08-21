<?php
namespace App\Websocket;

use App\Entity\Chat;
use App\Entity\Message;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use React\ZMQ\Context;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Thruway\Authentication\WampCraUserDbInterface;
use Thruway\Common\Utils;
use Thruway\Logging\Logger;
use Thruway\Message\UnsubscribeMessage;
use Thruway\Peer\Client;
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
    private $userIdToActiveTopicUuid = [];

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
        $session->register('setActiveChat', [$this, 'setActiveChat']);
        $session->register('getMainUserTopicInfo', [$this, 'getMainUserTopicInfo']);
        $session->register('getNewAndUpdatedChats', [$this, 'getNewAndUpdatedChats']);
        $session->subscribe('wamp.metaevent.session.on_leave', [$this, 'onSessionLeave']);
        $session->subscribe('wamp.metaevent.session.on_join', [$this, 'onSessionJoin']);

    }

    /**
     * @param $args
     * @return array
     * @throws WampErrorException
     */
    public function onSessionLeave($args, $kwargs)
    {
        Logger::debug($this, '-------------On leave event' );

    }

    /**
     * @param $args
     * @return array
     * @throws WampErrorException
     */
    public function onSessionJoin($args, $kwargs)
    {
        Logger::debug($this, '-------------On JOIN event');
    }


    /**
     * @param $args
     * @return array
     * @throws WampErrorException
     */
    public function createActiveChat($args, $kwargs):array
    {
        if(isset($kwargs->userToken)){
            //1st app message with connected user
            $owner = $this->getAuthenticatedUser($kwargs->userToken);
            $userRepo = $this->em->getRepository(User::class);
            $user = $userRepo->find($kwargs->calleeId);

            if($user->getTreeRoot() != $owner->getTreeRoot()){
                throw new WampErrorException('chat.validation.error', ['Callee is not in the same users tree']);
            }

            $chat = new Chat();
            $chat->setOwner($owner);
            $chat->setUser($user);
            $chat->setStrategy(Chat::STRATEGY_INTERNAL_CHAT);
            $this->em->persist($chat);
            $this->em->flush($chat);
            $this->getSession()->subscribe($chat->getUuid(), [$this, 'incomeMessage']);
            $this->chatUiidToIdMapping[$chat->getUuid()] = $chat->getId(); // TODO: Remove mapping element in onCLose and onUnsubscribe events
            $this->userIdToActiveTopicUuid[$owner->getId()] = $chat->getUuid();
//            $this->userIdToActiveTopicUuid[$user->getId()] = $chat->getUuid();
            $this->activeTopics[] = $chat->getUuid();
            //if user online
            if($this->checkIfUserOnline($kwargs->calleeId)){
                Logger::info($this, '-------------Initiated chat with online user. callee user chat ==' . $this->userIdToUserTopicId[$kwargs->calleeId]);///TODO: add support for external client
                $this->getSession()->publish($this->userIdToUserTopicId[$kwargs->calleeId],
                    [],
                    ['type' => 'income_chat', 'chatUuid' => $chat->getUuid()]
                );
            } else {
                Logger::info($this, '-------------Chat to offline user');
            }

        } else {
            //error
            Logger::info($this, '-------------External clients tries to create a chat');///TODO: add support for external client
        }

        return ['chatUuid' => $chat->getUuid()];
    }

    public function setActiveChat($args, $kwargs):array
    {
        if(isset($kwargs->userToken)){
            //1st app message with connected user
            $owner = $this->getAuthenticatedUser($kwargs->userToken);
            $userRepo = $this->em->getRepository(User::class);
            $chatRepo = $this->em->getRepository(Chat::class);
            $callee = $userRepo->find($kwargs->calleeId);

            if($callee->getTreeRoot() != $owner->getTreeRoot()){
                throw new WampErrorException('chat.validation.error', ['Callee is not in the same users tree']);
            }
            $chat = $chatRepo->findChatByUuid($kwargs->chatUuid, $callee);
            $this->getSession()->subscribe($chat->getUuid(), [$this, 'incomeMessage']);
            $this->chatUiidToIdMapping[$chat->getUuid()] = $chat->getId(); // TODO: Remove mapping element in onCLose and onUnsubscribe events

            if($this->userIdToActiveTopicUuid[$callee->getId()]){
                $subscriptionId = $this
                    ->getMatcher()
                    ->matches($this->userIdToActiveTopicUuid[$callee->getId()], $this->getUri(), []);
                $this->getSession()->sendMessage(new UnsubscribeMessage(Utils::getUniqueId(), $subscriptionId));
                Logger::info($this, '-------------Unsubscribe');

//                unsubscribe somehow
            }

            $this->userIdToActiveTopicUuid[$callee->getId()] = $chat->getUuid();
            $this->userIdToActiveTopicUuid[$owner->getId()] = $chat->getUuid();
            $this->activeTopics[] = $chat->getUuid();
            //if user online
            if(isset($this->userIdToUserTopicId[$kwargs->calleeId])){
//                $this->getSession()->call('connectToNewChat', [], ['chatUuid' => $chat->getUuid()], ['exclude_me' => true]);
                $this->getSession()->publish($this->userIdToUserTopicId[$kwargs->calleeId],
                    ['type' => 'income_chat', 'chatUuid' => $chat->getUuid()],
                    ['exclude_me' => true]
                );
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
        try{

        Logger::info($this, '-------------Income message processing');

        $this->validateMessage($kwargs);
        $chatRepo = $this->em->getRepository(Chat::class);
        $messageRepo = $this->em->getRepository(Message::class);
        $sender = $this->getAuthenticatedUser($kwargs->userToken);
        $activeChat = $chatRepo->findChatByUuid($kwargs->openedChatUuid, $sender);

        $message = new Message();
        $message->setChat($activeChat);
        $message->setUser($sender);
        $message->setText($kwargs->message);
        $chatUsers = [$activeChat->getUser()->getId(), $activeChat->getOwner()->getId()];
        unset($chatUsers[array_keys($chatUsers, $sender->getId())[0]]);
        $calleeId = reset($chatUsers); //now chatUsers contain only one element with calleeId
        Logger::info($this, '--------------------------' . $calleeId);
        if($this->checkIfUserOnline($calleeId)) {
            $message->setIsRead(true);
        } else {
            $message->setIsRead(false);
        }
        $message->setOrdering($messageRepo->getMaxOrderForChat($activeChat) + 1);
        $this->em->persist($message);
        $this->em->flush($message);
        }
            catch (\Exception $exception){
                var_dump($exception->getMessage(), $exception->getTraceAsString());exit;
            }
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
            var_dump($e->getMessage(), $e->getTraceAsString());exit;
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
    private function validateMessage(object $kwargs, $requiredProperties = ['openedChatUuid', 'userToken', 'message'])
    {
        foreach ($requiredProperties as $property){
            if(!property_exists($kwargs, $property)){
                throw new WampErrorException('message.invalid_arguments', [$property . ' is required']);
            }
        }
    }

    private function checkIfUserOnline(int $userId)
    {
        $isOnline = false;
        $isOnline = $this->session->call('checkIfOnline', [])->then(
            function ($res) use ($isOnline, $userId){
                if($res->user_id == $userId){
                    $isOnline = true;
                }

                return $isOnline;
            }
        );
        var_dump('--------is online', $isOnline); exit;
        if(!$isOnline){
            if(isset($this->userIdToUserTopicId[$userId])){
                unset($this->userIdToUserTopicId[$userId]);
            }
            if(isset($this->userIdToActiveTopicUuid[$userId])){
                $activeChatUuid = $this->userIdToActiveTopicUuid[$userId];
                unset($this->chatUiidToIdMapping[$activeChatUuid]);
                unset($this->userIdToActiveTopicUuid[$userId]);
            }

        }

        return $isOnline;
    }

}