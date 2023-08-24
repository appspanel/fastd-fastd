<?php
/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2017
 *
 * @see      https://www.github.com/janhuang
 * @see      http://www.fast-d.cn/
 */
use FastD\Application;
use FastD\Model\Model;

class ModelTest extends \FastD\TestCase
{
    public function createApplication(): Application
    {
        $app = new Application(__DIR__.'/../../app');

        return $app;
    }

    public function testCreateModel(): void
    {
        $model = new Model(database());
        $this->assertNotNull($model->getDatabase());
    }

    public function testFactoryCreateModel(): void
    {
        $demo = \FastD\Model\ModelFactory::createModel('demo');
        $this->assertEquals($demo, new \Model\DemoModel(database()));
    }
}
