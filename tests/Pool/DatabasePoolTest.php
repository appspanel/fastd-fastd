<?php
/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2016
 *
 * @see      https://www.github.com/janhuang
 * @see      https://fastdlabs.com
 */

use FastD\Model\Database;
use FastD\Pool\DatabasePool;
use PHPUnit\Framework\TestCase;

class DatabasePoolTest extends TestCase
{
    protected function getDatabasePool(): DatabasePool
    {
        return new DatabasePool([
            'default' => [
                'adapter' => 'mysql',
                'name' => 'ci',
                'host' => '127.0.0.1',
                'user' => 'ci',
                'pass' => 'ci',
                'charset' => 'utf8',
                'port' => 3306,
            ],
        ]);
    }

    public function testGetNotExistsConnection(): void
    {
        $this->expectException(LogicException::class);

        $this->getDatabasePool()->getConnection('not_exists');
    }

    public function testPoolNotInitialized(): void
    {
        $pool = $this->getDatabasePool();

        $this->assertInstanceOf(Database::class, $pool->getConnection('default'));
    }

    public function testPoolInitialized(): void
    {
        $pool = $this->getDatabasePool();
        $pool->initPool();

        $this->assertInstanceOf(Database::class, $pool->getConnection('default'));
    }
}
