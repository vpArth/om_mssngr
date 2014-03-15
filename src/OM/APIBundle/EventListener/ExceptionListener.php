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
//        if (!$exception->getCode()) {
//            throw $exception;
//        }
        $request = $event->getRequest();
        $message = json_encode(
            array(
                'status' => $exception->getCode() ?: -1,
                'message' => $exception->getMessage(),
            )
        );

        $response = new Response();

        if ($exception instanceof HttpExceptionInterface) {
            $response->setStatusCode($exception->getStatusCode());
            $response->headers->replace($exception->getHeaders());
        } else {
            $response->headers->set('X-Status-Code', Response::HTTP_OK);
            $response->setStatusCode(Response::HTTP_OK);
        }

        $response->headers->set('Content-Type', 'application/json');
        $response->setContent($message);
        $event->setResponse($response);
        $this->logger->error($response->getContent());
    }
}
