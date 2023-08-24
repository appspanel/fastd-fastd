<?php
/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2016
 *
 * @see      https://www.github.com/janhuang
 * @see      https://fastdlabs.com
 */

namespace FastD\Servitization\Server;

use FastD\Http\Response;
use FastD\Http\SwooleServerRequest;
use FastD\Servitization\OnWorkerStart;
use FastD\Swoole\Server\HTTP;
use Psr\Http\Message\ServerRequestInterface;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;

/**
 * Class HTTPServer.
 */
class HTTPServer extends HTTP
{
    use OnWorkerStart;

    /**
     * @param \Swoole\Http\Request $swooleRequest
     * @param \Swoole\Http\Response $swooleResponse
     * @throws \Throwable
     */
    public function onRequest(SwooleRequest $swooleRequest, SwooleResponse $swooleResponse): void
    {
        $request = SwooleServerRequest::createServerRequestFromSwoole($swooleRequest);

        $response = $this->doRequest($request);
        foreach ($response->getHeaders() as $key => $header) {
            $swooleResponse->header($key, $response->getHeaderLine($key));
        }
        foreach ($response->getCookieParams() as $key => $cookieParam) {
            $swooleResponse->cookie(
                $key,
                $cookieParam->getValue(),
                $cookieParam->getExpire(),
                $cookieParam->getPath(),
                $cookieParam->getDomain(),
                $cookieParam->isSecure(),
                $cookieParam->isHttpOnly()
            );
        }

        $swooleResponse->status($response->getStatusCode());
        $swooleResponse->end((string) $response->getBody());
        app()->shutdown($request, $response);
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $serverRequest
     * @return \FastD\Http\Response
     * @throws \Throwable
     */
    public function doRequest(ServerRequestInterface $serverRequest): Response
    {
        return app()->handleRequest($serverRequest);
    }
}
