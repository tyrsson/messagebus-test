<?php

declare(strict_types=1);

namespace App\Listener;

use Tracy\Debugger;
use Tracy\ILogger;
use Webware\MessageBus\Event\Command\CommandPostHandleEvent;
use Webware\MessageBus\Event\Command\CommandPreHandleEvent;
use Webware\MessageBus\Event\EventInterface;
use Webware\MessageBus\Event\ListenerInterface;
use Webware\MessageBus\Event\Query\QueryPostHandleEvent;
use Webware\MessageBus\Event\Query\QueryPreHandleEvent;

use function sprintf;

// Logs the messagebus-event pipeline lifecycle (command and query pre/post-handle
// events) to the Tracy log, demonstrating the pipeline events shipped by
// webware/messagebus-event.
final class MessageLifecycleListener implements ListenerInterface
{
    public function __invoke(EventInterface $event): void
    {
        $message = match (true) {
            $event instanceof CommandPreHandleEvent => sprintf(
                'command pre-handle: %s',
                $event->getCommand()::class,
            ),
            $event instanceof CommandPostHandleEvent => sprintf(
                'command post-handle: %s status=%s',
                $event->getCommand()::class,
                $event->getResult()->getStatus()->name,
            ),
            $event instanceof QueryPreHandleEvent => sprintf(
                'query pre-handle: %s',
                $event->getQuery()::class,
            ),
            $event instanceof QueryPostHandleEvent => sprintf(
                'query post-handle: %s status=%s',
                $event->getQuery()::class,
                $event->getResult()->getStatus()->name,
            ),
            default => null,
        };

        if ($message !== null) {
            Debugger::log($message, ILogger::INFO);
        }
    }
}
