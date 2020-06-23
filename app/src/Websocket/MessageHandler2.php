<?php
namespace App\Websocket;

use App\Entity\Chat;
use App\Entity\Message;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Authentication\Token\JWTUserToken;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Ratchet\Wamp\WampServerInterface;
use SplObjectStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class MessageHandler2 implements WampServerInterface
{

    protected $container;
    protected $em;
    protected $totalSubscribers = 0;
    protected $stopCallback;
    protected $topicSubscribers = [];

    public function __construct(ContainerInterface $container, EntityManagerInterface $em)
    {
        $this->container = $container;
        $this->em = $em;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        throw new Exception("userId or clientId must to be passed! self::class->saveNewMessage()", 500);

        $conn->send('..:: Websocket chat started ::..');
        echo "New connection \n";
    }

    public function onPublish(ConnectionInterface $conn, $topic, $event, array $exclude, array $eligible) {
        $topic->broadcast('on publish');
        $topic->broadcast($event);
    }

    /**
     * To be called by clients to subscribe for a topic
     *
     * @param ConnectionInterface $conn
     * @param Topic $topic
     */
    public function onSubscribe(ConnectionInterface $conn, $topic) {

        // Update the number of total subscribers
        $this->totalSubscribers++;
        $topic->broadcast('on subscribe');


        if( isset($this->topicSubscribers[$topic->getId()]) &&  count($this->topicSubscribers[$topic->getId()]) > 2){
        }
        // Add the new subscriber to the list of this topic's subscribers
        $this->topicSubscribers[$topic->getId()] = $topic;

        // Inform the subscribers of this topic about the number of total subscribers
        $messageData = array(
            'about' => 'subscribers',
            'subscribers' => $this->totalSubscribers,
            'when'     => date('H:i:s')
        );
        $topic->broadcast($messageData);

    }

    public function onUnSubscribe(ConnectionInterface $conn, $topic) {
        $topic->broadcast('on unsubscribe');
    }

    /**
     * Executes when a client has closed its connection
     *
     * @param ConnectionInterface $conn
     */
    public function onClose(ConnectionInterface $conn) {
        $conn->send('connection closed');
    }
    /**
     * Used when a client sends data
     *
     * @param ConnectionInterface $conn
     * @param string $id
     * @param Topic $topic
     * @param array $params
     */
    public function onCall(ConnectionInterface $conn, $id, $topic, array $params) {
        $topic->broadcast('on call');
        switch ($topic) {
            case 'createInternalChat':
                //a user auth, else, app sending message auth
                echo "Check user credentials\n";
                //get credentials
                $jwt_manager = $this->container->get('lexik_jwt_authentication.jwt_manager');
                $token = new JWTUserToken();
                $token->setRawToken($params['userToken']);
                $payload = $jwt_manager->decode($token);

                $user = $this->getUserByEmail($payload['email']);
                $chat = new Chat();
                $chat->setUser($user);
                $this->em->persist($chat);
                $this->em->flush($chat);
                if (!$chat->getId()) {
                    return $conn->callError($id, 'Chat is not saved!');
                } else {
                    $this->chats[$chat->getId()] = new \SplObjectStorage;
                    $this->chatsLookup[$topic] = $chat->getId();
                }

                break;
            case 'createClientChat':

                break;
            default:
                return $conn->callError($id, 'Unknown call');
                break;
        }
        // In this application if clients send data it's because the user hacked around in console
        $conn->callError($id, $topic, 'You are not allowed to make calls')->close();
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        $conn->send('on error');
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

    /**
     * @param array $messageData
     * @param UserInterface|null $user - user that sent a message
     * @param null $client - client that sent a message
     * @throws Exception
     * @return Message
     */
    protected function saveNewMessage(array $messageData, ?UserInterface $user = null, $client = null)
    {
        if(!$user && !$client){
            throw new Exception('user or client must to be passed! ' . self::class . ' ->saveNewMessage()', 500);
        }

        $message = new Message();
        $message->setChat($this->chat);
        $message->setText($messageData['text']);
        $message->setUser($user);
//      $message->setClient($client);
        if(isset($messageData['parent_message_id'])){
            $repo = $this->em->getRepository(Message::class);
            $parentMessage = $repo->findOneBy(['chat_id' => $this->chat->getId(), 'id' => $messageData['parent_id']]);
            if(!$parentMessage){
                throw new Exception('Parent message not found', 500);
            } else {
                $message->setParent($parentMessage);
            }
        }
        $this->em->persist($message);
        $this->em->flush($message);

        return $message;
    }

    private function setChat(array $messageData, ConnectionInterface $conn, ?UserInterface $user = null)
    {
        $chat = null;
        if(isset($messageData['chat_id']) && $user){
            $repo = $this->em->getRepository(Message::class);
            $participatedIdChat = $repo->findOneBy(['chat_id' => $messageData['chat_id'], 'user_id' => $user->getId()]);
            if(!$participatedIdChat){
                $conn->send('..:: User hasn\'t participated in this chat ::..');
                return false;
            }
            $this->chat = $participatedIdChat->getChat();
        } else {
            $chat = new Chat();
            $chat->setUser($user);
            $chat->setUser($user);
        }
    }
    /**
     * Publish a new message to a topic's subscribers. The topic name is
     * included in the message itself. In this application, we call this method
     * periodically through the periodic timer that we have added to the loop.
     *
     * @param string $message
     */
    public function onMessageToPush($message){

        $messageData = json_decode($message, true);

        // If the lookup topic object isn't set there is no one to publish to
        if (!array_key_exists($messageData['topic_id'], $this->topicSubscribers)) {
            return;
        }

        $topic = $this->topicSubscribers[$messageData['topic_id']];

        // re-send the data to all the clients subscribed to that topic
        $topic->broadcast($messageData);

    }
}