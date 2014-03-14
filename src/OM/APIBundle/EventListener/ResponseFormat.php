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
        $response->setContent(json_encode($data));
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
        $content = array();
        $content['result'] = $result;
        $content['status'] = self::STATUS_SUCCESS;
        // $content['timestamp'] = time();
        $content['time'] = microtime(1) - $request->startTime;
        $event->setResponse($this->format($content));
    }
}
