<?php

use FastD\Http\Response;
use FastD\Application;
use FastD\Container\NotFoundException;
use FastD\Http\JsonResponse;
use Monolog\Handler\StreamHandler;

/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2016
 *
 * @see      https://www.github.com/janhuang
 * @see      https://fastdlabs.com
 */
class HelpersTest extends \FastD\TestCase
{
    public function createApplication(): Application
    {
        return new Application(__DIR__.'/../app');
    }

    public function testFunctionApp(): void
    {
        $this->assertEquals('fast-d', app()->getName());
    }

    public function testFunctionRoute(): void
    {
        $router = route();
        $map = $router->aliasMap;
        $this->assertArrayHasKey('GET', $map);
    }

    public function testFunctionConfig(): void
    {
        $this->assertEquals('fast-d', config()->get('name'));
        $this->assertArrayHasKey('database', config()->all());
    }

    public function testFunctionRequestInApplicationNotBootstrap(): void
    {
        $this->expectException(NotFoundException::class);

        request();
    }

    public function testFunctionRequestInApplicationHandleRequest(): void
    {
        $this->handleRequest($this->request('GET', '/'));
        $request = request();
        $this->assertEquals('/', $request->getUri()->getPath());
        $this->assertEquals('GET', $request->getMethod());
    }

    public function testFunctionResponseInApplicationNotBootstrapped(): void
    {
        $response = response();

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testFunctionResponseInApplicationHandleRequest(): void
    {
        $response = $this->handleRequest($this->request('GET', '/'));

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testFunctionJson(): void
    {
        $response = json(['foo' => 'bar']);
        $this->assertEquals(
            (string) $response->getContents(),
            (string) (new JsonResponse(['foo' => 'bar']))->getContents()
        );
    }

    public function testFunctionAbort(): void
    {
        $this->expectException(Exception::class);

        abort('400');
    }

    public function testFunctionLogger(): void
    {
        $logFile = app()->getPath().'/runtime/logs/demo.log';
        logger()->pushHandler(new StreamHandler($logFile));
        logger()->notice('hello world');
        $this->assertTrue(file_exists($logFile));
        unset($logFile);
    }

    public function testFunctionCache(): void
    {
        $item = cache()->getItem('hello');
        $item->set('world');
        cache()->save($item);
        $this->assertTrue(cache()->getItem('hello')->isHit());
    }

    public function testFunctionDatabase(): void
    {
        $this->assertEquals('mysql', database()->info()['driver']);
        $this->assertNotNull(database());
    }
}
