<?php

use FastD\Application;
use FastD\Model\Database;

/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2016
 *
 * @see      https://www.github.com/janhuang
 * @see      https://fastdlabs.com
 */
class DatabaseTest extends \FastD\TestCase
{
    public function createApplication(): Application
    {
        $app = new Application(__DIR__.'/../../app');

        return $app;
    }

    public function createDatabase(): Database
    {
        $config = config()->get('database.default');

        return new Database([
            'database_type' => isset($config['adapter']) ? $config['adapter'] : 'mysql',
            'database_name' => $config['name'],
            'server' => $config['host'],
            'username' => $config['user'],
            'password' => $config['pass'],
            'charset' => isset($config['charset']) ? $config['charset'] : 'utf8',
            'port' => isset($config['port']) ? $config['port'] : 3306,
            'prefix' => isset($config['prefix']) ? $config['prefix'] : '',
        ]);
    }

    public function testGoneAwayConnection(): void
    {
        $database = $this->createDatabase();
        $database->query('show tables;')->fetchAll();
        $this->assertTrue(true);
    }

    public function testInsert(): void
    {
        database()->insert('hello', [
            'content' => 'hello world',
            'user' => 'foo',
            'created' => date('Y-m-d H:i:s'),
        ]);

        $row = database()->get('hello', '*', [
            'id' => database()->id(),
        ]);
        $this->assertIsInt($row['id']);

        $this->assertSame(true, database()->has('hello', [
            'id' => $row['id'],
        ]));
    }
}
