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
use FastD\Swoole\Server\TCP;
use Swoole\Server as SwooleServer;

/**
 * Class TCPServer.
 */
class TCPServer extends TCP
{
    use OnWorkerStart;

    /**
     * @param \Swoole\Server $server
     * @param $fd
     * @param $data
     * @param $from_id
     *
     * @return int|mixed
     *
     * @throws \Exception
     * @throws \FastD\Packet\Exceptions\PacketException
     */
    public function doWork(SwooleServer $server, $fd, $data, $from_id)
    {
        if ('quit' === $data) {
            $server->close($fd);

            return 0;
        }

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

        if (null !== $response->getFileDescriptor()) {
            $fd = $response->getFileDescriptor();
        }

        if (false === $server->connection_info($fd)) {
            return -1;
        }

        $server->send($fd, (string) $response->getBody());
        app()->shutdown($request, $response);

        return 0;
    }
}
