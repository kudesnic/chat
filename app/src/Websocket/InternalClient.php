<?php
namespace App\Websocket;


use App\Entity\Message;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\ZMQ\Context;
use Thruway\Logging\Logger;
use Thruway\Message\PublishMessage;
use Thruway\Module\RouterModuleInterface;
use Thruway\Peer\Client;
use Thruway\Peer\RouterInterface;
use Thruway\Transport\TransportInterface;

class InternalClient extends Client
{

    /**
     * @param \Thruway\ClientSession $session
     * @param \Thruway\Transport\TransportInterface $transport
     */
    public function onSessionStart($session, $transport)
    {
        // TODO: now that the session has started, setup the stuff
        echo "--------------- Hello from InternalClient ------------\n";
        $loop   = Factory::create();
        $context = new Context($loop);
        $pull    = $context->getSocket(\ZMQ::SOCKET_PULL);
        $pull->bind('tcp://127.0.0.1:5555');



        $session->subscribe('chat1', [$this, 'allEvents'], [ 'match' => 'prefix' ]);
        $this->on('publish', [$this, 'message']);
        //$this->on('message', [$this, 'message']);
        $session->register('create_chat', [$this, 'createChat']);

    }

    /**
     * Handle get PHP version
     *
     * @return array
     */
    public function createChat()
    {
//        return $chat->getId();
        return 'chat_uid';
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
        var_dump($details);
        Logger::debug($this, '--------------------------all events' );
        return 'aaaalll';
    }

    public function onMessage(TransportInterface $transport, \Thruway\Message\Message $msg)
    {
var_dump($msg);
        Logger::debug($this, "--------------------------------------Client onMessage: {$msg}");
        return parent::onMessage($transport, $msg);
    }

}