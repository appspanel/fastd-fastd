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
use Exception;
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
    protected $path;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var bool
     */
    protected $booted = false;

    /**
     * @var int
     */
    protected $mode;

    /**
     * AppKernel constructor.
     *
     * @param $path
     * @param $mode
     */
    public function __construct($path, $mode = Application::MODE_FPM)
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
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function isBooted()
    {
        return $this->booted;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Application bootstrap.
     */
    public function bootstrap()
    {
        if (!$this->booted) {
            $config = load($this->path.'/config/app.php');
            $this->name = $config['name'];

            date_default_timezone_set(isset($config['timezone']) ? $config['timezone'] : 'UTC');

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
    public function reboot()
    {
        $this->booted = false;

        $this->bootstrap();
    }

    protected function registerExceptionHandler()
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
    protected function registerServicesProviders(array $services)
    {
        $this->register(new ConfigServiceProvider());
        foreach ($services as $service) {
            $this->register(new $service());
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @return Response|\Symfony\Component\HttpFoundation\Response
     * @throws \Throwable
     */
    public function handleRequest(ServerRequestInterface $request)
    {
        try {
            $this->add('request', $request);

            return $this->get('dispatcher')->dispatch($request);
        } catch (Throwable $exception) {
            return $this->handleException($exception);
        }
    }

    /**
     * @param Response|\Symfony\Component\HttpFoundation\Response $response
     */
    public function handleResponse($response)
    {
        $response->send();
    }

    /**
     * @param \Throwable $e
     * @return \FastD\Http\Response
     * @throws \Throwable
     */
    public function handleException(Throwable $e)
    {
        try {
            $trace = call_user_func(config()->get('exception.log'), $e);
        } catch (Throwable $exception) {
            $trace = [
                'original' => explode("\n", $e->getTraceAsString()),
                'handler' => explode("\n", $exception->getTraceAsString()),
            ];
        }

        logger()->log(Logger::ERROR, $e->getMessage(), $trace);

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
     * @param ServerRequestInterface                                       $request
     * @param ResponseInterface|\Symfony\Component\HttpFoundation\Response $response
     *
     * @return int
     */
    public function shutdown(ServerRequestInterface $request, $response)
    {
        $this->offsetUnset('request');
        $this->offsetUnset('response');

        unset($request, $response);

        return 0;
    }

    /**
     * @return int
     *
     * @throws Exception
     */
    public function run()
    {
        $request = ServerRequest::createServerRequestFromGlobals();

        $response = $this->handleRequest($request);

        $this->handleResponse($response);

        return $this->shutdown($request, $response);
    }
}
