<?php
/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2016
 *
 * @see      https://www.github.com/janhuang
 * @see      https://fastdlabs.com
 */

namespace FastD;

use ErrorException;
use FastD\Config\Config;
use FastD\Container\Container;
use FastD\Container\ServiceProviderInterface;
use FastD\Http\HttpException;
use FastD\Http\Response;
use FastD\Http\ServerRequest;
use FastD\Logger\Logger;
use FastD\ServiceProvider\ConfigServiceProvider;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Class Application.
 */
class Application extends Container
{
    const VERSION = 'v3.2.0';
    const MODE_FPM = 1;
    const MODE_SWOOLE = 2;
    const MODE_CLI = 3;

    /**
     * @var Application
     */
    public static $app;

    /**
     * @var string
     */
    protected string $path;

    /**
     * @var string
     */
    protected string $name;

    /**
     * @var bool
     */
    protected bool $booted = false;

    /**
     * @var int
     */
    protected int $mode;

    /**
     * AppKernel constructor.
     *
     * @param string $path
     * @param int $mode
     */
    public function __construct(string $path, int $mode = Application::MODE_FPM)
    {
        $this->path = $path;

        $this->mode = $mode;

        static::$app = $this;

        $this->add('app', $this);

        $this->bootstrap();
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function isBooted(): bool
    {
        return $this->booted;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Application bootstrap.
     */
    public function bootstrap(): void
    {
        if (!$this->booted) {
            $config = load($this->path.'/config/app.php');
            $this->name = $config['name'];

            date_default_timezone_set($config['timezone'] ?? 'UTC');

            $this->add('config', new Config($config));
            $this->add('logger', new Logger($this->name));

            $this->registerExceptionHandler();
            $this->registerServicesProviders($config['services']);

            unset($config);
            $this->booted = true;
        }
    }

    /**
     * Application reboot.
     */
    public function reboot(): void
    {
        $this->booted = false;

        $this->bootstrap();
    }

    protected function registerExceptionHandler(): void
    {
        $level = config()->get('error_reporting', E_ALL);
        error_reporting($level);

        set_exception_handler([$this, 'handleException']);

        set_error_handler(function ($level, $message, $file, $line) {
            throw new ErrorException($message, 0, $level, $file, $line);
        }, $level);
    }

    /**
     * @param ServiceProviderInterface[] $services
     */
    protected function registerServicesProviders(array $services): void
    {
        $this->register(new ConfigServiceProvider());

        foreach ($services as $service) {
            $this->register(new $service());
        }
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return \FastD\Http\Response
     * @throws \Throwable
     */
    public function handleRequest(ServerRequestInterface $request): Response
    {
        try {
            $this->add('request', $request);

            return $this->get('dispatcher')->dispatch($request);
        } catch (Throwable $exception) {
            return $this->handleException($exception);
        }
    }

    /**
     * @param \FastD\Http\Response $response
     */
    public function handleResponse(Response $response): void
    {
        $response->send();
    }

    /**
     * @param \Throwable $e
     * @return \FastD\Http\Response
     * @throws \Throwable
     */
    public function handleException(Throwable $e): Response
    {
        try {
            $trace = call_user_func(config()->get('exception.log'), $e);
        } catch (Throwable $exception) {
            $trace = [
                'original' => explode("\n", $e->getTraceAsString()),
                'handler' => explode("\n", $exception->getTraceAsString()),
            ];
        }

        logger()->error($e->getMessage(), $trace);

        if (Application::MODE_CLI === $this->mode) {
            throw $e;
        }

        $status = ($e instanceof HttpException) ? $e->getStatusCode() : $e->getCode();

        if (!array_key_exists($status, Response::$statusTexts)) {
            $status = Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        $response = json(call_user_func(config()->get('exception.response'), $e), $status);

        if (!$this->isBooted()) {
            $this->handleResponse($response);
        }

        return $response;
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return int
     */
    public function shutdown(ServerRequestInterface $request, ResponseInterface $response): int
    {
        $this->offsetUnset('request');
        $this->offsetUnset('response');

        unset($request, $response);

        return 0;
    }

    /**
     * @return int
     * @throws \Throwable
     */
    public function run(): int
    {
        $request = ServerRequest::createServerRequestFromGlobals();

        $response = $this->handleRequest($request);

        $this->handleResponse($response);

        return $this->shutdown($request, $response);
    }
}
