<?php

namespace OM\APIBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Psr\Log\LoggerInterface;

class ExceptionListener
{
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function onException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();
        $message = json_encode(
            array(
                'status' => $exception->getCode(),
                'message' => $exception->getMessage(),
                'timestamp' => time()
            )
        );

        $response = new Response();
        $response->setContent($message);
        $response->headers->set('Content-Type', 'application/json');

        if ($exception instanceof HttpExceptionInterface) {
            $response->setStatusCode($exception->getStatusCode());
            $response->headers->replace($exception->getHeaders());
        } else {
            $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $event->setResponse($response);
        $this->logger->error($response->getContent());
    }
}
