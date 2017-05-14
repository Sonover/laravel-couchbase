<?php


namespace Sonover\Couchbase;


use CouchbaseBucket;
use CouchbaseCluster;
use CouchbaseN1qlQuery;
use Sonover\Couchbase\Query\Builder;
use Sonover\Couchbase\Query\Grammar;
use Illuminate\Database\Connection;

class CouchbaseConnection extends Connection
{

    /** @var string */
    protected $bucket;

    /** @var \CouchbaseCluster */
    protected $connection;

    /** @var */
    protected $managerUser;

    /** @var */
    protected $managerPassword;

    /** @var int */
    protected $fetchMode = 0;

    /** @var array */
    protected $enableN1qlServers = [];

    /** @var string  */
    protected $bucketPassword = '';

    /** @var int */
    protected $consistency = CouchbaseN1qlQuery::NOT_BOUNDED;

    /**
     * CouchbaseConnection constructor.
     */
    public function __construct($config)
    {
        $this->setBucket($config['bucket']);
        $this->connection = $this->createConnection($config);
        $this->useDefaultQueryGrammar();
        $this->useDefaultPostProcessor();
    }


    /**
     * @return Grammar
     */
    protected function getDefaultQueryGrammar()
    {
        return new Grammar();
    }

    /**
     * @param $password
     *
     * @return $this
     */
    public function setBucketPassword($password)
    {
        $this->bucketPassword = $password;

        return $this;
    }

    /**
     * @param $name
     *
     * @return \CouchbaseBucket
     */
    public function openBucket($name = null)
    {
        $name = $name ?: $this->bucket;
        return $this->connection->openBucket($name, $this->bucketPassword);
    }


    /**
     *
     */
    public function flush(){
       return $this->openBucket()->manager()->flush();
    }


    /**
     * Get a new query builder instance.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function query()
    {
        return new Builder(
            $this, $this->getQueryGrammar(), $this->getPostProcessor()
        );
    }


    public function setBucket($bucket)
    {
        $this->bucket = $bucket;
        $this->table($bucket);
    }

    /**
     * @return CouchbaseCluster
     */
    public function getCouchbase()
    {
        return $this->connection;
    }

    public function quote()
    {

    }
    /**
     * @param int $consistency
     *
     * @return $this
     */
    public function consistency($consistency)
    {
        $this->consistency = $consistency;

        return $this;
    }

    public function getBucket()
    {
        return $this->bucket;
    }

    public function select($query, $bindings = [], $useReadPdo = true)
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending()) {
                return [];
            }
            $query = CouchbaseN1qlQuery::fromString($query);
            $query->consistency($this->consistency);
            $query->adhoc(false);
            $query->positionalParams($bindings);
            $bucket = $this->openBucket($this->bucket);

            return $bucket->query($query, true)->rows;
        });
    }

    protected function createConnection($config)
    {

        return new \CouchbaseCluster(
            $config['host']
        );
    }

    /**
     * @param string $query
     * @param array  $bindings
     *
     * @return int|mixed
     */
    public function insert($query, $bindings = [])
    {
        return $this->affectingStatement($query, $bindings);
    }

    /**
     * {@inheritdoc}
     */
    public function affectingStatement($query, $bindings = [])
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending()) {
                return 0;
            }

            $query = CouchbaseN1qlQuery::fromString($query);
            $query->consistency($this->consistency);
            $query->adhoc(false);
            $bindings = isset($bindings[0]) ? $bindings[0] : $bindings;
            $query->namedParams([
                'parameters' => $bindings
            ]);

            $bucket = $this->openBucket($this->bucket);
            $result = $bucket->query($query);

            return $result->rows;
        });
    }

    /**
     * @param       $query
     * @param array $bindings
     *
     * @return mixed
     */
    public function positionalStatement($query, array $bindings = [])
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending()) {
                return 0;
            }
            $query = CouchbaseN1qlQuery::fromString($query);
            $query->consistency($this->consistency);
            $query->adhoc(false);
            $query->options['args'] = $bindings;
            $bucket = $this->openBucket($this->bucket);

            return $bucket->query($query)->rows;
        });
    }


    /**
     * {@inheritdoc}
     */
    protected function reconnectIfMissingConnection()
    {
        if (is_null($this->connection)) {
            $this->reconnect();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect()
    {
        $this->connection = null;
    }

    /**
     * @param CouchbaseBucket $bucket
     *
     * @return CouchbaseBucket
     */
    protected function enableN1ql(CouchbaseBucket $bucket)
    {
        if (!count($this->enableN1qlServers)) {
            return $bucket;
        }
        $bucket->enableN1ql($this->enableN1qlServers);

        return $bucket;
    }

    /**
     * N1QL upsert query.
     *
     * @param string $query
     * @param array  $bindings
     *
     * @return int
     */
    public function upsert($query, $bindings = [])
    {
        return $this->affectingStatement($query, $bindings);
    }

    /**
     * Run an update statement against the database.
     *
     * @param string $query
     * @param array  $bindings
     *
     * @return int|\stdClass
     */
    public function update($query, $bindings = [])
    {
        return $this->positionalStatement($query, $bindings);
    }

    /**
     * Run a delete statement against the database.
     *
     * @param string $query
     * @param array  $bindings
     *
     * @return int|\stdClass
     */
    public function delete($query, $bindings = [])
    {
        return $this->positionalStatement($query, $bindings);
    }


}