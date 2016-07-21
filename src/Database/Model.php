<?php
/**
 * Model - A simple Database Model.
 *
 * @author Virgil-Adrian Teaca - virgil@giulianaeassociati.com
 * @version 3.0
 */

namespace Nova\Database;

use Nova\Database\Connection;
use Nova\Database\ConnectionResolverInterface as Resolver;
use Database\Query\Builder as QueryBuilder;
use Database\Query as Builder;
use Nova\Helpers\Inflector;

use DB;


class Model
{
    /**
     * The Database Connection name.
     *
     * @var string
     */
    protected $connection = null;

    /**
     * The database connection instance.
     *
     * @var \Nova\Database\Connection
     */
    protected $db;

    /**
     * The table associated with the Model.
     *
     * @var string
     */
    protected $table;

    /**
     * The primary key for the Model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The number of Records to return for pagination.
     *
     * @var int
     */
    protected $perPage = 15;

    /**
     * The connection resolver instance.
     *
     * @var \Nova\Database\ConnectionResolverInterface
     */
    protected static $resolver;


    /**
     * Create a new Model instance.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct($connection = null)
    {
        // Setup the Connection name.
        if(! is_null($connection)) {
            $this->connection = $connection;
        }

        // Setup the Connection instance.
        $this->db = $this->getConnection();
    }

    /**
     * Get all of the Records from the database.
     *
     * @param  array  $columns
     * @return array
     */
    public function all($columns = array('*'))
    {
        return $this->newQuery()->get($columns);
    }

    /**
     * Find a Record by its primary key.
     *
     * @param  mixed  $id
     * @param  array  $columns
     * @return Model
     */
    public function find($id, $columns = array('*'))
    {
        return $this->newQuery()->find($id, $columns);
    }

    /**
     * Find Records by their primary key.
     *
     * @param  array  $ids
     * @param  array  $columns
     * @return Model
     */
    public function findMany($ids, $columns = array('*'))
    {
        return $this->newQuery()->findMany($ids, $columns);
    }

    /**
     * Insert a new Record and get the value of the primary key.
     *
     * @param  array   $values
     * @return int
     */
    public function insert(array $values)
    {
        return $this->newQuery()->insertGetId($values);
    }

    /**
     * Update the Model in the database.
     *
     * @param  mixed  $id
     * @param  array  $attributes
     * @return mixed
     */
    public function update($id, array $attributes = array())
    {
        return $this->newQuery()
            ->where($this->getKeyName(), $id)
            ->update($attributes);
    }

    /**
     * Delete the Record from the database.
     *
     * @return bool|null
     */
    public function delete($id)
    {
        $this->newQuery()
            ->where($this->getKeyName(), $id)
            ->delete();

        return true;
    }

    /**
     * Get the Table for the Model.
     *
     * @return string
     */
    public function getTable()
    {
        if (isset($this->table)) return $this->table;

        $baseName = class_basename($this);

        return str_replace('\\', '', Inflector::tableize($baseName));
    }

    /**
     * Get the Primary Key for the Model.
     *
     * @return string
     */
    public function getKeyName()
    {
        return $this->primaryKey;
    }

    /**
     * Get the number of models to return per page.
     *
     * @return int
     */
    public function getPerPage()
    {
        return $this->perPage;
    }

    /**
     * Set the number of models to return per page.
     *
     * @param  int   $perPage
     * @return void
     */
    public function setPerPage($perPage)
    {
        $this->perPage = $perPage;
    }

    /**
     * Get the database Connection instance.
     *
     * @return \Nova\Database\Connection
     */
    public function getConnection()
    {
        return $this->resolveConnection($this->connection);
    }

    /**
     * Get the current Connection name for the Model.
     *
     * @return string
     */
    public function getConnectionName()
    {
        return $this->connection;
    }

    /**
     * Set the Connection associated with the Model.
     *
     * @param  string  $name
     * @return \Nova\Database\Model
     */
    public function setConnection($name)
    {
        $this->connection = $name;

        return $this;
    }

    /**
     * Resolve a connection instance.
     *
     * @param  string  $connection
     * @return \Nova\Database\Connection
     */
    public static function resolveConnection($connection = null)
    {
        return static::$resolver->connection($connection);
    }

    /**
     * Get the connection resolver instance.
     *
     * @return \Nova\Database\ConnectionResolverInterface
     */
    public static function getConnectionResolver()
    {
        return static::$resolver;
    }

    /**
     * Set the connection resolver instance.
     *
     * @param  \Nova\Database\ConnectionResolverInterface  $resolver
     * @return void
     */
    public static function setConnectionResolver(Resolver $resolver)
    {
        static::$resolver = $resolver;
    }

    /**
     * Unset the connection resolver for models.
     *
     * @return void
     */
    public static function unsetConnectionResolver()
    {
        static::$resolver = null;
    }

    /**
     * Get a new Query for the Model's table.
     *
     * @return \Nova\Database\Query
     */
    public function newQuery()
    {
        $query = new QueryBuilder($this->db);

        $builder = $this->newBuilder($query);

        $builder->setModel($this);

        return $builder;
    }

    /**
     * Create a new ORM query builder for the Model.
     *
     * @param  \Database\Query\Builder $query
     * @return \Database\Query|static
     */
    public function newBuilder($query)
    {
        return new Builder($query);
    }

    /**
     * Handle dynamic method calls into the method.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $query = $this->newQuery();

        return call_user_func_array(array($query, $method), $parameters);
    }
}
