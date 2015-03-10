<?php

namespace Forestry\Orm;

class Storage {

	/**
	 * @var array
	 */
	private static $instances = ['default' => []];

    /**
     * Set a new connection with the given name.
     *
     * @param string $name
     * @param array $config
     * @return mixed
     * @throws \LogicException
     * @throws \PDOException
     */
    public static function set($name = 'default', array $config = []) {
        if(isset(self::$instances[$name]) && self::$instances[$name] instanceof \PDO) {
            throw new \LogicException(sprintf('Connection "%s" already set', $name));
        }

        self::$instances[$name] = new \PDO(
            isset($config['dsn']) ? $config['dsn'] : 'mysql:host=localhost',
            isset($config['user']) ? $config['user'] : 'root',
            isset($config['password']) ? $config['password']: '',
            isset($config['option']) ? $config['option'] : array()
        );

        return self::$instances[$name];
    }

}
