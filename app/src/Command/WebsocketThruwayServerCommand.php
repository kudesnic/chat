<?php
namespace App\Command;

use App\Websocket\InternalClient;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Logger;
use React\EventLoop\Factory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Thruway\Authentication\AuthenticationManager;
use Thruway\Authentication\WampCraAuthProvider;
use Thruway\Peer\Router;
use Thruway\Realm;
use Thruway\Transport\RatchetTransportProvider;

class WebsocketThruwayServerCommand extends Command
{
    protected static $defaultName = "run:thruway-websocket-server";

    protected $loop;
    protected $timer = 'off';

    private $container;
    private $em;
    private $paramsBag;

    private $realm = 'realm1';
    private $bindIp = '0.0.0.0'; //Binding to 0.0.0.0 means remotes can connect
    private $bindPort = 3001;

    public function __construct(ContainerInterface $container, EntityManagerInterface $em, ContainerBagInterface $paramsBag)
    {
        $this->container = $container;
        $this->em = $em;
        $this->paramsBag = $paramsBag;
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
        $loop   = Factory::create();
//        $logger = new Logger('WAMP2');
//        $logger->pushHandler(new \Monolog\Handler\StreamHandler($this->paramsBag->get('wamp_log_path'), Logger::ERROR));
//        \Thruway\Logging\Logger::set($logger);
        $pusher = new InternalClient($this->container, $this->em, $this->realm, $loop);
        $router = new Router($loop);
        $router->addInternalClient($pusher);
        $router->addTransportProvider(new RatchetTransportProvider($this->bindIp, $this->bindPort));

        $router->start();
    }

}