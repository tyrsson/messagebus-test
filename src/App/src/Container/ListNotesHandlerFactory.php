<?php

declare(strict_types=1);

namespace App\Container;

use App\QueryHandler\ListNotesHandler;
use Laminas\ServiceManager\ServiceManager;
use PhpDb\TableGateway\TableGateway;
use Psr\Container\ContainerInterface;

final class ListNotesHandlerFactory
{
    public function __invoke(ContainerInterface&ServiceManager $container): ListNotesHandler
    {
        return new ListNotesHandler($container->build(TableGateway::class));
    }
}
