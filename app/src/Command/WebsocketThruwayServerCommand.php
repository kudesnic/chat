<?php
namespace App\Command;

use App\Websocket\InternalClient;
use Doctrine\ORM\EntityManagerInterface;
use React\EventLoop\Factory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Thruway\Peer\Router;
use Thruway\Transport\RatchetTransportProvider;

class WebsocketThruwayServerCommand extends Command
{
    protected static $defaultName = "run:thruway-websocket-server";

    protected $loop;
    protected $timer = 'off';

    private $container;
    private $em;

    private $realm = 'realm1';
    private $bindIp = '0.0.0.0'; //Binding to 0.0.0.0 means remotes can connect
    private $bindPort = 3001;

    public function __construct(ContainerInterface $container, EntityManagerInterface $em)
    {
        $this->container = $container;
        $this->em = $em;
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
        $pusher = new InternalClient($this->container, $this->em, $this->realm, $loop);
        $router = new Router($loop);
        $router->addInternalClient($pusher);
        $router->addTransportProvider(new RatchetTransportProvider($this->bindIp, $this->bindPort));
        $router->start();
    }

}