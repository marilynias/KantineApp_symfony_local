<?php

namespace Shared\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\TerminateEvent;

final class PerformanceListener
{
    private LoggerInterface $logger;
    
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    
    #[AsEventListener]
    public function onTerminateEvent(TerminateEvent $event): void
    {
        $duration = microtime(true) - $event->getRequest()->server->get('REQUEST_TIME_FLOAT');
        
        if ($duration > 0.5) {
            $this->logger->warning('Slow request detected', [
                'url' => $event->getRequest()->getUri(),
                'duration' => $duration,
                'memory' => memory_get_peak_usage(true) / 1024 / 1024,
            ]);
        }
    }
}
