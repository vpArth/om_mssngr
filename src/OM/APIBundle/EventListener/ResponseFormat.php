<?php

namespace OM\APIBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpFoundation\Response;

class ResponseFormat
{
    const STATUS_SUCCESS = 0;

    private $responseFormat;

    public function __construct($responseFormat)
    {
        $this->responseFormat = $responseFormat;
    }

    private function jsonFormat($data)
    {
        $response = new Response();
        $data = json_encode($data);
        $response->setContent($data);
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    private function format($data)
    {
        switch ($this->responseFormat) {
            default:
            case 'json':
                return $this->jsonFormat($data);
        }
    }

    public function onResponse(GetResponseForControllerResultEvent $event)
    {
        $request = $event->getRequest();
        $result = $event->getControllerResult();
        $content = $result;
        $content['status'] = self::STATUS_SUCCESS;
        $event->setResponse($this->format($content));
    }
}
