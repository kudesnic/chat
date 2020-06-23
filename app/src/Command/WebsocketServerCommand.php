<?php
namespace App\Command;

use App\Websocket\MessageHandler2;
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
use \ZMQContext;
use \ZMQ;

class WebsocketServerCommand extends Command
{
    protected static $defaultName = "run:websocket-server";

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
            ->setName('run:websocket-server')
            ->setDescription('Start the websocket server.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
       $this->startServer($output);
    }


    /**
     * Creates and activates a WAMP server
     */
    public function startServer(OutputInterface $output)
    {


        if (empty($this->ioserver)) {
            $output->writeln( 'Server starts on ' . $this->bindIp . ':' . $this->bindPort);
            // An event loop
            $this->loop = Factory::create();

            // An object that will handle the WampServer events through its methods
            $pusher = new MessageHandler2($this->container, $this->em);

//          ZMQ is needed only in case if we want send info to sockets from another php endpoint or script
//            // Listen for the web server to make a ZeroMQ push after an ajax request
//            $context = new Context($this->loop);
//            $pull = $context->getSocket(ZMQ::SOCKET_PULL);
//            $pull->bind('tcp://127.0.0.1:5555'); // Binding to 127.0.0.1 means the only client that can connect is itself
//            $pull->on('message', array($pusher, 'onBlogEntry'));

            // Set up our WebSocket server for clients wanting real-time updates
            $webSock = new Server($this->bindIp . ':' . $this->bindPort, $this->loop); // remote clients can connect to bindPort
            $this->ioserver = new IoServer(
                new HttpServer(
                    new WsServer(
                        new WampServer(
                            $pusher
                        )
                    )
                ),
                $webSock,
                $this->loop
            );

//            $this->loop->run();

            // Add a timer to server's event loop
            $this->loop->addPeriodicTimer(30, function () use ($pusher) {
                $data = array(
                    'topic_id' => 'newsTopic',
                    'about' => 'news',
                    'title' => 'rock 2',
                    'subscribers' => 'dummy data',
                    'when' => date('H:i:s')
                );
                $message = json_encode($data);
                $pusher->onMessageToPush($message);
            });
            $this->ioserver->run();  // Equals to $loop->run();
            $output->writeln( 'Server started');

        } else {
            $output->writeln('Server already started');
        }

    }

    /**
     * The function we want to be executed when the last subscriber unsubscribes
     */
    public function stopServerCallback()
    {
        $this->loop->stop();
    }
}