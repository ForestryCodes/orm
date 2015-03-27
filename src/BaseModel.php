<?php

namespace Forestry\Orm;

abstract class BaseModel {

    /**
     * @var array
     */
    protected $data = array();

    /**
     * @var array
     */
    protected $modified = array();

    /**
     * @var string
     */
    protected static $storage = 'default';

    /**
     * @var string
     */
    protected static $database;

    /**
     * @var string
     */
    protected static $table;

    /**
     * @var string
     */
    protected static $primaryKey = 'id';

    /**
     * Constructor provides option to prefill object.
     *
     * @param array $data
     */
    public function __construct(array $data = array())
    {
        if(!empty($data)) {
            $this->data = $data;
        }
    }

    public function set($property, $value) {
        $property = $this->sanitizePropertyName($property);
        if(!isset($this->data[$property]) || $this->data[$property] != $value) {
            $this->data[$property] = $value;
            $this->modified[] = $property;
        }
    }

    public function get($property)
    {
        $property = $this->sanitizePropertyName($property);

        return (isset($this->data[$property]) ? $this->data[$property] : null);
    }

    /**
     * Gets name of the table.
     *
     * Includes name of the database if set.
     *
     * @static
     * @return string
     */
    public static function getTable()
    {
        $database = static::$database ? static::quotePropertyName(static::$database) . '.' : '';
        $table = static::$table ? static::$table : strtolower(get_called_class());

        return $database . static::quotePropertyName($table);
    }

    /**
     * Gets a property.
     *
     * @param string $property
     * @return mixed
     */
    public function __get($property)
    {
        $property = $this->sanitizePropertyName($property);

        return (isset($this->data[$property]) ? $this->data[$property] : null);
    }

    /**
     * Sets a property.
     *
     * @param string $property
     * @param mixed $value
     * @return null
     */
    public function __set($property, $value)
    {
        $this->set($property, $value);
    }

    /**
     * Updates the current entry.
     *
     * @return bool
     */
    public function update()
    {
        $storage = Storage::get(static::$storage);
        $values = array();
        $params = array();
        foreach($this->modified as $property) {
            $values[] = sprintf('%s = :%s', $this->quotePropertyName($property), $property);
            $params[':' . $property] = $this->data[$property];
        }
        $params[':' . static::$primaryKey] = $this->__get(static::$primaryKey);
        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s = :%s',
            static::getTable(),
            implode(',', $values),
            $this->quotePropertyName(static::$primaryKey),
            static::$primaryKey
        );
        $query = $storage->prepare($sql);
        $return = $query->execute($params);
        if($return) {
            $this->modified = array();
        }

        return $return;
    }

    /**
     * Removes an entry from the database and flushes data of current instance.
     *
     * @return bool
     */
    public function delete()
    {
        $storage = Storage::get(static::$storage);
        $sql = sprintf(
            'DELETE FROM %s WHERE %s = ?',
            static::getTable(),
            static::$primaryKey
        );
        $query = $storage->prepare($sql);
        $return = $query->execute(array($this->__get(static::$primaryKey)));
        if($return) {
            $this->data = array();
            $this->modified = array();
        }

        return $return;
    }

    /**
     * Creates a new entry.
     *
     * @return bool
     */
    public function insert()
    {
        $storage = Storage::get(static::$storage);
        $columns = implode(',', array_map(array($this, 'quotePropertyName'), $this->modified));
        $params = array();
        foreach($this->modified as $property) {
            $params[':' . $property] = $this->data[$property];
        }
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            static::getTable(),
            $columns,
            implode(',', array_keys($params))
        );
        $query = $storage->prepare($sql);
        $result = $query->execute($params);
        if($result) {
            $this->data[static::$primaryKey] = $storage->lastInsertId();
            $this->modified = array();
        }

        return $result;
    }

    /**
     * Saves the current entry.
     *
     * Shortcut for insert or update, depending on a set primary key.
     *
     * @return bool
     */
    public function save()
    {
        $return = false;
        if($this->__get(static::$primaryKey)) {
            $return = $this->update();
        } else {
            $return = $this->insert();
        }

        return $return;
    }

    /**
     * Returns a string representation of the current object.
     *
     * @return string
     */
    public function __toString()
    {
        $return = sprintf(
            '%s.%s@%s {' . PHP_EOL, static::$storage, static::$table, get_class($this));
        foreach($this->data as $key => $value) {
            $return .= sprintf(
                '    %s: %s' . PHP_EOL,
                $key,
                (strlen($value) > 60 ? substr($value, 0, 60) . '...' : $value)
            );
        }
        $return .= '}' . PHP_EOL;

        return $return;
    }

    /**
     * Returns an array representation of the current object.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->data;
    }

    /**
     * Get the value of the primary key.
     *
     * @return int
     */
    public function getPK()
    {
        return $this->__get(static::$primaryKey);
    }

    /**
     * Retrieves entry by its primary key.
     *
     * @param mixed $key
     * @return object
     */
    public static function retrieveByPK($key)
    {
        $return = null;
        $storage = Storage::get(static::$storage);
        $sql = sprintf(
            'SELECT * FROM %s WHERE %s = ?',
            static::getTable(),
            static::$primaryKey
        );
        $query = $storage->prepare($sql);
        $query->execute(array($key));
        $data = $query->fetch(\PDO::FETCH_ASSOC);
        if (is_array($data)) {
            $class = get_called_class();
            $return = new $class($data);
        }

        return $return;
    }

    /**
     * Performs a custom query and returns an array of objects of the called class.
     *
     * @static
     * @param string $sql
     * @return mixed
     */
    public static function query($sql)
    {
        $return = null;
        $storage = Storage::get(static::$storage);
        $result = $storage->query($sql);
        if (false !== $result) {
            $class = get_called_class();
            while($data = $result->fetch(\PDO::FETCH_ASSOC)) {
                $return[] = new $class($data);
            }
        }

        return $return;
    }

    /**
     * Quotes property names like database, field and table names.
     *
     * @param string $property
     * @return string
     */
    private static function quotePropertyName($property)
    {
        return '`' . static::sanitizePropertyName($property) . '`';
    }

    /**
     * Sanitize property names like database, field and table names.
     *
     * @param string $property
     * @return mixed
     */
    private static function sanitizePropertyName($property)
    {
        return str_replace(array('`', '\'', '"'), '', $property);
    }

}
