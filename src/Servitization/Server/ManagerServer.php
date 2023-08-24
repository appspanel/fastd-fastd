<?php
/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2016
 *
 * @see      https://www.github.com/janhuang
 * @see      https://fastdlabs.com
 */

namespace FastD\Servitization\Server;

use FastD\Application;
use FastD\Swoole\Server\TCP;
use Swoole\Server as SwooleServer;

/**
 * Class MonitorStatusServer.
 */
class ManagerServer extends TCP
{
    /**
     * @param \Swoole\Server $server
     * @param int $fd
     * @param int $reactorId
     */
    public function doConnect(SwooleServer $server, int $fd, int $reactorId): void
    {
        $server->send($fd, sprintf('server: %s %s', app()->getName(), Application::VERSION).PHP_EOL);
    }

    /**
     * @param \Swoole\Server $server
     * @param int $fd
     * @param mixed $data
     * @param int $reactorId
     * @return mixed
     */
    public function doWork(SwooleServer $server, int $fd, mixed $data, int $reactorId): mixed
    {
        switch (trim($data)) {
            case 'quit':
                $server->send($fd, 'connection closed');
                $server->close($fd);

                break;

            case 'reload':
                $this->getSwoole()->reload();

                break;

            case 'status':
            default:
                $info = $server->stats();
                $status = '';
                foreach ($info as $key => $value) {
                    $status .= '['.date('Y-m-d H:i:s').']: '.$key.': '.$value.PHP_EOL;
                }
                $server->send($fd, $status);

                break;
        }

        return null;
    }
}
