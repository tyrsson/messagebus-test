<?php

declare(strict_types=1);

namespace App\Container;

use PhpDb\Adapter\AdapterInterface;
use PhpDb\TableGateway\TableGateway;
use Psr\Container\ContainerInterface;

final class NotesTableGatewayFactory
{
    public function __invoke(ContainerInterface $container): TableGateway
    {
        return new TableGateway('notes', $container->get(AdapterInterface::class));
    }
}
