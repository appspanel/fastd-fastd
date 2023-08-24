<?php
    /**
     * @author    jan huang <bboyjanhuang@gmail.com>
     * @copyright 2016
     *
     * @see      https://www.github.com/janhuang
     * @see      https://fastdlabs.com
     */
    use FastD\Application;
    use FastD\TestCase;

    class IndexControllerTest extends TestCase
    {
        public function createApplication(): Application
        {
            return new Application(__DIR__.'/../..');
        }

        public function testSayHello(): void
        {
            $response = $this->app->handleRequest($this->request('GET', '/'));
            $this->equalsJson($response, ['foo' => 'bar']);
            $response = $this->app->handleRequest($this->request('GET', '/?foo=var'));
            $this->equalsJson($response, ['foo' => 'var']);
            $response = $this->app->handleRequest($this->request('GET', '/?foo=bar'));
            $this->equalsJson($response, ['foo' => 'bar']);
        }

        public function testDb(): void
        {
            $response = $this->app->handleRequest($this->request('GET', '/db'));

            $this->equalsStatus($response, 200);
        }

        public function testHandleDynamicRequest(): void
        {
            $response = $this->app->handleRequest($this->request('GET', '/foo/bar'));
            $this->equalsJson($response, ['foo' => 'bar']);
            $response = $this->app->handleRequest($this->request('GET', '/foo/foobar'));
            $this->equalsJson($response, ['foo' => 'foobar']);
        }

        public function testHandleMiddlewareRequest(): void
        {
            $response = $this->app->handleRequest($this->request('POST', '/foo/bar'));
            $this->equalsJson($response, ['foo' => 'middleware']);
            $response = $this->app->handleRequest($this->request('POST', '/foo/not'));
            $this->equalsJson($response, ['foo' => 'bar']);
        }

        public function testModel(): void
        {
            $response = $this->app->handleRequest($this->request('GET', '/model'));
            $this->assertEquals(200, $response->getStatusCode());
            $this->isSuccessful($response);
        }

        public function testAuth(): void
        {
            $response = $this->app->handleRequest($this->request('GET', '/auth'));

            $this->assertEquals(401, $response->getStatusCode());
            $this->equalsJson($response, [
                'msg' => 'not allow access',
                'code' => 401,
            ]);

            $response = $this->app->handleRequest($this->request('GET', 'http://foo:bar@127.0.0.1/auth'));

            $this->assertEquals(200, $response->getStatusCode());
            $this->equalsJson($response, [
                'foo' => 'bar',
            ]);
        }
    }
