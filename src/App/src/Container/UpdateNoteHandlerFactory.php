<?php

declare(strict_types=1);

namespace App\Container;

use App\CommandHandler\UpdateNoteHandler;
use Laminas\ServiceManager\ServiceManager;
use PhpDb\TableGateway\TableGateway;
use Psr\Container\ContainerInterface;

final class UpdateNoteHandlerFactory
{
    public function __invoke(ContainerInterface&ServiceManager $container): UpdateNoteHandler
    {
        return new UpdateNoteHandler($container->build(TableGateway::class));
    }
}
