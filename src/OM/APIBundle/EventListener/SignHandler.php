<?php
/**
 * Created by PhpStorm.
 * User: arth
 * Date: 3/14/14
 * Time: 12:42 AM
 */

namespace OM\APIBundle\EventListener;

use OM\APIBundle\Controller;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class SignHandler
{
    private $secretKey;
    private $needSign = true;

    private $hash;

    public function __construct($secretKey, $needSign)
    {
        $this->secretKey = $secretKey;
        $this->needSign = $needSign;
    }

    private function requestHash(array $params)
    {
        unset($params['hash']);
        ksort($params);
        $hash = sha1(implode('', $params) . $this->secretKey);
        // echo $hash;
        return $hash;
    }

    private function responseHash($raw)
    {
        return sha1($raw . $this->secretKey);
    }

    private function requestSigned(FilterControllerEvent $event)
    {
        $request = $event->getRequest();
        return $request->query->get('hash', '')
        ==
        ($this->hash = $this->requestHash($request->query->all()));
    }

    private function isBadRequest(FilterControllerEvent $event)
    {
        return 0;
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        $controller = $event->getController();

        if (!is_array($controller)) {
            return;
        }
        if ($this->needSign && $controller[0] instanceof Controller\ISignedController) {
            if (!$this->requestSigned($event)) {
                throw new AccessDeniedHttpException('This action needs a valid sign!');
            }
        }
        if ($res = $this->isBadRequest($event)) {
            throw new BadRequestHttpException($res);
        }
        $event->getRequest()->attributes->set('signed', true);
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (!$event->getRequest()->attributes->get('signed')) {
            return;
        }

        $response = $event->getResponse();
        $response->headers->set('X-Content-DSA', $this->responseHash($response->getContent()));
    }
}
