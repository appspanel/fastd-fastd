<?php
/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2016
 *
 * @see      https://www.github.com/janhuang
 * @see      https://fastdlabs.com
 */

namespace FastD\Servitization;

use FastD\Pool\PoolInterface;
use Swoole\Server as SwooleServer;
use Throwable;

/**
 * Trait OnWorkerStart.
 */
trait OnWorkerStart
{
    /**
     * @param \Swoole\Server $server
     * @param int $workerId
     */
    public function onWorkerStart(SwooleServer $server, int $workerId): void
    {
        parent::onWorkerStart($server, $workerId);

        try {
            app()->reboot();

            foreach (app() as $service) {
                if ($service instanceof PoolInterface) {
                    $service->initPool();
                }
            }
        } catch (Throwable $e) {
            echo $e->getMessage().PHP_EOL;
        }
    }
}
