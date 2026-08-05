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

use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function is_array;
use function is_string;
use function json_decode;
use function rawurlencode;
use function str_contains;

// Wraps the JSON-returning Note*Handlers for plain HTML form submissions (identified
// by the browser's default form Content-Type), converting their response into a
// redirect back to the home page - a standard Post/Redirect/Get flow so the browser
// never displays raw JSON. Requests made with an explicit JSON Content-Type (the API
// used by curl/tests) are returned unmodified.
final class NoteFormRedirectMiddleware implements MiddlewareInterface
{
    private const string HOME_PATH = '/';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if (! $this->isFormSubmission($request)) {
            return $response;
        }

        if ($response->getStatusCode() >= 400) {
            return new RedirectResponse(self::HOME_PATH . '?error=' . rawurlencode($this->extractError($response)));
        }

        return new RedirectResponse(self::HOME_PATH);
    }

    private function extractError(ResponseInterface $response): string
    {
        $decoded = json_decode((string) $response->getBody(), true);

        return is_array($decoded) && is_string($decoded['error'] ?? null)
            ? $decoded['error']
            : 'unexpected error';
    }

    private function isFormSubmission(ServerRequestInterface $request): bool
    {
        return str_contains($request->getHeaderLine('Content-Type'), 'application/x-www-form-urlencoded');
    }
}
