<?php
namespace App\Websocket;


use React\ZMQ\Context;
use Thruway\Peer\Client;

class Pusher extends Client
{
    public function onSessionStart($session, $transport)
    {
        $context = new Context($this->getLoop());
        $pull    = $context->getSocket(\ZMQ::SOCKET_PULL);
        $pull->bind('tcp://127.0.0.1:9371');
        $pull->on('publish', [$this, 'publish']);
        $pull->on('subscribe', [$this, 'onSubscribe']);
        $pull->on('call', [$this, 'createChat']);
        $pull->on('message', [$this, 'message']);

    }


    /**
     * @param string JSON'ified string we'll receive from ZeroMQ
     */
    public function publish($entry)
    {
        $entryData = json_decode($entry, true);
        echo 'publish1111111111111111111111111111111111111111111111111111111111';
        $this->getSession()->publish('chat1', ['method' => 'onPublish', 'data' => [$entryData,'dummy']]);
    }

    /**
     * @param string JSON'ified string we'll receive from ZeroMQ
     */
    public function message($entry)
    {
        $entryData = json_decode($entry, true);
        echo 'publish1111111111111111111111111111111111111111111111111111111111';
        $this->getSession()->publish('chat1', ['method' => 'onPublish', 'data' => [$entryData,'dummy']]);
    }

    /**
     * @param string JSON'ified string we'll receive from ZeroMQ
     */
    public function onSubscribe($entry)
    {
        $entryData = json_decode($entry, true);



        $this->getSession()->publish('chat1', ['method' => 'onSubscribe', 'data' => $entryData]);
    }


    /**
     * @param string JSON'ified string we'll receive from ZeroMQ
     */
    public function createChat($entry)
    {
        $entryData = json_decode($entry, true);


        $this->getSession()->publish('chat1', ['method' => 'onCreateChat', 'data' => $entryData]);
    }

}