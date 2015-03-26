<?php

namespace Forestry\Orm;

use Forestry\Orm\Test\ModelImplementation;

class BaseModelTest extends \PHPUnit_Framework_TestCase {

    public function setUp() {
        Storage::set('test', [
            'dsn' => 'sqlite::memory:',
            '',
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]]
        );
        Storage::get('test')->exec('
            DROP TABLE IF EXISTS `test_table`'
        );
        Storage::get('test')->exec('
            CREATE TABLE `test_table` (`id` INT PRIMARY KEY, `name` TEXT);'
        );
    }

    public function tearDown() {
        Storage::delete('test');
    }

    /**
     * Create basic model.
     */
    public function testConstructor() {
        $model = new ModelImplementation();

        $this->assertInstanceof('Forestry\Orm\BaseModel', $model);
    }

    /**
     * Create basic model with constructor options.
     */
    public function testConstructorWithParameters() {
        $model = new ModelImplementation([
            'id' => 1,
            'name' => 'Test'
        ]);

        $this->assertInstanceof('Forestry\Orm\BaseModel', $model);
        $this->assertEquals('Test', $model->name);
    }

    public function testSetAndGet() {
        $stub = $this->getMockForAbstractClass('Forestry\Orm\BaseModel');
        $stub->set('`name`', 'Test');

        $this->assertEquals('Test', $stub->get('name'));
    }

    public function testGetPK() {
        $model = new ModelImplementation([
            'id' => 1,
            'name' => 'Test'
        ]);

        $this->assertEquals(1, $model->getPK());
    }

    public function testGetTableName() {
        $table = ModelImplementation::getTable();

        $this->assertEquals('`test_table`', $table);
    }

    public function testToArray() {
        $model = new ModelImplementation([
            'id' => 1,
            'name' => 'Test'
        ]);

        $this->assertEquals([
            'id' => 1,
            'name' => 'Test'
        ], $model->toArray());
    }

    public function testToString() {
        $model = new ModelImplementation([
            'id' => 1,
            'name' => 'Test'
        ]);

        $this->assertEquals('test.test_table@Forestry\Orm\Test\ModelImplementation {
    id: 1
    name: Test
}
', (string)$model);
    }

    public function testSet() {
        $model = new ModelImplementation();
        $model->name = 'Test';

        $this->assertEquals(['name' => 'Test'], $model->toArray());
    }

    public function testInsert() {
        $model = new ModelImplementation();
        $model->name = 'test';
        $model->insert();

        $this->assertEquals(1, $model->getPK());
    }

    public function testUpdate() {
        $model = new ModelImplementation([
            'id' => 1,
            'name' => 'test'
        ]);
        $model->name = 'test2';
        $model->update();

        $this->assertEquals('test2', $model->name);
    }

    public function testSaveUpdate() {
        $model = new ModelImplementation([
            'id' => 1,
            'name' => 'test2'
        ]);
        $model->name = 'test3';
        $model->save();

        $this->assertEquals('test3', $model->name);
    }

    public function testSaveInsert() {
        $model = new ModelImplementation();
        $model->name = 'test4';
        $model->save();

        $this->assertEquals('test4', $model->name);
    }

    public function testDelete() {
        $model = new ModelImplementation([
            'id' => 1,
            'name' => 'test2'
        ]);
        $model->delete();

        $this->assertEquals(null, $model->name);
    }

    public function testRetrieveByPK() {
        $model = new ModelImplementation();
        $model->id = 1;
        $model->name = 'test';
        $model->insert();

        $result = ModelImplementation::retrieveByPK(1);

        $this->assertInstanceof('Forestry\Orm\BaseModel', $result);
        $this->assertEquals('test', $model->name);
    }

    public function testQuery() {
        $model = new ModelImplementation();
        $model->id = 1;
        $model->name = 'test';
        $model->insert();

        $result = ModelImplementation::query('
            SELECT
                *
            FROM
                `test_table`
            WHERE
                `name` LIKE "test"');

        $this->assertArrayHasKey(0, $result);
    }

}