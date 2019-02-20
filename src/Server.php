<?php
/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2016
 *
 * @see      https://www.github.com/janhuang
 * @see      https://fastdlabs.com
 */

namespace FastD;

use FastD\ServiceProvider\SwooleServiceProvider;
use FastD\Servitization\Server\HTTPServer;
use swoole_server;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Class App.
 */
class Server
{
    /**
     * @var Application
     */
    protected $application;

    /**
     * @var \FastD\Swoole\Server
     */
    protected $server;

    /**
     * Server constructor.
     *
     * @param Application $application The application.
     * @param array $options Options to manipulate the server.
     */
    public function __construct(Application $application, array $options = [])
    {
        $application->register(new SwooleServiceProvider());

        $server = config()->get('server.class', HTTPServer::class);
        $serverOptions = config()->get('server.options', []);

        // Compute host and port from CLI and default options
        if(!empty($options['host']) && !empty($options['port'])) {
            $serverHost = $options['host'].':'.$options['port'];
        }
        else {
            $defaultHost = config()->get('server.host');
            $defaultComponents = parse_url($defaultHost);

            if(!empty($options['host'])) {
                $serverHost = $options['host'].':'.(isset($defaultComponents['port']) ? $defaultComponents['port'] : 9999);
            }
            elseif(!empty($options['port'])) {
                $serverHost = (isset($defaultComponents['host']) ? $defaultComponents['host'] : '127.0.0.1').':'.$options['port'];
            }
            else {
                $serverHost = $defaultHost;
            }
        }

        // Compute PID file
        if(!empty($options['pid_file'])) {
            $serverOptions['pid_file'] = $options['pid_file'];
        }

        $this->server = $server::createServer(
            $application->getName().(!empty($options['name_suffix']) ? '-'.$options['name_suffix'] : ''),
            $serverHost,
            $serverOptions
        );

        $application->add('server', $this->server);

        $this->initListeners();
        $this->initProcesses();
    }

    /**
     * @return swoole_server
     */
    public function getSwoole()
    {
        return $this->server->getSwoole();
    }

    /**
     * @return Swoole\Server
     */
    public function bootstrap()
    {
        return $this->server->bootstrap();
    }

    /**
     * @return $this
     */
    public function initListeners()
    {
        $listeners = config()->get('server.listeners', []);
        foreach ($listeners as $listener) {
            $this->server->listen(new $listener['class'](
                isset($listener['name']) ? $listener['name'] : app()->getName().' ports',
                $listener['host'],
                isset($listener['options']) ? $listener['options'] : []
            ));
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function initProcesses()
    {
        $processes = config()->get('server.processes', []);
        foreach ($processes as $process) {
            $this->server->process(new $process(app()->getName().' process'));
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function daemon()
    {
        $this->server->daemon();

        return $this;
    }

    /**
     * @return int
     */
    public function start()
    {
        return $this->server->start();
    }

    /**
     * @return int
     */
    public function stop()
    {
        return $this->server->shutdown();
    }

    /**
     * @return int
     */
    public function restart()
    {
        return $this->server->restart();
    }

    /**
     * @return int
     */
    public function reload()
    {
        return $this->server->reload();
    }

    /**
     * @return int
     */
    public function status()
    {
        return $this->server->status();
    }

    /**
     * @param array $dir
     *
     * @return int
     */
    public function watch(array $dir = ['.'])
    {
        return $this->server->watch($dir);
    }

    /**
     * @param InputInterface $input
     */
    public function run(InputInterface $input)
    {
        if ($input->hasParameterOption(['--daemon', '-d'], true)) {
            $this->daemon();
        }

        switch ($input->getArgument('action')) {
            case 'start':
                if ($input->hasParameterOption(['--dir'])) {
                    $this->watch([$input->getOption('dir')]);
                } else {
                    $this->start();
                }

                break;
            case 'stop':
                $this->stop();

                break;
            case 'reload':
                $this->reload();

                break;
            case 'status':
            default:
                $this->status();
        }
    }
}
