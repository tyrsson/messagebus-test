<?php

declare(strict_types=1);

namespace App\Container;

use App\Handler\NoteCommandHandler;
use Psr\Container\ContainerInterface;
use Webware\MessageBus\MessageBusInterface;

final class NoteCommandHandlerFactory
{
    public function __invoke(ContainerInterface $container): NoteCommandHandler
    {
        return new NoteCommandHandler($container->get(MessageBusInterface::class));
    }
}
