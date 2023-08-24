<?php
/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2016
 *
 * @see      https://www.github.com/janhuang
 * @see      https://fastdlabs.com
 */

use FastD\Application;
use FastD\Server;

class ServerTest extends FastD\TestCase
{
    public function createApplication(): Application
    {
        $app = new Application(__DIR__.'/../app');

        return $app;
    }

    public function createServer(): Server
    {
        return new Server($this->createApplication());
    }

    public function testServerInit(): void
    {
        $server = $this->createServer();
        $server->bootstrap();
    }
}
