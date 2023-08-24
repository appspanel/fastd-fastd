<?php
/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2016
 *
 * @see      https://www.github.com/janhuang
 * @see      http://www.fast-d.cn/
 */

namespace FastD\Pool;

use LogicException;
use ReflectionClass;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Throwable;

/**
 * Class CachePool.
 */
class CachePool implements PoolInterface
{
    /**
     * @var array<string,AbstractAdapter>
     */
    protected array $caches = [];

    /**
     * @var array<string,array<string,mixed>>
     */
    protected array $config;

    /**
     * @var array<string,AbstractAdapter>
     */
    protected array $redises = [];

    /**
     * Cache constructor.
     *
     * @param array<string,array<string,mixed>> $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @param string $key
     * @return AbstractAdapter
     * @throws \ReflectionException
     */
    protected function connect(string $key): AbstractAdapter
    {
        if (!isset($this->config[$key])) {
            throw new LogicException(sprintf('No set %s cache', $key));
        }
        $config = $this->config[$key];

        // 解决使用了自定义的 RedisAdapter 时无法正常创建的问题
        if (
            $config['adapter'] === RedisAdapter::class
            || (new ReflectionClass($config['adapter']))->isSubclassOf(RedisAdapter::class)) {
            return $this->getRedisAdapter($config, $key);
        }

        return $this->getAdapter($config);
    }

    /**
     * @param string $key
     * @return AbstractAdapter
     * @throws \ReflectionException
     */
    public function getCache(string $key): AbstractAdapter
    {
        if (!isset($this->caches[$key])) {
            $this->caches[$key] = $this->connect($key);
        }

        if (isset($this->redises[$key])) {
            if (
                null === $this->redises[$key]['connect']
                || false === $this->redises[$key]['connect']->ping()
            ) {
                $this->caches[$key] = $this->connect($key);
            }
        }

        return $this->caches[$key];
    }

    /**
     * {@inheritdoc}
     */
    public function initPool(): void
    {
        foreach ($this->config as $name => $config) {
            $this->getCache($name);
        }
    }

    /**
     * @param array $config
     * @param string $key
     * @return AbstractAdapter
     */
    protected function getRedisAdapter(array $config, string $key): AbstractAdapter
    {
        $connect = null;
        try {
            $connect = RedisAdapter::createConnection($config['params']['dsn']);
            $cache = new $config['adapter'](
                $connect,
                $config['params']['namespace'] ?? '',
                $config['params']['lifetime'] ?? ''
            );
        } catch (Throwable) {
            $cache = new FilesystemAdapter('', 0, '/tmp/cache');
        }

        $this->redises[$key] = [
            'connect' => $connect,
            'driver' => RedisAdapter::class,
        ];

        return $cache;
    }

    /**
     * @param array $config
     * @return AbstractAdapter
     */
    protected function getAdapter(array $config): AbstractAdapter
    {
        return new $config['adapter'](
            $config['params']['namespace'] ?? '',
            $config['params']['lifetime'] ?? '',
            $config['params']['directory'] ?? app()->getPath().'/runtime/cache'
        );
    }
}
