<?php
namespace App\Websocket;


use App\Entity\Chat;
use App\Entity\Message;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Authentication\Token\JWTUserToken;
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

    public function __construct(ContainerInterface $container, EntityManagerInterface $em, $realm, $loop)
    {
        $this->container = $container;
        $this->em = $em;
        $this->activeTopics = new \SplObjectStorage();
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

        $this->on('publish', [$this, 'message']);
        //$this->on('message', [$this, 'message']);
        $session->register('createChat', [$this, 'createChat']);

    }

    /**
     * Handle get PHP version
     *
     * @return Chat
     */
    public function createChat($args):Chat
    {
        $args = json_decode($args, true);
        if(isset($args['userToken'])){
            //1st app message with connected user

            //a user auth, else, app sending message auth
            echo "Check user credentials\n";
            //get credentials
            $jwt_manager = $this->container->get('lexik_jwt_authentication.jwt_manager');
            $token = new JWTUserToken();
            $token->setRawToken($args['userToken']);
            $payload = $jwt_manager->decode($token);

            //getUser by email
            if(!$user = $this->getUserByEmail($payload['email'])){
                $this->getSession()->close();
            }
            $chat = new Chat();
            $chat->setUser($user);
            $this->em->persist($chat);
            $this->em->flush($chat);
            $this->getSession()->subscribe($chat->getUuid(), [$this, 'allEvents']);
            $this->chatUiidToIdMapping[$chat->getUuid()] = $chat->getId(); // TODO: Remove mapping element in onCLose and onUnsubscribe events
            $this->getCaller()->call($this->getSession(), 'connectToNewChat', ['chat_uiid' => $chat->getUuid()]);
        } else {
            //error
            Logger::info($this, '-------------External clients tries to create a chat');///TODO: add support for external client
        }

        return $chat;
    }

    /**
     * Handle get PHP version
     *
     * @return array
     */
    public function message($args, $argsKw=[], $details=[], $publicationId=null)
    {
        Logger::debug($this, '--------------------------message event');
        $this->getSession()->publish('chat1', ['exclude_me' => true]);

        return 'message';
    }


    /**
     * Handle get PHP version
     *
     * @return array
     */
    public function allEvents($args, $argsKw, $details, $publicationId)
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