<?php
/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2016
 *
 * @see      https://www.github.com/janhuang
 * @see      https://fastdlabs.com
 */

namespace FastD\Servitization;

use Exception;
use FastD\Pool\PoolInterface;
use Swoole\Server as SwooleServer;

/**
 * Trait OnWorkerStart.
 */
trait OnWorkerStart
{
    /**
     * @param \Swoole\Server $server
     * @param int           $worker_id
     */
    public function onWorkerStart(SwooleServer $server, $worker_id)
    {
        parent::onWorkerStart($server, $worker_id);

        try {
            app()->reboot();

            foreach (app() as $service) {
                if ($service instanceof PoolInterface) {
                    $service->initPool();
                }
            }
        } catch (Exception $e) {
            echo $e->getMessage().PHP_EOL;
        }
    }
}
