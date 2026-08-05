<?php

declare(strict_types=1);

namespace App\Container;

use App\Handler\NoteQueryHandler;
use Psr\Container\ContainerInterface;
use Webware\MessageBus\MessageBusInterface;

final class NoteQueryHandlerFactory
{
    public function __invoke(ContainerInterface $container): NoteQueryHandler
    {
        return new NoteQueryHandler($container->get(MessageBusInterface::class));
    }
}
