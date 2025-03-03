<?php
/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2016
 *
 * @see      https://www.github.com/janhuang
 * @see      https://fastdlabs.com
 */

use FastD\Application;
use FastD\Container\NotFoundException;
use FastD\Http\Response as FastDResponse;
use FastD\TestCase;
use Psr\Http\Message\ResponseInterface;
use ServiceProvider\FooServiceProvider;

class ApplicationTest extends TestCase
{
    public function createApplication(): Application
    {
        $app = new Application(__DIR__.'/../app');

        return $app;
    }

    public function testApplicationBootstrap(): void
    {
        $this->assertEquals('fast-d', $this->app->getName());
        $this->assertTrue($this->app->isBooted());
        $this->assertEquals(date_default_timezone_get(), config()->get('timezone'));
    }

    public function testServiceProvider(): void
    {
        $this->app->register(new FooServiceProvider());

        $this->assertEquals('foo', $this->app['foo']->name);
    }

    public function testServiceProviderAutomateConsole(): void
    {
        $this->app->register(new FooServiceProvider());

        $consoles = config()->get('consoles');
        $consoles = array_unique($consoles);
        $this->assertEquals(['ServiceProvider\DemoConsole',], $consoles);
    }

    public function testConfigurationServiceProvider(): void
    {
        $this->assertEquals('fast-d', $this->app->get('config')->get('name'));
        $this->assertNull(config()->get('foo'));
        $this->assertFalse(config()->has('not_exists_key'));
        $this->assertEquals('default', config()->get('foo', 'default'));
        $this->assertEquals('bar', config()->get('env.foo'));
    }

    public function testHandleRequest(): void
    {
        $response = $this->app->handleRequest($this->request('GET', '/'));

        $this->equalsJson($response, [
            'foo' => 'bar',
        ]);
    }

    public function testHandleException(): void
    {
        try
        {
            $e = new Exception('exception');
            $response = $this->app->handleException($e);

            $this->assertInstanceOf(FastDResponse::class, $response);
        }
        catch(Exception $e)
        {
            $this->assertEquals('exception', $e->getMessage());
        }
    }

    public function testHandleResponse(): void
    {
        $response = json([
            'foo' => 'bar',
        ]);
        $this->app->handleResponse($response);

        $this->expectOutputString((string) $response->getBody());
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testApplicationShutdown(): void
    {
        $request = $this->request('GET', '/');
        $response = $this->handleRequest($request);
        $this->app->shutdown($request, $response);

        $this->assertFalse($this->app->has('request'));
        $this->assertFalse($this->app->has('response'));

        $this->expectException(NotFoundException::class);
        $this->app->get('request');
        $this->app->get('response');
    }
}
