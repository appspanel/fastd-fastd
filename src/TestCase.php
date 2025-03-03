<?php
/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2016
 *
 * @see      https://www.github.com/janhuang
 * @see      https://fastdlabs.com
 */

namespace FastD;

use FastD\Http\Response;
use FastD\Testing\WebTestCase;
use PHPUnit_Extensions_Database_DataSet_ArrayDataSet;
use PHPUnit_Extensions_Database_DataSet_CompositeDataSet;
use PHPUnit_Extensions_Database_DataSet_IDataSet;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Class TestCase.
 */
class TestCase extends WebTestCase
{
    protected string $connection = 'default';

    /**
     * @var Application
     */
    protected Application $app;

    /**
     * Set up unit.
     */
    public function setUp(): void
    {
        $this->app = $this->createApplication();
        parent::setUp();
    }

    /**
     * @return bool
     */
    public function isLocal(): bool
    {
        return 'prod' !== config()->get('env');
    }

    /**
     * @return Application
     */
    public function createApplication(): Application
    {
        return new Application(getcwd());
    }

    /**
     * @param ServerRequestInterface $request
     * @param array $params
     * @param array $headers
     * @return Response
     * @throws \Throwable
     */
    public function handleRequest(ServerRequestInterface $request, array $params = [], array $headers = []): Response
    {
        if (!empty($params)) {
            if ('GET' === $request->getMethod()) {
                $request->withQueryParams($params);
            } elseif ('POST' === $request->getMethod()) {
                $request->withParsedBody($params);
            } else {
                $request->getBody()->write(http_build_query($params));
            }
        }

        if (!empty($headers)) {
            foreach ($headers as $name => $header) {
                $request->withAddedHeader($name, $header);
            }
        }

        return $this->app->handleRequest($request);
    }

    /**
     * Returns the test database connection.
     *
     * @return \PHPUnit_Extensions_Database_DB_IDatabaseConnection|null
     */
    protected function getConnection()
    {
        try {
            return $this->createDefaultDBConnection(database($this->connection)->pdo);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Returns the test dataset.
     *
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    protected function getDataSet()
    {
        $path = app()->getPath().'/database/dataset/'.$this->connection;

        if (!file_exists($path) && !empty($this->connection)) {
            $path = dirname($path);
        }

        $composite = new PHPUnit_Extensions_Database_DataSet_CompositeDataSet();

        foreach (glob($path.'/*') as $file) {
            $dataSet = load($file);
            if (empty($dataSet)) {
                $dataSet = [];
            }
            $tableName = pathinfo($file, PATHINFO_FILENAME);
            $composite->addDataSet(
                new PHPUnit_Extensions_Database_DataSet_ArrayDataSet(
                    [
                        $tableName => $dataSet,
                    ]
                )
            );
        }

        return $composite;
    }
}
