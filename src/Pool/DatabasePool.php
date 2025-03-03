<?php
/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2016
 *
 * @see      https://www.github.com/janhuang
 * @see      https://fastdlabs.com
 */

namespace FastD\Pool;

use FastD\Model\Database;
use LogicException;

/**
 * Class DatabasePool.
 */
class DatabasePool implements PoolInterface
{
    /**
     * @var array<string,Database>
     */
    protected array $connections = [];

    /**
     * @var array<string,array<string,mixed>>
     */
    protected array $config;

    /**
     * Database constructor.
     *
     * @param array<string,array<string,mixed>> $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @param string $key
     * @param bool $force
     * @return Database
     */
    public function getConnection(string $key, bool $force = false): Database
    {
        if ($force || !isset($this->connections[$key])) {
            if (!isset($this->config[$key])) {
                throw new LogicException(sprintf('No set %s database', $key));
            }
            $config = $this->config[$key];
            $this->connections[$key] = new Database(
                [
                    'database_type' => isset($config['adapter']) ? $config['adapter'] : 'mysql',
                    'database_name' => $config['name'],
                    'server' => $config['host'],
                    'username' => $config['user'],
                    'password' => $config['pass'],
                    'charset' => isset($config['charset']) ? $config['charset'] : 'utf8',
                    'port' => isset($config['port']) ? $config['port'] : 3306,
                    'prefix' => isset($config['prefix']) ? $config['prefix'] : '',
                    'option' => isset($config['option']) ? $config['option'] : [],
                    'command' => isset($config['command']) ? $config['command'] : [],
                ]
            );
        }

        return $this->connections[$key];
    }

    /**
     * {@inheritdoc}
     */
    public function initPool(): void
    {
        foreach ($this->config as $name => $config) {
            $this->getConnection($name, true);
        }
    }
}
