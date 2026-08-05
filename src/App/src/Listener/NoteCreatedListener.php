<?php

declare(strict_types=1);

namespace App\Listener;

use Tracy\Debugger;
use Tracy\ILogger;
use Webware\MessageBus\Event\EventInterface;
use Webware\MessageBus\Event\ListenerInterface;

use function sprintf;

final class NoteCreatedListener implements ListenerInterface
{
    public function __invoke(EventInterface $event): void
    {
        Debugger::log(
            sprintf(
                'Note created: id=%s, title=%s',
                $event->getParam('id'),
                $event->getParam('title'),
            ),
            ILogger::INFO,
        );
    }
}
