<?php

namespace OM\APIBundle\EventListener;

use OM\APIBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller as Ctrlr;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\Bundle\DoctrineBundle\DataCollector\DoctrineDataCollector;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\HttpKernel\Profiler\Profiler;

class ProfileListener
{
    /** @var ContainerInterface $container */
    protected $container;
    /** @var DebugStack $logger */
    protected $logger;
    protected $start;


    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->logger = new DebugStack();
        $this->start = microtime(1);
    }

    public function onRequest(Event\GetResponseEvent $event)
    {
        $this->container
            ->get('doctrine')
            ->getConnection()
            ->getConfiguration()
            ->setSQLLogger($this->logger);
    }

    public function onController(Event\FilterControllerEvent $event)
    {
    }

    public function onView(Event\GetResponseForControllerResultEvent $event)
    {
        $result = $event->getControllerResult();
        $request = $event->getRequest();
        $result = array(
          'result' => $result
        );
        $event->setControllerResult($result);
    }

    public function onResponse(Event\FilterResponseEvent $event)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();
        $content = json_decode($response->getContent(), 1);
        $content['db_count'] = count($this->logger->queries);
        $content['db_queries'] = $this->logger->queries;
        $content['time'] = microtime(1) - $this->start;
        $response->setContent(json_encode($content));
        $event->setResponse($response);
    }

    public function onFinishRequest(Event\FinishRequestEvent $event)
    {
    }

    public function onTerminate(Event\PostResponseEvent $event)
    {

    }
    public function onException(Event\GetResponseForExceptionEvent $event)
    {

    }
}
