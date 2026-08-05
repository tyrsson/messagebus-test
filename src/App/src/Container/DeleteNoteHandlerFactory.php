<?php

declare(strict_types=1);

namespace App\Container;

use App\CommandHandler\DeleteNoteHandler;
use Laminas\ServiceManager\ServiceManager;
use PhpDb\TableGateway\TableGateway;
use Psr\Container\ContainerInterface;

final class DeleteNoteHandlerFactory
{
    public function __invoke(ContainerInterface&ServiceManager $container): DeleteNoteHandler
    {
        return new DeleteNoteHandler($container->build(TableGateway::class));
    }
}
