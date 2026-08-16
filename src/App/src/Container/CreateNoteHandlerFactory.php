<?php

declare(strict_types=1);

namespace App\Container;

use App\CommandHandler\CreateNoteHandler;
use Laminas\ServiceManager\ServiceManager;
use PhpDb\TableGateway\TableGateway;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

final class CreateNoteHandlerFactory
{
    public function __invoke(ContainerInterface&ServiceManager $container): CreateNoteHandler
    {
        return new CreateNoteHandler(
            $container->build(TableGateway::class),
            $container->get(EventDispatcherInterface::class),
        );
    }
}
