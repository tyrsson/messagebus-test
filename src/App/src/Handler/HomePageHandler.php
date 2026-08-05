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

namespace App\Handler;

use App\Query\ListNotesQuery;
use Laminas\Diactoros\Response;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webware\MessageBus\MessageBusInterface;

use function is_string;

final class HomePageHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly ?TemplateRendererInterface $template = null,
        private readonly ?MessageBusInterface $bus = null,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $data = [
            'message' => 'Welcome to Mezzio!',
        ];
        if (null === $this->template) {
            return new Response\JsonResponse($data);
        }

        if (null !== $this->bus) {
            $data['notes'] = $this->bus->handle(new ListNotesQuery())->getResult();
        }

        $error = $request->getQueryParams()['error'] ?? null;
        if (is_string($error)) {
            $data['error'] = $error;
        }

        return new Response\HtmlResponse($this->template->render('app::home-page', $data));
    }
}
