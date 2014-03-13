<?php

namespace OM\APIBundle\EventListener;

use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Psr\Log\LoggerInterface;

class FinishListener
{
    private $logger;
    private $logMessage;

    public function __construct(LoggerInterface $logger, $logMessage = 0)
    {
        $this->logger = $logger;
        $this->logMessage = $logMessage;
    }

    public function onFinish(PostResponseEvent $event)
    {
        $responseContent = $event->getResponse()->getContent();
        if ($this->logMessage['maxLength']) {
            if (strlen($responseContent) > intval($this->logMessage['maxLength'])) {
                $trimLength =
                    $this->logMessage['trimLength']
                        ? intval($this->logMessage['trimLength'])
                        : 1000;
                $responseContent = substr($responseContent, 0, $trimLength) . "...";
            }
        }
        $this->logger->info($responseContent);
    }
}
