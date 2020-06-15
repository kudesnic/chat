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

class MessageHandler implements WampServerInterface
{

    protected $container;
    protected $em;
    protected $connections;

    public function __construct(ContainerInterface $container, EntityManagerInterface $em)
    {
        $this->container = $container;
        $this->em = $em;
        $this->connections = new SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        throw new Exception("userId or clientId must to be passed! self::class->saveNewMessage()", 500);

        $this->connections->attach($conn);
        $conn->send('..:: Websocket chat started ::..');
        echo "New connection \n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $messageData = json_decode(trim($msg));
        if(isset($messageData['userToken'])){
            //1st app message with connected user

            //a user auth, else, app sending message auth
            echo "Check user credentials\n";
            //get credentials
            $jwt_manager = $this->container->get('lexik_jwt_authentication.jwt_manager');
            $token = new JWTUserToken();
            $token->setRawToken($messageData['userToken']);
            $payload = $jwt_manager->decode($token);

            //getUser by email
            if(!$user = $this->getUserByEmail($payload['email'])){
                $from->close();
            }
            $this->setChat($messageData, $user);
            echo "User found : " . $user->getFirstname() . "\n";
            foreach($this->connections as $connection)
            {
                if($connection === $from)
                {
                    continue;
                }

                $connection->send($msg);
            }

            $this->saveNewMessage($messageData, $user);

        } else {
            //error
            $from->send("client message!");
        }

    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->connections->detach($conn);
    }

    public function onError(ConnectionInterface $conn, Exception $e)
    {
        $this->connections->detach($conn);
        $conn->close();
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
}