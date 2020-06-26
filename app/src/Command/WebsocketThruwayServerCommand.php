<?php
namespace App\Command;

use App\Websocket\MessageHandler2;
use App\Websocket\Pusher;
use Doctrine\ORM\EntityManagerInterface;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\Wamp\WampServer;
use App\Websocket\MessageHandler;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory;
use React\Socket\SecureServer;
use React\Socket\Server;
use React\ZMQ\Context;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Thruway\Peer\Client;
use Thruway\Peer\Router;
use Thruway\Transport\RatchetTransportProvider;
use \ZMQContext;
use \ZMQ;

class WebsocketThruwayServerCommand extends Command
{
    protected static $defaultName = "run:thruway-websocket-server";

    private $container;
    private $em;

    private $bindIp;
    private $bindPort;

    private $ioserver = null;
    private $wampServer = null;
    protected $loop;
    protected $timer = 'off';

    public function __construct(ContainerInterface $container, EntityManagerInterface $em)
    {
        $this->container = $container;
        $this->em = $em;
        $this->bindPort = 3001;
        $this->bindIp = '0.0.0.0'; // Binding to 0.0.0.0 means remotes can connect
        parent::__construct();
    }

    /**
     * Configure a new Command Line
     */
    protected function configure()
    {
        $this
            ->setName('run:thruway-websocket-server')
            ->setDescription('Start the websocket server.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
       $this->startServer();
    }


    /**
     * Creates and activates a WAMP server
     */
    public function startServer()
    {

        $loop   = Factory::create();
        $pusher = new Pusher("realm1", $loop);



        $router = new Router($loop);
        $router->addInternalClient($pusher);
        $router->addTransportProvider(new RatchetTransportProvider("0.0.0.0", 3001));
        $router->start();

    }


}