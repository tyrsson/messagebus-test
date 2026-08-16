<?php

declare(strict_types=1);

namespace App\Middleware;

use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DetectAjaxRequestMiddleware implements MiddlewareInterface
{
    public function __construct(
        private TemplateRendererInterface $template,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // htmx adds an HX-Request header to every request it issues. PSR-7
        // header lookups are case-insensitive.
        if ($request->hasHeader('HX-Request')) {
            $this->template->addDefaultParam(
                TemplateRendererInterface::TEMPLATE_ALL,
                'layout',
                false,
            );
        }

        return $handler->handle($request);
    }
}
