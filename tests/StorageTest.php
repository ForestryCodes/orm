<?php

namespace Forestry\Orm;

class StorageTest extends \PHPUnit_Framework_TestCase {

    /**
     * Create basic SQLite connection.
     */
    public function testSetSQLiteConnection() {
        $result = Storage::set('default', ['dsn' => 'sqlite::memory:', '']);

        $this->assertInstanceof('PDO', $result);
    }

    /**
     * Test exception when an already set connection should be set.
     *
     * @depends testSetSQLiteConnection
     * @expectedException \LogicException
     */
    public function testLogicExceptionOnAlreadySetConnection() {
        Storage::set('default', ['dsn' => 'sqlite::memory:', '']);
    }

    /**
     * Test retrieval of a connection.
     *
     * @depends testSetSQLiteConnection
     */
    public function testGetConnection() {
        $result = Storage::get('default');

        $this->assertInstanceof('PDO', $result);
    }

    /**
     * Test exception when a connection is not set.
     *
     * @depends testSetSQLiteConnection
     * @expectedException \OutOfBoundsException
     */
    public function testOutOfBoundExceptionOnNonSetConnection() {
        Storage::get('notset');
    }

}
