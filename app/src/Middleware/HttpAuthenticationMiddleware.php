<?php

namespace Middleware;

use FastD\Http\JsonResponse;
use FastD\Middleware\DelegateInterface;
use FastD\Middleware\Middleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class HttpAuthenticationMiddleware
 *
 * @package Middleware
 */
class HttpAuthenticationMiddleware extends Middleware
{

    public function handle(ServerRequestInterface $request, DelegateInterface $next): ResponseInterface
    {
        $uri = $request->getUri();

        if('foo:bar' !== $uri->getUserInfo()) {
            return new JsonResponse(
                ['msg' => 'not allow access', 'code' => 401],
                401
            );
        }

        return $next->process($request);
    }
}
