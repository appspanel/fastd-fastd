<?php
/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2016
 *
 * @see      https://www.github.com/janhuang
 * @see      https://fastdlabs.com
 */

namespace FastD\Model;

use Medoo\Medoo;
use PDO;
use Throwable;

/**
 * Class Database.
 */
class Database extends Medoo
{
    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var PDO
     */
    public $pdo;

    /**
     * Database constructor.
     *
     * @param array $options
     */
    public function __construct(array $options = null)
    {
        $this->config = $options;

        parent::__construct($this->config);

        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $this->pdo->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
    }

    /**
     * reconnect database.
     */
    public function reconnect()
    {
        $this->__construct($this->config);
    }

    /**
     * @param $query
     * @param array $map
     * @return bool|false|\PDOStatement
     */
    public function query($query, $map = [])
    {
        if(empty($map)) {
            return $this->pdo->query($query);
        }

        $statement = $this->pdo->prepare($query);

        foreach($map as $key => $value) {
            $statement->bindValue($key, $value[0], $value[1]);
        }

        try {
            return $statement->execute();
        } catch (Throwable $e) {
            $this->reconnect();

            return $statement->execute();
        }
    }

    /**
     * @param $query
     * @param array $map
     * @return bool|int
     */
    public function exec($query, $map = [])
    {
        file_put_contents('/tmp/debug.log', $query.PHP_EOL, FILE_APPEND);
        if(empty($map)) {
            return $this->pdo->exec($query);
        }

        $statement = $this->pdo->prepare($query);

        foreach($map as $key => $value) {
            $statement->bindValue($key, $value[0], $value[1]);
        }

        try {
            $statement->execute();

            return $statement->rowCount();
        } catch (Throwable $e) {
            $this->reconnect();
            $statement->execute();

            return $statement->rowCount();
        }
    }

    /**
     * @param $table
     * @param $join
     * @param null $where
     *
     * @return bool
     */
    public function has($table, $join, $where = null)
    {
        $column = null;

        $query = $this->query('SELECT EXISTS('.$this->selectContext($table, $join, $column, $where, 1).')');

        if ($query && 1 === intval($query->fetchColumn())) {
            return true;
        }

        return false;
    }
}
