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
    /**
     * Topics internal client subscribed for
     * @var \SplObjectStorage
     */
    private $activeTopics = []; // topic equal to chat
    private $chatUiidToIdMapping = [];
    private $userIdToUserTopicId = [];

    public function __construct(ContainerInterface $container, EntityManagerInterface $em, $realm, $loop)
    {
        $this->container = $container;
        $this->em = $em;
        $this->activeTopics = new \SplObjectStorage();


        parent::__construct($realm, $loop);
    }

    /**
     * @param \Thruway\ClientSession $session
     * @param \Thruway\Transport\TransportInterface $transport
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
            $user = $this->getAuthenticatedUser($kwargs->userToken);

            $chat = new Chat();
            $chat->setUser($user);
            $chat->setStrategy(Chat::STRATEGY_INTERNAL_CHAT);
            $this->em->persist($chat);
            $this->em->flush($chat);
            $this->getSession()->subscribe($chat->getUuid(), [$this, 'incomeMessage']);
            $this->chatUiidToIdMapping[$chat->getUuid()] = $chat->getId(); // TODO: Remove mapping element in onCLose and onUnsubscribe events
//            $this->getCaller()->call($this->getSession(), 'connectToNewChat', ['chat_uiid' => $chat->getUuid()]);
            //if user online
            if(isset($this->userIdToUserTopicId[$kwargs->callee_id])){
                $this->getSession()->publish($this->userIdToUserTopicId[$kwargs->callee_id],
                    ['type' => 'income_chat', 'chat_uuid' => $chat->getUuid()],
                    ['exclude_me' => true]
                );
            }

        } else {
            //error
            Logger::info($this, '-------------External clients tries to create a chat');///TODO: add support for external client
        }

        return ['chat_uuid' => $chat->getUuid()];
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
     * Handle get PHP version
     *
     * @return array
     */
    public function incomeMessage($args, $kwargs = [], $details = [], $publicationId=null)
    {
        $chatRepo = $this->em->getRepository(Chat::class);
        $message = new Message();
        $message->setChat($chatRepo->findChatByUuid($kwargs->activeChatUuid)->getId());
        $message->setUser($this->getAuthenticatedUser($kwargs->userToken));
        $message->setText($kwargs->message);
        Logger::debug($this, '--------------------------Income message to active chat');
//       $this->validateMessage(); message must have sender, receiver, active chat

        return 'message';
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
     * Get user from email credential
     *
     * @param $email
     * @return false|User
     * @throws Exception
     */
    protected function getUserByEmail($email)
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

}