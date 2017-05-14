<?php
namespace Sonover\Couchbase\Eloquent;


use Illuminate\Database\Eloquent\Model as Eloquent;
use Sonover\Couchbase\Query\Builder as QueryBuilder;


abstract class Model extends Eloquent
{
    use UseSingleBucket, UUID;

    public $incrementing = false;

    protected $singleBucket = true;

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return Builder
     */
    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    /**
     * Get a new query builder instance for the connection.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function newBaseQueryBuilder()
    {
        $conn = $this->getConnection();

        $grammar = $conn->getQueryGrammar();

        return new QueryBuilder($conn, $grammar, $conn->getPostProcessor());
    }


}