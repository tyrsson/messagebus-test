<?php

declare(strict_types=1);

/**
 * This file is part of the Webware Mezzio Bleeding Edge package.
 *
 * Copyright (c) 2026 Joey Smith <jsmith@webinertia.net>
 * and contributors.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App;

use Mezzio\Helper\BodyParams\BodyParamsMiddleware;
use PhpDb\TableGateway\TableGateway;
use Webware\MessageBus\ConfigProvider as BusConfigProvider;
use Webware\MessageBus\Event\ConfigProvider as EventConfigProvider;
use Webware\MessageBus\MessageBusInterface;

/**
 * @phpstan-type dependencyArray array{
 *                      delegators?: array<class-string, list<class-string>>,
 *                      factories?: array<class-string, class-string>,
 *                      invokables?: array<class-string, class-string>
 *               }
 * @phpstan-type routeProviderArray array{
 *                      route-providers: list<class-string>
 *                }
 * @phpstan-type templateArray array{
 *                      map: array<string, string>,
 *                      paths: array<string, list<string>>,
 *                      default_layout: string
 *                }
 */
class ConfigProvider
{
    /**
     * Returns the container dependencies
     *
     * @phpstan-return dependencyArray
     */
    public function getDependencies(): array
    {
        return [
            'factories'  => [
                Handler\HomePageHandler::class          => Container\HomePageHandlerFactory::class,
                Handler\NoteCommandHandler::class       => Container\NoteCommandHandlerFactory::class,
                Handler\NoteQueryHandler::class         => Container\NoteQueryHandlerFactory::class,
                RouteProvider::class                    => Container\RouteProviderFactory::class,
                TableGateway::class                     => Container\NotesTableGatewayFactory::class,
                CommandHandler\CreateNoteHandler::class => Container\CreateNoteHandlerFactory::class,
                CommandHandler\UpdateNoteHandler::class => Container\UpdateNoteHandlerFactory::class,
                QueryHandler\ListNotesHandler::class    => Container\ListNotesHandlerFactory::class,
            ],
            'invokables' => [
                Handler\PingHandler::class          => Handler\PingHandler::class,
                BodyParamsMiddleware::class         => BodyParamsMiddleware::class,
                Listener\NoteCreatedListener::class => Listener\NoteCreatedListener::class,
            ],
        ];
    }

    /**
     * Returns the messagebus-event listener wiring for this module's events
     *
     * @phpstan-return array<class-string, list<class-string>>
     */
    public function getListeners(): array
    {
        return [
            Event\NoteCreatedEvent::class => [
                Listener\NoteCreatedListener::class,
            ],
        ];
    }

    /**
     * Returns the MessageBus command/query map wiring for this module's messages
     *
     * @phpstan-return array{
     *     command_map: array<class-string, class-string>,
     *     query_map: array<class-string, class-string>
     * }
     */
    public function getMessageBusConfig(): array
    {
        return [
            BusConfigProvider::COMMAND_MAP_KEY => [
                Command\CreateNoteCommand::class => CommandHandler\CreateNoteHandler::class,
                Command\UpdateNoteCommand::class => CommandHandler\UpdateNoteHandler::class,
            ],
            BusConfigProvider::QUERY_MAP_KEY   => [
                Query\ListNotesQuery::class => QueryHandler\ListNotesHandler::class,
            ],
        ];
    }

    /**
     * Returns the route provider configuration
     *
     * @phpstan-return routeProviderArray
     */
    public function getRouteProviders(): array
    {
        return [
            'route-providers' => [
                RouteProvider::class,
            ],
        ];
    }

    /**
     * Returns the templates configuration
     *
     * @phpstan-return templateArray
     */
    public function getTemplates(): array
    {
        return [
            'map'            => [
                'layout::default' => __DIR__ . '/../templates/layout/default.phtml',
                'app::home-page'  => __DIR__ . '/../templates/app/home-page.phtml',
                'error::404'      => __DIR__ . '/../templates/error/404.phtml',
                'error::error'    => __DIR__ . '/../templates/error/error.phtml',
            ],
            'paths'          => [
                'app'   => [__DIR__ . '/../templates/app'],
                'error' => [__DIR__ . '/../templates/error'],
            ],
            'default_layout' => 'layout::default',
        ];
    }

    /**
     * Returns the configuration array
     *
     * To add a bit of a structure, each section is defined in a separate
     * method which returns an array with its configuration.
     *
     * @phpstan-return array{dependencies: dependencyArray, templates: templateArray}
     */
    public function __invoke(): array
    {
        return [
            'dependencies'                    => $this->getDependencies(),
            'router'                          => $this->getRouteProviders(),
            'templates'                       => $this->getTemplates(),
            MessageBusInterface::class        => $this->getMessageBusConfig(),
            EventConfigProvider::LISTENER_KEY => $this->getListeners(),
        ];
    }
}
