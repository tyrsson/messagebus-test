<?php

declare(strict_types=1);

namespace App\Container;

use App\Handler\NoteListHandler;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;
use Webware\MessageBus\MessageBusInterface;

final class NoteListHandlerFactory
{
    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): NoteListHandler
    {
        return new NoteListHandler(
            $container->get(MessageBusInterface::class),
            $container->get(TemplateRendererInterface::class),
        );
    }
}
