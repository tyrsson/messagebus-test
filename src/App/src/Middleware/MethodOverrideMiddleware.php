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

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function is_array;
use function is_string;
use function strtoupper;

// HTML forms cannot submit PATCH/PUT/DELETE; browsers only support GET/POST. This
// mirrors the standard "_method" override convention used by Laravel, Symfony, etc.
final class MethodOverrideMiddleware implements MiddlewareInterface
{
    private const string FIELD = '_method';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getMethod() === 'POST') {
            $body = $request->getParsedBody();

            if (is_array($body) && is_string($body[self::FIELD] ?? null) && $body[self::FIELD] !== '') {
                $request = $request->withMethod(strtoupper($body[self::FIELD]));
            }
        }

        return $handler->handle($request);
    }
}
