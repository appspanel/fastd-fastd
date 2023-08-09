<?php
/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2016
 *
 * @see      https://www.github.com/janhuang
 * @see      https://fastdlabs.com
 */

namespace FastD\Servitization\Server;

use FastD\Http\ServerRequest;
use FastD\Packet\Json;
use FastD\Servitization\OnWorkerStart;
use FastD\Swoole\Server\WebSocket;
use Swoole\Server as SwooleServer;
use Swoole\WebSocket\Frame as SwooleFrame;

/**
 * Class WebSocketServer.
 */
class WebSocketServer extends WebSocket
{
    use OnWorkerStart;

    /**
     * @param \Swoole\Server          $server
     * @param \Swoole\WebSocket\Frame $frame
     *
     * @return int|mixed
     *
     * @throws \Exception
     * @throws \FastD\Packet\Exceptions\PacketException
     */
    public function doMessage(SwooleServer $server, SwooleFrame $frame)
    {
        $data = $frame->data;
        $data = Json::decode($data);
        $request = new ServerRequest($data['method'], $data['path']);

        if (isset($data['args'])) {
            if ('GET' === $request->getMethod()) {
                $request->withQueryParams($data['args']);
            } else {
                $request->withParsedBody($data['args']);
            }
        }

        $response = app()->handleRequest($request);
        $fd = null !== ($fd = $response->getFileDescriptor()) ? $fd : $frame->fd;

        if (false === $server->connection_info($fd)) {
            return -1;
        }

        $server->push($fd, (string) $response->getBody());
        app()->shutdown($request, $response);

        return 0;
    }
}
